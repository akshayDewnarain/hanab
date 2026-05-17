import type { ValidatorFunction } from '@/modules/types/support/forms/ValidatorFunction.ts';

/**
 * Either a single validator function or a compact declarative rule object.
 *
 * Used by {@link import('@/composables/forms/useFormValidator.ts').useFormValidator useFormValidator} and field options that attach `validators` alongside {@link import('@/modules/enums/support/forms/ValidatorType.ts').ValidatorType ValidatorType} keys.
 *
 * When the rule is a {@link import('@/modules/types/support/forms/ValidatorFunction.ts').ValidatorFunction ValidatorFunction}, it runs as one step in the composed pipeline.
 *
 * When the rule is the object variant, each optional flag or limit maps to built-in checks (see {@link import('@/modules/enums/support/forms/ValidatorType.ts').ValidatorType ValidatorType} for recognized keys when normalizing from plain records).
 *
 * @property required - When true, empty values must fail validation before other checks.
 * @property minLength - Minimum length for string-like values.
 * @property maxLength - Maximum length for string-like values.
 * @property pattern - Regular expression the string representation must satisfy.
 * @property custom - Additional {@link import('@/modules/types/support/forms/ValidatorFunction.ts').ValidatorFunction ValidatorFunction} for bespoke rules.
 */
export type ValidatorRule =
    | ValidatorFunction
    | {
          required?: boolean;
          minLength?: number;
          maxLength?: number;
          pattern?: RegExp;
          custom?: ValidatorFunction;
      };
