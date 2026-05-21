<template>
    <div class="flex flex-col w-full h-full">
        <div class="relative flex-grow overflow-hidden">
            <div :style="barWrapperStyle" class="w-full overflow-hidden">
                <Transition
                    appear
                    enter-active-class="transform transition duration-300 ease-out"
                    enter-from-class="-translate-y-full opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transform transition duration-200 ease-in"
                    leave-to-class="-translate-y-full opacity-0"
                >
                    <div
                        v-if="anySelected && showActionBar"
                        ref="barInnerRef"
                        class="border-b border-gray-300 bg-gray-100"
                    >
                        <div class="max-w-full px-4 py-2 flex items-center justify-between gap-3">
                            <div class="text-sm text-gray-700 font-bold">
                                {{ selectedCount }} {{ selectedCount === 1 ? t('ITEM_SELECTED') : t('ITEMS_SELECTED') }}
                            </div>
                            <div class="flex items-center gap-2">
                                <slot
                                    :clear="clearSelection"
                                    :remove="removeSelection"
                                    :selected-ids="localSelectedIds"
                                    name="bulk-actions"
                                />

                                <template v-if="showDefaultBulkActions">
                                    <button
                                        class="flex text-white items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded py-1 px-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in shadow-md"
                                        type="button"
                                        @click="removeSelection"
                                    >
                                        {{ t('REMOVE_SELECTED') }}
                                    </button>
                                    <button
                                        class="flex text-[var(--color-primary)] items-center justify-center bg-white border border-[var(--color-primary)] rounded py-1 px-2 cursor-pointer hover:bg-gray-100 transition-colors duration-200 ease-in shadow-md"
                                        type="button"
                                        @click="clearSelection"
                                    >
                                        {{ t('CLEAR_SELECTION') }}
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Content translates by bar height -->
            <div class="h-full overflow-auto bg-white will-change-transform">
                <table class="min-w-full bg-white">
                    <thead v-if="showTableHeader" class="sticky top-0 bg-gray-100">
                        <tr class="w-full border-b border-gray-300">
                            <th
                                v-if="selectable"
                                class="py-2 px-4 text-center font-bold text-gray-700 border-r border-gray-300 w-10"
                            >
                                <input
                                    :checked="allSelected"
                                    class="cursor-pointer"
                                    type="checkbox"
                                    @change="toggleSelectAll"
                                />
                            </th>
                            <th
                                v-for="column in visibleColumns"
                                :key="column.name"
                                :class="widthClass(column)"
                                :style="widthStyle(column)"
                                class="px-4 text-center font-bold border-r border-gray-300 whitespace-nowrap text-gray-700 text-sm"
                            >
                                {{ t(column.label).toUpperCase() }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="border-t border-gray-300">
                        <tr
                            v-for="item in items"
                            :key="(item as Record<string, unknown>).id as string | number"
                            class="border-b border-gray-300 hover:bg-gray-100 transition ease-in duration-100"
                        >
                            <td v-if="selectable" class="py-2 px-4 text-center font-bold text-gray-700 w-10">
                                <input
                                    :checked="isChecked((item as Record<string, unknown>).id as number)"
                                    :value="(item as Record<string, unknown>).id"
                                    class="cursor-pointer"
                                    type="checkbox"
                                    @change="toggleOne((item as Record<string, unknown>).id as number, $event)"
                                />
                            </td>

                            <td
                                v-for="column in visibleColumns"
                                :key="column.name"
                                :class="widthClass(column)"
                                :style="widthStyle(column)"
                                class="py-2 px-4 text-gray-600 whitespace-nowrap"
                            >
                                <component
                                    :is="getComponentType(column.component?.type)"
                                    v-if="column.component"
                                    :badges="column.component.badges"
                                    :item="
                                        column.component?.type === TableColumnComponentEnum.SKILL_LABEL
                                            ? item
                                            : undefined
                                    "
                                    :value="getColumnValue(item, column) as string"
                                />
                                <template v-else>
                                    {{ getColumnValue(item, column) }}
                                </template>
                            </td>
                        </tr>

                        <tr v-if="!items.length">
                            <td
                                :colspan="visibleColumns.length + (selectable ? 1 : 0)"
                                class="py-6 text-center text-sm text-gray-400"
                            >
                                {{ t('NO_RESULTS') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import type { Component } from 'vue';
    import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';

    // import your cell components + enum
    import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
    import DateComponent from '@/components/table-fields/DateComponent.vue';
    import CurrencyComponent from '@/components/table-fields/CurrencyComponent.vue';
    import BooleanComponent from '@/components/table-fields/BooleanComponent.vue';
    import BadgeComponent from '@/components/table-fields/BadgeComponent.vue';
    import ImageComponent from '@/components/table-fields/ImageComponent.vue';
    import EnumComponent from '@/components/table-fields/EnumComponent.vue';
    import WeekdayComponent from '@/components/table-fields/WeekdayComponent.vue';
    import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';
    import TimeComponent from '@/components/table-fields/TimeComponent.vue';
    import SkillLabelCellComponent from '@/components/table-fields/SkillLabelCellComponent.vue';

    const { t } = useI18n();

    const props = withDefaults(
        defineProps<{
            items: unknown[];
            columns: TableColumnInterface[];
            showTableHeader?: boolean;
            selectable?: boolean;
            showDefaultBulkActions?: boolean;
            showActionBar: boolean;
            attachedIds?: number[];
            attachedIdsAsSelected?: boolean;
        }>(),
        {
            showDefaultBulkActions: true,
            showActionBar: true,
            showTableHeader: true,
            selectable: true,
            attachedIdsAsSelected: false,
            attachedIds: () => [],
        },
    );

    const emit = defineEmits<{
        (e: 'update:selectedIds', v: number[]): void;
        (e: 'remove-selected', v: number[]): void; // new
        (e: 'clear-selected'): void;
    }>();

    const localSelectedIds = ref<number[]>([]);
    const anySelected = computed(() => localSelectedIds.value.length > 0);
    const selectedCount = computed(() => localSelectedIds.value.length);
    const visibleColumns = computed(() => props.columns.filter((c) => c.visible));
    const localSelectedIdsSet = computed(() => new Set(localSelectedIds.value));
    const allSelected = computed(
        () =>
            props.items.length > 0 &&
            props.items.every((i) => localSelectedIdsSet.value.has((i as Record<string, unknown>).id as number)),
    );
    const barInnerRef = ref<HTMLElement | null>(null);
    const barHeight = ref(0);

    let ro: ResizeObserver | null = null;

    function updateBarHeight() {
        barHeight.value = barInnerRef.value ? barInnerRef.value.offsetHeight : 0;
    }

    watch(
        () => anySelected.value,
        async (v) => {
            await nextTick();
            updateBarHeight();
            if (ro) {
                ro.disconnect();
                ro = null;
            }
            if (v && barInnerRef.value && 'ResizeObserver' in window) {
                ro = new ResizeObserver(updateBarHeight);
                ro.observe(barInnerRef.value);
            }
        },
        { immediate: true },
    );

    function toggleSelectAll(e: Event) {
        const checked = (e.target as HTMLInputElement).checked;
        localSelectedIds.value = checked ? props.items.map((i) => (i as Record<string, unknown>).id as number) : [];
        emit('update:selectedIds', localSelectedIds.value);
    }

    function toggleOne(id: number, e: Event) {
        const checked = (e.target as HTMLInputElement).checked;
        const set = new Set(localSelectedIds.value);
        if (checked) set.add(id);
        else set.delete(id);
        localSelectedIds.value = Array.from(set);
        emit('update:selectedIds', localSelectedIds.value);
    }

    const isChecked = (id: number) => localSelectedIdsSet.value.has(id);

    function removeSelection() {
        const remaining = props.items
            .map((i) => (i as Record<string, unknown>).id as number)
            .filter((id) => !localSelectedIds.value.includes(id));

        emit('remove-selected', remaining);
    }
    function clearSelection() {
        localSelectedIds.value = [];
        emit('clear-selected');
    }

    function getComponentType(type: TableColumnComponentEnum | undefined): Component | null {
        if (type == null) return null;
        switch (type) {
            case TableColumnComponentEnum.DATE:
                return DateComponent;
            case TableColumnComponentEnum.CURRENCY:
                return CurrencyComponent;
            case TableColumnComponentEnum.BOOLEAN:
                return BooleanComponent;
            case TableColumnComponentEnum.BADGE:
                return BadgeComponent;
            case TableColumnComponentEnum.ENUM:
                return EnumComponent;
            case TableColumnComponentEnum.IMAGE:
                return ImageComponent;
            case TableColumnComponentEnum.TIME:
                return TimeComponent;
            case TableColumnComponentEnum.WEEKDAY:
                return WeekdayComponent;
            case TableColumnComponentEnum.SKILL_LABEL:
                return SkillLabelCellComponent;
            default:
                return null;
        }
    }

    const barWrapperStyle = computed(() => ({
        height: `${anySelected.value ? barHeight.value : 0}px`,
        transition: `height ${anySelected.value ? 300 : 200}ms ${anySelected.value ? 'ease-out' : 'ease-in'}`,
    }));

    function widthClass(c: TableColumnInterface) {
        if (!c.width) return '';
        if (c.width.startsWith('tw:')) return c.width.slice(3);
        if (c.width === 'fit') return 'whitespace-nowrap';
        if (c.width === 'content') return '';
        return '';
    }

    function widthStyle(c: TableColumnInterface) {
        if (!c.width) return undefined;

        if (c.width.startsWith('tw:')) return undefined;

        if (c.width === 'fit') return { width: '1%' };
        if (c.width === 'content') return { width: 'auto' };

        if (/^\d+(\.\d+)?(px|rem|em|%|vh|vw|ch)$/.test(c.width)) {
            return { width: c.width };
        }

        return undefined;
    }

    function safeGet(obj: unknown, path: string) {
        return path.split('.').reduce((o: unknown, k: string) => {
            if (o && typeof o === 'object' && o !== null) {
                return (o as Record<string, unknown>)[k];
            }
            return undefined;
        }, obj);
    }

    function getColumnValue(item: unknown, col: TableColumnInterface) {
        if (col.relation) {
            const rel = safeGet(item, col.relation.path);
            if (!rel) return col.relation.emptyLabel ?? '—';

            if (col.relation.accessor) return col.relation.accessor(rel, item);

            if (col.relation.type === 'belongsTo') {
                return (
                    (rel as Record<string, unknown>)[col.relation.displayKey ?? 'id'] ?? col.relation.emptyLabel ?? '—'
                );
            }

            if (Array.isArray(rel)) {
                return rel
                    .map((r: unknown) => (r as Record<string, unknown>)[col.relation?.displayKey ?? 'id'])
                    .join(col.relation.multipleSeparator ?? ', ');
            }
            return col.relation.emptyLabel ?? '—';
        }

        if (col.name.includes('.')) {
            return safeGet(item, col.name);
        }

        return (item as Record<string, unknown>)[col.name];
    }

    function syncAttachedSelection(): void {
        if (!props.attachedIdsAsSelected || props.attachedIds == null) return;
        localSelectedIds.value = [...props.attachedIds];
    }

    watch(
        () => props.attachedIds,
        () => {
            syncAttachedSelection();
        },
    );

    onMounted(() => {
        window.addEventListener('resize', updateBarHeight);
        syncAttachedSelection();
    });
    onBeforeUnmount(() => {
        window.removeEventListener('resize', updateBarHeight);
        if (ro) ro.disconnect();
    });
</script>
