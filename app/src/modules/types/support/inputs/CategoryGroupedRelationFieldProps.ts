import type { CategoryGroupedRelationCategory } from '@/modules/types/support/forms/CategoryGroupedRelationCategory.ts';
import type { CategoryGroupedRelationHowToTip } from '@/modules/types/support/forms/CategoryGroupedRelationHowToTip.ts';
import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';

/**
 * Static props for {@link CustomFieldOptions} when the component is a category-grouped relation manager.
 *
 * Set on `Employee` (and similar) `skill_ids` / `certificate_ids` fields via `CustomField` `props`.
 *
 * @property entity - API resource segment (e.g. `skills`, `certificates`).
 * @property model - Child model class constructor.
 * @property categories - Accordion category definitions.
 * @property columnNames - Visible columns in each linked-items list.
 * @property pickerTitleKey - i18n key for the entity picker modal title.
 * @property addButtonKey - i18n key for per-category add buttons.
 * @property countLabelKey - i18n key for count suffix in accordion headers.
 * @property introKey - Optional i18n key for manager intro text.
 * @property tipsKey - Optional i18n key for manager tips text.
 * @property useSimplePicker - When true, uses the HR-friendly chip + checklist picker UI.
 * @property searchPlaceholderKey - Optional i18n override for simple picker search.
 * @property saveButtonKey - Optional i18n override for simple picker save (e.g. `EMPLOYEE_SKILL_PICKER_SAVE`).
 * @property addInCategoryKey - Optional i18n override for per-category add button.
 * @property emptyCategoryKey - Optional i18n override for empty category message.
 * @property summaryTitleKey - Rich intro card title.
 * @property summaryDescriptionKey - Rich intro card description.
 * @property summaryIcon - Iconify id for summary card.
 * @property linkedCountKey - i18n with `{count}` for linked total.
 * @property howToTitleKey - “How this works” callout title.
 * @property howToTips - Callout bullet tips.
 * @property sectionHeadingKey - Label above category blocks.
 * @property footerNoteKey - Footnote under categories.
 * @property panelExpandLabelKey - Optional i18n key for information panel collapsed hint.
 * @property panelCollapseLabelKey - Optional i18n key for information panel expanded hint.
 */
export type CategoryGroupedRelationFieldProps = {
    entity: string;
    model: ModelConstructor<unknown>;
    categories: CategoryGroupedRelationCategory[];
    columnNames: string[];
    pickerTitleKey: string;
    addButtonKey: string;
    countLabelKey: string;
    introKey?: string;
    tipsKey?: string;
    useSimplePicker?: boolean;
    searchPlaceholderKey?: string;
    saveButtonKey?: string;
    addInCategoryKey?: string;
    emptyCategoryKey?: string;
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
};
