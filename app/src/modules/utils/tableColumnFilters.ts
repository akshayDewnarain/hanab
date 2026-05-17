import type TableColumn from '@/modules/models/support/list-views/TableColumn';

/** Old filter keys from localStorage / URLs → current API scope keys. */
const LEGACY_FILTER_KEY_ALIASES: Record<string, string> = {
    employee_role_id: 'employee_role_name',
    employee_location_id: 'employee_location_name',
    skills: 'skill_ids',
    certificates: 'certificate_ids',
};

function isNumericIdValue(value: unknown): boolean {
    return typeof value === 'string' && /^\d+$/.test(value.trim());
}

function migrateLegacyFilterKeys(filters: Record<string, unknown>): void {
    for (const [legacyKey, currentKey] of Object.entries(LEGACY_FILTER_KEY_ALIASES)) {
        if (filters[legacyKey] === undefined || filters[currentKey] !== undefined) {
            continue;
        }

        const value = filters[legacyKey];

        // Keep numeric ID filters on *_id keys (exact match filters).
        if (legacyKey.endsWith('_id') && isNumericIdValue(value)) {
            continue;
        }

        filters[currentKey] = value;
        delete filters[legacyKey];
    }
}

/** API / query-builder filter key for a column (column.filterKey → relation.filterKey → name). */
export function getColumnFilterKey(column: TableColumn): string {
    return column.filterKey ?? column.relation?.filterKey ?? column.name;
}

/** Read a saved filter value, including legacy keys stored under `column.name`. */
export function resolveSavedFilterValue(
    column: TableColumn,
    saved: Record<string, unknown>,
): unknown {
    const filterKey = getColumnFilterKey(column);
    if (saved[filterKey] !== undefined) {
        return saved[filterKey];
    }
    if (filterKey !== column.name && saved[column.name] !== undefined) {
        return saved[column.name];
    }

    for (const [legacyKey, currentKey] of Object.entries(LEGACY_FILTER_KEY_ALIASES)) {
        if (currentKey === filterKey && saved[legacyKey] !== undefined) {
            const legacyValue = saved[legacyKey];
            if (legacyKey.endsWith('_id') && isNumericIdValue(legacyValue)) {
                continue;
            }
            return legacyValue;
        }
    }

    return undefined;
}

/** Rename legacy filter keys (column.name) to the API filter key (e.g. skills → skill_ids). */
export function normalizeFiltersForColumns(
    filters: Record<string, unknown>,
    columns: TableColumn[],
): Record<string, unknown> {
    const normalized = { ...filters };

    migrateLegacyFilterKeys(normalized);

    for (const column of columns) {
        const filterKey = getColumnFilterKey(column);
        if (filterKey === column.name) {
            continue;
        }
        if (normalized[column.name] !== undefined && normalized[filterKey] === undefined) {
            normalized[filterKey] = normalized[column.name];
        }
        if (filterKey !== column.name) {
            delete normalized[column.name];
        }
    }

    return normalized;
}
