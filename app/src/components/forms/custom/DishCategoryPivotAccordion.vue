<template>
    <Accordion v-model="open" :defaultOpen="defaultOpen">
        <template #header>
            <div class="flex justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <Icon class="w-4 h-4 text-[var(--color-primary)]" icon="material-symbols:lunch-dining-rounded" />
                            <span class="font-semibold truncate">{{ category.name }}</span>
                        </div>
                        <span class="text-xs font-medium text-gray-500">Add or remove dishes for this order.</span>
                    </div>
                    <span v-if="pivotItems.length" class="text-sm text-gray-500 font-bold">
                        • {{ pivotItems.length + ` ${t('GENERAL_SELECTED')}` }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        class="flex text-[var(--color-primary)] items-center justify-center bg-white border border-[var(--color-primary)] rounded py-1 px-2 cursor-pointer hover:bg-gray-100 transition-colors duration-200 ease-in shadow-md"
                        type="button"
                        @click.stop="openModal"
                    >
                        {{ t('ADD_DISH') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="overflow-x-auto">
            <table class="min-w-full table-fixed bg-white border border-gray-200">
                <thead class="bg-gray-100 border-b border-gray-300">
                    <tr>
                        <th
                            class="w-20 py-2 px-3 text-center font-bold text-gray-700 border-r border-gray-300 whitespace-nowrap text-sm"
                        >
                            {{ t('GENERAL_IMAGE') }}
                        </th>
                        <th
                            class="w-[40%] py-2 px-3 text-left font-bold text-gray-700 border-r border-gray-300 whitespace-nowrap text-sm"
                        >
                            {{ t('GENERAL_NAME') }}
                        </th>
                        <th
                            class="w-32 py-2 px-3 text-center font-bold text-gray-700 border-r border-gray-300 whitespace-nowrap text-sm"
                        >
                            {{ t('GENERAL_PRICE') }}
                        </th>
                        <th
                            class="w-28 py-2 px-3 text-center font-bold text-gray-700 border-r border-gray-300 whitespace-nowrap text-sm"
                        >
                            {{ t('GENERAL_QUANTITY') }}
                        </th>
                        <th
                            class="w-36 py-2 px-3 text-center font-bold text-gray-700 border-r border-gray-300 whitespace-nowrap text-sm"
                        >
                            {{ t('GENERAL_UNIT_PRICE') }}
                        </th>
                        <th class="w-12 py-2 px-2 text-center font-bold text-gray-700 text-sm"></th>
                    </tr>
                </thead>
                <tbody class="border-t border-gray-300">
                    <tr
                        v-for="(item, index) in pivotItems"
                        :key="`${item.id}-${index}`"
                        class="border-b border-gray-200 hover:bg-gray-50 transition ease-in duration-100"
                    >
                        <td class="py-2 px-3 text-center border-r border-gray-200">
                            <div class="flex justify-center">
                                <div
                                    v-if="getDish(item.id)?.image?.thumb_url"
                                    class="rounded-full border border-gray-300 bg-gray-200 shadow-md"
                                >
                                    <img
                                        :src="getDish(item.id)?.image?.thumb_url"
                                        alt=""
                                        class="rounded-full aspect-square max-h-12"
                                        loading="lazy"
                                    />
                                </div>
                                <div v-else class="rounded-full p-3 border border-gray-300 bg-gray-200 shadow-md">
                                    <Icon
                                        class="text-gray-400"
                                        height="24"
                                        icon="material-symbols:image-not-supported-outline-rounded"
                                    />
                                </div>
                            </div>
                        </td>
                        <td class="py-2 px-3 text-gray-800 border-r border-gray-200 font-medium">
                            {{ getDish(item.id)?.name ?? item.name ?? `#${item.id}` }}
                        </td>
                        <td class="py-2 px-3 text-center text-gray-600 border-r border-gray-200">
                            {{ formatPrice(getDish(item.id)?.price) }}
                        </td>
                        <td class="py-2 px-3 border-r border-gray-200 text-center">
                            <input
                                v-model.number="item.quantity"
                                type="number"
                                min="1"
                                step="1"
                                class="w-16 rounded border border-gray-300 px-2 py-1 text-sm text-center"
                                @input="emitUpdate"
                            />
                        </td>
                        <td class="py-2 px-3 border-r border-gray-200">
                            <input
                                v-model.number="item.unit_price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-20 rounded border border-gray-300 px-2 py-1 text-sm text-center"
                                @input="emitUpdate"
                            />
                        </td>
                        <td class="py-2 px-2 text-center">
                            <button
                                type="button"
                                class="p-1.5 rounded text-red-600 hover:bg-red-50 border border-red-200"
                                :aria-label="t('GENERAL_REMOVE')"
                                @click="removeItem(index)"
                            >
                                <Icon class="w-4 h-4" icon="material-symbols:delete-outline-rounded" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!pivotItems.length">
                        <td :colspan="6" class="py-4 px-4 text-center text-gray-500 text-sm">
                            {{ t('GENERAL_NONE_ADDED') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Accordion>
</template>

<script lang="ts" setup>
    import Accordion from '@/components/forms/custom/Accordion.vue';
    import { ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { Icon } from '@iconify/vue';
    import { useModalService } from '@/composables/useModalService';
    import { ModalActionTarget, ModalActionType, ModalResultType, ModalType } from '@/modules/enums/modals/ModalEnums';
    import { ModalResult } from '@/modules/types/ModalTypes';
    import EntityPicker from '@/components/modals/content-modals/EntityPicker.vue';
    import type Model from '@/modules/models/Model';
    import type Dish from '@/models/general/Dish';

    export interface PivotItem {
        id: number;
        name?: string;
        quantity: number;
        unit_price: number | string;
        meta?: unknown;
    }

    interface PickerDishEntityRow {
        name?: unknown;
        price?: unknown;
    }

    const props = defineProps<{
        category: { id: number; name: string };
        pivotItems: PivotItem[];
        dishesInCategory: Dish[];
        dishModel: new () => Model<unknown>;
        defaultOpen?: boolean;
    }>();

    const emit = defineEmits<{ (e: 'update:items', items: PivotItem[]): void }>();

    const { t } = useI18n();
    const modalService = useModalService();
    const open = ref(true);

    const localItems = ref<PivotItem[]>([]);

    watch(
        () => props.pivotItems,
        (v) => {
            localItems.value = (v ?? []).map((i) => ({
                id: i.id,
                name: i.name,
                quantity: Number(i.quantity) || 1,
                unit_price: i.unit_price ?? 0,
                meta: i.meta ?? null,
            }));
        },
        { immediate: true, deep: true },
    );

    const pivotItems = localItems;

    const dishMap = ref<Map<number, Dish>>(new Map());

    watch(
        () => props.dishesInCategory,
        (dishes) => {
            const m = new Map<number, Dish>();
            for (const d of dishes ?? []) {
                m.set(d.id, d as Dish);
            }
            dishMap.value = m;
        },
        { immediate: true },
    );

    function getDish(id: number): Dish | undefined {
        return dishMap.value.get(id) as Dish | undefined;
    }

    function formatPrice(price: number | undefined): string {
        if (price == null) return '—';
        return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'EUR' }).format(Number(price));
    }

    function emitUpdate(): void {
        emit(
            'update:items',
            pivotItems.value.map((i) => ({ ...i })),
        );
    }

    function removeItem(index: number): void {
        pivotItems.value.splice(index, 1);
        emitUpdate();
    }

    function openModal(): void {
        const currentIds = pivotItems.value.map((i) => i.id);
        modalService.open({
            title: 'DISH_PICKER',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: EntityPicker,
            panelClass: 'w-[80vw] h-[90vh] max-w-[80vw] max-h-[90vh]',
            props: {
                entity: 'dishes',
                model: props.dishModel,
                includes: ['category'],
                ids: currentIds,
            },
            actions: [
                {
                    type: ModalResultType.DISMISS,
                    name: 'GENERAL_CANCEL',
                    variant: ModalActionType.DISMISS,
                    target: ModalActionTarget.CANCEL,
                },
                {
                    type: ModalResultType.ACCEPT,
                    name: 'GENERAL_SAVE',
                    variant: ModalActionType.PRIMARY,
                    target: ModalActionTarget.SAVE,
                },
            ],
            onAccept(result: ModalResult | null) {
                if (!result?.data) return;
                const data = result.data as { selectedIds?: number[]; selectedEntities?: Model<unknown>[] };
                const selectedIds = data.selectedIds ?? [];
                const entities = (data.selectedEntities ?? []) as unknown as PickerDishEntityRow[];
                const existingIds = new Set(pivotItems.value.map((i) => i.id));
                for (let i = 0; i < selectedIds.length; i++) {
                    const id = selectedIds[i];
                    if (existingIds.has(id)) continue;
                    existingIds.add(id);
                    const entity = entities[i];
                    const price = entity?.price ?? 0;
                    pivotItems.value.push({
                        id,
                        name: entity?.name != null ? String(entity.name) : undefined,
                        quantity: 1,
                        unit_price: Number(price) || 0,
                        meta: null,
                    });
                }
                emitUpdate();
            },
        });
    }
</script>
