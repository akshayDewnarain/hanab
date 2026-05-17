import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

/**
 * Options for a split international phone input (country + national number).
 *
 * Inherits all properties from {@link BaseInputFieldOptions}.
 *
 * @property preferredCountries - ISO country codes listed first in the country selector.
 */
export type PhoneSplitFieldOptions = BaseInputFieldOptions & {
    preferredCountries?: string[];
};
