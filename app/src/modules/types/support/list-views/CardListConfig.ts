import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';

/**
 * Display type for a single value on a {@link import('@/components/list-views/CardListItem.vue').default CardListItem}.
 */
export type CardListFieldType = 'text' | 'badges' | 'date' | 'boolean';

/**
 * One label/value row on a list card.
 */
export type CardListFieldConfig = {
    /** Dot path on the record, or logical key resolved via `columnName`. */
    key: string;
    /** i18n key for the row label; falls back to column label when `columnName` is set. */
    label?: string;
    /** Iconify icon id shown beside the label. */
    icon?: string;
    type?: CardListFieldType;
    /** When set, value is resolved with the same rules as table {@link TableColumnInterface} columns. */
    columnName?: string;
    hideEmpty?: boolean;
    /** When set with `type: 'badges'`, read this property from each relation object (default `name`). */
    badgeKey?: string;
    /** i18n key for a warning row when the value is empty and `hideEmpty` is false. */
    emptyWarningKey?: string;
    format?: (value: unknown, item: Record<string, unknown>) => string | null | undefined;
};

/**
 * Configuration for {@link import('@/components/list-views/CardListView.vue').default CardListView} card rendering.
 */
export type CardListConfig = {
    titleField?: string;
    /** Combined when `titleField` is not set (e.g. `first_name` + `last_name`). */
    titleFields?: string[];
    titleSeparator?: string;
    imageField?: string;
    subtitleField?: string;
    /** Static CSS color for the card header bar. */
    headerColor?: string;
    /** Dot path on the record for a per-row header color (hex). */
    headerColorField?: string;
    fields?: CardListFieldConfig[];
    /** When true and `fields` is empty, build rows from visible table columns. */
    deriveFromColumns?: boolean;
    /** Column names omitted when deriving from columns (e.g. image, timestamps). */
    excludeColumnNames?: string[];
    /** i18n key for empty state; defaults to `NO_RESULTS`. */
    emptyStateKey?: string;
};
