import type { MetaLink } from '@/modules/types/support/responses/MetaLink.ts';

export interface Meta {
    current_page: number;
    per_page: number;
    total: number;
    from?: number;
    last_page?: number;
    links?: MetaLink[];
    path?: string;
    to?: number;
}
