<template>
    <div class="space-y-3">
        <Accordion v-model="open" :defaultOpen="true">
            <template #header>
                <div class="flex justify-between items-center">
                    <span class="font-semibold truncate">{{ t(label) }}</span>
                    <button
                        class="flex text-[var(--color-primary)] items-center justify-center bg-white border border-[var(--color-primary)] rounded py-1 px-2 cursor-pointer hover:bg-gray-100 transition-colors duration-200 ease-in shadow-md text-sm"
                        type="button"
                        @click.stop="openPicker"
                    >
                        {{ t(addLabel) }}
                    </button>
                </div>
            </template>

            <div class="space-y-2">
                <div
                    v-for="(item, index) in items"
                    :key="`${item.id}-${index}`"
                    class="flex flex-wrap items-center gap-2 py-2 px-3 rounded border border-gray-200 bg-gray-50"
                >
                    <span class="min-w-0 flex-1 truncate font-medium text-gray-800">
                        {{ displayName(item) }}
                    </span>
                    <label class="flex items-center gap-1 text-sm text-gray-600">
                        <span>{{ t('GENERAL_QUANTITY') }}:</span>
                        <input
                            v-model.number="item.quantity"
                            class="w-16 rounded border border-gray-300 px-2 py-1 text-sm"
                            min="1"
                            type="number"
                            @input="emitChange"
                        />
                    </label>
                    <label class="flex items-center gap-1 text-sm text-gray-600">
                        <span>{{ t('GENERAL_UNIT_PRICE') }}:</span>
                        <input
                            v-model.number="item.unit_price"
                            class="w-20 rounded border border-gray-300 px-2 py-1 text-sm"
                            min="0"
                            step="0.01"
                            type="number"
                            @input="emitChange"
                        />
                    </label>
                    <button
                        :aria-label="t('GENERAL_REMOVE')"
                        class="p-1.5 rounded text-red-600 hover:bg-red-50 border border-red-200"
                        type="button"
                        @click="removeAt(index)"
                    >
                        <Icon class="w-4 h-4" icon="material-symbols:delete-outline-rounded" />
                    </button>
                </div>
                <p v-if="items.length === 0" class="text-sm text-gray-500 py-2">
                    {{ t('GENERAL_NONE_ADDED') }}
                </p>
            </div>
        </Accordion>
    </div>
</template>

<script lang="ts" setup>
    import { ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { Icon } from '@iconify/vue';
    import Accordion from '@/components/forms/custom/Accordion.vue';
    import { useModalService } from '@/composables/useModalService';
    import { ModalActionTarget, ModalActionType, ModalResultType, ModalType } from '@/modules/enums/modals/ModalEnums';
    import { ModalResult } from '@/modules/types/ModalTypes';
    import EntityPicker from '@/components/modals/content-modals/EntityPicker.vue';
    import type Model from '@/modules/models/Model';

    export interface PivotItem {
        id: number;
        name?: string;
        quantity: number;
        unit_price: number | string;
        meta?: unknown;
    }

    /** Plain shape returned from EntityPicker (not a `Model` instance). */
    interface PickerEntityRow {
        name?: unknown;
        price?: unknown;
        unit_price?: unknown;
    }

    const props = withDefaults(
        defineProps<{
            modelValue: PivotItem[];
            entity: string;
            model: new () => Model<unknown>;
            label: string;
            addLabel: string;
            includes?: string[];
        }>(),
        { includes: () => [] },
    );

    const emit = defineEmits<{ (e: 'update:modelValue', v: PivotItem[]): void }>();

    const { t } = useI18n();
    const modalService = useModalService();
    const open = ref(true);

    const items = ref<PivotItem[]>([]);

    watch(
        () => props.modelValue,
        (v) => {
            items.value = (v ?? []).map((i) => ({
                id: i.id,
                name: i.name,
                quantity: Number(i.quantity) || 1,
                unit_price: i.unit_price ?? 0,
                meta: i.meta ?? null,
            }));
        },
        { immediate: true, deep: true },
    );

    function displayName(item: PivotItem): string {
        return item.name != null && String(item.name).trim() !== '' ? String(item.name) : `#${item.id}`;
    }

    function emitChange(): void {
        emit(
            'update:modelValue',
            items.value.map((i) => ({ ...i })),
        );
    }

    function removeAt(index: number): void {
        items.value.splice(index, 1);
        emitChange();
    }

    function openPicker(): void {
        const currentIds = items.value.map((i) => i.id);
        modalService.open({
            title: t(props.addLabel),
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: EntityPicker,
            panelClass: 'w-[80vw] h-[90vh] max-w-[80vw] max-h-[90vh]',
            props: {
                entity: props.entity,
                model: props.model,
                includes: props.includes,
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
                const entities = (data.selectedEntities ?? []) as unknown as PickerEntityRow[];
                const existingIds = new Set(items.value.map((i) => i.id));
                for (let i = 0; i < selectedIds.length; i++) {
                    const id = selectedIds[i];
                    if (existingIds.has(id)) continue;
                    existingIds.add(id);
                    const entity = entities[i];
                    const price = entity?.price ?? entity?.unit_price ?? 0;
                    items.value.push({
                        id,
                        name: entity?.name != null ? String(entity.name) : undefined,
                        quantity: 1,
                        unit_price: Number(price) || 0,
                        meta: null,
                    });
                }
                emitChange();
            },
        });
    }
</script>
