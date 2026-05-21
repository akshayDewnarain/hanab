<template>
    <div :class="layout === 'sidebar' ? 'flex flex-col gap-0.5' : 'space-y-2'">
        <p v-if="loading" class="text-xs text-slate-500">{{ t('FORM_LOADING') }}…</p>
        <p v-else-if="loadError" class="text-xs text-red-600">{{ loadError }}</p>
        <p v-else-if="options.length === 0" class="text-xs text-slate-500">{{ t('FORM_NO_OPTIONS') }}</p>
        <template v-else>
            <div
                v-for="option in options"
                :key="option.value"
            :class="[
                'flex items-center gap-2.5 rounded-md hover:bg-slate-50',
                layout === 'sidebar' ? 'px-2 py-1.5 hover:bg-white/80' : 'px-1 py-1',
            ]"
        >
            <input
                :id="`${inputIdPrefix}-${option.value}`"
                :checked="isChecked(option.value)"
                class="size-4 shrink-0 cursor-pointer rounded border-slate-300 text-[var(--color-primary)]"
                type="checkbox"
                @change="onToggle(option.value)"
            />
            <label
                :for="`${inputIdPrefix}-${option.value}`"
                class="min-w-0 flex-1 cursor-pointer text-sm leading-snug text-slate-700"
            >
                {{ option.label }}
                </label>
            </div>
        </template>
    </div>
</template>

<script lang="ts" setup>
    import { onMounted, ref, useId } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { convertToString } from '@/composables/useGlobalHelpers';
    import type TableColumn from '@/modules/models/support/list-views/TableColumn.ts';

    export type MultiSelectOption = {
        value: string;
        label: string;
    };

    const props = withDefaults(
        defineProps<{
            column: TableColumn;
            modelValue: string[];
            layout?: 'default' | 'sidebar';
        }>(),
        {
            layout: 'default',
        },
    );

    const emit = defineEmits<{
        (e: 'update:modelValue', value: string[]): void;
    }>();

    const { t } = useI18n();
    const inputIdPrefix = useId();

    const loading = ref(true);
    const loadError = ref<string | null>(null);
    const options = ref<MultiSelectOption[]>([]);

    function isChecked(value: string): boolean {
        return props.modelValue.includes(value);
    }

    function onToggle(value: string): void {
        const current = [...props.modelValue];

        if (current.includes(value)) {
            emit(
                'update:modelValue',
                current.filter((item) => item !== value),
            );
            return;
        }

        emit('update:modelValue', [...current, value]);
    }

    async function loadOptions(): Promise<void> {
        const config = props.column.component?.multiSelect;
        if (!config?.model) {
            loadError.value = t('ERROR_FAILED_TO_FETCH');
            loading.value = false;
            return;
        }

        loading.value = true;
        loadError.value = null;

        try {
            const model = new config.model();
            const labelKey = config.labelKey ?? 'name';
            const valueKey = config.valueKey ?? 'id';

            const response = await model.index({
                page: 1,
                per_page: 500,
                filter: config.staticFilter ?? {},
            });

            const payload = response.data as { data?: unknown[] };
            const rows = Array.isArray(payload.data) ? payload.data : [];

            options.value = rows
                .map((row) => {
                    const record = row as Record<string, unknown>;
                    const rawValue = record[valueKey];
                    const rawLabel = record[labelKey];
                    if (rawValue == null) {
                        return null;
                    }
                    return {
                        value: convertToString(rawValue),
                        label: rawLabel != null ? convertToString(rawLabel) : convertToString(rawValue),
                    };
                })
                .filter((option): option is MultiSelectOption => option != null)
                .sort((a, b) => a.label.localeCompare(b.label, undefined, { sensitivity: 'base' }));
        } catch {
            loadError.value = t('ERROR_FAILED_TO_FETCH');
            options.value = [];
        } finally {
            loading.value = false;
        }
    }

    onMounted(() => {
        void loadOptions();
    });
</script>
