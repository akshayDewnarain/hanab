<template>
    <div
        ref="shell"
        class="flex w-full max-h-[90vh] min-h-0 min-w-0 flex-col overflow-hidden bg-white text-list-text motion-reduce:transition-none"
        :class="sizeReady ? 'transition-[height] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]' : ''"
        :style="{ height: shellHeight || undefined }"
        @keydown.esc.prevent="requestClose"
    >
        <div ref="measure" class="flex h-full min-h-0 min-w-0 w-full flex-col">
        <header class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h2 class="m-0 text-lg font-semibold">{{ t('IMPORT_DATA_HEADING', { model: modelLabel(modelKey) }) }}</h2>
                    <HelpTrigger context="employees.import" />
                </div>
                <p class="mt-1 mb-0 text-sm text-list-text-muted">{{ t('IMPORT_WORKSPACE_INTRO') }}</p>
            </div>
            <button
                type="button"
                class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"
                :aria-label="t('IMPORT_CLOSE')"
                @click="requestClose"
            >
                <Icon icon="material-symbols:close-rounded" class="size-5" />
            </button>
        </header>

        <ol class="m-0 flex shrink-0 list-none gap-2 border-b border-slate-100 px-5 py-3" :aria-label="t('IMPORT_PROGRESS')">
            <li v-for="item in steps" :key="item.id" class="flex min-w-0 items-center gap-2">
                <button
                    type="button"
                    class="inline-flex min-w-0 items-center gap-2 rounded-full px-1 text-left text-xs font-semibold"
                    :class="stepClass(item.id)"
                    :disabled="!canOpenStep(item.id)"
                    @click="goTo(item.id)"
                >
                    <span
                        class="inline-flex size-5 shrink-0 items-center justify-center rounded-full text-[11px]"
                        :class="badgeClass(item.id)"
                    >
                        <Icon v-if="item.id < step && !stepHasError(item.id)" icon="material-symbols:check-rounded" class="size-3.5" />
                        <span v-else>{{ item.id }}</span>
                    </span>
                    <span class="truncate">{{ t(item.label) }}</span>
                </button>
            </li>
        </ol>

        <div class="min-h-0 min-w-0 flex-1 overflow-auto px-5 py-4">
            <section v-if="step === 1" class="mx-auto flex max-w-xl flex-col gap-3">
                <p v-if="modelKey === 'employees'" class="m-0 text-sm text-list-text-muted">{{ t('DATA_TRANSFER_EMPLOYEE_INTRO') }}</p>

                <div v-if="fileName" class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                    <Icon icon="material-symbols:description-outline-rounded" class="mt-0.5 size-5 shrink-0 text-primary" />
                    <div class="min-w-0 flex-1">
                        <p class="m-0 truncate text-sm font-semibold">{{ fileName }}</p>
                        <p class="mt-0.5 mb-0 text-xs text-list-text-muted">
                            {{ t('IMPORT_FILE_META', { rows: rowCount, columns: columns.length, size: fileSizeLabel }) }}
                        </p>
                        <p class="mt-1 mb-0 text-xs font-medium text-emerald-700">{{ t('IMPORT_FILE_READY') }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button type="button" class="cursor-pointer text-xs font-semibold text-primary" @click="fileInput?.click()">
                            {{ t('IMPORT_REPLACE_FILE') }}
                        </button>
                        <button type="button" class="cursor-pointer text-xs font-semibold text-slate-500" @click="removeFile">
                            {{ t('IMPORT_REMOVE_FILE') }}
                        </button>
                    </div>
                </div>

                <button
                    v-else
                    type="button"
                    class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center disabled:cursor-not-allowed disabled:opacity-50"
                    :class="dragging ? 'border-primary bg-primary/5' : ''"
                    :disabled="!hasFields"
                    @click="fileInput?.click()"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="onDrop"
                >
                    <Icon icon="material-symbols:upload-file-outline-rounded" class="size-6 text-primary" />
                    <span class="text-sm font-semibold">{{ t('IMPORT_BROWSE') }}</span>
                    <span class="text-xs text-list-text-muted">{{ t('IMPORT_FILE_LIMITS') }}</span>
                </button>
                <input
                    ref="fileInput"
                    class="hidden"
                    type="file"
                    accept=".csv,.txt,.xlsx,text/csv,text/plain,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    @change="onFile"
                />

                <div v-if="fileKind === 'csv' || fileKind === 'txt'" class="flex max-w-xs flex-col gap-1 text-xs font-semibold text-list-text-muted">
                    {{ t('DATA_TRANSFER_DELIMITER') }}
                    <ChoiceDropdown v-model="delimiter" :options="delimiterChoices" />
                </div>
                <p v-if="fileKind === 'xlsx'" class="m-0 text-xs text-list-text-muted">{{ t('IMPORT_FIRST_SHEET') }}</p>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex min-h-9 cursor-pointer items-center rounded-lg border border-slate-200 px-3 text-sm font-semibold disabled:opacity-50"
                        :disabled="!hasFields"
                        @click="downloadTemplate"
                    >
                        {{ t('DATA_TRANSFER_TEMPLATE') }}
                    </button>
                    <div class="inline-flex min-w-40 items-center gap-2 text-xs font-semibold text-list-text-muted">
                        {{ t('IMPORT_TEMPLATE_FORMAT') }}
                        <ChoiceDropdown v-model="format" class="min-w-28 flex-1" :options="formatChoices" />
                    </div>
                </div>
            </section>

            <section v-else-if="step === 2" class="flex min-w-0 flex-col gap-3">
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <span class="font-semibold">{{ t('IMPORT_MAPPED_COUNT', { mapped: mappedCount, total: columns.length }) }}</span>
                    <span v-if="attentionCount" class="font-medium text-amber-700">
                        {{ t('IMPORT_MAP_ATTENTION', { count: attentionCount }) }}
                    </span>
                    <span v-else class="font-medium text-emerald-700">{{ t('IMPORT_MAP_READY') }}</span>
                </div>
                <p v-if="missingRequired.length" class="m-0 text-sm font-medium text-error">
                    {{ t('IMPORT_REQUIRED_MISSING', { fields: missingRequired.join(', ') }) }}
                </p>
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full table-fixed border-collapse text-sm">
                        <colgroup>
                            <col class="w-[20%]" />
                            <col class="w-[34%]" />
                            <col class="w-[28%]" />
                            <col class="w-[18%]" />
                        </colgroup>
                        <thead class="sticky top-0 bg-slate-50 text-left text-xs font-semibold text-slate-500">
                            <tr>
                                <th class="px-3 py-2">{{ t('DATA_TRANSFER_COLUMN') }}</th>
                                <th class="px-3 py-2">{{ t('DATA_TRANSFER_SAMPLES') }}</th>
                                <th class="px-3 py-2">{{ t('IMPORT_MAP_TO') }}</th>
                                <th class="px-3 py-2">{{ t('IMPORT_FIELD_ACTION') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="column in columns" :key="column.id" class="border-t border-slate-100">
                                <td class="truncate px-3 py-2 font-medium" :title="column.label">{{ column.label }}</td>
                                <td class="truncate px-3 py-2 text-list-text-muted" :title="sampleText(column.samples)">
                                    {{ sampleText(column.samples) }}
                                </td>
                                <td class="px-3 py-2">
                                    <ChoiceDropdown
                                        :model-value="mappingFor(column.id)?.fieldId ?? ''"
                                        :options="fieldChoices"
                                        @update:model-value="setField(column.id, $event)"
                                    />
                                </td>
                                <td class="px-3 py-2 text-xs font-semibold" :class="mappingTone(column.id)">
                                    {{ mappingStatus(column.id) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <details class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <summary class="cursor-pointer font-semibold">{{ t('IMPORT_PREVIEW_TOGGLE') }}</summary>
                    <div class="mt-2 min-w-0 overflow-auto">
                        <table class="w-max min-w-full border-collapse text-xs">
                            <thead>
                                <tr>
                                    <th v-for="column in columns" :key="column.id" class="whitespace-nowrap px-3 py-1 text-left font-semibold">{{ column.label }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in rawPreview" :key="index" class="border-t border-slate-100">
                                    <td v-for="column in columns" :key="column.id" class="whitespace-nowrap px-3 py-1">
                                        {{ row[column.id] }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </details>
            </section>

            <section v-else-if="step === 3" class="flex min-w-0 flex-col gap-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="flex min-w-0 flex-col gap-1 text-xs font-semibold text-list-text-muted">
                        {{ t('IMPORT_STRATEGY') }}
                        <ChoiceDropdown v-model="strategy" :options="strategyChoices" />
                    </div>
                    <div v-if="partialAllowed" class="flex min-w-0 flex-col gap-1 text-xs font-semibold text-list-text-muted">
                        {{ t('IMPORT_POLICY') }}
                        <ChoiceDropdown v-model="policy" :options="policyChoices" />
                    </div>
                </div>
                <p v-if="busy && !report" class="m-0 text-sm text-list-text-muted">{{ t('IMPORT_CHECKING') }}</p>
                <div v-if="report" class="grid grid-cols-2 gap-2 sm:grid-cols-5" :class="busy ? 'opacity-60' : ''">
                    <p class="m-0 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <span class="block text-xs text-list-text-muted">{{ t('IMPORT_FILTER_NEW') }}</span>
                        <span class="font-semibold tabular-nums">{{ counts.created }}</span>
                    </p>
                    <p class="m-0 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <span class="block text-xs text-list-text-muted">{{ t('IMPORT_FILTER_UPDATES') }}</span>
                        <span class="font-semibold tabular-nums">{{ counts.updated }}</span>
                    </p>
                    <p class="m-0 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <span class="block text-xs text-list-text-muted">{{ t('IMPORT_FILTER_UNCHANGED') }}</span>
                        <span class="font-semibold tabular-nums">{{ counts.unchanged }}</span>
                    </p>
                    <p class="m-0 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <span class="block text-xs text-list-text-muted">{{ t('IMPORT_FILTER_SKIPPED') }}</span>
                        <span class="font-semibold tabular-nums">{{ counts.skipped }}</span>
                    </p>
                    <p class="m-0 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <span class="block text-xs text-list-text-muted">{{ t('IMPORT_FILTER_ERRORS') }}</span>
                        <span class="font-semibold tabular-nums">{{ counts.invalid }}</span>
                    </p>
                </div>
                <label
                    v-if="partialAllowed && policy === 'valid_rows' && (report?.invalid ?? 0) > 0"
                    class="flex cursor-pointer items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm"
                >
                    <input v-model="confirmPartial" class="mt-1" type="checkbox" />
                    <span>{{ t('IMPORT_PARTIAL_CONFIRM', { count: report?.invalid ?? 0, eligible: (report?.created ?? 0) + (report?.updated ?? 0) }) }}</span>
                </label>
                <button
                    v-if="(report?.errors.length ?? 0) > 0"
                    type="button"
                    class="w-fit cursor-pointer text-sm font-semibold text-primary"
                    @click="downloadErrors"
                >
                    {{ t('IMPORT_DOWNLOAD_ERRORS') }}
                </button>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="filter in visibleFilters"
                        :key="filter"
                        type="button"
                        class="cursor-pointer rounded-full border px-2.5 py-1 text-xs font-semibold"
                        :class="rowFilter === filter ? 'border-primary bg-primary/10 text-primary' : 'border-slate-200 text-slate-600'"
                        @click="rowFilter = filter"
                    >
                        {{ t(filterLabel(filter)) }}
                    </button>
                    <input
                        v-model="rowQuery"
                        class="min-h-8 min-w-40 flex-1 rounded-lg border border-slate-200 px-2 text-sm"
                        type="search"
                        :placeholder="t('IMPORT_SEARCH_ROWS')"
                    />
                </div>
                <div class="min-w-0 max-h-[42vh] overflow-auto rounded-xl border border-slate-200">
                    <table class="w-max min-w-full border-collapse text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50 text-left text-xs font-semibold text-slate-500">
                            <tr>
                                <th class="whitespace-nowrap px-3 py-2">{{ t('IMPORT_ROW') }}</th>
                                <th v-for="field in reviewFields" :key="field.id" class="whitespace-nowrap px-3 py-2">{{ field.label }}</th>
                                <th class="whitespace-nowrap px-3 py-2">{{ t('IMPORT_FIELD_ACTION') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="entry in pagedRows" :key="entry.index">
                                <tr class="cursor-pointer border-t border-slate-100 hover:bg-slate-50" @click="expanded = expanded === entry.index ? -1 : entry.index">
                                    <td class="whitespace-nowrap px-3 py-2 tabular-nums">{{ entry.index + 1 }}</td>
                                    <td
                                        v-for="field in reviewFields"
                                        :key="field.id"
                                        class="whitespace-nowrap px-3 py-2"
                                        :class="fieldChanged(entry.index, field.id) ? 'bg-amber-50 font-medium' : ''"
                                    >
                                        {{ entry.row[field.id] }}
                                    </td>
                                    <td class="px-3 py-2 text-xs font-semibold">{{ t(statusLabel(entry.status)) }}</td>
                                </tr>
                                <tr v-if="expanded === entry.index" class="border-t border-slate-100 bg-slate-50">
                                    <td :colspan="reviewFields.length + 2" class="px-3 py-2 text-xs">
                                        <p v-for="error in errorsFor(entry.index)" :key="error" class="m-0 text-error">{{ error }}</p>
                                        <table v-if="changesFor(entry.index).length" class="w-full border-collapse text-xs">
                                            <thead>
                                                <tr class="text-left text-slate-500">
                                                    <th class="py-1 pr-3 font-semibold">{{ t('IMPORT_FIELD') }}</th>
                                                    <th class="py-1 pr-3 font-semibold">{{ t('IMPORT_CURRENT') }}</th>
                                                    <th class="py-1 font-semibold">{{ t('IMPORT_IMPORTED') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="change in changesFor(entry.index)" :key="change.field" class="border-t border-slate-200">
                                                    <td class="py-1 pr-3 font-medium">{{ change.field }}</td>
                                                    <td class="py-1 pr-3">{{ change.from || t('IMPORT_NOT_SET') }}</td>
                                                    <td class="py-1 font-medium text-amber-800">{{ change.to || t('IMPORT_NOT_SET') }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <p v-if="!errorsFor(entry.index).length && !changesFor(entry.index).length" class="m-0 text-list-text-muted">
                                            {{ t('IMPORT_NO_ROW_DETAIL') }}
                                        </p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p v-if="filteredRows.length > pageSize" class="m-0 flex items-center justify-between text-xs text-list-text-muted">
                    <span>{{ t('IMPORT_SHOWING_ROWS', { from: pageStart + 1, to: pageEnd, total: filteredRows.length }) }}</span>
                    <span class="flex gap-2">
                        <button type="button" class="cursor-pointer font-semibold disabled:opacity-40" :disabled="page === 0" @click="page -= 1">
                            {{ t('IMPORT_BACK') }}
                        </button>
                        <button type="button" class="cursor-pointer font-semibold disabled:opacity-40" :disabled="pageEnd >= filteredRows.length" @click="page += 1">
                            {{ t('IMPORT_NEXT') }}
                        </button>
                    </span>
                </p>
            </section>

            <section v-else class="mx-auto flex max-w-md flex-col items-center gap-2 py-8 text-center">
                <Icon icon="material-symbols:check-circle-rounded" class="size-10 text-emerald-600" />
                <h3 class="m-0 text-lg font-semibold">{{ t('IMPORT_COMPLETED') }}</h3>
                <p class="m-0 text-sm text-list-text-muted">{{ completionMessage }}</p>
                <p class="m-0 text-sm font-medium">{{ completionCounts }}</p>
                <button
                    v-if="(report?.errors.length ?? 0) > 0"
                    type="button"
                    class="mt-2 cursor-pointer text-sm font-semibold text-primary"
                    @click="downloadErrors"
                >
                    {{ t('IMPORT_DOWNLOAD_ERRORS') }}
                </button>
            </section>
        </div>

        <p v-if="message && step < 4" class="m-0 shrink-0 border-t border-slate-100 px-5 py-2 text-sm font-medium">{{ message }}</p>

        <footer class="flex shrink-0 items-center justify-between gap-3 border-t border-slate-200 px-5 py-3">
            <div>
                <button
                    v-if="step > 1 && step < 4"
                    type="button"
                    class="inline-flex min-h-9 cursor-pointer items-center rounded-lg border border-slate-200 px-3 text-sm font-semibold"
                    :disabled="busy"
                    @click="step -= 1"
                >
                    {{ t('IMPORT_BACK') }}
                </button>
                <button
                    v-else-if="step < 4"
                    type="button"
                    class="inline-flex min-h-9 cursor-pointer items-center rounded-lg px-3 text-sm font-semibold text-slate-600"
                    @click="requestClose"
                >
                    {{ t('IMPORT_CANCEL') }}
                </button>
            </div>
            <div class="flex gap-2">
                <button
                    v-if="step === 4"
                    type="button"
                    class="inline-flex min-h-9 cursor-pointer items-center rounded-lg border border-slate-200 px-3 text-sm font-semibold"
                    @click="importAnother"
                >
                    {{ t('IMPORT_ANOTHER') }}
                </button>
                <button
                    v-if="step < 4"
                    type="button"
                    class="inline-flex min-h-9 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="!primaryEnabled || busy"
                    @click="primary"
                >
                    {{ busy ? t('IMPORT_WORKING') : primaryLabel }}
                </button>
                <button
                    v-else
                    type="button"
                    class="inline-flex min-h-9 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-white"
                    @click="finish"
                >
                    {{ t('IMPORT_DONE') }}
                </button>
            </div>
        </footer>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import HelpTrigger from '@/components/help/HelpTrigger.vue';
    import { computed, inject, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useDataTransfer, type TransferFieldChange } from '@/composables/useDataTransfer';
    import ChoiceDropdown from '@/components/inputs/ChoiceDropdown.vue';

    defineOptions({ name: 'ImportDataModal' });

    const props = defineProps<{
        props?: {
            modelKey?: string;
            refresh?: () => void;
        };
    }>();

    const { t } = useI18n();
    const dismissModal = inject<() => void>('dismissModal', () => undefined);
    const fileInput = ref<HTMLInputElement | null>(null);
    const shell = ref<HTMLElement | null>(null);
    const measure = ref<HTMLElement | null>(null);
    const shellHeight = ref('');
    const sizeReady = ref(false);
    const dragging = ref(false);
    const step = ref(1);
    const saved = ref(false);
    const rowFilter = ref<'all' | 'new' | 'update' | 'unchanged' | 'error' | 'skip'>('all');
    const rowQuery = ref('');
    const page = ref(0);
    const expanded = ref(-1);
    const pageSize = 20;

    type CountKey = 'created' | 'updated' | 'unchanged' | 'skipped' | 'invalid';
    const countKeys: CountKey[] = ['created', 'updated', 'unchanged', 'skipped', 'invalid'];
    const counts = ref<Record<CountKey, number>>({ created: 0, updated: 0, unchanged: 0, skipped: 0, invalid: 0 });
    let countFrame = 0;
    let countTimer = 0;

    function countTargets(): Record<CountKey, number> {
        return {
            created: report.value?.created ?? 0,
            updated: report.value?.updated ?? 0,
            unchanged: report.value?.unchanged ?? 0,
            skipped: report.value?.skipped ?? 0,
            invalid: report.value?.invalid ?? 0,
        };
    }

    function stopCounts(): void {
        cancelAnimationFrame(countFrame);
        window.clearTimeout(countTimer);
    }

    function playCounts(): void {
        stopCounts();
        const targets = countTargets();
        const zeros = { created: 0, updated: 0, unchanged: 0, skipped: 0, invalid: 0 };
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const max = Math.max(...countKeys.map((key) => targets[key]));
        if (reduce || max === 0) {
            counts.value = targets;
            return;
        }
        counts.value = { ...zeros };
        if (max <= 40) {
            let current = 0;
            const stepCount = () => {
                current += 1;
                const next = { ...zeros };
                for (const key of countKeys) next[key] = Math.min(targets[key], current);
                counts.value = next;
                if (current < max) countTimer = window.setTimeout(stepCount, 48);
            };
            countTimer = window.setTimeout(stepCount, 48);
            return;
        }
        const started = performance.now();
        const tick = (now: number) => {
            const progress = Math.min(1, (now - started) / 520);
            const eased = 1 - (1 - progress) ** 3;
            const next = { ...zeros };
            for (const key of countKeys) next[key] = Math.round(targets[key] * eased);
            counts.value = next;
            if (progress < 1) countFrame = requestAnimationFrame(tick);
        };
        countFrame = requestAnimationFrame(tick);
    }

    async function syncSize(): Promise<void> {
        await nextTick();
        const el = measure.value;
        if (!el) return;
        const probe = el.cloneNode(true) as HTMLElement;
        probe.style.position = 'fixed';
        probe.style.left = '-10000px';
        probe.style.top = '0';
        probe.style.width = '100%';
        probe.style.height = 'auto';
        probe.style.maxHeight = 'none';
        probe.style.visibility = 'hidden';
        probe.style.pointerEvents = 'none';
        document.body.appendChild(probe);
        const cap = Math.max(320, Math.round(window.innerHeight * 0.9));
        const next = `${Math.min(probe.scrollHeight, cap)}px`;
        probe.remove();
        shellHeight.value = next;
        if (!sizeReady.value) requestAnimationFrame(() => { sizeReady.value = true; });
    }

    const steps = [
        { id: 1, label: 'IMPORT_STEP_UPLOAD' },
        { id: 2, label: 'IMPORT_STEP_FIELDS' },
        { id: 3, label: 'IMPORT_STEP_REVIEW' },
        { id: 4, label: 'IMPORT_STEP_COMPLETE' },
    ];

    const {
        modelKey,
        columns,
        mappings,
        delimiter,
        format,
        strategy,
        policy,
        strategyChoices,
        policyChoices,
        partialAllowed,
        confirmPartial,
        report,
        message,
        busy,
        rowCount,
        previewRows,
        sourceRows,
        fileName,
        fileSize,
        fileKind,
        hasFields,
        missingRequired,
        mapperFields,
        canPreview,
        canCommit,
        selectModel,
        loadFile,
        clearFile,
        downloadTemplate,
        preview,
        commit,
        downloadErrors,
        modelLabel,
    } = useDataTransfer();

    const fileSizeLabel = computed(() => {
        const size = fileSize.value;
        if (size < 1024) return `${size} B`;
        return `${Math.max(1, Math.round(size / 1024))} KB`;
    });

    const mappedCount = computed(
        () => mappings.value.filter((mapping) => mapping.decision === 'accepted' && mapping.fieldId).length,
    );

    const duplicateFields = computed(() => {
        const seen = new Set<string>();
        const duplicates = new Set<string>();
        for (const mapping of mappings.value) {
            if (mapping.decision !== 'accepted' || !mapping.fieldId) continue;
            if (seen.has(mapping.fieldId)) duplicates.add(mapping.fieldId);
            seen.add(mapping.fieldId);
        }
        return duplicates;
    });

    const attentionCount = computed(() => missingRequired.value.length + duplicateFields.value.size);

    const rawPreview = computed(() => sourceRows.value.slice(0, 5));

    const reviewFields = computed(() =>
        mapperFields.value.filter((field) =>
            mappings.value.some((mapping) => mapping.decision === 'accepted' && mapping.fieldId === field.id),
        ),
    );

    type RowStatus = 'create' | 'update' | 'unchanged' | 'skip' | 'error';

    const reviewed = computed(() =>
        previewRows.value.map((row, index) => ({ index, row, status: statusFor(index, row) })),
    );

    const visibleFilters = computed(() => {
        const filters: Array<'all' | 'new' | 'update' | 'unchanged' | 'error' | 'skip'> = ['all'];
        const present = new Set(reviewed.value.map((entry) => entry.status));
        if (present.has('create')) filters.push('new');
        if (present.has('update')) filters.push('update');
        if (present.has('unchanged')) filters.push('unchanged');
        if (present.has('error')) filters.push('error');
        if (present.has('skip')) filters.push('skip');
        return filters;
    });

    const filteredRows = computed(() => {
        const query = rowQuery.value.trim().toLowerCase();
        return reviewed.value.filter((entry) => {
            if (rowFilter.value === 'new' && entry.status !== 'create') return false;
            if (rowFilter.value === 'update' && entry.status !== 'update') return false;
            if (rowFilter.value === 'unchanged' && entry.status !== 'unchanged') return false;
            if (rowFilter.value === 'error' && entry.status !== 'error') return false;
            if (rowFilter.value === 'skip' && entry.status !== 'skip') return false;
            if (!query) return true;
            return Object.values(entry.row).some((value) => value.toLowerCase().includes(query));
        });
    });

    const pageStart = computed(() => page.value * pageSize);
    const pageEnd = computed(() => Math.min(filteredRows.value.length, pageStart.value + pageSize));
    const pagedRows = computed(() => filteredRows.value.slice(pageStart.value, pageEnd.value));

    const primaryEnabled = computed(() => {
        if (step.value === 1) return rowCount.value > 0;
        if (step.value === 2) return canPreview.value && missingRequired.value.length === 0;
        return canCommit.value;
    });

    const primaryLabel = computed(() => {
        if (step.value === 1) return t('IMPORT_CONTINUE');
        if (step.value === 2) return t('IMPORT_REVIEW_ACTION');
        const created = report.value?.created ?? report.value?.valid ?? 0;
        const updated = report.value?.updated ?? 0;
        if (updated > 0) return t('IMPORT_APPLY_COUNT', { count: created + updated });
        return t('IMPORT_IMPORT_COUNT', { count: created });
    });

    const completionMessage = computed(() =>
        t('IMPORT_COMPLETED_BODY', {
            count: (report.value?.created ?? 0) + (report.value?.updated ?? 0),
            model: modelLabel(modelKey.value),
        }),
    );

    const completionCounts = computed(() =>
        t('IMPORT_RESULT_COUNTS', {
            created: report.value?.created ?? 0,
            updated: report.value?.updated ?? 0,
            unchanged: report.value?.unchanged ?? 0,
            skipped: report.value?.skipped ?? 0,
            failed: report.value?.failed ?? report.value?.invalid ?? 0,
        }),
    );

    watch([strategy, policy], () => {
        if (step.value === 3 && rowCount.value > 0) {
            void preview();
        }
    });

    watch(
        [
            step,
            busy,
            expanded,
            page,
            rowFilter,
            confirmPartial,
            () => message.value,
            () => fileName.value,
            () => columns.value.length,
            () => report.value?.created,
            () => report.value?.updated,
            () => report.value?.unchanged,
            () => report.value?.skipped,
            () => report.value?.invalid,
        ],
        () => {
            void syncSize();
        },
    );

    watch(
        [step, busy, () => report.value?.created, () => report.value?.updated, () => report.value?.unchanged, () => report.value?.skipped, () => report.value?.invalid],
        () => {
            if (step.value !== 3 || !report.value) {
                stopCounts();
                counts.value = { created: 0, updated: 0, unchanged: 0, skipped: 0, invalid: 0 };
                return;
            }
            if (busy.value) return;
            playCounts();
        },
    );

    watch(rowFilter, () => {
        page.value = 0;
    });
    watch(rowQuery, () => {
        page.value = 0;
    });

    function mappingFor(columnId: string) {
        return mappings.value.find((mapping) => mapping.columnId === columnId);
    }

    function sampleText(samples: readonly string[] | undefined): string {
        return (samples ?? []).filter(Boolean).slice(0, 3).join(', ');
    }

    function setField(columnId: string, fieldId: string): void {
        mappings.value = mappings.value.map((mapping) =>
            mapping.columnId === columnId
                ? { ...mapping, fieldId: fieldId || null, decision: fieldId ? 'accepted' : 'denied' }
                : mapping,
        );
    }

    function mappingStatus(columnId: string): string {
        const mapping = mappingFor(columnId);
        if (!mapping || mapping.decision === 'denied' || !mapping.fieldId) return t('IMPORT_STATUS_IGNORED');
        if (duplicateFields.value.has(mapping.fieldId)) return t('IMPORT_STATUS_INVALID');
        if (mapping.fieldId === mapping.suggestionFieldId) return t('IMPORT_STATUS_MATCHED');
        return t('IMPORT_STATUS_MANUAL');
    }

    function mappingTone(columnId: string): string {
        const mapping = mappingFor(columnId);
        if (mapping?.fieldId && duplicateFields.value.has(mapping.fieldId)) return 'text-error';
        if (!mapping?.fieldId || mapping.decision === 'denied') return 'text-slate-400';
        return 'text-emerald-700';
    }

    function statusFor(index: number, row: Record<string, string>): RowStatus {
        const rowNumber = index + 1;
        if (Object.values(row).every((value) => value.trim() === '')) return 'skip';
        if (report.value?.errors.some((error) => error.row === rowNumber)) return 'error';
        const decision = report.value?.rows?.find((item) => item.row === rowNumber);
        if (decision?.operation === 'create' || decision?.operation === 'update' || decision?.operation === 'unchanged' || decision?.operation === 'skip') {
            return decision.operation;
        }
        if (report.value?.updates?.some((item) => item.row === rowNumber || item.employee_number === row.employee_number)) {
            return 'update';
        }
        if (report.value?.creates?.some((item) => item.row === rowNumber || item.employee_number === row.employee_number)) {
            return 'create';
        }
        if (typeof report.value?.updated === 'number') return 'unchanged';
        return 'create';
    }

    const delimiterChoices = computed(() => [
        { value: 'auto' as const, label: t('DATA_TRANSFER_DELIMITER_AUTO') },
        { value: ',' as const, label: t('DATA_TRANSFER_DELIMITER_COMMA') },
        { value: ';' as const, label: t('DATA_TRANSFER_DELIMITER_SEMICOLON') },
        { value: '\t' as const, label: t('DATA_TRANSFER_DELIMITER_TAB') },
        { value: '|' as const, label: t('DATA_TRANSFER_DELIMITER_PIPE') },
    ]);

    const formatChoices = computed(() => [
        { value: 'csv' as const, label: 'CSV' },
        { value: 'txt' as const, label: 'TXT' },
        { value: 'xlsx' as const, label: 'Excel' },
    ]);

    const fieldChoices = computed(() => [
        { value: '', label: t('DATA_TRANSFER_IGNORE') },
        ...mapperFields.value.map((field) => ({
            value: field.id,
            label: field.required ? `${field.label} (${t('DATA_TRANSFER_REQUIRED')})` : field.label,
        })),
    ]);

    function statusLabel(status: RowStatus): string {
        if (status === 'create') return 'IMPORT_STATUS_CREATE';
        if (status === 'update') return 'IMPORT_STATUS_UPDATE';
        if (status === 'unchanged') return 'IMPORT_STATUS_UNCHANGED';
        if (status === 'skip') return 'IMPORT_STATUS_SKIP';
        return 'IMPORT_STATUS_ERROR';
    }

    function filterLabel(filter: 'all' | 'new' | 'update' | 'unchanged' | 'error' | 'skip'): string {
        if (filter === 'all') return 'IMPORT_FILTER_ALL';
        if (filter === 'new') return 'IMPORT_FILTER_NEW';
        if (filter === 'update') return 'IMPORT_FILTER_UPDATES';
        if (filter === 'unchanged') return 'IMPORT_FILTER_UNCHANGED';
        if (filter === 'error') return 'IMPORT_FILTER_ERRORS';
        return 'IMPORT_FILTER_SKIPPED';
    }

    function errorsFor(index: number): string[] {
        return report.value?.errors.find((error) => error.row === index + 1)?.messages ?? [];
    }

    function changesFor(index: number): TransferFieldChange[] {
        const decision = report.value?.rows?.find((item) => item.row === index + 1);
        if (decision?.changes) return decision.changes;
        const row = previewRows.value[index];
        const match = report.value?.updates?.find(
            (item) => item.row === index + 1 || item.employee_number === row?.employee_number,
        );
        return match?.changes ?? [];
    }

    function fieldChanged(index: number, fieldId: string): boolean {
        return changesFor(index).some((change) => change.field === fieldId);
    }

    function stepClass(id: number): string {
        if (id === step.value) return 'text-primary';
        if (id < step.value) return 'text-slate-700';
        return 'text-slate-400';
    }

    function badgeClass(id: number): string {
        if (stepHasError(id)) return 'bg-red-100 text-error';
        if (id === step.value) return 'bg-primary text-white';
        if (id < step.value) return 'bg-emerald-100 text-emerald-700';
        return 'bg-slate-100 text-slate-500';
    }

    function stepHasError(id: number): boolean {
        return id === 3 && (report.value?.invalid ?? 0) > 0;
    }

    function canOpenStep(id: number): boolean {
        if (saved.value) return id === 4;
        return id < step.value;
    }

    function goTo(id: number): void {
        if (canOpenStep(id)) step.value = id;
    }

    function onDrop(event: DragEvent): void {
        dragging.value = false;
        const file = event.dataTransfer?.files?.[0];
        if (file && hasFields.value) void loadFile(file);
    }

    function onFile(event: Event): void {
        const file = (event.target as HTMLInputElement).files?.[0];
        if (file) void loadFile(file);
    }

    function removeFile(): void {
        clearFile();
        if (fileInput.value) fileInput.value.value = '';
    }

    async function primary(): Promise<void> {
        if (step.value === 1 && rowCount.value > 0) {
            step.value = 2;
            return;
        }
        if (step.value === 2) {
            step.value = 3;
            await preview();
            return;
        }
        if (step.value === 3) {
            const ok = await commit();
            if (!ok) return;
            saved.value = true;
            step.value = 4;
            props.props?.refresh?.();
        }
    }

    function requestClose(): void {
        if (!saved.value && rowCount.value > 0 && !window.confirm(t('IMPORT_DISCARD_CONFIRM'))) return;
        dismissModal();
    }

    function finish(): void {
        dismissModal();
    }

    async function importAnother(): Promise<void> {
        saved.value = false;
        step.value = 1;
        report.value = null;
        message.value = '';
        const key = props.props?.modelKey;
        if (key) await selectModel(key);
    }

    onMounted(() => {
        const key = props.props?.modelKey;
        if (key) void selectModel(key);
        void syncSize();
        window.addEventListener('keydown', onEscape);
    });

    onBeforeUnmount(() => {
        stopCounts();
        window.removeEventListener('keydown', onEscape);
    });

    function onEscape(event: KeyboardEvent): void {
        if (event.key === 'Escape') requestClose();
    }
</script>

<style>
    .import-modal-panel {
        width: min(76rem, calc(100vw - 2rem)) !important;
        max-width: calc(100vw - 2rem) !important;
    }
</style>
