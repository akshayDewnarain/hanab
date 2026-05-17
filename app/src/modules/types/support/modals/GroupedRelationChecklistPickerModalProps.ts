import type { CategoryGroupedRelationCategory } from '@/modules/types/support/forms/CategoryGroupedRelationCategory.ts';
import type { CategoryGroupedRelationRow } from '@/modules/types/support/forms/CategoryGroupedRelationRow.ts';

/**
 * Props for {@link GroupedRelationChecklistPickerModal} (grouped checklist, no table).
 *
 * @property library - Full child library loaded from index.
 * @property categories - Category buckets with user-facing label keys.
 * @property selectedIds - Draft selection when the modal opens.
 * @property restrictedCategory - When set, only this category group is shown (per-category add flow).
 * @property pickerTitleKey - i18n modal title.
 * @property searchPlaceholderKey - i18n search input placeholder.
 */
export type GroupedRelationChecklistPickerModalProps = {
    library: CategoryGroupedRelationRow[];
    categories: CategoryGroupedRelationCategory[];
    selectedIds: number[];
    restrictedCategory?: string | null;
    pickerTitleKey: string;
    searchPlaceholderKey?: string;
};
