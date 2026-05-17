import type { Primitive } from '@/modules/types/support/inputs/Primitive.ts';

/**
 * Static choices for select and multi-select fields before remote loading runs.
 *
 * Supported shapes:
 * - Array of `{ value, label }` for explicit display text
 * - Array of {@link Primitive} when labels are derived elsewhere
 * - Record of string key to primitive value for compact maps
 */
export type StaticOptions = Array<{ value: unknown; label: string }> | Array<Primitive> | Record<string, Primitive>;
