<template>
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-4 flex items-center gap-2">
            <Icon class="h-4 w-4 text-[var(--color-primary)]" icon="material-symbols:info-outline" />
            <span class="text-sm font-semibold text-gray-800">{{ t(options.label) }}</span>
        </div>

        <div class="flex flex-col gap-4">
            <SelectFieldComponent
                :model-value="activityModelValue"
                :options="activitySelectOptions"
                @update:model-value="onActivityUpdate"
                @blur="() => {}"
            />

            <SelectFieldComponent
                :model-value="statusModelValue"
                :options="statusSelectOptions"
                @update:model-value="onStatusUpdate"
                @blur="() => {}"
            />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <NumberFieldComponent
                    :model-value="guestsModelValue"
                    :options="guestsFieldOptions"
                    @update:model-value="(v) => props.setFieldValue?.('guests', v)"
                    @blur="() => {}"
                />
                <NumberFieldComponent
                    :model-value="childrenModelValue"
                    :options="childrenFieldOptions"
                    @update:model-value="(v) => props.setFieldValue?.('children', v)"
                    @blur="() => {}"
                />
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import Activity from '@/models/general/Activity';
    import { ReservationStatus } from '@/modules/enums/general/ReservationStatus';
    import SelectFieldComponent from '@/components/forms/inputs/SelectFieldComponent.vue';
    import NumberFieldComponent from '@/components/forms/inputs/NumberFieldComponent.vue';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
    import type { NumberFieldOptions } from '@/modules/types/support/inputs/NumberFieldOptions.ts';
    import type { SelectFieldOptions } from '@/modules/types/support/inputs/SelectFieldOptions.ts';

    defineComponent({ name: 'OrderGeneralSection' });

    const props = defineProps<{
        modelValue?: unknown;
        options: BaseInputFieldOptions;
        error?: string;
        formData?: Record<string, unknown>;
        setFieldValue?: (name: string, value: unknown) => void;
        getFieldValue?: (name: string) => unknown;
    }>();

    const { t } = useI18n();

    function statusEnumOptions(): { label: string; value: string }[] {
        return Object.entries(ReservationStatus)
            .filter(([k]) => Number.isNaN(Number(k)))
            .map(([key, value]) => ({
                label: 'GENERAL_' + key,
                value: value as string,
            }));
    }

    const activitySelectOptions = computed<SelectFieldOptions>(() => ({
        name: 'activity_id',
        label: 'GENERAL_ACTIVITY',
        required: false,
        placeholder: 'GENERAL_ACTIVITY_PLACEHOLDER',
        position: 'bottom',
        align: 'right',
        remoteFetch: {
            instance: Activity,
            map: { label: 'name', value: 'id' },
            params: { per_page: 100 },
            immediate: true,
            translate: false,
        },
    }));

    const statusSelectOptions = computed<SelectFieldOptions>(() => ({
        name: 'status',
        label: 'GENERAL_STATUS',
        required: false,
        placeholder: 'GENERAL_STATUS_PLACEHOLDER',
        position: 'bottom',
        align: 'right',
        options: statusEnumOptions().map((o) => ({
            label: t(o.label),
            value: o.value,
        })),
    }));

    const guestsFieldOptions = computed<NumberFieldOptions>(() => ({
        name: 'guests',
        label: 'GENERAL_GUESTS',
        required: false,
        placeholder: 'GENERAL_GUESTS_PLACEHOLDER',
        validators: {
            required: false,
            min: 1,
        },
        min: 1,
    }));

    const childrenFieldOptions = computed<NumberFieldOptions>(() => ({
        name: 'children',
        label: 'GENERAL_CHILDREN',
        required: false,
        placeholder: 'GENERAL_CHILDREN_PLACEHOLDER',
        validators: {
            required: false,
            min: 0,
        },
        min: 0,
    }));

    const activityModelValue = computed(() => {
        const v = props.getFieldValue?.('activity_id') ?? props.formData?.activity_id;
        if (v === '' || v == null) return '';
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : '';
    });

    const statusModelValue = computed(() => {
        const v = props.getFieldValue?.('status') ?? props.formData?.status;
        if (v === '' || v == null) return '';
        return String(v);
    });

    const guestsModelValue = computed(() => {
        const v = props.getFieldValue?.('guests') ?? props.formData?.guests;
        if (v === '' || v == null) return null;
        const n = typeof v === 'number' ? v : Number(v);
        return Number.isFinite(n) ? n : null;
    });

    const childrenModelValue = computed(() => {
        const v = props.getFieldValue?.('children') ?? props.formData?.children;
        if (v === '' || v == null) return null;
        const n = typeof v === 'number' ? v : Number(v);
        return Number.isFinite(n) ? n : null;
    });

    function onActivityUpdate(v: string | number | (string | number)[] | null): void {
        if (Array.isArray(v)) return;
        if (v === '' || v == null) {
            props.setFieldValue?.('activity_id', null);
            return;
        }
        const n = Number(v);
        props.setFieldValue?.('activity_id', Number.isFinite(n) && n > 0 ? n : null);
    }

    function onStatusUpdate(v: string | number | (string | number)[] | null): void {
        if (Array.isArray(v)) return;
        if (v === '' || v == null) {
            props.setFieldValue?.('status', null);
            return;
        }
        props.setFieldValue?.('status', String(v));
    }
</script>
