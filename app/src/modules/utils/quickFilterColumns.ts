import TableColumn from '@/modules/models/support/list-views/TableColumn';
import type { TableColumnComponentType } from '@/modules/types/support/list-views/TableColumnComponentType';
import type { TableRelation } from '@/modules/types/support/list-views/TableRelation';

function mergeComponent(
    live?: TableColumnComponentType,
    stored?: TableColumnComponentType,
): TableColumnComponentType | undefined {
    if (!live && !stored) {
        return undefined;
    }
    if (!live) {
        return stored;
    }
    if (!stored) {
        return live;
    }

    return {
        ...live,
        ...stored,
        type: stored.type ?? live.type,
        enumValues: live.enumValues ?? stored.enumValues,
        multiSelect: live.multiSelect ?? stored.multiSelect,
    };
}

function mergeRelation(live?: TableRelation, stored?: TableRelation): TableRelation | undefined {
    if (!live && !stored) {
        return undefined;
    }
    if (!live) {
        return stored;
    }
    if (!stored) {
        return live;
    }

    return {
        ...live,
        ...stored,
        type: stored.type ?? live.type,
        path: live.path ?? stored.path,
        displayKey: live.displayKey ?? stored.displayKey,
        idKey: live.idKey ?? stored.idKey,
        sortKey: live.sortKey ?? stored.sortKey,
        filterKey: live.filterKey ?? stored.filterKey,
        multipleSeparator: live.multipleSeparator ?? stored.multipleSeparator,
        emptyLabel: live.emptyLabel ?? stored.emptyLabel,
    };
}

function mergeColumnWithDefaults(storedCol: TableColumn, live: TableColumn): TableColumn {
    return new TableColumn({
        ...live,
        ...storedCol,
        quickFilter: 'quickFilter' in storedCol ? storedCol.quickFilter : live.quickFilter,
        visible: storedCol.visible ?? live.visible,
        filterable: storedCol.filterable ?? live.filterable,
        sortable: storedCol.sortable ?? live.sortable,
        label: storedCol.label ?? live.label,
        filterKey: live.filterKey ?? storedCol.filterKey,
        relation: mergeRelation(live.relation, storedCol.relation),
        component: mergeComponent(live.component, storedCol.component),
    });
}

/**
 * Table configuration in localStorage is JSON — class constructors (e.g. multiSelect.model) are lost.
 * Reattach live column metadata from the model definition while keeping user visibility/order prefs.
 *
 * Quick-filter order follows the saved table configuration column order (TableConfigurationModal).
 */
export function mergeQuickFilterColumnsWithDefaults(
    storedColumns: TableColumn[],
    defaultQuickFilters: TableColumn[],
): TableColumn[] {
    const defaultByName = new Map(defaultQuickFilters.map((col) => [col.name, col]));
    const ordered: TableColumn[] = [];
    const seen = new Set<string>();

    for (const storedCol of storedColumns) {
        const name = storedCol.name;
        if (!name) {
            continue;
        }

        const live = defaultByName.get(name);
        if (!live) {
            seen.add(name);
            if (storedCol.quickFilter) {
                ordered.push(Object.assign(new TableColumn(storedCol), storedCol));
            }
            continue;
        }

        seen.add(name);
        const merged = mergeColumnWithDefaults(storedCol, live);
        if (merged.quickFilter) {
            ordered.push(merged);
        }
    }

    for (const live of defaultQuickFilters) {
        if (!seen.has(live.name)) {
            ordered.push(live);
        }
    }

    return ordered;
}

type ModelWithColumns = {
    getEntity(): string;
    columns(): TableColumn[];
};

/** Load quick-filter columns in table-configuration order. */
export function loadQuickFilterColumns(modelInstance: ModelWithColumns): TableColumn[] {
    const defaultQuickFilters = modelInstance.columns().filter((column) => column.quickFilter);

    const storageKey = `${modelInstance.getEntity()}_table_configuration`;
    const storedConfig = localStorage.getItem(storageKey);

    if (storedConfig) {
        try {
            const parsed: TableColumn[] = JSON.parse(storedConfig);

            if (Array.isArray(parsed) && parsed.length > 0) {
                return mergeQuickFilterColumnsWithDefaults(parsed, defaultQuickFilters);
            }
        } catch (e) {
            console.warn(`Failed to parse table configuration from localStorage for key "${storageKey}"`, e);
        }
    }

    return defaultQuickFilters;
}
