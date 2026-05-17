import type { Links } from '@/modules/types/support/responses/Link.ts';
import type { Meta } from '@/modules/types/support/responses/Meta.ts';

export interface IndexResponse<T> {
    data: T[];
    links: Links;
    meta: Meta;
}
