/**
 * Category bucket used to group linked rows in a category-grouped relation manager.
 *
 * Values align with the child model's `category` field (e.g. skill or certificate category enums).
 *
 * @property value - Stored category value on the child record.
 * @property labelKey - i18n key for the accordion header label.
 */
export type CategoryGroupedRelationCategory = {
    value: string;
    labelKey: string;
};
