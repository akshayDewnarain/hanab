import type { Component } from 'vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { AxiosError, type AxiosResponse } from 'axios';
import { useRoute, useRouter } from 'vue-router';
import { kebabCase, snakeCase } from 'lodash-es';


import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { Meta } from '@/modules/types/support/responses/Meta.ts';
import type { QueryParameters } from '@/modules/types/support/responses/QueryParameters.ts';
import { useLocalStorage } from '@/composables/useLocalStorage';
import { useDebounce } from '@/composables/useDebounce.ts';
import { useComponentState } from '@/composables/components/useComponentState';
import { useModalService } from '@/composables/useModalService';
import { useToastService } from '@/composables/useToastService';
import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget.ts';
import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType.ts';
import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
import { ModalVariant } from '@/modules/enums/support/modals/ModalVariant.ts';
import type { ModalProps } from '@/modules/types/support/modals/ModalProps.ts';
import type { ModalResult } from '@/modules/types/support/modals/ModalResult.ts';
import type { EntityModalDataResult } from '@/modules/types/support/inputs/EntityModalDataResult.ts';
import { convertToString } from '@/composables/useGlobalHelpers';
import { normalizeFiltersForColumns } from '@/modules/utils/tableColumnFilters';

import DateComponent from '@/components/table-fields/DateComponent.vue';
import CurrencyComponent from '@/components/table-fields/CurrencyComponent.vue';
import BooleanComponent from '@/components/table-fields/BooleanComponent.vue';
import BadgeComponent from '@/components/table-fields/BadgeComponent.vue';
import ImageComponent from '@/components/table-fields/ImageComponent.vue';
import EnumComponent from '@/components/table-fields/EnumComponent.vue';
import TableConfigurationModal from '@/components/modals/content-modals/TableConfigurationModal.vue';
import EntityModal from '@/components/modals/content-modals/EntityModal.vue';
import ConfirmationModal from '@/components/modals/content-modals/ConfirmationModal.vue';
import TimeComponent from '@/components/table-fields/TimeComponent.vue';
import WeekdayComponent from '@/components/table-fields/WeekdayComponent.vue';
import ComponentPreview from '@/components/table-fields/ComponentPreview.vue';
import SkillLabelCellComponent from '@/components/table-fields/SkillLabelCellComponent.vue';
import type TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';
import type { UseListViewHelperProps } from '@/modules/types/support/list-views/UseListViewHelperProps.ts';

