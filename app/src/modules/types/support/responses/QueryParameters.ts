import type { Query } from '@/modules/types/support/responses/Query.ts';

export interface QueryParameters extends Query {
    [key: string]: unknown;
}
