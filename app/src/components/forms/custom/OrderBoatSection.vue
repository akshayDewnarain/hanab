<template>
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center gap-2">
            <Icon class="h-4 w-4 text-[var(--color-primary)]" icon="material-symbols:directions-boat" />
            <span class="text-sm font-semibold text-gray-800">{{ t(options.label) }}</span>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium" for="order-boat-readonly-service-date">
                    {{ t('GENERAL_SERVICE_DATE') }}
                </label>
                <div class="relative block w-full min-w-0 text-left group">
                    <div>
                        <div
                            id="order-boat-readonly-service-date"
                            class="relative inline-flex w-full cursor-not-allowed items-center gap-x-2 rounded border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700 shadow"
                        >
                            <Icon
                                class="h-5 w-5 shrink-0 text-gray-500"
                                icon="material-symbols:calendar-month-outline"
                            />
                            <span
                                class="min-w-0 flex-1 text-left"
                                :class="serviceDate ? 'text-black' : 'text-[var(--color-placeholder)]'"
                            >
                                {{ formattedServiceDate }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium" for="order-boat-readonly-time-slot">
                    {{ t('GENERAL_TIME_SLOT') }}
                </label>
                <div class="relative block w-full min-w-0 text-left group">
                    <div>
                        <div
                            id="order-boat-readonly-time-slot"
                            class="relative inline-flex w-full cursor-not-allowed items-center gap-x-2 rounded border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700 shadow"
                        >
                            <Icon class="h-5 w-5 shrink-0 text-gray-500" icon="material-symbols:schedule-outline" />
                            <span
                                class="w-full min-w-0 text-left"
                                :class="timeSlotId ? 'text-black' : 'text-[var(--color-placeholder)]'"
                            >
                                {{ timeSlotLabel }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <SelectFieldComponent
                :model-value="categoryModelValue"
                :options="categorySelectOptions"
                @update:model-value="onCategoryModelUpdate"
                @blur="() => {}"
            />

            <SelectFieldComponent
                v-if="categoryId != null"
                :key="`boat-select-${categoryId}`"
                :model-value="boatModelValue"
                :options="boatSelectOptions"
                @update:model-value="onBoatModelUpdate"
                @blur="() => {}"
            />
            <div v-else class="flex flex-col gap-1">
                <span class="text-sm font-medium text-gray-800">{{ t('GENERAL_BOAT') }}</span>
                <div
                    class="rounded border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-500 shadow-sm"
                >
                    {{ t('GENERAL_BOAT_CATEGORY_PLACEHOLDER') }}
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, ref, watch } from 'vue';
    import dayjs from 'dayjs';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import Boat from '@/models/general/Boat';
    import BoatCategory from '@/models/general/BoatCategory';
    import TimeSlot from '@/models/general/TimeSlot';
    import SelectFieldComponent from '@/components/forms/inputs/SelectFieldComponent.vue';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
    import type { SelectFieldOptions } from '@/modules/types/support/inputs/SelectFieldOptions.ts';
    import { formatTimeslotHeading } from '@/utils/calendarTimeslotPresentation';

    defineComponent({ name: 'OrderBoatSection' });

    const props = defineProps<{
        modelValue?: unknown;
        options: BaseInputFieldOptions;
        error?: string;
        formData?: Record<string, unknown>;
        setFieldValue?: (name: string, value: unknown) => void;
        getFieldValue?: (name: string) => unknown;
    }>();

    const { t } = useI18n();

    const timeSlotLabel = ref<string>('—');
    const timeSlotModel = new TimeSlot();

    const serviceDate = computed(() => {
        const v = props.getFieldValue?.('service_date') ?? props.formData?.service_date;
        return typeof v === 'string' && v.length > 0 ? v : null;
    });

    const timeSlotId = computed((): number | null => {
        const v = props.getFieldValue?.('time_slot_id') ?? props.formData?.time_slot_id;
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    });

    const categoryId = computed((): number | null => {
        const v = props.getFieldValue?.('boat_category_id') ?? props.formData?.boat_category_id;
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    });

    const boatId = computed((): number | null => {
        const v = props.getFieldValue?.('boat_id') ?? props.formData?.boat_id;
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    });

    const categoryModelValue = computed(() => categoryId.value ?? '');
    const boatModelValue = computed(() => boatId.value ?? '');

    const categorySelectOptions = computed<SelectFieldOptions>(() => ({
        name: 'boat_category_id',
        label: 'GENERAL_BOAT_CATEGORY',
        required: false,
        disabled: true,
        hideClearButton: true,
        placeholder: 'GENERAL_BOAT_CATEGORY_PLACEHOLDER',
        position: 'bottom',
        align: 'right',
        remoteFetch: {
            instance: BoatCategory,
            map: { label: 'name', value: 'id' },
            params: { per_page: 200 },
            immediate: true,
            translate: false,
        },
    }));

    const boatSelectOptions = computed<SelectFieldOptions>(() => {
        const cat = categoryId.value;
        return {
            name: 'boat_id',
            label: 'GENERAL_BOAT',
            required: false,
            disabled: true,
            hideClearButton: true,
            placeholder: 'GENERAL_BOAT_PLACEHOLDER',
            position: 'bottom',
            align: 'right',
            remoteFetch: {
                instance: Boat,
                map: { label: 'name', value: 'id' },
                params: {
                    per_page: 200,
                    ...(cat != null ? { filter: { boat_category_id: cat } } : {}),
                },
                immediate: true,
                translate: false,
            },
        };
    });

    const formattedServiceDate = computed(() => {
        if (!serviceDate.value) return '—';
        return dayjs(serviceDate.value).format('dddd, DD MMMM YYYY');
    });

    function normalizeId(v: string | number | (string | number)[] | null): number | null {
        if (v === '' || v == null) return null;
        if (Array.isArray(v)) return null;
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    }

    function onCategoryModelUpdate(v: string | number | (string | number)[] | null): void {
        const next = normalizeId(v);
        props.setFieldValue?.('boat_category_id', next);
        if (next == null) {
            props.setFieldValue?.('boat_id', null);
        }
    }

    function onBoatModelUpdate(v: string | number | (string | number)[] | null): void {
        props.setFieldValue?.('boat_id', normalizeId(v));
    }

    async function resolveTimeSlotLabel(): Promise<void> {
        const id = timeSlotId.value;
        if (id == null) {
            timeSlotLabel.value = '—';
            return;
        }
        try {
            const res = await timeSlotModel.show(id);
            const raw = res.data as unknown;
            const payload =
                raw && typeof raw === 'object' && 'data' in (raw as Record<string, unknown>)
                    ? (raw as Record<string, unknown>).data
                    : raw;
            const row = payload as Record<string, unknown>;
            timeSlotLabel.value = formatTimeslotHeading({
                label: row.label != null ? String(row.label) : null,
                start_time: row.start_time != null ? String(row.start_time) : null,
                end_time: row.end_time != null ? String(row.end_time) : null,
            });
        } catch {
            timeSlotLabel.value = '—';
        }
    }

    watch(
        timeSlotId,
        () => {
            resolveTimeSlotLabel().catch(() => {
                timeSlotLabel.value = '—';
            });
        },
        { immediate: true },
    );
</script>
