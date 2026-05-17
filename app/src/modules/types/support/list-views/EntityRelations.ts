/**
 * Cardinality flavor for {@link import('@/modules/interfaces/support/inputs/FieldRelation.ts').FieldRelation FieldRelation}.
 *
 * Mirrors Laravel-style relations for hydration and payload shaping:
 * - `belongsTo` — single parent relation; submit often uses one foreign key.
 * - `hasMany` — collection owned by the parent without pivot payload semantics.
 * - `manyToMany` — collection that may include pivot attributes when `pivot` is true on the field relation.
 */
export type EntityRelations = 'belongsTo' | 'hasMany' | 'manyToMany';
