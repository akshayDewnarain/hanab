import type { CategoryGroupedRelationCategory } from '@/modules/types/support/forms/CategoryGroupedRelationCategory.ts';
import type { CategoryGroupedRelationRow } from '@/modules/types/support/forms/CategoryGroupedRelationRow.ts';

/**
 * Rows from the library that are currently linked, preserving library order within each category.
 */
export function getAttachedRows(
    attachedIds: number[],
    library: CategoryGroupedRelationRow[],
): CategoryGroupedRelationRow[] {
    const idSet = new Set(attachedIds);
    return library.filter((row) => idSet.has(row.id));
}

/**
 * Linked rows grouped by category value, sorted by name within each group.
 */
export function attachedRowsByCategory(
    attachedIds: number[],
    library: CategoryGroupedRelationRow[],
    categories: CategoryGroupedRelationCategory[],
): Record<string, CategoryGroupedRelationRow[]> {
    const attached = getAttachedRows(attachedIds, library);
    const map: Record<string, CategoryGroupedRelationRow[]> = {};

    for (const row of attached) {
        const key = row.category ?? '';
        (map[key] ||= []).push(row);
    }

    for (const key of Object.keys(map)) {
        const rows = map[key];
        if (rows) {
            rows.sort((a, b) => String(a.name ?? '').localeCompare(String(b.name ?? '')));
        }
    }

    for (const cat of categories) {
        map[cat.value] ||= [];
    }

    return map;
}

/**
 * Library rows grouped by category for checklist display (only categories with at least one library row).
 */
export function libraryGroupsForPicker(
    library: CategoryGroupedRelationRow[],
    categories: CategoryGroupedRelationCategory[],
    options?: { restrictedCategory?: string | null },
): { category: CategoryGroupedRelationCategory; rows: CategoryGroupedRelationRow[] }[] {
    const restricted = options?.restrictedCategory ?? null;
    const visibleCategories = restricted
        ? categories.filter((c) => c.value === restricted)
        : categories;

    return visibleCategories
        .map((category) => {
            const rows = library
                .filter((row) => (row.category ?? '') === category.value)
                .sort((a, b) => String(a.name ?? '').localeCompare(String(b.name ?? '')));
            return { category, rows };
        })
        .filter((group) => group.rows.length > 0);
}

export function filterRowsByName(
    rows: CategoryGroupedRelationRow[],
    query: string,
): CategoryGroupedRelationRow[] {
    const q = query.trim().toLowerCase();
    if (!q) {
        return rows;
    }
    return rows.filter((row) => {
        const name = String(row.name ?? '').toLowerCase();
        const code = String(row.code ?? '').toLowerCase();
        return name.includes(q) || code.includes(q);
    });
}

export function toggleAttachedId(attachedIds: number[], id: number): number[] {
    if (attachedIds.includes(id)) {
        return attachedIds.filter((x) => x !== id);
    }
    return [...attachedIds, id];
}

export function removeAttachedId(attachedIds: number[], id: number): number[] {
    return attachedIds.filter((x) => x !== id);
}

export function categoryOfRow(
    library: CategoryGroupedRelationRow[],
    id: number,
): string | undefined {
    return library.find((row) => row.id === id)?.category ?? undefined;
}

export function relationRowDisplayLabel(row: CategoryGroupedRelationRow): string {
    return row.code?.trim() || row.name?.trim() || '';
}

export function relationRowChipStyle(row: CategoryGroupedRelationRow): Record<string, string> {
    return {
        backgroundColor: row.label_background_color || '#f1f5f9',
        color: row.label_text_color || '#334155',
        borderColor: row.label_border_color || '#cbd5e1',
    };
}
