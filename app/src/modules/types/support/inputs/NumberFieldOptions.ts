import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

/**
 * Options for numeric inputs with optional HTML5 constraints.
 *
 * Inherits all properties from {@link BaseInputFieldOptions}.
 *
 * @property min - When set, forwarded as HTML `min`; when omitted, no minimum constraint.
 * @property max - When set, forwarded as HTML `max`; when omitted, no maximum constraint.
 * @property step - HTML `step`; use `any` for decimals (e.g. prices) or an integer for whole numbers only.
 */
export type NumberFieldOptions = BaseInputFieldOptions & {
    min?: number;
    max?: number;
    step?: number | 'any';
};
