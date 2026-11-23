<template>
    <UiListView
        v-model:search="searchModel"
        v-model:page="pageModel"
        v-model:per-page="perPageModel"
        v-model:selected="selectedModel"
        class="h-full min-h-0"
        :actions="listActions"
        :bulk-actions="bulkActions"
        :clickable="!disableRowNavigation"
        :columns="listColumns"
        :filters="filterChips"
        :labels="labels"
        :loading="isLoading"
        :page-size-options="pageSizeOptions"
        :rows="rows"
        :selectable="selectable"
        :show-header="showListHeader"
        :show-settings="displayTableConfiguration"
        :title="listTitle"
        :total="meta.total"
        @action="onAction"
        @bulk-action="onBulkAction"
        @refresh="fetchData"
        @remove-filter="onRemoveFilter"
        @reset-filters="resetFilters"
        @row-click="onRowClick"
        @settings="openTableConfiguration"
    >
        <template #filters>
            <QuickFilters
                :key="quickFilterKey"
                :entity="props.entity"
                :filters="filters"
                :model="props.model"
                @update:filters="applyFilters"
                @update:mounted="onQuickFiltersMounted"
            />
        </template>

        <template #bulk>
            <slot :clear="clearSelection" :selected-ids="localSelectedIds" name="bulk-actions" />
        </template>

        <template #cell="{ row, column: listColumn }">
            <component
                :is="cellComponent(listColumn.id)"
                v-if="cellComponent(listColumn.id)"
                :badges="columnFor(listColumn.id)?.component?.badges"
                :custom-classes="columnFor(listColumn.id)?.component?.customClasses"
                :item="
                    columnFor(listColumn.id)?.component?.type === TableColumnComponentEnum.SKILL_LABEL ? row : undefined
                "
                :options="columnFor(listColumn.id)?.component?.options"
                :use-localized-string="columnFor(listColumn.id)?.component?.useLocalizedString"
                :value="cellText(row, listColumn.id)"
            />
            <template v-else>
                {{ cellText(row, listColumn.id) }}
            </template>
        </template>
    </UiListView>
</template>

