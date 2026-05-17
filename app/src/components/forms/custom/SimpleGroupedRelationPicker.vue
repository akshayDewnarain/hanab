<template>
    <div class="space-y-4">
        <!-- Legacy intro (certificates / minimal config) -->
        <InformationPanel
            v-if="introKey && !summaryTitleKey"
            v-model:expanded="infoExpanded"
            :icon="summaryIcon"
            :title="t(introKey)"
        >
            <p v-if="tipsKey" class="text-sm leading-relaxed">{{ t(tipsKey) }}</p>
        </InformationPanel>

        <!-- Summary + how-to (employee skills, etc.) -->
        <InformationPanel
            v-if="summaryTitleKey"
            v-model:expanded="infoExpanded"
            :icon="summaryIcon"
            :title="t(summaryTitleKey)"
            :expand-label-key="panelExpandLabelKey"
            :collapse-label-key="panelCollapseLabelKey"
        >
            <p v-if="summaryDescriptionKey" class="leading-relaxed">
                {{ t(summaryDescriptionKey) }}
            </p>
            <p
                v-if="linkedCountKey"
                class="mt-2 text-sm font-semibold text-[var(--color-primary)]"
            >
                {{ t(linkedCountKey, { count: linkedCount }) }}
            </p>

            <template v-if="howToTitleKey && howToTips?.length">
                <p class="mb-2 mt-4 text-sm font-semibold text-slate-800">{{ t(howToTitleKey) }}</p>
                <ul class="space-y-2.5">
                    <li
                        v-for="(tip, index) in howToTips"
                        :key="`${tip.textKey}-${index}`"
                        class="flex gap-2.5 leading-relaxed"
                    >
                        <Icon
                            :icon="tip.icon"
                            class="mt-0.5 size-[18px] shrink-0 text-[var(--color-primary)]"
                        />
                        <span>{{ t(tip.textKey) }}</span>
                    </li>
                </ul>
            </template>
        </InformationPanel>

        <!-- Section heading -->
        <div
            v-if="sectionHeadingKey"
            class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-600"
        >
            <Icon class="size-4 text-[var(--color-primary)]" icon="material-symbols:list-alt-outline" />
            {{ t(sectionHeadingKey) }}
        </div>

        <!-- Per-category blocks -->
        <div
            class="space-y-2 rounded-sm border border-slate-200/90 bg-slate-50/80 p-3"
            :class="{ 'mt-1': sectionHeadingKey }"
        >
            <section
                v-for="cat in categories"
                :key="cat.value"
                class="overflow-hidden rounded-sm border border-slate-200/90 bg-white shadow-sm"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 bg-slate-50 px-3 py-2.5"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <Icon
                            class="size-4 shrink-0 text-slate-400"
                            icon="material-symbols:chevron-right-rounded"
                        />
                        <h3 class="text-sm font-semibold text-slate-800">{{ t(cat.labelKey) }}</h3>
                        <span
                            class="rounded border border-slate-300/80 bg-slate-200/80 px-2 py-0.5 text-xs font-medium text-slate-600"
                        >
                            {{ categoryCountLabel(cat.value) }}
                        </span>
                    </div>
                    <button
                        v-if="!readonly && !disabled"
                        type="button"
                        class="inline-flex shrink-0 cursor-pointer items-center rounded-sm border border-[var(--color-primary)] bg-white px-2.5 py-1 text-xs font-medium text-[var(--color-primary)] shadow-sm transition-colors hover:bg-slate-50"
                        @click="openPicker(cat.value)"
                    >
                        {{ t(addButtonKey) }}
                    </button>
                </div>

                <div class="space-y-3 px-3 py-3">
                    <p
                        v-if="(selectedByCategory[cat.value] ?? []).length === 0"
                        class="text-sm text-slate-500"
                    >
                        {{ t(emptyCategoryKey, { category: t(cat.labelKey) }) }}
                    </p>

                    <div v-else class="flex flex-wrap gap-2">
                        <span
                            v-for="row in selectedByCategory[cat.value]"
                            :key="row.id"
                            class="inline-flex max-w-full items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold shadow-sm"
                            :style="relationRowChipStyle(row)"
                        >
                            <span class="truncate">{{ relationRowDisplayLabel(row) }}</span>
                            <button
                                v-if="!readonly && !disabled"
                                type="button"
                                class="flex size-5 shrink-0 cursor-pointer items-center justify-center rounded-full opacity-80 transition-opacity hover:opacity-100"
                                :aria-label="
                                    t('SIMPLE_RELATION_REMOVE_ITEM', { name: relationRowDisplayLabel(row) })
                                "
                                @click="removeRow(row.id)"
                            >
                                <Icon icon="material-symbols:close-rounded" class="size-4" />
                            </button>
                        </span>
                    </div>
                </div>
            </section>
        </div>

        <!-- Footer note -->
        <p
            v-if="footerNoteKey"
            class="flex items-start gap-2 text-xs leading-relaxed text-slate-500"
        >
            <Icon
                class="mt-0.5 size-4 shrink-0 text-slate-400"
                icon="material-symbols:info-outline-rounded"
            />
            <span>{{ t(footerNoteKey) }}</span>
        </p>
    </div>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref, watch } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import InformationPanel from '@/components/feedback/InformationPanel.vue';
    import { useModalService } from '@/composables/useModalService';
    import GroupedRelationChecklistPickerModal from '@/components/forms/custom/GroupedRelationChecklistPickerModal.vue';
    import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget.ts';
    import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType.ts';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
    import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
    import type { CategoryGroupedRelationManagerProps } from '@/modules/types/support/forms/CategoryGroupedRelationManagerProps.ts';
    import type { CategoryGroupedRelationRow } from '@/modules/types/support/forms/CategoryGroupedRelationRow.ts';
    import type { ModalResult } from '@/modules/types/support/modals/ModalResult.ts';
    import type { EntityPickerSelectionData } from '@/modules/types/support/modals/EntityPickerSelectionData.ts';
    import {
        attachedRowsByCategory,
        relationRowChipStyle,
        relationRowDisplayLabel,
        removeAttachedId,
    } from '@/modules/utils/groupedRelationPicker.ts';

    const props = withDefaults(defineProps<CategoryGroupedRelationManagerProps>(), {
        modelValue: () => [],
        searchPlaceholderKey: 'SIMPLE_RELATION_SEARCH',
        saveButtonKey: 'SIMPLE_RELATION_SAVE',
        emptyCategoryKey: 'SIMPLE_RELATION_EMPTY_CATEGORY',
        addInCategoryKey: 'SIMPLE_RELATION_ADD_IN_CATEGORY',
        summaryIcon: 'material-symbols:workspace-premium-outline',
    });

    const emit = defineEmits<{ (e: 'update:modelValue', v: number[]): void }>();

    const { t } = useI18n();
    const modalService = useModalService();
    const infoExpanded = ref(false);
    const attachedIds = ref<number[]>([]);
    const library = ref<CategoryGroupedRelationRow[]>([]);

    watch(
        () => props.modelValue,
        (v) => {
            attachedIds.value = v ? [...v] : [];
        },
        { immediate: true },
    );

    const linkedCount = computed(() => attachedIds.value.length);

    const selectedByCategory = computed(() =>
        attachedRowsByCategory(attachedIds.value, library.value, props.categories),
    );

    function categoryCountLabel(categoryValue: string): string {
        const n = (selectedByCategory.value[categoryValue] ?? []).length;
        return `${n} ${t(props.countLabelKey)}`;
    }

    function removeRow(id: number): void {
        if (props.readonly || props.disabled) {
            return;
        }
        attachedIds.value = removeAttachedId(attachedIds.value, id);
        emit('update:modelValue', attachedIds.value);
    }

    function openPicker(restrictedCategory?: string): void {
        if (props.readonly || props.disabled) {
            return;
        }

        modalService.open({
            title: props.pickerTitleKey,
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: GroupedRelationChecklistPickerModal,
            heightClass: 'min-h-[min(72vh,38rem)] max-h-[90vh]',
            panelClass: 'flex flex-col',
            props: {
                library: library.value,
                categories: props.categories,
                selectedIds: [...attachedIds.value],
                restrictedCategory: restrictedCategory ?? null,
                pickerTitleKey: props.pickerTitleKey,
                searchPlaceholderKey: props.searchPlaceholderKey,
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
                    name: props.saveButtonKey,
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
                    attachedIds.value = [...data.selectedIds];
                    emit('update:modelValue', attachedIds.value);
                }
            },
        });
    }

    onMounted(async () => {
        const res = await new props.model().index({ per_page: 500 });
        library.value = (res.data.data ?? []) as CategoryGroupedRelationRow[];
    });
</script>
