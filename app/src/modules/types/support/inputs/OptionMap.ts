import type { Primitive } from '@/modules/types/support/inputs/Primitive.ts';

/**
 * Normalized label/value pair produced when mapping API rows to select options.
 *
 * @property label - Text shown in the option list.
 * @property value - Scalar stored on the model or submitted with the form.
 */
export type OptionMap = { label: string; value: Primitive };