<script lang="ts" setup>
    import { computed, type Component } from 'vue';
    import { snakeCase } from 'lodash-es';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { ListView as UiListView, type ListAction, type ListFilterChip } from '@freelance/vue-ui';
    import dayjs from 'dayjs';
    import { useModalService } from '@/composables/useModalService';
    import { useListViewHelper } from '@/composables/useListViewHelper';
    import type { DateRange } from '@/components/filters/list-views/DatePickerFilter.vue';
    import QuickFilters from '@/components/filters/list-views/QuickFilters.vue';
    import { useTransferModals } from '@/composables/useTransferModals';
    import { transferPermission } from '@/modules/support/transfer/transferModels';
    import { useAuthStore } from '@/stores/auth';
    import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
    import type { ListViewActionType } from '@/modules/types/support/list-views/ListViewActionType.ts';
    import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';
    import type { UseListViewHelperProps } from '@/modules/types/support/list-views/UseListViewHelperProps.ts';
    import { getColumnFilterKey } from '@/modules/utils/tableColumnFilters';

    defineOptions({ name: 'ListView' });

    const props = withDefaults(defineProps<UseListViewHelperProps>(), {
        enableDefaultActions: true,
        displayHeader: true,
        displayTableConfiguration: true,
        showDefaultBulkActions: true,
        selectable: true,
        disableRowNavigation: false,
        useURLQueries: true,
    });

    const { t } = useI18n();
    const router = useRouter();
    const modalService = useModalService();
    const auth = useAuthStore();
    const { openImport, openTransfer } = useTransferModals();

    const pageSizeOptions = [10, 25, 50, 100];

    const {
        isLoading,
        localSelectedIds,
        quickFilterKey,
        filters,
        searchQuery,
        items,
        meta,
        visibleColumns,
        selectedCount,
        modelInstance,
        fetchData,
        handleInputChange,
        applyFilters,
        resetFilters,
        removeFilter,
        removeArrayFilter,
        openTableConfiguration,
        handlePageChange,
        handlePerPageChange,
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

    const transferKey = modelInstance.transferKey();

    const rows = computed(() => items.value as Record<string, unknown>[]);

    const columnsByName = computed(() => {
        const map = new Map<string, TableColumnInterface>();
        for (const column of visibleColumns.value) {
            map.set(column.name, column);
        }
        return map;
    });

    const listColumns = computed(() =>
        visibleColumns.value.map((column) => ({
            id: column.name,
            label: t(column.label),
        })),
    );

    const listTitle = computed(() =>
        props.displayHeader
            ? t('ENTITY_TABLE_OVERVIEW', {
                  entity: t(snakeCase(modelInstance.getEntity()).toUpperCase()),
              })
            : undefined,
    );

    const listActions = computed<ListAction[]>(() => {
        const actions: ListAction[] = (props.customActions ?? []).map((action, index) => ({
            id: `custom:${index}`,
            label: t(action.label),
        }));

        if (transferKey && auth.hasPermission(transferPermission(transferKey))) {
            actions.push(
                { id: 'transfer:import', label: t('IMPORT_DATA'), icon: 'material-symbols:upload-file-outline-rounded' },
                { id: 'transfer:export', label: t('EXPORT_DATA'), icon: 'material-symbols:download-rounded' },
                {
                    id: 'transfer:template',
                    label: t('DATA_TRANSFER_TEMPLATE'),
                    icon: 'material-symbols:draft-outline-rounded',
                },
            );
        }

        if (props.enableDefaultActions) {
            actions.push({ id: 'create', label: t('MODAL_CREATE'), icon: 'add' });
        }

        return actions;
    });

    const showListHeader = computed(() => props.displayHeader || listActions.value.length > 0);

    const bulkActions = computed(() =>
        props.showDefaultBulkActions
            ? [{ id: 'delete', label: t('GENERAL_DELETE_SELECTED'), icon: 'trash', danger: true }]
            : [],
    );

    const labels = computed(() => ({
        list: listTitle.value,
        filters: t('FILTER_QUICK_FILTERS'),
        refresh: t('TABLE_REFRESH'),
        settings: t('TABLE_SETTINGS'),
        search: t('GENERAL_SEARCH_PLACEHOLDER'),
        searchPlaceholder: t('GENERAL_SEARCH_PLACEHOLDER'),
        emptyTitle: t('NO_RESULTS'),
        emptyDescription: '',
        clearFilters: t('FILTER_RESET'),
        selected: `{count} ${selectedCount.value === 1 ? t('ITEM_SELECTED') : t('ITEMS_SELECTED')}`,
        clearSelection: t('CLEAR_SELECTION'),
        perPage: t('PAGINATION_PER_PAGE'),
        showing: t('PAGINATION_SHOWING'),
        to: t('PAGINATION_TO'),
        of: t('PAGINATION_OF'),
        results: t('PAGINATION_RESULTS'),
        previous: t('PAGINATION_PREV'),
        next: t('PAGINATION_NEXT'),
        pagination: t('PAGINATION_PAGE_LABEL'),
    }));

    const filterChips = computed<ListFilterChip[]>(() => {
        const chips: ListFilterChip[] = [];

        for (const [key, value] of Object.entries(filters.value)) {
            if (Array.isArray(value)) {
                value.forEach((item, index) => {
                    if (item === null || item === undefined || item === '') return;
                    chips.push({
                        id: `array:${encodeURIComponent(key)}:${index}`,
                        label: getArrayFilterLabel(key, index),
                        value: formatArrayFilterValue(item),
                    });
                });
                continue;
            }

            if (value === null || value === undefined || value === '') continue;

            chips.push({
                id: `scalar:${encodeURIComponent(key)}`,
                label: getFilterLabel(key),
                value: formatFilterValue(value),
            });
        }

        return chips;
    });

    const searchModel = computed({
        get: () => searchQuery.value,
        set: (value: string) => {
            if (value === searchQuery.value) return;
            searchQuery.value = value;
            handleInputChange();
        },
    });

    const pageModel = computed({
        get: () => meta.value.current_page,
        set: (page: number) => {
            if (page === meta.value.current_page) return;
            handlePageChange(page);
        },
    });

    const perPageModel = computed({
        get: () => meta.value.per_page,
        set: (perPage: number) => {
            if (perPage === meta.value.per_page) return;
            handlePerPageChange(perPage);
        },
    });

    const selectedModel = computed<string[]>({
        get: () => localSelectedIds.value.map((id) => String(id)),
        set: (ids: string[]) => {
            localSelectedIds.value = ids.map((id) => Number(id)).filter((id) => Number.isFinite(id));
        },
    });

    function columnFor(id: string): TableColumnInterface | undefined {
        return columnsByName.value.get(id);
    }

    function cellComponent(id: string): Component | null {
        const column = columnFor(id);
        if (!column?.component) return null;
        return getComponentType(column.component.type);
    }

    function cellText(row: Record<string, unknown>, id: string): unknown {
        const column = columnFor(id);
        if (!column) return '';
        return getColumnValue(row, column);
    }

    function handleCustomAction(customAction: ListViewActionType): void {
        if (customAction.action) {
            customAction.action();
        } else if (customAction.modal) {
            modalService.open(customAction.modal);
        } else if (customAction.to) {
            router.push(customAction.to);
        }
    }

    function onAction(id: string): void {
        if (id === 'create') {
            openModal();
            return;
        }

        if (transferKey && id === 'transfer:import') {
            openImport(transferKey, () => {
                void fetchData();
            });
            return;
        }

        if (transferKey && id === 'transfer:export') {
            openTransfer(transferKey, 'export', { ...filters.value }, searchQuery.value);
            return;
        }

        if (transferKey && id === 'transfer:template') {
            openTransfer(transferKey, 'template');
            return;
        }

        if (!id.startsWith('custom:')) return;

        const index = Number(id.slice('custom:'.length));
        const action = props.customActions?.[index];
        if (action) handleCustomAction(action);
    }

    function onBulkAction(id: string): void {
        if (id === 'delete') deleteSelected();
    }

    function onRowClick(row: Record<string, unknown>): void {
        navigateToDetail(getItemId(row));
    }

    function onRemoveFilter(id: string): void {
        const separator = id.indexOf(':');
        if (separator === -1) return;

        const kind = id.slice(0, separator);
        const rest = id.slice(separator + 1);

        if (kind === 'array') {
            const indexSeparator = rest.lastIndexOf(':');
            if (indexSeparator === -1) return;

            const key = decodeURIComponent(rest.slice(0, indexSeparator));
            const index = Number(rest.slice(indexSeparator + 1));
            const value = filters.value[key];
            if (!Array.isArray(value) || !Number.isInteger(index)) return;

            removeArrayFilter(key, value[index]);
            return;
        }

        if (kind === 'scalar') {
            removeFilter(decodeURIComponent(rest));
        }
    }

    function getFilterLabel(key: string): string {
        const column = modelInstance.columns().find((col) => getColumnFilterKey(col) === key);
        if (column) return t(column.label);
        return t(`GENERAL_${convertToString(key).toUpperCase()}`);
    }

    function formatFilterValue(value: unknown): string {
        if (value && typeof value === 'object' && 'from' in value && 'to' in value) {
            const dateRange = value as DateRange;
            const parts: string[] = [];
            if (dateRange.from) parts.push(dayjs(dateRange.from).format('DD MMM YYYY'));
            if (dateRange.to) parts.push(dayjs(dateRange.to).format('DD MMM YYYY'));
            if (parts.length > 0) return parts.join(' – ');
            return '';
        }

        return convertToString(value);
    }

    function getArrayFilterLabel(key: string, index: number): string {
        if (key.endsWith('_between')) {
            const labelType = index === 0 ? 'FROM' : 'TO';
            return t(`GENERAL_${key.toUpperCase()}_${labelType}`);
        }

        return t(`GENERAL_${convertToString(key).toUpperCase()}`);
    }

    function formatArrayFilterValue(item: unknown): string {
        if (typeof item === 'string' && /^\d{4}-\d{2}-\d{2}/.test(item)) {
            return dayjs(item).format('DD MMM YYYY');
        }

        return convertToString(item);
    }
</script>
