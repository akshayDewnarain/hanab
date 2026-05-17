/**
 * Synchronous check for a single control value.
 *
 * Used by {@link import('@/composables/forms/useFormValidator.ts').useFormValidator useFormValidator}, `utils/validators.ts` helpers, and the object branch of {@link import('@/modules/types/support/forms/ValidatorRule.ts').ValidatorRule ValidatorRule} via `custom`.
 *
 * Receives the current field value (any JSON-serializable shape) and returns a user-visible error message when invalid, or `null` when the value passes.
 */
export type ValidatorFunction = (value: unknown) => string | null;
