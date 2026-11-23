<template>
    <PageLayout class="h-full" :label="t('DATA_TRANSFER_TITLE')">
        <div class="flex h-full min-h-0 flex-col gap-4 overflow-auto p-1">
            <header class="shrink-0">
                <h1 class="text-lg font-semibold text-list-text">{{ t('DATA_TRANSFER_TITLE') }}</h1>
                <p class="mt-1 max-w-3xl text-sm text-list-text-muted">{{ t('DATA_TRANSFER_INTRO') }}</p>
                <p v-if="modelKey === 'employees'" class="mt-2 max-w-3xl text-sm text-list-text-muted">
                    {{ t('DATA_TRANSFER_EMPLOYEE_INTRO') }}
                </p>
            </header>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="flex flex-col gap-1 text-xs font-semibold text-list-text-muted">
                    {{ t('DATA_TRANSFER_MODEL') }}
                    <ChoiceDropdown :model-value="modelKey" :options="modelChoices" @update:model-value="selectModel" />
                </div>
                <div class="flex flex-col gap-1 text-xs font-semibold text-list-text-muted">
                    {{ t('DATA_TRANSFER_DELIMITER') }}
                    <ChoiceDropdown v-model="delimiter" :options="delimiterChoices" />
                </div>
                <div class="flex flex-col gap-1 text-xs font-semibold text-list-text-muted">
                    {{ t('DATA_TRANSFER_FORMAT') }}
                    <ChoiceDropdown v-model="format" :options="formatChoices" />
                </div>
                <label class="flex flex-col gap-1 text-xs font-semibold text-list-text-muted">
                    {{ t('DATA_TRANSFER_FILE') }}
                    <input
                        class="min-h-10 text-sm text-list-text file:mr-3 file:min-h-10 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:font-semibold file:text-white"
                        type="file"
                        accept=".csv,.txt,.xlsx,text/csv,text/plain,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        @change="onFile"
                    />
                </label>
            </div>

            <p class="m-0 text-xs text-list-text-muted">{{ t('DATA_TRANSFER_XLSX_NOTE') }}</p>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="flex flex-col gap-1 text-xs font-semibold text-list-text-muted">
                    {{ t('IMPORT_STRATEGY') }}
                    <ChoiceDropdown v-model="strategy" :options="strategyChoices" />
                </div>
                <div v-if="partialAllowed" class="flex flex-col gap-1 text-xs font-semibold text-list-text-muted">
                    {{ t('IMPORT_POLICY') }}
                    <ChoiceDropdown v-model="policy" :options="policyChoices" />
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-list-border bg-list-surface px-3 text-sm font-semibold text-list-text"
                    @click="downloadTemplate"
                >
                    {{ t('DATA_TRANSFER_TEMPLATE') }}
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-list-border bg-list-surface px-3 text-sm font-semibold text-list-text"
                    :disabled="busy"
                    @click="downloadExport"
                >
                    {{ t('DATA_TRANSFER_EXPORT') }}
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-10 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="!canPreview"
                    @click="preview"
                >
                    {{ t('DATA_TRANSFER_PREVIEW') }}
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-10 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="!canCommit"
                    @click="commit"
                >
                    {{ t('DATA_TRANSFER_COMMIT') }}
                </button>
            </div>

            <p v-if="message" class="m-0 text-sm font-medium text-list-text">{{ message }}</p>

            <label
                v-if="partialAllowed && policy === 'valid_rows' && (report?.invalid ?? 0) > 0"
                class="flex cursor-pointer items-start gap-2 text-sm text-list-text"
            >
                <input v-model="confirmPartial" class="mt-1" type="checkbox" />
                <span>{{ t('IMPORT_PARTIAL_CONFIRM', { count: report?.invalid ?? 0, eligible: (report?.created ?? 0) + (report?.updated ?? 0) }) }}</span>
            </label>

            <div v-if="report" class="flex flex-wrap gap-3 text-sm text-list-text">
                <span>{{ t('DATA_TRANSFER_WILL_CREATE', { count: report.created ?? 0 }) }}</span>
                <span>{{ t('DATA_TRANSFER_WILL_UPDATE', { count: report.updated ?? 0 }) }}</span>
                <span>{{ t('DATA_TRANSFER_UNCHANGED', { count: report.unchanged ?? 0 }) }}</span>
                <span>{{ t('DATA_TRANSFER_INVALID', { count: report.invalid }) }}</span>
                <span>{{ t('DATA_TRANSFER_SKIPPED', { count: report.skipped }) }}</span>
                <button
                    v-if="report.errors.length"
                    type="button"
                    class="cursor-pointer font-semibold text-primary"
                    @click="downloadErrors"
                >
                    {{ t('IMPORT_DOWNLOAD_ERRORS') }}
                </button>
            </div>
            <ul v-if="report?.errors.length" class="m-0 flex list-none flex-col gap-1 p-0 text-sm text-error">
                <li v-for="error in report.errors" :key="`${error.row}-${error.messages.join('|')}`">
                    {{ t('DATA_TRANSFER_ROW_ERROR', { row: error.row }) }}: {{ error.messages.join(' ') }}
                </li>
            </ul>

            <ImportMapper v-model:mappings="mappings" :columns="columns" :fields="mapperFields" :labels="mapperLabels" />
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { ImportMapper, PageLayout } from '@freelance/vue-ui';
    import { computed, onMounted } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useDataTransfer } from '@/composables/useDataTransfer';
    import ChoiceDropdown from '@/components/inputs/ChoiceDropdown.vue';

    defineOptions({ name: 'DataTransferPage' });

    const { t } = useI18n();
    const {
        modelKey,
        modelKeys,
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
        mapperFields,
        mapperLabels,
        canPreview,
        canCommit,
        loadModels,
        selectModel,
        loadFile,
        downloadTemplate,
        downloadExport,
        preview,
        commit,
        downloadErrors,
        modelLabel,
    } = useDataTransfer();

    const modelChoices = computed(() => modelKeys.value.map((key) => ({ value: key, label: modelLabel(key) })));
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

    function onFile(event: Event): void {
        const file = (event.target as HTMLInputElement).files?.[0];
        if (file) {
            void loadFile(file);
        }
    }

    onMounted(() => {
        void loadModels().catch(() => {
            message.value = t('DATA_TRANSFER_FAILED');
        });
    });
</script>
