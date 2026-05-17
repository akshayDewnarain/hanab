import type { FieldRelation } from '@/modules/types/support/inputs/FieldRelation.ts';
import type { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';

/**
 * Shared metadata for declarative form and table inputs, usually driven by model config.
 *
 * Used by field definitions and builders to:
 * - identify the field and label shown in the UI
 * - apply validation, layout width, and create/edit visibility
 * - link the field to API relation paths when values are nested on the row
 *
 * @property name - Stable field key; default submitted payload key unless `relation.formDataKey` overrides.
 * @property label - Display label (often passed through i18n).
 * @property required - When true, a value must be present before submit.
 * @property validators - Client-side validation rules (shape defined by your validator layer).
 * @property placeholder - Input placeholder text.
 * @property autocomplete - HTML autocomplete hint.
 * @property disabled - When true, the control is non-interactive.
 * @property readonly - When true, the value is visible but not editable.
 * @property defaultValue - Initial value when no record value exists.
 * @property width - Layout width token for the field grid.
 * @property hideInCreate - Hide this field on create forms.
 * @property hideInEdit - Hide this field on edit forms.
 * @property relation - Optional relation wiring for nested row data and payload keys.
 * @property hintKey - Optional i18n key shown in an info tooltip beside the label.
 */
export type BaseInputFieldOptions = {
    name: string;
    label: string;
    hintKey?: string;
    required?: boolean;
    validators?: Record<string, unknown>;
    placeholder?: string;
    autocomplete?: string;
    disabled?: boolean;
    readonly?: boolean;
    defaultValue?: unknown;
    width?: FieldWidth;
    hideInCreate?: boolean;
    hideInEdit?: boolean;
    relation?: FieldRelation;
};
