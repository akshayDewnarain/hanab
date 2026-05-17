import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';

/**
 * Props for the entity create/edit form body rendered inside a modal.
 *
 * Passed as {@link import('@/modules/types/support/modals/ModalProps.ts').ModalProps ModalProps}.props when the body component is the entity modal.
 *
 * @property model - API model class used to build fields and submit shape.
 * @property entity - Stable entity name for logging and modal identity.
 * @property record - Optional existing values when editing or prefilling.
 */
export interface EntityModalProps {
    model: ModelConstructor<unknown>;
    entity: string;
    record?: Record<string, unknown>;
}
