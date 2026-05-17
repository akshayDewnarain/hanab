import type { EntityRelations } from '@/modules/types/support/list-views/EntityRelations.ts';

export interface TableRelation {
    type: EntityRelations;
    path: string;
    accessor?: (relation: unknown, row: unknown) => unknown;
    displayKey?: string;
    idKey?: string;
    sortKey?: string;
    filterKey?: string;
    multipleSeparator?: string;
    emptyLabel?: string;
}