export function useListViewHelper(props: UseListViewHelperProps) {
    const { isLoading, setLoading, setContent } = useComponentState();
    const { getLocalStorageItem, setLocalStorageItem } = useLocalStorage();
    const router = useRouter();
    const route = useRoute();
    const _modalService = useModalService();
    const _toastService = useToastService();

    const modelInstance = new props.model();
    const STORAGE_KEY = `${modelInstance.getEntity()}_table_configuration`;

    const localSelectedIds = ref<number[]>([]);
    const localSelectedIdsSet = computed(() => new Set(localSelectedIds.value));
    const anySelected = computed(() => props.selectable && localSelectedIds.value.length > 0);
    const selectedCount = computed(() => localSelectedIds.value.length);
    const quickFilterKey = ref(0);
    const filters = ref<Record<string, unknown>>({});
    const searchQuery = ref<string>('');
    const columns = ref<TableColumn[]>([]);
    const items = ref<unknown[]>([]);
    const allSelected = ref<boolean>(false);
    const storedConfig = ref<TableColumn[] | null>(null);
    const meta = ref<Meta>({
        current_page: 1,
        per_page: 10,
        total: 1,
    });

    // Selection bar refs
    const barInnerRef = ref<HTMLElement | null>(null);
    const barHeight = ref(0);
    let ro: ResizeObserver | null = null;

    // Computed properties
    const visibleColumns = computed(() => {
        const source: TableColumnInterface[] = storedConfig.value ?? columns.value;
        return source.filter((column: TableColumnInterface) => column.visible);
    });

    const filterCount = computed(() => {
        if (!filters.value || typeof filters.value !== 'object') return 0;

        return Object.values(filters.value).reduce((count: number, value: unknown) => {
            if (Array.isArray(value)) {
                return count + (value.length > 0 ? 1 : 0);
            }
            return count + (value !== null && value !== undefined && value !== '' ? 1 : 0);
        }, 0);
    });

    const barWrapperStyle = computed(() => ({
        height: `${anySelected.value ? barHeight.value : 0}px`,
        transition: `height ${anySelected.value ? 300 : 200}ms ${anySelected.value ? 'ease-out' : 'ease-in'}`,
    }));

    const handleInputChange = useDebounce(() => fetchData(), 500);

    function updateBarHeight() {
        barHeight.value = barInnerRef.value ? barInnerRef.value.offsetHeight : 0;
    }

    function cleanFilters(filters: Record<string, unknown>): Record<string, unknown> {
        const cleaned: Record<string, unknown> = {};

        for (const [key, value] of Object.entries(filters)) {
            if (value === null || value === undefined || value === '') {
                continue;
            }

            // Convert DateRange objects to arrays for API
            // Date range filters expect [from, to] format, with null for missing dates
            if (value && typeof value === 'object' && 'from' in value && 'to' in value) {
                const dateRange = value as { from: string | null; to: string | null };
                // Only add if at least one date is set
                if (dateRange.from || dateRange.to) {
                    cleaned[key] = [dateRange.from || null, dateRange.to || null];
                }
            } else if (Array.isArray(value)) {
                // Filter out null, undefined, and empty string values
                const filteredArray = value.filter(
                    (item: unknown) => item !== null && item !== undefined && item !== '',
                );
                // Only add the array if it has at least one valid value
                if (filteredArray.length > 0) {
                    cleaned[key] = filteredArray;
                }
            } else {
                cleaned[key] = value;
            }
        }

        return cleaned;
    }

    function safeGet(obj: unknown, path: string) {
        return path.split('.').reduce((o: unknown, k: string) => {
            if (o && typeof o === 'object') {
                return (o as Record<string, unknown>)[k];
            }
            return undefined;
        }, obj);
    }

    function getItemId(item: unknown): number {
        return (item as Record<string, unknown>).id as number;
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

    function getComponentType(type: TableColumnComponentEnum): Component | null {
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
            case TableColumnComponentEnum.COMPONENT_PREVIEW:
                return ComponentPreview;
            case TableColumnComponentEnum.SKILL_LABEL:
                return SkillLabelCellComponent;
            default:
                return null;
        }
    }

    function buildIndexParams(filterOverrides?: Record<string, unknown>): QueryParameters {
        const mergedFilter = {
            ...cleanFilters(
                normalizeFiltersForColumns(filterOverrides ?? filters.value, modelInstance.columns()),
            ),
            ...(props.staticFilters ?? {}),
        };

        return {
            page: meta.value.current_page,
            per_page: meta.value.per_page,
            search: searchQuery.value,
            filter: mergedFilter,
            include: props.includes ?? [],
        };
    }

    async function fetchData(): Promise<void> {
        setLoading(true);

        columns.value = modelInstance.columns();

        const params = buildIndexParams();

        try {
            const response: AxiosResponse<unknown> = await modelInstance.index(params);
            const responseData = response.data as { data: unknown[]; meta: Meta };
            items.value = responseData.data;
            meta.value = responseData.meta;

            if (props.useURLQueries) {
                updateURL(params);
            }

            saveFilters(filters.value);
        } catch {
            _toastService.error('GENERAL_FETCH_ERROR');
        } finally {
            setContent();
        }
    }

    function applyFilters(appliedFilters: Record<string, unknown>): void {
        setLoading(true);

        columns.value = modelInstance.columns();

        const cleanedFilters = cleanFilters(
            normalizeFiltersForColumns(appliedFilters, columns.value),
        );
        filters.value = cleanedFilters;

        const params = buildIndexParams(cleanedFilters);

        modelInstance
            .index(params)
            .then((response: AxiosResponse<unknown>) => {
                const responseData = response.data as { data: unknown[]; meta: Meta };
                items.value = responseData.data;
                meta.value = responseData.meta;

                if (props.useURLQueries) {
                    updateURL(params);
                }
                saveFilters(filters.value);
            })
            .catch((error: AxiosError) => {
                console.log('Error fetching data', { error });
            })
            .finally(() => {
                setContent();
            });
    }

    function updateURL(params: QueryParameters): void {
        const queryParams: Record<string, string | string[]> = {
            page: String(params.page),
            per_page: String(params.per_page),
            search: params.search || '',
        };

        if (params.filter) {
            const filter = params.filter as Record<string, unknown>;
            Object.keys(filter).forEach((key) => {
                const value = filter[key];

                // DateRange objects should already be converted to arrays by cleanFilters
                // But handle them here just in case (fallback)
                if (value && typeof value === 'object' && 'from' in value && 'to' in value) {
                    const dateRange = value as { from: string | null; to: string | null };
                    if (dateRange.from || dateRange.to) {
                        const rangeArray = [dateRange.from || null, dateRange.to || null];
                        rangeArray.forEach((dateValue: string | null, index: number) => {
                            if (dateValue) {
                                queryParams[`filter[${key}][${index}]`] = dateValue;
                            }
                        });
                    }
                } else if (Array.isArray(value)) {
                    value.forEach((item: unknown, index: number) => {
                        queryParams[`filter[${key}][${index}]`] = String(item);
                    });
                } else {
                    queryParams[`filter[${key}]`] = String(value);
                }
            });
        }

        router.push({ query: queryParams });
    }

    function loadRouteParams(): void {
        const query = route.query;

        if (query.page && typeof query.page === 'string') {
            meta.value.current_page = parseInt(query.page);
        }

        if (query.per_page && typeof query.per_page === 'string') {
            meta.value.per_page = parseInt(query.per_page);
        }

        if (query.search && typeof query.search === 'string') {
            searchQuery.value = query.search;
        }

        const routeFilters: Record<string, unknown> = {};
        Object.keys(query).forEach((key) => {
            if (key.startsWith('filter[') && key.endsWith(']')) {
                const filterKey = key.slice(7, -1); // Remove 'filter[' and ']'
                const value = query[key];

                if (filterKey.includes('[') && filterKey.endsWith(']')) {
                    const openBracket = filterKey.indexOf('[');
                    if (openBracket <= 0) {
                        routeFilters[filterKey] = value;
                    } else {
                        const filterName = filterKey.slice(0, openBracket);
                        const indexStr = filterKey.slice(openBracket + 1, -1);
                        const index = parseInt(indexStr, 10);
                        if (filterName.length === 0 || Number.isNaN(index)) {
                            routeFilters[filterKey] = value;
                        } else {
                            if (!routeFilters[filterName]) {
                                routeFilters[filterName] = [];
                            }
                            const bucket = routeFilters[filterName];
                            if (Array.isArray(bucket)) {
                                (bucket as unknown[])[index] = value;
                            }
                        }
                    }
                } else {
                    routeFilters[filterKey] = value;
                }
            }
        });

        // Convert date range arrays back to DateRange objects
        // Check if any filter key ends with '_between' (date range filters)
        const columns = modelInstance.columns();
        const dateRangeColumns = columns.filter(
            (col) => col.component?.type === TableColumnComponentEnum.DATE && col.filterKey,
        );

        Object.keys(routeFilters).forEach((key) => {
            const column = dateRangeColumns.find((col) => col.filterKey === key);
            if (column && Array.isArray(routeFilters[key])) {
                const dateArray = routeFilters[key] as (string | undefined)[];
                const dateRange: { from: string | null; to: string | null } = {
                    from: (dateArray[0] && String(dateArray[0])) || null,
                    to: (dateArray[1] && String(dateArray[1])) || null,
                };
                // Only convert if at least one date is set
                if (dateRange.from || dateRange.to) {
                    routeFilters[key] = dateRange;
                } else {
                    delete routeFilters[key];
                }
            }
        });

        // Clean filters to remove arrays with only null/empty values
        const cleanedRouteFilters = cleanFilters(
            normalizeFiltersForColumns(routeFilters, columns),
        );

        if (Object.keys(cleanedRouteFilters).length > 0) {
            filters.value = { ...filters.value, ...cleanedRouteFilters };
        }
    }

    function loadFilters(): void {
        const filtersFromStorage = getLocalStorageItem(`${modelInstance.getEntity()}_filters`);
        if (filtersFromStorage) {
            // Clean filters to remove arrays with only null/empty values
            filters.value = cleanFilters(
                normalizeFiltersForColumns(filtersFromStorage, modelInstance.columns()),
            );
        } else {
            filters.value = {} as Record<string, unknown>;
        }
    }

    function saveFilters(filters: Record<string, unknown>): void {
        setLocalStorageItem(`${modelInstance.getEntity()}_filters`, filters);
    }

    function resetFilters(): void {
        filters.value = {} as Record<string, unknown>;
        applyFilters(filters.value);
    }

    const removeFilter = (key: string) => {
        delete filters.value[key];
        applyFilters(filters.value);
    };

    const removeArrayFilter = (key: string, valueToRemove: unknown) => {
        const arr = filters.value[key];
        if (!Array.isArray(arr)) return;

        filters.value[key] = arr.filter((val: unknown) => val !== valueToRemove);

        if (Array.isArray(filters.value[key]) && filters.value[key].length === 0) {
            delete filters.value[key];
        }

        applyFilters(filters.value);
    };

    function refreshStoredConfig() {
        const config = getLocalStorageItem(STORAGE_KEY);
        storedConfig.value = Array.isArray(config) && config.length > 0 ? config : null;
    }

    function saveTableConfig(config: TableColumn[]): void {
        setLocalStorageItem(STORAGE_KEY, config);
        _toastService.success('TABLE_CONFIGURATION_SAVED');
    }

    function resetTableConfig(): void {
        setLocalStorageItem(STORAGE_KEY, null);
        _toastService.success('TABLE_CONFIGURATION_RESET');
    }

    function openTableConfiguration(): void {
        _modalService.open({
            title: `${modelInstance.getEntity()}_table_configuration`.toUpperCase(),
            type: ModalType.SLIDE_IN_RIGHT,
            component: TableConfigurationModal,
            props: {
                entity: props.entity,
                model: props.model,
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
                    name: 'GENERAL_RESET',
                    variant: ModalActionType.PRIMARY_OUTLINE,
                    target: ModalActionTarget.RESET,
                    confirm: true,
                },
                {
                    type: ModalResultType.ACCEPT,
                    name: 'GENERAL_SAVE',
                    variant: ModalActionType.PRIMARY,
                    target: ModalActionTarget.SAVE,
                },
            ],
            onAccept: (result: ModalResult | null) => {
                if (!result || !result.target) return;
                switch (result.target) {
                    case ModalActionTarget.SAVE:
                        saveTableConfig((result.data as { columns: TableColumn[] }).columns);
                        refreshStoredConfig();
                        quickFilterKey.value++;
                        break;
                    case ModalActionTarget.RESET:
                        resetTableConfig();
                        refreshStoredConfig();
                        quickFilterKey.value++;
                        break;
                    default:
                        break;
                }
            },
        });
    }

    // Pagination
    function handlePageChange(page: number): void {
        meta.value.current_page = page;
        fetchData();
    }

    function handlePerPageChange(perPage: number): void {
        meta.value.per_page = perPage;
        setLocalStorageItem(`${props.entity}_perPage`, perPage);
        fetchData();
    }

    function toggleSelectAll(e: Event) {
        const checked = (e.target as HTMLInputElement).checked;
        localSelectedIds.value = checked ? items.value.map((i) => (i as Record<string, unknown>).id as number) : [];
    }

    function toggleOne(id: number, e: Event) {
        const checked = (e.target as HTMLInputElement).checked;
        const set = new Set(localSelectedIds.value);
        if (checked) set.add(id);
        else set.delete(id);
        localSelectedIds.value = Array.from(set);
    }

    function clearSelection() {
        localSelectedIds.value = [];
    }

    function deleteSelected(): void {
        if (localSelectedIds.value.length === 0) {
            return;
        }

        const idsToDelete = [...localSelectedIds.value];

        _modalService.open({
            title: 'GENERAL_WARNING_MESSAGE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: ConfirmationModal,
            props: {
                message: 'GENERAL_WARNING_BULK_DELETE_MESSAGE',
                variant: ModalVariant.WARNING,
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
                    name: 'GENERAL_ACCEPT',
                    variant: ModalActionType.DANGER,
                    target: ModalActionTarget.DELETE,
                },
            ],
            onAccept: async () => {
                try {
                    await Promise.all(idsToDelete.map((id) => modelInstance.destroy(id)));
                    clearSelection();
                    await fetchData();
                } catch {
                    _toastService.error('GENERAL_FETCH_ERROR');
                }
            },
        });
    }

    function navigateToDetail(id: number): void {
        const name = props.detailRouteName ?? `admin-${kebabCase(props.entity)}-detail`;
        router.push({
            name,
            params: { id },
        });
    }

    const openModal = () => {
        _modalService.open({
            title: snakeCase(modelInstance.getEntitySingular()).toUpperCase() + '_MODAL_CREATE',
            type: ModalType.SLIDE_IN_RIGHT,
            component: EntityModal,
            props: {
                entity: props.entity,
                model: props.model,
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
            async onAccept(result: ModalResult | null) {
                if (!result) {
                    return;
                }

                if (result.target === ModalActionTarget.SAVE) {
                    const data = result.data as EntityModalDataResult;
                    const modelInstance = new data.model();

                    if (data.record.id) {
                        //todo: handle update
                    } else {
                        await modelInstance.create(data.record);
                        await fetchData();
                    }
                }
            },
        });
    };

    function onQuickFiltersMounted(value: boolean): void {
        if (value) {
            loadFilters();
        }
    }

    // Lifecycle
    onMounted(async () => {
        window.addEventListener('resize', updateBarHeight);

        loadFilters();

        if (props.useURLQueries) {
            loadRouteParams();
        }
        const perPage = getLocalStorageItem(`${props.entity}_perPage`);
        if (perPage && (!props.useURLQueries || !route.query.per_page)) {
            meta.value.per_page = parseInt(perPage);
        }

        await fetchData();
    });

    onBeforeUnmount(() => {
        window.removeEventListener('resize', updateBarHeight);
        if (ro) ro.disconnect();
    });

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

    refreshStoredConfig();

    return {
        // State
        isLoading,
        localSelectedIds,
        localSelectedIdsSet,
        anySelected,
        selectedCount,
        quickFilterKey,
        filters,
        searchQuery,
        columns,
        items,
        allSelected,
        storedConfig,
        meta,
        barInnerRef,
        barHeight,
        visibleColumns,
        filterCount,
        barWrapperStyle,
        modelInstance,

        // Methods
        handleInputChange,
        fetchData,
        applyFilters,
        loadRouteParams,
        loadFilters,
        saveFilters,
        resetFilters,
        removeFilter,
        removeArrayFilter,
        refreshStoredConfig,
        saveTableConfig,
        resetTableConfig,
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
    };
}
