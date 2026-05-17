import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

/**
 * Options for a boolean toggle (switch) input.
 *
 * Used by form builders to render binary state with distinct visual tones for neutral vs status UX.
 *
 * Inherits all properties from {@link BaseInputFieldOptions}.
 *
 * @property toggleTone - `neutral` uses theme gray/brand; `status` uses green when on and red when off (e.g. active/inactive).
 */
export type ToggleFieldOptions = BaseInputFieldOptions & {
    toggleTone?: 'neutral' | 'status';
};
