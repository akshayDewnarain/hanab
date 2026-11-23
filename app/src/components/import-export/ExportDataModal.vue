<template>
    <div class="flex flex-col gap-4">
        <div>
            <h2 class="m-0 text-base font-semibold text-list-text">
                {{ t(purpose === 'template' ? 'TEMPLATE_HEADING' : 'EXPORT_DATA_HEADING', { model: modelLabel(modelKey) }) }}
            </h2>
            <p class="mt-1 mb-0 text-sm text-list-text-muted">
                {{ t(purpose === 'template' ? 'TEMPLATE_HELP' : 'EXPORT_DATA_HELP') }}
            </p>
        </div>

        <div class="flex flex-col gap-1 text-xs font-semibold text-list-text-muted">
            {{ t('DATA_TRANSFER_FORMAT') }}
            <ChoiceDropdown v-model="format" :options="formatChoices" />
        </div>

        <div v-if="purpose === 'template'" class="flex flex-col gap-1 text-xs font-semibold text-list-text-muted">
            {{ t('DATA_TRANSFER_DELIMITER') }}
            <ChoiceDropdown v-model="delimiter" :options="delimiterChoices" />
        </div>

        <fieldset v-else class="m-0 flex flex-col gap-2 border-0 p-0">
            <legend class="mb-1 text-xs font-semibold text-list-text-muted">{{ t('EXPORT_SCOPE') }}</legend>
            <label class="flex items-start gap-2 text-sm text-list-text">
                <input v-model="scope" class="mt-1" type="radio" value="filtered" />
                <span>
                    <span class="font-semibold">{{ t('EXPORT_SCOPE_FILTERED') }}</span>
                    <span class="mt-0.5 block text-xs text-list-text-muted">{{ t('EXPORT_SCOPE_FILTERED_HELP') }}</span>
                </span>
            </label>
            <label class="flex items-start gap-2 text-sm text-list-text">
                <input v-model="scope" class="mt-1" type="radio" value="all" />
                <span>
                    <span class="font-semibold">{{ t('EXPORT_SCOPE_ALL') }}</span>
                    <span class="mt-0.5 block text-xs text-list-text-muted">{{ t('EXPORT_SCOPE_ALL_HELP') }}</span>
                </span>
            </label>
        </fieldset>

        <p v-if="message" class="m-0 text-sm font-medium text-list-text">{{ message }}</p>

        <div class="flex justify-end">
            <button
                type="button"
                class="inline-flex min-h-10 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="busy || !hasFields"
                @click="run"
            >
                {{ busy ? t('IMPORT_WORKING') : t(purpose === 'template' ? 'DATA_TRANSFER_TEMPLATE' : 'EXPORT_DOWNLOAD') }}
            </button>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useDataTransfer } from '@/composables/useDataTransfer';
    import ChoiceDropdown from '@/components/inputs/ChoiceDropdown.vue';

    defineOptions({ name: 'ExportDataModal' });

    const props = defineProps<{
        props?: {
            modelKey?: string;
            purpose?: 'export' | 'template';
            filters?: Record<string, unknown>;
            search?: string;
        };
    }>();

    const { t } = useI18n();
    const scope = ref<'filtered' | 'all'>('filtered');
    const purpose = props.props?.purpose ?? 'export';
    const formatChoices = computed(() => [
        { value: 'csv' as const, label: 'CSV' },
        { value: 'txt' as const, label: 'TXT' },
        { value: 'xlsx' as const, label: 'Excel' },
    ]);
    const delimiterChoices = computed(() => [
        { value: ',' as const, label: t('DATA_TRANSFER_DELIMITER_COMMA') },
        { value: ';' as const, label: t('DATA_TRANSFER_DELIMITER_SEMICOLON') },
        { value: '\t' as const, label: t('DATA_TRANSFER_DELIMITER_TAB') },
        { value: '|' as const, label: t('DATA_TRANSFER_DELIMITER_PIPE') },
    ]);

    const { modelKey, delimiter, format, message, busy, hasFields, selectModel, downloadTemplate, downloadExport, modelLabel } =
        useDataTransfer();

    async function run(): Promise<void> {
        if (purpose === 'template') {
            downloadTemplate();
            return;
        }
        const query =
            scope.value === 'filtered'
                ? {
                      filter: props.props?.filters ?? {},
                      search: props.props?.search ?? '',
                  }
                : undefined;
        await downloadExport(query);
    }

    onMounted(() => {
        const key = props.props?.modelKey;
        if (key) void selectModel(key);
    });
</script>
