<template>
    <div class="flex h-full min-h-0 w-full flex-col">
        <div class="shrink-0 pb-3">
            <div class="relative flex w-full flex-row rounded shadow-sm">
                <input
                    v-model="searchQuery"
                    :placeholder="t(searchPlaceholderKey)"
                    class="input-base w-full rounded px-3 py-2 text-sm"
                    type="search"
                />
                <div
                    class="pointer-events-none absolute right-0 flex h-full items-center justify-center rounded-r border border-gray-200 bg-[var(--color-background)] px-2"
                >
                    <Icon class="h-4 w-4 text-white" icon="material-symbols:search" />
                </div>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-col">
            <p
                v-if="!hasResults"
                class="smooth-expand-body flex flex-1 items-center justify-center px-2 py-8 text-center text-sm text-slate-500"
                :class="{ 'smooth-expand-body--visible': !hasResults }"
            >
                {{ t('SIMPLE_RELATION_PICKER_NO_RESULTS') }}
            </p>

            <div
                class="smooth-expand-grid grid min-h-0 flex-1"
                :class="{ 'smooth-expand-grid--open': hasResults }"
            >
                <div class="smooth-expand-inner flex min-h-0 flex-col">
                    <div
                        class="smooth-expand-body smooth-expand-body--visible min-h-[14rem] max-h-[min(48vh,26rem)] flex-1 overflow-y-auto overscroll-contain pr-1"
                    >
                        <div class="space-y-4 pb-1">
                            <section
                                v-for="group in visibleGroups"
                                :key="group.category.value"
                                class="rounded-sm border border-slate-200/90 bg-white shadow-sm"
                            >
                                <h3
                                    class="border-b border-slate-200/80 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800"
                                >
                                    {{ t(group.category.labelKey) }}
                                </h3>
                                <ul class="divide-y divide-slate-100">
                                    <li
                                        v-for="row in group.rows"
                                        :key="row.id"
                                        class="flex items-center gap-3 px-3 py-2.5 transition-colors hover:bg-slate-50/80"
                                    >
                                        <input
                                            :id="`relation-pick-${row.id}`"
                                            :checked="draftIds.includes(row.id)"
                                            class="size-4 shrink-0 cursor-pointer rounded border-slate-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                                            type="checkbox"
                                            @change="toggle(row.id)"
                                        />
                                        <label
                                            :for="`relation-pick-${row.id}`"
                                            class="flex min-w-0 flex-1 cursor-pointer items-center justify-between gap-3"
                                        >
                                            <span class="min-w-0 truncate text-sm font-medium text-slate-800">
                                                {{ row.name || '—' }}
                                            </span>
                                            <span
                                                v-if="row.code?.trim()"
                                                class="inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-xs font-semibold"
                                                :style="relationRowChipStyle(row)"
                                            >
                                                {{ row.code }}
                                            </span>
                                        </label>
                                    </li>
                                </ul>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { useComponentState } from '@/composables/components/useComponentState';
    import type { EntityPickerSelectionData } from '@/modules/types/support/modals/EntityPickerSelectionData.ts';
    import type { GroupedRelationChecklistPickerModalProps } from '@/modules/types/support/modals/GroupedRelationChecklistPickerModalProps.ts';
    import {
        filterRowsByName,
        libraryGroupsForPicker,
        relationRowChipStyle,
        toggleAttachedId,
    } from '@/modules/utils/groupedRelationPicker.ts';

    const raw = defineProps<{
        props: GroupedRelationChecklistPickerModalProps;
    }>();

    const { t } = useI18n();
    const { setContent } = useComponentState();

    const modalProps = computed(() => raw.props);
    const searchQuery = ref('');
    const draftIds = ref<number[]>([...modalProps.value.selectedIds]);

    const searchPlaceholderKey = computed(
        () => modalProps.value.searchPlaceholderKey ?? 'SIMPLE_RELATION_SEARCH',
    );

    const visibleGroups = computed(() => {
        const groups = libraryGroupsForPicker(modalProps.value.library, modalProps.value.categories, {
            restrictedCategory: modalProps.value.restrictedCategory,
        });

        const q = searchQuery.value.trim();
        if (!q) {
            return groups;
        }

        return groups
            .map((group) => ({
                ...group,
                rows: filterRowsByName(group.rows, q),
            }))
            .filter((group) => group.rows.length > 0);
    });

    const hasResults = computed(() => visibleGroups.value.length > 0);

    function toggle(id: number): void {
        draftIds.value = toggleAttachedId(draftIds.value, id);
    }

    function getData(): EntityPickerSelectionData {
        return { selectedIds: [...draftIds.value] };
    }

    function isValidForAccept(): boolean {
        return true;
    }

    defineExpose({ getData, isValidForAccept });

    onMounted(() => {
        setContent();
    });
</script>
