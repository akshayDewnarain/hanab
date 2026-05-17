<template>
    <Accordion v-model="open" :defaultOpen="defaultOpen">
        <template #header>
            <div class="flex justify-between">
                <div class="flex items-center gap-3">
                    <span class="font-semibold truncate">{{ t(category.labelKey) }}</span>
                    <div class="rounded border border-gray-400 bg-gray-300 px-2 shadow-sm">
                        <span class="text-sm text-gray-700">
                            {{ items.length }} {{ t(countLabelKey) }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        class="flex cursor-pointer items-center justify-center rounded border border-[var(--color-primary)] bg-white px-2 py-1 text-[var(--color-primary)] shadow-md transition-colors duration-200 ease-in hover:bg-gray-100"
                        type="button"
                        @click.stop="openModal"
                    >
                        {{ t(addButtonKey) }}
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-1">
            <SimpleListView
                :attached-ids="attachedIds"
                :columns="listColumns"
                :items="items"
                :show-action-bar="true"
                @remove-selected="onRemoveSelected"
            />
        </div>
    </Accordion>
</template>

<script lang="ts" setup>
    import Accordion from '@/components/forms/custom/Accordion.vue';
    import { ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import SimpleListView from '@/components/list-views/SimpleListView.vue';
    import { useModalService } from '@/composables/useModalService';
    import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget.ts';
    import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType.ts';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
    import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
    import type { ModalResult } from '@/modules/types/support/modals/ModalResult.ts';
    import type { EntityPickerSelectionData } from '@/modules/types/support/modals/EntityPickerSelectionData.ts';
    import type { CategoryGroupedRelationAccordionProps } from '@/modules/types/support/forms/CategoryGroupedRelationAccordionProps.ts';
    import EntityPicker from '@/components/modals/content-modals/EntityPicker.vue';

    const props = defineProps<CategoryGroupedRelationAccordionProps>();

    const emit = defineEmits<{
        (e: 'picker-accept', ids: number[]): void;
        (e: 'remove-in-category', remainingInCategory: number[]): void;
    }>();

    const { t } = useI18n();
    const modalService = useModalService();
    const open = ref(false);

    function onRemoveSelected(remainingInCategory: number[]): void {
        emit('remove-in-category', remainingInCategory);
    }

    function openModal(): void {
        modalService.open({
            title: props.pickerTitleKey,
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: EntityPicker,
            panelClass: 'w-[80vw] h-[90vh] max-w-[80vw] max-h-[90vh]',
            props: {
                entity: props.entity,
                model: props.model,
                includes: props.includes ?? [],
                ids: props.attachedIds,
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
                if (!result?.data) {
                    return;
                }

                const data = result.data as EntityPickerSelectionData;
                if (data.selectedIds) {
                    emit('picker-accept', data.selectedIds);
                }
            },
        });
    }
</script>
