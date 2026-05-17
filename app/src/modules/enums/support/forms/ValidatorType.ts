/**
 * Keys recognized when a {@link import('@/modules/types/support/forms/ValidatorRule.ts').ValidatorRule ValidatorRule} is a plain object (not a function).
 *
 * Used by {@link import('@/composables/forms/useFormValidator.ts').useFormValidator useFormValidator} when normalizing field `validators` into runnable checks.
 *
 * Enum members:
 * - REQUIRED — maps to `required: true` on the rule object; empty values fail (with edit-form “untouched with initial value” relaxation in the composable).
 * - MIN_LENGTH — maps to `minLength: number` for string-like values.
 * - MAX_LENGTH — maps to `maxLength: number` for string-like values.
 * - MIN — maps to `min: number` for minimum numeric value.
 * - MAX — maps to `max: number` for maximum numeric value.
 * - PATTERN — maps to `pattern: RegExp` for string matching.
 * - CUSTOM — maps to `custom` with a {@link import('@/modules/types/support/forms/ValidatorFunction.ts').ValidatorFunction ValidatorFunction}.
 */
export enum ValidatorType {
    REQUIRED = 'required',
    MIN_LENGTH = 'minLength',
    MAX_LENGTH = 'maxLength',
    MIN = 'min',
    MAX = 'max',
    PATTERN = 'pattern',
    CUSTOM = 'custom',
}
