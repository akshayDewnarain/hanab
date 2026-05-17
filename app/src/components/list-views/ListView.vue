<template>
    <div class="flex flex-col w-full h-full">
        <div class="flex items-center justify-between w-full py-2">
            <div class="flex px-4">
                <div
                    v-if="displayHeader"
                    class="bg-[var(--color-background)] rounded border border-gray-200 shadow px-2 py-1"
                >
                    <span class="text-xl font-bold text-white">
                        {{
                            t('ENTITY_TABLE_OVERVIEW', {
                                entity: t(snakeCase(modelInstance.getEntity()).toUpperCase()),
                            })
                        }}
                    </span>
                </div>
            </div>
            <div>
                <div class="flex p-2 gap-4">
                    <div
                        v-for="(customAction, index) in props.customActions || []"
                        :key="index"
                        class="flex items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded p-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in shadow-md"
                        @click="handleCustomAction(customAction)"
                    >
                        <Icon :icon="customAction.icon" class="text-white w-4 h-4" />
                        <span class="text-sm text-white mx-2">{{ t(customAction.label) }}</span>
                    </div>
                    <div
                        v-if="enableDefaultActions"
                        class="flex items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded p-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in shadow-md"
                        @click="openModal"
                    >
                        <Icon class="text-white w-4 h-4" icon="material-symbols:add-2-rounded" />
                        <span class="text-sm text-white mx-2">{{ t('MODAL_CREATE') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex p-2 bg-white items-center justify-between w-full border-t border-b border-gray-200">
            <div class="flex items-center overflow-x-auto whitespace-nowrap p-2">
                <span> {{ t('FILTER_QUICK_FILTERS') }} </span>
                <QuickFilters
                    :key="quickFilterKey"
                    :entity="props.entity"
                    :filters="filters"
                    :model="props.model"
                    @update:filters="applyFilters"
                    @update:mounted="onQuickFiltersMounted"
                />
            </div>
            <div class="flex items-center">
                <div class="flex p-2 gap-4">
                    <div
                        class="flex items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded p-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in shadow-sm"
                    >
                        <Icon class="text-white w-4 h-4" icon="material-symbols:refresh-rounded" />
                        <span class="text-sm text-white mx-2">{{ t('TABLE_REFRESH') }}</span>
                    </div>

                    <div
                        v-if="displayTableConfiguration"
                        class="flex items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded p-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in shadow-sm"
                        @click="openTableConfiguration()"
                    >
                        <Icon class="text-white w-4 h-4" icon="material-symbols:tools-wrench-outline-rounded" />
                        <span class="text-sm text-white mx-2">{{ t('TABLE_SETTINGS') }}</span>
                    </div>
                </div>
                <div class="flex flex-row w-64 bg-white rounded shadow relative">
                    <input
                        v-model="searchQuery"
                        :placeholder="t('GENERAL_SEARCH_PLACEHOLDER')"
                        class="input-base"
                        type="text"
                        @input="handleInputChange"
                    />
                    <div
                        class="absolute right-0 h-full flex items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded-r px-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                    >
                        <Icon class="text-white w-4 h-4" icon="material-symbols:search" />
                    </div>
                </div>
            </div>
        </div>
        <Collapse
            :customClasses="['flex bg-white border-b border-gray-200']"
            :duration="500"
            :max-height="'50px'"
            :show="filterCount > 0"
        >
            <div
                class="inline-flex items-center bg-white text-sm text-gray-700 border border-gray-200 rounded shadow mx-2 my-3 whitespace-nowrap"
            >
                <div
                    class="px-2 py-1 font-bold bg-[var(--color-primary)] rounded-l cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                    @click="resetFilters"
                >
                    <Icon
                        class="w-5 h-5 text-white transition-transform duration-200 ease-in-out text-xs"
                        icon="material-symbols:delete-outline-rounded"
                    />
                </div>
                <div class="px-4 py-1 bg-gray-100">
                    <span>{{ t(`FILTER_RESET`) }}</span>
                </div>
            </div>
            <transition-group
                class="flex overflow-x-auto whitespace-nowrap scrollbar-thin min-h-[40px]"
                name="slide-fade"
                tag="div"
            >
                <div
                    v-for="(value, key) in filters"
                    :key="key"
                    class="inline-flex items-center bg-white text-sm text-gray-700 rounded mx-2 my-3"
                >
                    <transition-group v-if="Array.isArray(value)" name="slide-fade" tag="div">
                        <template v-for="(item, index) in value">
                            <div
                                v-if="item !== null && item !== undefined && item !== ''"
                                :key="`${key}-${index}`"
                                class="inline-flex items-center bg-white text-sm text-gray-700 border border-gray-200 rounded shadow mx-2"
                            >
                                <div class="px-2 py-1 font-bold bg-[var(--color-primary)] rounded-l text-white">
                                    <span>{{ getArrayFilterLabel(key, index) }}</span>
                                </div>
                                <div class="px-4 py-1 bg-gray-100">
                                    <span>{{ formatArrayFilterValue(item) }}</span>
                                </div>
                                <div
                                    class="px-2 py-1 font-bold bg-[var(--color-primary)] rounded-r cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                                    @click="removeArrayFilter(key, item)"
                                >
                                    <Icon
                                        class="w-5 h-5 text-white transition-transform duration-200 ease-in-out text-xs"
                                        icon="material-symbols:close"
                                    />
                                </div>
                            </div>
                        </template>
                    </transition-group>

                    <div
                        v-else
                        class="inline-flex items-center bg-white text-sm text-gray-700 border border-gray-200 rounded shadow"
                    >
                        <div class="px-2 py-1 font-bold bg-[var(--color-primary)] rounded-l text-white">
                            <span>{{ getFilterLabel(key) }}</span>
                        </div>
                        <div class="px-4 py-1 bg-gray-100">
                            <span>{{ formatFilterValue(value) }}</span>
                        </div>
                        <div
                            class="px-2 py-1 font-bold bg-[var(--color-primary)] rounded-r cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                            @click="removeFilter(key)"
                        >
                            <Icon
                                class="w-5 h-5 text-white transition-transform duration-200 ease-in-out text-xs"
                                icon="material-symbols:close"
                            />
                        </div>
                    </div>
                </div>
            </transition-group>
        </Collapse>
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
                        v-if="selectable && anySelected"
                        ref="barInnerRef"
                        class="border-b border-gray-300 bg-gray-100"
                    >
                        <div class="max-w-full px-4 py-2 flex items-center justify-between gap-3">
                            <div class="text-sm text-gray-700 font-bold">
                                {{ selectedCount }}
                                {{ selectedCount === 1 ? t('ITEM_SELECTED') : t('ITEMS_SELECTED') }}
                            </div>
                            <div class="flex items-center gap-2">
                                <slot :clear="clearSelection" :selected-ids="localSelectedIds" name="bulk-actions" />

                                <template v-if="showDefaultBulkActions">
                                    <button
                                        class="flex text-white items-center justify-center bg-red-600 border border-red-700 rounded py-1 px-2 cursor-pointer hover:bg-red-700 transition-colors duration-200 ease-in shadow-md"
                                        type="button"
                                        @click="deleteSelected"
                                    >
                                        {{ t('GENERAL_DELETE_SELECTED') }}
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
            <div class="h-full overflow-auto bg-white">
                <table class="min-w-full bg-white">
                    <thead class="sticky top-0 bg-gray-100">
                        <tr class="w-full border-b border-gray-300">
                            <th
                                v-if="selectable"
                                class="py-2 px-4 text-center font-bold text-gray-700 border-r border-gray-300 w-10"
                            >
                                <input v-model="allSelected" type="checkbox" @change="toggleSelectAll" />
                            </th>
                            <th
                                v-for="column in visibleColumns"
                                :key="column.name"
                                class="px-4 text-center font-bold border-r border-gray-300 whitespace-nowrap text-gray-700 text-sm"
                            >
                                {{ t(column.label).toUpperCase() }}
                            </th>
                        </tr>
                    </thead>
                    <Transition mode="out-in" name="table-fade">
                        <tbody v-if="isLoading" key="loading">
                            <tr v-for="index in 8" :key="`skeleton-row-${index}`" class="border-b border-gray-300">
                                <td v-if="selectable" class="py-2 px-6 text-center border-r border-gray-300">
                                    <Skeleton class="h-4 w-4 mx-auto rounded" />
                                </td>
                                <td
                                    v-for="column in visibleColumns"
                                    :key="`${column.name}-skeleton-${index}`"
                                    class="py-2 px-4"
                                >
                                    <Skeleton class="h-4 w-full max-w-[220px]" />
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else key="content">
                            <tr
                                v-for="item in items"
                                :key="getItemId(item)"
                                :class="[
                                    'border-b border-gray-300 transition ease-in duration-100',
                                    props.disableRowNavigation ? 'hover:bg-gray-50' : 'cursor-pointer hover:bg-gray-100',
                                ]"
                                @click="onRowClick(getItemId(item))"
                            >
                                <td v-if="selectable" class="py-2 px-6 text-center border-r border-gray-300">
                                    <input
                                        :checked="localSelectedIdsSet.has(getItemId(item))"
                                        :value="getItemId(item)"
                                        type="checkbox"
                                        @change="toggleOne(getItemId(item), $event)"
                                        @click.stop
                                    />
                                </td>
                                <td
                                    v-for="column in visibleColumns"
                                    :key="column.name"
                                    class="py-2 px-4 text-gray-600 whitespace-nowrap"
                                >
                                    <component
                                        :is="getComponentType(column.component.type)"
                                        v-if="column.component"
                                        :badges="column.component.badges"
                                        :custom-classes="column.component.customClasses"
                                        :options="column.component.options"
                                        :item="
                                            column.component.type ===
                                                TableColumnComponentEnum.COMPONENT_PREVIEW ||
                                            column.component.type === TableColumnComponentEnum.SKILL_LABEL
                                                ? item
                                                : undefined
                                        "
                                        :use-localized-string="column.component.useLocalizedString"
                                        :value="getColumnValue(item, column)"
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
                    </Transition>
                </table>
            </div>
        </div>
        <ListViewPagination
            v-if="props.alwaysShowPagination || items.length > 0"
            :current="meta.current_page"
            :per-page="meta.per_page"
            :total="meta.total"
            @update:currentPage="handlePageChange"
            @update:perPage="handlePerPageChange"
        />
    </div>
</template>

<script lang="ts" setup>
    import { snakeCase } from 'lodash-es';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { useModalService } from '@/composables/useModalService';
    import { useListViewHelper } from '@/composables/useListViewHelper';
    import type { ListViewActionType } from '@/modules/types/support/list-views/ListViewActionType.ts';
    import type { UseListViewHelperProps } from '@/modules/types/support/list-views/UseListViewHelperProps.ts';
    import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
    import dayjs from 'dayjs';
    import type { DateRange } from '@/components/filters/list-views/DatePickerFilter.vue';

    import Skeleton from '@/components/skeleton/Skeleton.vue';
    import ListViewPagination from '@/components/pagination/ListViewPagination.vue';
    import { Icon } from '@iconify/vue';
    import QuickFilters from '@/components/filters/list-views/QuickFilters.vue';
    import { getColumnFilterKey } from '@/modules/utils/tableColumnFilters';
    import Collapse from '@/components/transitions/Collapse.vue';
    import { defineComponent } from 'vue';

    const { t } = useI18n();
    const router = useRouter();
    const modalService = useModalService();

    defineComponent({
        name: 'ListView',
    });

    const props = withDefaults(defineProps<UseListViewHelperProps>(), {
        enableDefaultActions: true,
        displayHeader: true,
        displayTableConfiguration: true,
        showDefaultBulkActions: true,
        selectable: true,
        disableRowNavigation: false,
        useURLQueries: true,
        alwaysShowPagination: false,
    });

    // Use the composable
    const {
        // State
        isLoading,
        localSelectedIds,
        localSelectedIdsSet,
        anySelected,
        selectedCount,
        quickFilterKey,
        filters,
        searchQuery,
        items,
        allSelected,
        meta,
        barInnerRef,
        visibleColumns,
        filterCount,
        barWrapperStyle,
        modelInstance,

        // Methods
        handleInputChange,
        applyFilters,
        resetFilters,
        removeFilter,
        removeArrayFilter,
        openTableConfiguration,
        handlePageChange,
        handlePerPageChange,
        toggleSelectAll,
        toggleOne,
        clearSelection,
        deleteSelected,
        navigateToDetail,
        openModal,
        onQuickFiltersMounted,
        getComponentType,
        getItemId,
        getColumnValue,
        convertToString,
    } = useListViewHelper(props);

    function handleCustomAction(customAction: ListViewActionType): void {
        if (customAction.action) {
            customAction.action();
        } else if (customAction.modal) {
            modalService.open(customAction.modal);
        } else if (customAction.to) {
            router.push(customAction.to);
        }
    }

    function onRowClick(id: number): void {
        if (props.disableRowNavigation) return;
        navigateToDetail(id);
    }

    // Helper function to get filter label
    const getFilterLabel = (key: string): string => {
        const columns = modelInstance.columns();
        const column = columns.find((col) => getColumnFilterKey(col) === key);

        if (column) {
            return t(column.label);
        }

        // Fallback to key if column not found
        return t(`GENERAL_${convertToString(key).toUpperCase()}`);
    };

    const formatFilterValue = (value: unknown): string => {
        if (value && typeof value === 'object' && 'from' in value && 'to' in value) {
            const dateRange = value as DateRange;
            const parts: string[] = [];
            if (dateRange.from) {
                parts.push(dayjs(dateRange.from).format('DD MMM YYYY'));
            }
            if (dateRange.to) {
                parts.push(dayjs(dateRange.to).format('DD MMM YYYY'));
            }
            if (parts.length > 0) {
                return parts.join(' – ');
            }
            return '';
        }

        return convertToString(value);
    };

    // Helper function to get array filter label (for date range filters)
    const getArrayFilterLabel = (key: string, index: number): string => {
        // Check if this is a date range filter (ends with _between)
        if (key.endsWith('_between')) {
            const labelType = index === 0 ? 'FROM' : 'TO';
            return t(`GENERAL_${key.toUpperCase()}_${labelType}`);
        }

        // For non-date-range array filters, use the key name
        return t(`GENERAL_${convertToString(key).toUpperCase()}`);
    };

    // Helper function to format array filter value
    const formatArrayFilterValue = (item: unknown): string => {
        // If it's a date string, format it nicely
        if (typeof item === 'string' && /^\d{4}-\d{2}-\d{2}/.test(item)) {
            return dayjs(item).format('DD MMM YYYY');
        }

        // Otherwise, use the original conversion
        return convertToString(item);
    };
</script>

<style scoped>
    .table-fade-enter-active,
    .table-fade-leave-active {
        transition: opacity 0.2s ease;
    }

    .table-fade-enter-from,
    .table-fade-leave-to {
        opacity: 0;
    }
</style>
