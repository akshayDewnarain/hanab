import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';

/**
 * Props for the entity picker list body inside a modal.
 *
 * Passed as {@link import('@/modules/types/support/modals/ModalProps.ts').ModalProps ModalProps}.props when the body component is the entity picker.
 *
 * @property model - API model class used for `index` queries and labels.
 * @property includes - Optional relationship names forwarded to the list API.
 * @property ids - Pre-selected row identifiers.
 * @property lockCurrent - When true, selection rules may prevent changing certain rows.
 * @property hideCurrent - When true, the current context row may be hidden from the list.
 */
export interface EntityPickerProps {
    model: ModelConstructor<unknown>;
    includes?: string[];
    ids?: number[];
    lockCurrent?: boolean;
    hideCurrent?: boolean;
}
