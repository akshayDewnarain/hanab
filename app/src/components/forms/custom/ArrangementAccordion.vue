<template>
    <Accordion v-model="open" :defaultOpen="defaultOpen">
        <template #header>
            <div class="flex justify-between">
                <div class="flex items-center gap-3">
                    <span class="font-semibold truncate">{{ t('GENERAL_ARRANGEMENTS') }}</span>
                    <div class="bg-gray-300 px-2 rounded border border-gray-400 shadow-sm">
                        <span class="text-sm text-gray-700"
                            >{{ arrangements.length + ` ${t('GENERAL_ARRANGEMENTS')}` }}
                        </span>
                    </div>
                    <span v-if="selectedCount" class="text-sm text-gray-500 font-bold">
                        • {{ selectedCount + ` ${t('GENERAL_SELECTED')}` }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        class="flex text-[var(--color-primary)] items-center justify-center bg-white border border-[var(--color-primary)] rounded py-1 px-2 cursor-pointer hover:bg-gray-100 transition-colors duration-200 ease-in shadow-md"
                        type="button"
                        @click.stop="openModal"
                    >
                        {{ t('GENERAL_ADD_ARRANGEMENT') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-1">
            <SimpleListView
                :attached-ids="attachedIds"
                :columns="arrangementColumns"
                :items="arrangements"
                :show-action-bar="true"
                @apply-selected="onApplySelected"
            />
        </div>
    </Accordion>
</template>

<script lang="ts" setup>
    import Accordion from '@/components/forms/custom/Accordion.vue';
    import { computed, ref, watch } from 'vue';
    import Arrangement from '@/models/general/Arrangement';
    import { useI18n } from 'vue-i18n';
    import SimpleListView from '@/components/list-views/SimpleListView.vue';
    import TableColumn from '@/modules/models/TableColumn';
    import { useModalService } from '@/composables/useModalService';
    import { ModalActionTarget, ModalActionType, ModalResultType, ModalType } from '@/modules/enums/modals/ModalEnums';
    import { ModalResult } from '@/modules/types/ModalTypes';
    import EntityPicker from '@/components/modals/content-modals/EntityPicker.vue';
    import Model from '@/modules/models/Model';

    const props = defineProps<{
        arrangements: Arrangement[];
        attachedIds: number[];
        defaultOpen?: boolean;
    }>();

    const modalService = useModalService();

    const arrangementColumns = new Arrangement()
        .columns()
        .filter((col: TableColumn) => ['name', 'price', 'drink_amount', 'active'].includes(col.name));

    const { t } = useI18n();

    const emit = defineEmits<{ (e: 'update:selectedIds', ids: number[], entities: Model<unknown>[]): void }>();

    const attachedIds = ref<number[]>([...props.attachedIds]);
    const selectedCount = computed(() => attachedIds.value.length);

    watch(
        () => props.attachedIds,
        (v) => (attachedIds.value = [...v]),
    );

    function onApplySelected(ids: number[]) {
        attachedIds.value = ids;
    }

    function openModal(): void {
        modalService.open({
            title: 'GENERAL_ARRANGEMENT_PICKER',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: EntityPicker,
            panelClass: 'w-[80vw] h-[90vh] max-w-[80vw] max-h-[90vh]',
            props: {
                entity: 'arrangements',
                model: Arrangement,
                includes: [],
                ids: attachedIds.value,
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
                if (!result) {
                    return;
                }

                const data = result.data as { selectedIds?: number[]; selectedEntities?: Model<unknown>[] };
                if (data.selectedIds && data.selectedEntities) {
                    attachedIds.value = data.selectedIds;
                    emit('update:selectedIds', data.selectedIds, data.selectedEntities);
                }
            },
        });
    }
    const open = ref<boolean>(false);
</script>
