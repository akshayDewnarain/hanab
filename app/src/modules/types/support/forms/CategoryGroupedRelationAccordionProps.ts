import type Model from '@/modules/models/support/Model.ts';
import type TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import type { CategoryGroupedRelationCategory } from '@/modules/types/support/forms/CategoryGroupedRelationCategory.ts';
import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';

/**
 * Props for one category accordion inside a category-grouped relation manager.
 *
 * @property category - Category metadata (value + i18n label).
 * @property items - Linked child rows currently shown in this category.
 * @property attachedIds - Full set of linked ids (global selection for the parent field).
 * @property listColumns - Table columns for the linked-items list.
 * @property entity - API resource segment passed to the entity picker modal.
 * @property model - Child model class for the picker and column definitions.
 * @property pickerTitleKey - i18n key for the entity picker modal title.
 * @property addButtonKey - i18n key for the header "add" button.
 * @property countLabelKey - i18n key for the count suffix (e.g. "skills").
 * @property includes - Optional API `include` list for the picker index.
 * @property defaultOpen - When true, the accordion starts expanded.
 */
export interface CategoryGroupedRelationAccordionProps {
    category: CategoryGroupedRelationCategory;
    items: Model<unknown>[];
    attachedIds: number[];
    listColumns: TableColumn[];
    entity: string;
    model: ModelConstructor<unknown>;
    pickerTitleKey: string;
    addButtonKey: string;
    countLabelKey: string;
    includes?: string[];
    defaultOpen?: boolean;
}
