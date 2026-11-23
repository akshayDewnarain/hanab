<template>
    <!-- Vertical sidebar layout (card list / directory views) -->
    <div v-if="layout === 'sidebar'" class="flex flex-col">
        <section
            v-for="column in quickFilters"
            :key="column.name"
            class="border-b border-list-border py-2.5 first:pt-0 last:border-b-0 last:pb-0"
        >
            <button
                v-if="discovery"
                type="button"
                class="flex w-full cursor-pointer items-center gap-2 rounded-md py-1 text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                :aria-expanded="sectionOpen(column.name)"
                :aria-controls="`directory-filter-${column.name}`"
                @click="toggleSection(column.name)"
            >
                <Icon class="size-5 shrink-0 text-list-text-muted" :icon="sectionIcon(column.name)" />
                <span class="min-w-0 flex-1 text-sm font-semibold text-list-text">
                    {{ t(sectionLabel(column)) }}
                    <span v-if="selectionCount(column)" class="font-medium text-list-text-muted">
                        ({{ selectionCount(column) }})
                    </span>
                </span>
                <Icon
                    class="size-5 shrink-0 text-list-text-muted transition-transform duration-200 ease-out motion-reduce:transition-none"
                    :class="sectionOpen(column.name) ? 'rotate-180' : ''"
                    icon="material-symbols:expand-more-rounded"
                />
            </button>
            <h4 v-else class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-list-text-muted">
                {{ t(column.label) }}
            </h4>
            <p v-if="discovery" class="pb-1 pl-7 text-sm text-list-text-muted">
                {{ sectionSummary(column) }}
            </p>

            <Collapsible :id="`directory-filter-${column.name}`" :open="!discovery || sectionOpen(column.name)">
                <div v-if="!column.component" class="pt-1">
                    <label :for="`sidebar-${getEffectiveKey(column)}`" class="sr-only">{{ t(column.label) }}</label>
                    <input
                        :id="`sidebar-${getEffectiveKey(column)}`"
                        v-model="_filters[getEffectiveKey(column)]"
                        class="input-base h-9 w-full min-w-0 rounded-lg border-list-border bg-list-surface px-3 text-sm text-list-text shadow-sm transition-[border-color,box-shadow] duration-200 ease-out focus:border-primary focus:ring-2 focus:ring-primary/15 motion-reduce:transition-none"
                        :placeholder="t('FILTER_CONTAINS')"
                        type="text"
                        @input="onInputChange"
                    />
                </div>

                <DatePickerFilter
                    v-else-if="column.component?.type === TableColumnComponentEnum.DATE"
                    :model-value="getDateRangeValue(getEffectiveKey(column))"
                    @update:model-value="onDateRangeChange($event, getEffectiveKey(column))"
                />

                <div
                    v-else-if="column.component?.type === TableColumnComponentEnum.MULTI_SELECT"
                    class="mt-1 rounded-lg border border-list-border bg-list-surface p-2"
                >
                    <MultiSelectFilter
                        layout="sidebar"
                        :column="column"
                        :model-value="getMultiSelectValue(column)"
                        @update:model-value="onMultiSelectChange($event, getEffectiveKey(column))"
                    />
                </div>

                <div
                    v-else-if="column.component?.type === TableColumnComponentEnum.BOOLEAN"
                    class="mt-1 flex h-9 items-center justify-between gap-3 rounded-lg border border-list-border bg-list-surface px-3"
                >
                    <span class="text-sm font-medium text-list-text">{{ t(column.label) }}</span>
                    <ToggleSwitch
                        :model-value="_filters[getEffectiveKey(column)] === true"
                        @update:model-value="onBooleanChange($event, getEffectiveKey(column))"
                    />
                </div>

                <div v-else-if="column.component?.enumValues" class="mt-1 rounded-lg border border-list-border bg-list-surface p-2">
                    <div class="flex flex-col gap-0.5">
                        <label
                            v-for="(label, value) in column.component?.enumValues"
                            :key="value"
                            :for="`sidebar-${getEffectiveKey(column)}-${value}`"
                            class="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 transition-colors hover:bg-list-row-hover"
                        >
                            <input
                                :id="`sidebar-${getEffectiveKey(column)}-${value}`"
                                :checked="isCheckboxChecked(getEffectiveKey(column), value)"
                                class="size-4 shrink-0 cursor-pointer rounded border-list-border text-[var(--color-primary)]"
                                type="checkbox"
                                @change="onCheckboxChange(convertToString(value), getEffectiveKey(column))"
                            />
                            <span class="min-w-0 text-sm leading-snug text-list-text">
                                {{ formatEnumLabel(label) }}
                            </span>
                        </label>
                    </div>
                </div>
            </Collapsible>
        </section>
    </div>

    <!-- Default horizontal dropdown layout (table list view) -->
    <div v-else class="flex gap-x-2 px-2">
        <FilterDropdown
            v-for="column in quickFilters"
            :key="column.name"
            :custom-class="[
                isActive(column)
                    ? '!border-[color-mix(in_srgb,var(--color-primary)_22%,#e2e8f0)] !bg-[color-mix(in_srgb,var(--color-primary)_6%,white)] !text-slate-800 !font-medium'
                    : '',
            ]"
            :model-value="_filters[getEffectiveKey(column)]"
            :placeholder="getFilterPlaceholder(column)"
        >
            <template #dropdown-content>
                <div v-if="!column.component" class="p-2">
                    <label :for="getEffectiveKey(column)" class="flex min-w-52 flex-col gap-1">
                        <span class="text-[11px] font-medium tracking-wide text-slate-500">
                            {{ t('FILTER_CONTAINS') }}
                        </span>
                        <input
                            :id="getEffectiveKey(column)"
                            v-model="_filters[getEffectiveKey(column)]"
                            class="h-8 w-full rounded-lg border border-slate-200 bg-white px-2 text-[13px] text-slate-800 outline-none placeholder:text-slate-400 focus:border-primary/30 focus:ring-2 focus:ring-primary/10"
                            placeholder="Type to filter..."
                            type="text"
                            @input="onInputChange"
                        />
                    </label>
                </div>
                <DatePickerFilter
                    v-else-if="column.component?.type === TableColumnComponentEnum.DATE"
                    :model-value="getDateRangeValue(getEffectiveKey(column))"
                    @update:model-value="onDateRangeChange($event, getEffectiveKey(column))"
                />
                <MultiSelectFilter
                    v-else-if="column.component?.type === TableColumnComponentEnum.MULTI_SELECT"
                    class="p-2"
                    :column="column"
                    :model-value="getMultiSelectValue(column)"
                    @update:model-value="onMultiSelectChange($event, getEffectiveKey(column))"
                />
                <div
                    v-else-if="column.component?.type === TableColumnComponentEnum.BOOLEAN"
                    class="flex min-w-40 items-center justify-between gap-3 px-2.5 py-2"
                >
                    <label :for="getEffectiveKey(column)" class="text-[13px] font-medium text-slate-700">
                        {{ t(column.label) }}
                    </label>
                    <ToggleSwitch
                        :model-value="_filters[getEffectiveKey(column)] === true"
                        @update:model-value="onBooleanChange($event, getEffectiveKey(column))"
                    />
                </div>
                <div v-else-if="column.component?.enumValues" class="flex flex-col py-1">
                    <div v-for="(label, value) in column.component?.enumValues" :key="value">
                        <div class="flex min-w-52 items-center gap-2 px-2.5 py-1">
                            <input
                                :id="`${getEffectiveKey(column)}-${value}`"
                                :checked="isCheckboxChecked(getEffectiveKey(column), value)"
                                :value="convertToString(value)"
                                type="checkbox"
                                @change="onCheckboxChange(convertToString(value), getEffectiveKey(column))"
                            />

                            <label :for="`${getEffectiveKey(column)}-${value}`" class="text-sm text-gray-800">{{
                                t(`GENERAL_${label.toUpperCase()}`)
                            }}</label>
                        </div>
                    </div>
                </div>
            </template>
        </FilterDropdown>
    </div>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { Collapsible } from '@freelance/vue-ui';
    import { onMounted, reactive, watch } from 'vue';
    import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
    import FilterDropdown from '@/components/filters/list-views/FilterDropdown.vue';
    import { useI18n } from 'vue-i18n';
    import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
    import { useDebounce } from '@/composables/useDebounce';
    import { convertToString } from '@/composables/useGlobalHelpers';
    import { useLocalStorage } from '@/composables/useLocalStorage';
    import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';
    import ToggleSwitch from '@/components/inputs/ToggleSwitch.vue';
    import DatePickerFilter, { type DateRange } from '@/components/filters/list-views/DatePickerFilter.vue';
    import MultiSelectFilter from '@/components/filters/list-views/MultiSelectFilter.vue';
    import { loadQuickFilterColumns } from '@/modules/utils/quickFilterColumns';
    import { getColumnFilterKey, resolveSavedFilterValue } from '@/modules/utils/tableColumnFilters';
    import dayjs from 'dayjs';

    // Define proper types for filters
    type FilterValue = string | string[] | boolean | DateRange | null | undefined;
    type Filters = Record<string, FilterValue>;

    const { t } = useI18n();
    const props = withDefaults(
        defineProps<{
            entity: string;
            model: ModelConstructor<unknown>;
            filters: Record<string, unknown>;
            /** `inline` = horizontal dropdowns (ListView). `sidebar` = stacked sections for right panel. */
            layout?: 'inline' | 'sidebar';
            /** When set, the sidebar shows these columns in order, with icons and collapse. */
            discovery?: { name: string; icon: string; label?: string }[];
        }>(),
        {
            layout: 'inline',
        },
    );

    const emit = defineEmits<{
        (e: 'update:filters', value: Record<string, unknown>): void;
        (e: 'update:mounted', value: boolean): void;
    }>();

    const modelInstance = new props.model();
    const discoveryIcons = new Map((props.discovery ?? []).map((section) => [section.name, section.icon]));
    const quickFilters = props.discovery?.length
        ? props.discovery
              .map((section) => modelInstance.columns().find((column) => column.name === section.name))
              .filter((column): column is TableColumn => Boolean(column))
        : loadQuickFilterColumns(modelInstance);
    const openSections = reactive<Record<string, boolean>>({});
    const { getLocalStorageItem } = useLocalStorage();

    const _filters = reactive<Filters>({});

    const getEffectiveKey = getColumnFilterKey;

    initializeFilters(loadFilters());

    const debouncedEmit = useDebounce(() => {
        emit('update:filters', _filters);
    }, 500);

    function onInputChange() {
        debouncedEmit();
    }

    function getMultiSelectValue(column: TableColumn): string[] {
        const value = _filters[getEffectiveKey(column)];
        if (!Array.isArray(value)) {
            return [];
        }
        return value.map((item) => convertToString(item));
    }

    function onMultiSelectChange(values: string[], columnName: string): void {
        _filters[columnName] = values.length > 0 ? values : [];
        emit('update:filters', _filters);
    }

    const onCheckboxChange = (value: string, columnName: string) => {
        value = value.toLowerCase();
        const currentFilter = _filters[columnName];

        if (Array.isArray(currentFilter)) {
            if (currentFilter.includes(value)) {
                _filters[columnName] = currentFilter.filter((item: string) => item !== value);
            } else {
                _filters[columnName] = [...currentFilter, value];
            }
        } else {
            // If it's not an array, initialize it as an array with the value
            _filters[columnName] = [value];
        }

        emit('update:filters', _filters);
    };

    const onBooleanChange = (value: boolean | undefined, columnName: string) => {
        _filters[columnName] = value ?? false;
        emit('update:filters', _filters);
    };

    const onDateRangeChange = (value: DateRange | null, columnName: string) => {
        _filters[columnName] = value;
        emit('update:filters', _filters);
    };

    const getDateRangeValue = (columnName: string): DateRange | null | undefined => {
        const value = _filters[columnName];
        if (!value) {
            return null;
        }

        if (typeof value === 'object') {
            if ('from' in value && 'to' in value) {
                // Already a DateRange object
                return value as DateRange;
            } else if (Array.isArray(value)) {
                // Convert array [from, to] to DateRange object
                const dateArray = value as (string | null | undefined)[];
                return {
                    from: (dateArray[0] && String(dateArray[0])) || null,
                    to: (dateArray[1] && String(dateArray[1])) || null,
                } as DateRange;
            }
        }

        return null;
    };

    const isCheckboxChecked = (columnName: string, value: unknown): boolean => {
        const filterValue = _filters[columnName];
        if (Array.isArray(filterValue)) {
            return filterValue.includes(convertToString(value).toLowerCase());
        }
        return false;
    };

    function formatEnumLabel(label: string): string {
        const key = `GENERAL_${label.toUpperCase()}`;
        const translated = t(key);
        if (translated !== key) {
            return translated;
        }
        const employeeKey = `EMPLOYEE_EMPLOYMENT_${label.toUpperCase()}`;
        const employeeTranslated = t(employeeKey);
        if (employeeTranslated !== employeeKey) {
            return employeeTranslated;
        }
        return label;
    }

    const getFilterPlaceholder = (column: TableColumn): string => {
        if (column.component?.type === TableColumnComponentEnum.MULTI_SELECT) {
            const selected = getMultiSelectValue(column);
            if (selected.length > 0) {
                return `${t(column.label)} (${selected.length})`;
            }
        }
        if (column.component?.type === TableColumnComponentEnum.DATE) {
            const dateRange = getDateRangeValue(getEffectiveKey(column));
            if (dateRange && (dateRange.from || dateRange.to)) {
                const parts: string[] = [];
                if (dateRange.from) {
                    parts.push(dayjs(dateRange.from).format('DD MMM'));
                }
                if (dateRange.to) {
                    parts.push(dayjs(dateRange.to).format('DD MMM'));
                }
                if (parts.length > 0) {
                    return `${t(column.label)}: ${parts.join(' – ')}`;
                }
            }
        }
        return column.label;
    };

    function initializeFilters(saved: Record<string, unknown>) {
        quickFilters.forEach((column: TableColumn) => {
            const effectiveKey = getEffectiveKey(column);
            const value = resolveSavedFilterValue(column, saved);

            if (column.component?.type === TableColumnComponentEnum.BOOLEAN) {
                _filters[effectiveKey] = value === true ? true : undefined;
            } else if (column.component?.type === TableColumnComponentEnum.DATE) {
                // Handle date range - can come as DateRange object or array [from, to]
                if (value && typeof value === 'object') {
                    if ('from' in value && 'to' in value) {
                        // Already a DateRange object
                        _filters[effectiveKey] = value as DateRange;
                    } else if (Array.isArray(value)) {
                        // Convert array [from, to] to DateRange object
                        const dateArray = value as (string | null | undefined)[];
                        _filters[effectiveKey] = {
                            from: (dateArray[0] && String(dateArray[0])) || null,
                            to: (dateArray[1] && String(dateArray[1])) || null,
                        } as DateRange;
                    } else {
                        _filters[effectiveKey] = null;
                    }
                } else {
                    _filters[effectiveKey] = null;
                }
            } else if (Array.isArray(value)) {
                _filters[effectiveKey] = [...value] as string[];
            } else {
                _filters[effectiveKey] = (value as string) ?? '';
            }
        });
    }

    function sectionOpen(name: string): boolean {
        return openSections[name] === true;
    }

    function toggleSection(name: string): void {
        openSections[name] = !openSections[name];
    }

    function sectionIcon(name: string): string {
        return discoveryIcons.get(name) ?? 'material-symbols:filter-alt-outline-rounded';
    }

    function sectionLabel(column: TableColumn): string {
        return props.discovery?.find((section) => section.name === column.name)?.label ?? column.label;
    }

    function selectionCount(column: TableColumn): number {
        const value = _filters[getEffectiveKey(column)];
        if (Array.isArray(value)) return value.filter((item) => item !== '' && item !== null && item !== undefined).length;
        if (typeof value === 'boolean') return 1;
        if (typeof value === 'string' && value.trim() !== '') return 1;
        if (value && typeof value === 'object' && 'from' in value) {
            const range = value as { from?: string | null; to?: string | null };
            return range.from || range.to ? 1 : 0;
        }
        return 0;
    }

    function sectionSummary(column: TableColumn): string {
        if (!isActive(column)) {
            return t('DIRECTORY_FILTER_ALL');
        }
        const value = _filters[getEffectiveKey(column)];
        if (Array.isArray(value)) {
            return t('DIRECTORY_FILTER_SELECTED', { count: value.length });
        }
        if (typeof value === 'boolean') {
            return value ? t('DIRECTORY_FILTER_ACTIVE') : t('DIRECTORY_FILTER_INACTIVE');
        }
        return String(value);
    }

    const isActive = (column: TableColumn): boolean => {
        const effectiveKey = getEffectiveKey(column);
        const val = _filters[effectiveKey];
        if (Array.isArray(val)) {
            return val.length > 0;
        }
        if (typeof val === 'boolean') {
            return true;
        }
        if (val && typeof val === 'object' && 'from' in val && 'to' in val) {
            const dateRange = val as DateRange;
            return !!(dateRange.from || dateRange.to);
        }
        return !!val;
    };

    const adjustFilters = (newValues: Record<string, unknown>) => {
        if (Object.keys(newValues).length === 0) {
            quickFilters.forEach((column: TableColumn) => {
                const effectiveKey = getEffectiveKey(column);
                if (column.component?.type === TableColumnComponentEnum.BOOLEAN) {
                    _filters[effectiveKey] = undefined;
                } else if (column.component?.type === TableColumnComponentEnum.DATE) {
                    _filters[effectiveKey] = null;
                } else {
                    _filters[effectiveKey] = '';
                }
            });

            return;
        }

        // First, clear all existing filters for quick filter columns
        quickFilters.forEach((column: TableColumn) => {
            const effectiveKey = getEffectiveKey(column);
            const newValue = resolveSavedFilterValue(column, newValues);

            if (newValue === undefined) {
                if (column.component?.type === TableColumnComponentEnum.BOOLEAN) {
                    _filters[effectiveKey] = undefined;
                } else if (column.component?.type === TableColumnComponentEnum.DATE) {
                    _filters[effectiveKey] = null;
                } else {
                    _filters[effectiveKey] = '';
                }
                return;
            }

            if (column.component?.type === TableColumnComponentEnum.BOOLEAN) {
                _filters[effectiveKey] = newValue === true ? true : undefined;
                return;
            }

            if (column.component?.type === TableColumnComponentEnum.DATE) {
                if (newValue && typeof newValue === 'object') {
                    if ('from' in newValue && 'to' in newValue) {
                        // Already a DateRange object
                        _filters[effectiveKey] = newValue as DateRange;
                    } else if (Array.isArray(newValue)) {
                        // Convert array [from, to] to DateRange object
                        const dateArray = newValue as (string | null | undefined)[];
                        _filters[effectiveKey] = {
                            from: (dateArray[0] && String(dateArray[0])) || null,
                            to: (dateArray[1] && String(dateArray[1])) || null,
                        } as DateRange;
                    } else {
                        _filters[effectiveKey] = null;
                    }
                } else {
                    _filters[effectiveKey] = null;
                }
                return;
            }

            if (Array.isArray(newValue)) {
                if (newValue.length === 0) {
                    delete _filters[effectiveKey];
                } else {
                    _filters[effectiveKey] = newValue as string[];
                }
                return;
            }

            _filters[effectiveKey] = newValue === '' || newValue === null ? '' : (newValue as string);
        });
    };

    function loadFilters(): Record<string, unknown> {
        const modelInstance = new props.model();
        const filtersFromStorage = getLocalStorageItem(`${modelInstance.getEntity()}_filters`);
        if (filtersFromStorage) {
            return filtersFromStorage;
        } else {
            return {} as Record<string, unknown>;
        }
    }

    onMounted(() => {
        quickFilters.forEach((column: TableColumn) => {
            const effectiveKey = getEffectiveKey(column);
            if (
                column.component?.type === TableColumnComponentEnum.ENUM ||
                column.component?.type === TableColumnComponentEnum.BADGE ||
                column.component?.type === TableColumnComponentEnum.MULTI_SELECT
            ) {
                _filters[effectiveKey] = [];
            } else if (column.component?.type === TableColumnComponentEnum.BOOLEAN) {
                _filters[effectiveKey] = undefined;
            } else if (column.component?.type === TableColumnComponentEnum.DATE) {
                _filters[effectiveKey] = null;
            } else {
                _filters[effectiveKey] = '';
            }
        });

        emit('update:mounted', true);
    });

    watch(
        () => props.filters,
        (newFilters) => {
            adjustFilters(newFilters);
            if (!props.discovery) {
                return;
            }
            quickFilters.forEach((column) => {
                if (isActive(column)) {
                    openSections[column.name] = true;
                }
            });
        },
        { deep: true },
    );
</script>
