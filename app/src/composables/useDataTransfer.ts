import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    mappingsFromSuggestions,
    suggestColumnMatches,
    type ImportMapperColumn,
    type ImportMapperField,
    type ImportMapperLabels,
    type ImportMapperMapping,
} from '@freelance/vue-ui';
import { encodeTabular, readTabularFile, type DelimiterChoice, type TabularFormat } from '@/modules/support/transfer/tabularFile';
import { TRANSFER_MODEL_LABELS, transferFieldLabelKeys } from '@/modules/support/transfer/transferModels';
import http from '@/utils/http';

export type TransferField = {
    id: string;
    required: boolean;
    type: string;
    example?: string | null;
    owner?: string;
};

export type TransferFieldChange = {
    field: string;
    from: string;
    to: string;
};

export type TransferChange = {
    row?: number;
    employee_number: string;
    fields: string[];
    changes?: TransferFieldChange[];
};

export type TransferDecision = {
    row: number;
    operation: 'create' | 'update' | 'unchanged' | 'skip' | 'error';
    label?: string;
    changes?: TransferFieldChange[];
};

export type TransferIssue = {
    row: number;
    field?: string;
    code?: string;
    messages: string[];
    values?: Record<string, string>;
};

export type TransferModelInfo = {
    key: string;
    strategies?: string[];
    default_strategy?: string;
    policies?: string[];
    default_policy?: string;
    partial?: boolean;
};

export type TransferReport = {
    valid: number;
    invalid: number;
    skipped: number;
    created?: number;
    updated?: number;
    unchanged?: number;
    failed?: number;
    saved?: boolean;
    strategy?: string;
    policy?: string;
    errors: TransferIssue[];
    rows?: TransferDecision[];
    updates?: TransferChange[];
    creates?: { row?: number; employee_number: string }[];
};

const MAX_ROWS = 2000;

const modelKey = ref('');
const models = ref<TransferModelInfo[]>([]);
const modelKeys = ref<string[]>([]);
const strategy = ref('create_only');
const policy = ref<'strict' | 'valid_rows'>('strict');
const confirmPartial = ref(false);
const fields = ref<TransferField[]>([]);
const columns = ref<ImportMapperColumn[]>([]);
const mappings = ref<ImportMapperMapping[]>([]);
const sourceRows = ref<Record<string, string>[]>([]);
const delimiter = ref<DelimiterChoice>('auto');
const format = ref<TabularFormat>('csv');
const report = ref<TransferReport | null>(null);
const message = ref('');
const busy = ref(false);
const ready = ref(false);

const fileName = ref('');
const fileSize = ref(0);
const fileKind = ref<'csv' | 'txt' | 'xlsx' | ''>('');
let sourceFile: File | null = null;
let previewSignature = '';
let previewToken = 0;
let watching = false;

function signature(): string {
    const accepted = mappings.value
        .filter((mapping) => mapping.decision === 'accepted' && mapping.fieldId)
        .map((mapping) => `${mapping.columnId}:${mapping.fieldId}`)
        .join('|');
    return `${modelKey.value}:${strategy.value}:${policy.value}:${accepted}:${sourceRows.value.length}`;
}

function duplicateAccepted(): boolean {
    const ids = mappings.value
        .filter((mapping) => mapping.decision === 'accepted' && mapping.fieldId)
        .map((mapping) => mapping.fieldId as string);
    return new Set(ids).size !== ids.length;
}

function mappedRows(): Record<string, string>[] {
    const accepted = mappings.value.filter((mapping) => mapping.decision === 'accepted' && mapping.fieldId);
    return sourceRows.value.map((row) => {
        const mapped: Record<string, string> = {};
        for (const mapping of accepted) {
            mapped[mapping.fieldId as string] = row[mapping.columnId] ?? '';
        }
        return mapped;
    });
}

function readReport(error: unknown): TransferReport | null {
    const data = (error as { response?: { data?: { data?: TransferReport; message?: string } } }).response?.data;
    if (data?.data && typeof data.data.valid === 'number') {
        return data.data;
    }
    return null;
}

function readMessage(error: unknown): string {
    const data = (error as { response?: { data?: { message?: string } } }).response?.data;
    return data?.message || '';
}

