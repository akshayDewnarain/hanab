import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
import type { RemoteFetchConfig } from '@/modules/types/support/inputs/RemoteFetchConfig.ts';
import type { StaticOptions } from '@/modules/types/support/inputs/StaticOptions.ts';

/**
 * Options for a single- or multi-select backed by static options or remote fetch.
 *
 * Inherits all properties from {@link BaseInputFieldOptions}.
 *
 * @property position - Placement of the dropdown (interpreted by the select UI).
 * @property align - Alignment of the dropdown panel (interpreted by the select UI).
 * @property options - Inline choices when not loading from the API.
 * @property remoteFetch - Configuration to load options via a model-backed query.
 * @property multiple - When true, more than one option can be selected.
 * @property hideClearButton - When true, hide the clear control and ignore clear actions.
 * @property translateOptionLabels - When true, pass each option `label` through i18n (for static `options` using message keys).
 */
export type SelectFieldOptions = BaseInputFieldOptions & {
    position: string;
    align: string;
    options?: StaticOptions;
    remoteFetch?: RemoteFetchConfig;
    multiple?: boolean;
    hideClearButton?: boolean;
    translateOptionLabels?: boolean;
};
