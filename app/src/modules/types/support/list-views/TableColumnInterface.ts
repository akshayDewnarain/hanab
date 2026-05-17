import type { TableColumnComponentType } from '@/modules/types/support/list-views/TableColumnComponentType.ts';
import type { TableRelation } from '@/modules/types/support/list-views/TableRelation.ts';

/**
 * Declarative description of a list or table column (header, filters, cell rendering).
 *
 * Used by {@link import('@/components/list-views/SimpleListView.vue').default SimpleListView}, {@link import('@/components/list-views/ListView.vue').default ListView}, and {@link import('@/composables/useListViewHelper.ts').useListViewHelper useListViewHelper} alongside {@link import('@/modules/models/support/list-views/TableColumn.ts').default TableColumn} instances.
 *
 * @property name - Field key on the row object (may use dot paths when not using `relation`).
 * @property label - i18n key or literal shown in the column header.
 * @property sortable - Whether the API/UI allows sorting on this column.
 * @property filterable - Whether advanced filters may target this column.
 * @property quickFilter - Whether the column participates in quick-filter chips.
 * @property visible - Whether the column is shown (column picker may toggle this).
 * @property component - Optional cell renderer config ({@link TableColumnComponentType}).
 * @property width - Optional layout hint (`fit`, `content`, or a `tw:`-prefixed class token).
 * @property formatter - Optional function to stringify the raw cell value for plain-text fallback.
 * @property relation - Optional relation wiring for nested row values.
 * @property filterKey - Override query key for filters (e.g. `created_at_between`).
 */
export interface TableColumnInterface {
    name: string;
    label: string;
    sortable: boolean;
    filterable: boolean;
    quickFilter: boolean;
    visible: boolean;
    component?: TableColumnComponentType;
    width?: string;
    formatter?: (value: unknown) => string;
    relation?: TableRelation;
    filterKey?: string;
}