export function useDataTransfer() {
    const { t } = useI18n();

    const mapperFields = computed<ImportMapperField[]>(() => {
        const labels = transferFieldLabelKeys(modelKey.value);
        return fields.value.map((field) => ({
            id: field.id,
            label: labels.has(field.id) ? t(labels.get(field.id) as string) : field.id,
            required: field.required,
        }));
    });

    const mapperLabels = computed<ImportMapperLabels>(() => ({
        column: t('DATA_TRANSFER_COLUMN'),
        samples: t('DATA_TRANSFER_SAMPLES'),
        suggested: t('DATA_TRANSFER_SUGGESTED'),
        accept: t('DATA_TRANSFER_ACCEPT'),
        deny: t('DATA_TRANSFER_DENY'),
        change: t('DATA_TRANSFER_CHANGE'),
        ignore: t('DATA_TRANSFER_IGNORE'),
        pending: t('DATA_TRANSFER_PENDING'),
        accepted: t('DATA_TRANSFER_ACCEPTED'),
        ignored: t('DATA_TRANSFER_IGNORED'),
        duplicate: t('DATA_TRANSFER_DUPLICATE_FIELD'),
        required: t('DATA_TRANSFER_REQUIRED'),
        empty: t('DATA_TRANSFER_NO_COLUMNS'),
    }));

    const canPreview = computed(
        () =>
            modelKey.value !== '' &&
            sourceRows.value.length > 0 &&
            mappings.value.some((mapping) => mapping.decision === 'accepted' && mapping.fieldId) &&
            !duplicateAccepted() &&
            !busy.value,
    );

    const rowCount = computed(() => sourceRows.value.length);
    const previewRows = computed(() => mappedRows());
    const hasFields = computed(() => fields.value.length > 0);

    const missingRequired = computed(() =>
        mapperFields.value
            .filter(
                (field) =>
                    field.required &&
                    !mappings.value.some((mapping) => mapping.decision === 'accepted' && mapping.fieldId === field.id),
            )
            .map((field) => field.label),
    );

    const currentModel = computed(() => models.value.find((model) => model.key === modelKey.value));
    const strategies = computed(() => currentModel.value?.strategies ?? ['create_only', 'upsert', 'update_only']);
    const policies = computed(() => currentModel.value?.policies ?? ['strict']);
    const partialAllowed = computed(() => currentModel.value?.partial === true);

    function strategyText(item: string, help = false): string {
        if (item === 'upsert') return help ? 'IMPORT_STRATEGY_UPSERT_HELP' : 'IMPORT_STRATEGY_UPSERT';
        if (item === 'update_only') return help ? 'IMPORT_STRATEGY_UPDATE_ONLY_HELP' : 'IMPORT_STRATEGY_UPDATE_ONLY';
        return help ? 'IMPORT_STRATEGY_CREATE_ONLY_HELP' : 'IMPORT_STRATEGY_CREATE_ONLY';
    }

    function policyText(item: string, help = false): string {
        if (item === 'valid_rows') return help ? 'IMPORT_POLICY_VALID_HELP' : 'IMPORT_POLICY_VALID';
        return help ? 'IMPORT_POLICY_STRICT_HELP' : 'IMPORT_POLICY_STRICT';
    }

    const strategyChoices = computed(() =>
        strategies.value.map((item) => ({
            value: item,
            label: t(strategyText(item)),
            help: t(strategyText(item, true)),
        })),
    );
    const policyChoices = computed(() =>
        policies.value.map((item) => ({
            value: (item === 'valid_rows' ? 'valid_rows' : 'strict') as 'strict' | 'valid_rows',
            label: t(policyText(item)),
            help: t(policyText(item, true)),
        })),
    );

    const canCommit = computed(() => {
        const checked = report.value;
        if (!canPreview.value || missingRequired.value.length > 0 || checked === null || previewSignature !== signature()) {
            return false;
        }
        const eligible = (checked.created ?? 0) + (checked.updated ?? 0);
        if (eligible < 1 || busy.value) {
            return false;
        }
        if (policy.value === 'valid_rows') {
            return partialAllowed.value && (checked.invalid === 0 || confirmPartial.value);
        }
        return checked.invalid === 0;
    });

    async function loadModels(): Promise<void> {
        const response = await http.get<{ data: TransferModelInfo[] }>('transfers/models');
        models.value = response.data.data;
        modelKeys.value = response.data.data.map((model) => model.key);
        if (!modelKey.value && modelKeys.value.length > 0) {
            await selectModel(modelKeys.value[0]);
        }
        ready.value = true;
    }

function clearFile(): void {
    sourceFile = null;
    fileName.value = '';
    fileSize.value = 0;
    fileKind.value = '';
    columns.value = [];
        mappings.value = [];
        sourceRows.value = [];
        report.value = null;
        previewSignature = '';
    }

    async function selectModel(key: string): Promise<void> {
        modelKey.value = key;
        const chosen = models.value.find((model) => model.key === key);
        strategy.value = chosen?.default_strategy ?? 'create_only';
        policy.value = chosen?.default_policy === 'valid_rows' ? 'valid_rows' : 'strict';
        confirmPartial.value = false;
        clearFile();
        message.value = '';
        const response = await http.get<{ data: TransferField[] }>(`transfers/${key}/fields`);
        fields.value = response.data.data;
    }

    async function loadFile(file: File): Promise<void> {
        message.value = '';
        report.value = null;
        const name = file.name.toLowerCase();
        if (!name.endsWith('.csv') && !name.endsWith('.txt') && !name.endsWith('.xlsx')) {
            clearFile();
            message.value = t('IMPORT_UNSUPPORTED');
            return;
        }
        sourceFile = file;
        try {
            const sheet = await readTabularFile(file, delimiter.value);
            if (sheet.columns.length === 0 || sheet.rows.length === 0) {
                clearFile();
                message.value = t('IMPORT_EMPTY_FILE');
                return;
            }
            if (sheet.rows.length > MAX_ROWS) {
                clearFile();
                message.value = t('DATA_TRANSFER_TOO_MANY');
                return;
            }
            columns.value = sheet.columns;
            sourceRows.value = sheet.rows;
            fileName.value = file.name;
            fileSize.value = file.size;
            fileKind.value = name.endsWith('.xlsx') ? 'xlsx' : name.endsWith('.txt') ? 'txt' : 'csv';
            const suggestions = suggestColumnMatches(sheet.columns, mapperFields.value);
            mappings.value = mappingsFromSuggestions(sheet.columns, suggestions).map((mapping) =>
                mapping.suggestionFieldId
                    ? { ...mapping, fieldId: mapping.suggestionFieldId, decision: 'accepted' as const }
                    : mapping,
            );
        } catch {
            clearFile();
            message.value = t('IMPORT_UNREADABLE');
        }
    }

    function download(bytes: Uint8Array, filename: string, mime: string): void {
        const blob = new Blob([bytes], { type: mime });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();
        URL.revokeObjectURL(url);
    }

    function chosenDelimiter(): string {
        if (delimiter.value !== 'auto') {
            return delimiter.value;
        }
        return format.value === 'txt' ? ';' : ',';
    }

    function mimeFor(kind: TabularFormat): string {
        if (kind === 'xlsx') {
            return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }
        if (kind === 'txt') {
            return 'text/plain';
        }
        return 'text/csv';
    }

    function downloadTemplate(): void {
        const headers = fields.value.map((field) => field.id);
        const example = fields.value.map((field) => field.example ?? '');
        const rows = example.some((value) => value !== '') ? [example] : [];
        const bytes = encodeTabular(headers, rows, format.value, chosenDelimiter());
        download(bytes, `${modelKey.value}-template.${format.value}`, mimeFor(format.value));
    }

    async function downloadExport(query?: { filter?: Record<string, unknown>; search?: string }): Promise<boolean> {
        busy.value = true;
        message.value = '';
        try {
            const response = await http.get<{ data: { fields: TransferField[]; rows: Record<string, string>[] } }>(
                `transfers/${modelKey.value}/rows`,
                { params: query },
            );
            const headers = response.data.data.fields.map((field) => field.id);
            const rows = response.data.data.rows.map((row) => headers.map((header) => row[header] ?? ''));
            const bytes = encodeTabular(headers, rows, format.value, chosenDelimiter());
            download(bytes, `${modelKey.value}-export.${format.value}`, mimeFor(format.value));
            return true;
        } catch (error) {
            message.value = readMessage(error) || t('DATA_TRANSFER_FAILED');
            return false;
        } finally {
            busy.value = false;
        }
    }

    async function preview(): Promise<boolean> {
        if (!canPreview.value || missingRequired.value.length > 0) {
            message.value = duplicateAccepted()
                ? t('DATA_TRANSFER_DUPLICATE')
                : missingRequired.value.length > 0
                  ? t('IMPORT_REQUIRED_MISSING', { fields: missingRequired.value.join(', ') })
                  : t('DATA_TRANSFER_NEED_MAPPING');
            return false;
        }
        const token = ++previewToken;
        busy.value = true;
        message.value = '';
        try {
            const response = await http.post<{ data: TransferReport }>(`transfers/${modelKey.value}/preview`, {
                rows: mappedRows(),
                file_name: sourceFile?.name ?? null,
                strategy: strategy.value,
                policy: policy.value,
            });
            if (token !== previewToken) return false;
            report.value = response.data.data;
            previewSignature = signature();
            return true;
        } catch (error) {
            if (token !== previewToken) return false;
            report.value = readReport(error);
            previewSignature = '';
            message.value = readMessage(error) || t('DATA_TRANSFER_FAILED');
            return false;
        } finally {
            if (token === previewToken) busy.value = false;
        }
    }

    async function commit(): Promise<boolean> {
        if (!canCommit.value || busy.value) {
            return false;
        }
        busy.value = true;
        message.value = '';
        try {
            const response = await http.post<{ data: TransferReport }>(`transfers/${modelKey.value}/commit`, {
                rows: mappedRows(),
                file_name: sourceFile?.name ?? null,
                strategy: strategy.value,
                policy: policy.value,
                confirm_partial: confirmPartial.value,
            });
            const saved = response.data.data;
            const failed = (saved.failed ?? 0) > 0 || (saved.invalid ?? 0) > 0;
            if (!failed) {
                clearFile();
            } else {
                previewSignature = '';
            }
            report.value = saved;
            message.value =
                typeof saved.updated === 'number'
                    ? t('DATA_TRANSFER_APPLIED', {
                          created: saved.created ?? 0,
                          updated: saved.updated ?? 0,
                          unchanged: saved.unchanged ?? 0,
                      })
                    : t('DATA_TRANSFER_CREATED', { count: saved.created ?? 0 });
            return true;
        } catch (error) {
            const failed = readReport(error);
            report.value = failed;
            previewSignature = '';
            message.value = failed ? t('DATA_TRANSFER_NOT_SAVED') : readMessage(error) || t('DATA_TRANSFER_FAILED');
            return false;
        } finally {
            busy.value = false;
        }
    }

    if (!watching) {
        watching = true;
        watch(delimiter, () => {
            if (sourceFile && !sourceFile.name.toLowerCase().endsWith('.xlsx')) {
                void loadFile(sourceFile);
            }
        });

        watch(mappings, () => {
            if (previewSignature && previewSignature !== signature()) {
                report.value = null;
                previewSignature = '';
            }
        }, { deep: true });

        watch([strategy, policy], () => {
            confirmPartial.value = false;
            if (previewSignature && previewSignature !== signature()) {
                previewSignature = '';
            }
        });
    }

    function downloadErrors(): void {
        const issues = report.value?.errors ?? [];
        if (issues.length === 0) return;
        const rows = issues.map((issue) => [
            String(issue.row),
            issue.field ?? '',
            issue.code ?? 'invalid',
            issue.messages.join(' '),
            Object.entries(issue.values ?? {})
                .map(([key, value]) => `${key}=${value}`)
                .join('; '),
        ]);
        const bytes = encodeTabular(['row', 'field', 'code', 'message', 'values'], rows, 'csv', ',');
        download(bytes, `${modelKey.value}-import-errors.csv`, 'text/csv');
    }

    function modelLabel(key: string): string {
        return TRANSFER_MODEL_LABELS[key] ? t(TRANSFER_MODEL_LABELS[key]) : key;
    }

    return {
        modelKey,
        modelKeys,
        strategy,
        policy,
        strategies,
        policies,
        strategyChoices,
        policyChoices,
        partialAllowed,
        confirmPartial,
        columns,
        mappings,
        delimiter,
        format,
        report,
        message,
        busy,
        ready,
        mapperFields,
        mapperLabels,
        sourceRows,
        rowCount,
        previewRows,
        fileName,
        fileSize,
        fileKind,
        hasFields,
        missingRequired,
        canPreview,
        canCommit,
        clearFile,
        loadModels,
        selectModel,
        loadFile,
        downloadTemplate,
        downloadExport,
        preview,
        commit,
        downloadErrors,
        modelLabel,
    };
}
