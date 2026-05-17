import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';

/**
 * Payload returned when picking or creating a related entity from a modal.
 *
 * Used by relation fields to:
 * - know which model type was chosen
 * - merge the selected row into the parent form state
 *
 * @property model - Constructor for the entity’s API model.
 * @property record - Plain object snapshot of the chosen record.
 */
export type EntityModalDataResult = {
    model: ModelConstructor<unknown>;
    record: Record<string, unknown>;
};
