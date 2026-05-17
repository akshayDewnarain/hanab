<template>
    <div class="space-y-4">
        <div v-if="!categories.length" class="text-sm text-gray-500 py-4">
            {{ t('GENERAL_NO_CATEGORIES_AVAILABLE') }}
        </div>
        <div v-else>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                {{ t('GENERAL_NAME') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                {{ t('GENERAL_MAX_BOOKABLE_BOATS') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">
                                {{ t('GENERAL_ACTIONS') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="row in localRows" :key="row.boat_category_id">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ row.boat_category_name }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <input
                                    :disabled="readonly || isSaving"
                                    :value="row.max_bookable_boats ?? ''"
                                    class="w-24 px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] disabled:bg-gray-100 disabled:cursor-not-allowed"
                                    inputmode="numeric"
                                    min="0"
                                    placeholder="—"
                                    step="1"
                                    type="number"
                                    @input="(e) => onNumberInput(e, row)"
                                />
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                <button
                                    :disabled="readonly || isSaving"
                                    class="text-[var(--color-primary)] hover:text-[var(--color-highlight-dark)] disabled:text-gray-400 disabled:cursor-not-allowed transition-colors"
                                    type="button"
                                    @click="resetRow(row)"
                                >
                                    {{ t('GENERAL_RESET') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button
                    :disabled="readonly || isSaving"
                    class="px-4 py-2 bg-[var(--color-primary)] text-white rounded-md hover:bg-[var(--color-highlight-dark)] disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-medium"
                    type="button"
                    @click="saveCaps"
                >
                    {{ t('GENERAL_SAVE') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { onMounted, ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import Activity from '@/models/general/Activity';
    import BoatCategory from '@/models/general/BoatCategory';
    import { useToastService } from '@/composables/useToastService';

    type CapRow = {
        boat_category_id: number;
        boat_category_name: string;
        max_bookable_boats: number | null;
    };

    const props = withDefaults(
        defineProps<{
            modelValue?: Array<{ boat_category_id: number; max_bookable_boats: number | null }>;
            activityId?: number;
            readonly?: boolean;
        }>(),
        {
            modelValue: () => [],
            activityId: undefined,
            readonly: false,
        },
    );

    const emit = defineEmits<{
        (e: 'update:modelValue', v: Array<{ boat_category_id: number; max_bookable_boats: number | null }>): void;
    }>();

    const { t } = useI18n();
    const toastService = useToastService();

    const categories = ref<BoatCategory[]>([]);
    const existingCaps = ref<CapRow[]>([]);
    const localRows = ref<CapRow[]>([]);
    const isSaving = ref(false);

    const activity = new Activity();

    async function fetchCategories() {
        try {
            const response = await new BoatCategory().index({
                per_page: 999,
                filter: {
                    active: true,
                },
            });
            categories.value = response.data.data;
        } catch (error) {
            console.error('Failed to fetch boat categories', error);
            toastService.error('GENERAL_FETCH_ERROR');
        }
    }

    async function fetchExistingCaps() {
        const activityId = props.activityId;
        if (!activityId) {
            return;
        }

        try {
            const caps = await activity.fetchBoatCategoryCaps(activityId);
            existingCaps.value = caps;
        } catch (error) {
            console.error('Failed to fetch boat category caps', error);
            toastService.error('GENERAL_FETCH_ERROR');
        }
    }

    function mergeData() {
        // Create a map of existing caps by boat_category_id
        const capsMap = new Map<number, number | null>();
        existingCaps.value.forEach((cap) => {
            capsMap.set(cap.boat_category_id, cap.max_bookable_boats);
        });

        // Merge categories with existing caps
        localRows.value = categories.value.map((category) => ({
            boat_category_id: category.id,
            boat_category_name: category.name,
            max_bookable_boats: capsMap.get(category.id) ?? null,
        }));
    }

    function onNumberInput(event: Event, row: CapRow) {
        const target = event.target as HTMLInputElement;
        const value = target.value.trim();

        // Handle empty string as null
        if (value === '') {
            row.max_bookable_boats = null;
        } else {
            const numValue = parseInt(value, 10);
            // Only set if valid integer >= 0
            if (!isNaN(numValue) && numValue >= 0) {
                row.max_bookable_boats = numValue;
            } else {
                // Revert to previous value if invalid
                target.value = String(row.max_bookable_boats ?? '');
            }
        }

        // Emit updated value
        const caps = localRows.value.map((r) => ({
            boat_category_id: r.boat_category_id,
            max_bookable_boats: r.max_bookable_boats,
        }));
        emit('update:modelValue', caps);
    }

    function resetRow(row: CapRow) {
        row.max_bookable_boats = null;
        // Emit updated value
        const caps = localRows.value.map((r) => ({
            boat_category_id: r.boat_category_id,
            max_bookable_boats: r.max_bookable_boats,
        }));
        emit('update:modelValue', caps);
    }

    async function saveCaps() {
        const activityId = props.activityId;
        if (!activityId) {
            toastService.error('GENERAL_ERROR');
            return;
        }

        isSaving.value = true;
        try {
            const caps = localRows.value.map((row) => ({
                boat_category_id: row.boat_category_id,
                max_bookable_boats: row.max_bookable_boats === 0 ? null : row.max_bookable_boats,
            }));

            await activity.upsertBoatCategoryCaps(activityId, caps);
            toastService.success('GENERAL_SAVE_SUCCESS');
            // Refresh existing caps
            await fetchExistingCaps();
            mergeData();
        } catch (error) {
            console.error('Failed to save boat category caps', error);
            toastService.error('GENERAL_SAVE_ERROR');
        } finally {
            isSaving.value = false;
        }
    }

    onMounted(async () => {
        await fetchCategories();
        await fetchExistingCaps();
        mergeData();
    });

    watch(
        () => props.activityId,
        async (newId, oldId) => {
            if (newId === oldId) {
                return;
            }
            if (!newId) {
                existingCaps.value = [];
                mergeData();
                return;
            }
            await fetchExistingCaps();
            mergeData();
        },
    );
</script>
