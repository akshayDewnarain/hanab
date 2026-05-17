import type { CategoryGroupedRelationCategory } from '@/modules/types/support/forms/CategoryGroupedRelationCategory.ts';
import type { CategoryGroupedRelationHowToTip } from '@/modules/types/support/forms/CategoryGroupedRelationHowToTip.ts';
import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';

/**
 * Props for the category-grouped many-to-many manager custom field component.
 *
 * Bound via `v-model` to a `number[]` of linked child ids (`skill_ids`, `certificate_ids`, etc.).
 *
 * @property modelValue - Current linked child ids.
 * @property entity - API resource segment for {@link EntityPicker}.
 * @property model - Child model class (columns + index).
 * @property categories - Accordion groups (order preserved).
 * @property columnNames - Subset of model column names to show in each list.
 * @property pickerTitleKey - i18n key for the picker modal title.
 * @property addButtonKey - i18n key for per-category add buttons.
 * @property countLabelKey - i18n key for item count suffix in accordion headers.
 * @property introKey - Optional i18n key for intro copy above accordions.
 * @property tipsKey - Optional i18n key for helper tips below the intro.
 * @property includes - Optional API `include` for the library index and picker.
 * @property defaultOpenAccordions - When true, category accordions start expanded.
 * @property useSimplePicker - When true, renders {@link SimpleGroupedRelationPicker} instead of accordions + table picker.
 * @property searchPlaceholderKey - i18n key for simple picker search (default `SIMPLE_RELATION_SEARCH`).
 * @property saveButtonKey - i18n key for simple picker modal save (default `SIMPLE_RELATION_SAVE`).
 * @property emptyCategoryKey - i18n key for empty category copy (default `SIMPLE_RELATION_EMPTY_CATEGORY`).
 * @property addInCategoryKey - i18n key for per-category add button (default `SIMPLE_RELATION_ADD_IN_CATEGORY`).
 * @property summaryTitleKey - Optional rich intro title (e.g. skills for this employee).
 * @property summaryDescriptionKey - Optional rich intro description.
 * @property summaryIcon - Iconify id for the summary card (default `material-symbols:link-rounded`).
 * @property linkedCountKey - i18n key with `{count}` for total linked items.
 * @property howToTitleKey - Optional “how this works” callout title.
 * @property howToTips - Optional bullet list for the callout.
 * @property sectionHeadingKey - Optional uppercase section label above category blocks.
 * @property footerNoteKey - Optional footnote below the category list.
 * @property panelExpandLabelKey - Optional i18n key for {@link InformationPanel} collapsed hint.
 * @property panelCollapseLabelKey - Optional i18n key for {@link InformationPanel} expanded hint.
 * @property readonly - Disables add/remove when true.
 * @property disabled - Disables add/remove when true.
 */
export interface CategoryGroupedRelationManagerProps {
    modelValue?: number[];
    entity: string;
    model: ModelConstructor<unknown>;
    categories: CategoryGroupedRelationCategory[];
    columnNames: string[];
    pickerTitleKey: string;
    addButtonKey: string;
    countLabelKey: string;
    introKey?: string;
    tipsKey?: string;
    includes?: string[];
    defaultOpenAccordions?: boolean;
    useSimplePicker?: boolean;
    searchPlaceholderKey?: string;
    saveButtonKey?: string;
    emptyCategoryKey?: string;
    addInCategoryKey?: string;
    summaryTitleKey?: string;
    summaryDescriptionKey?: string;
    summaryIcon?: string;
    linkedCountKey?: string;
    howToTitleKey?: string;
    howToTips?: CategoryGroupedRelationHowToTip[];
    sectionHeadingKey?: string;
    footerNoteKey?: string;
    panelExpandLabelKey?: string;
    panelCollapseLabelKey?: string;
    readonly?: boolean;
    disabled?: boolean;
}
