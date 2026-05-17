import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
import type { RemoteFetchConfig } from '@/modules/types/support/inputs/RemoteFetchConfig.ts';
import type { StaticOptions } from '@/modules/types/support/inputs/StaticOptions.ts';

/**
 * Options for a multi-select control (tags, checklist, etc.).
 *
 * Inherits all properties from {@link BaseInputFieldOptions}.
 *
 * @property position - Placement of the dropdown (interpreted by the multi-select UI).
 * @property align - Alignment of the dropdown panel (interpreted by the multi-select UI).
 * @property options - Inline choices when not loading from the API.
 * @property remoteFetch - Configuration to load options via a model-backed query.
 */
export type MultiSelectFieldOptions = BaseInputFieldOptions & {
    position: string;
    align: string;
    options?: StaticOptions;
    remoteFetch?: RemoteFetchConfig;
};
