/**
 * Minimal child row shape loaded for category-grouped relation managers.
 *
 * Used when indexing the full skills/certificates library for grouping and labels.
 *
 * @property id - Primary key of the child record.
 * @property category - Category value used to place the row in an accordion group.
 * @property name - Display name for sorting within a group.
 * @property code - Short abbreviation shown on labels when set.
 * @property label_background_color - Optional chip background (hex).
 * @property label_text_color - Optional chip text color (hex).
 * @property label_border_color - Optional chip border color (hex).
 */
export type CategoryGroupedRelationRow = {
    id: number;
    category?: string | null;
    name?: string;
    code?: string | null;
    label_background_color?: string | null;
    label_text_color?: string | null;
    label_border_color?: string | null;
};
