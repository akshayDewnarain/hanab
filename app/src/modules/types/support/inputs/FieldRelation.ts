import type { EntityRelations } from '@/modules/types/support/list-views/EntityRelations.ts';

/**
 * Describes how a table/form field is connected to a related entity.
 *
 * Used by table columns and form builders to:
 * - resolve relation values from a row object
 * - map selected relation IDs to form data
 * - handle direct relations and pivot-based many-to-many relations
 *
 * @property type - Type of entity relation, e.g. belongsTo, hasMany, manyToMany.
 * @property idKey - Optional key used as the related entity identifier. Defaults to `id`.
 * @property formDataKey - Optional override for the submitted form data key.
 * @property path - Dot path to the relation inside the row object, e.g. `employee_role.name`.
 * @property pivot - When true, the value is treated as pivot data instead of plain IDs.
 */
export type FieldRelation = {
    type: EntityRelations;
    idKey?: string;
    formDataKey?: string;
    path: string;
    pivot?: boolean;
};
