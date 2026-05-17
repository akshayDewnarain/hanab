import type { IndexResponse } from '@/modules/types/support/responses/IndexResponse.ts';
import type { PlainIndexResponse } from '@/modules/types/support/responses/PlainIndexResponse.ts';

/**
 * Any index-style payload the client may treat as “a list of `T`”.
 *
 * Covers {@link import('@/modules/types/support/responses/IndexResponse.ts').IndexResponse} (full Laravel pagination),
 * {@link import('@/modules/types/support/responses/PlainIndexResponse.ts').PlainIndexResponse}, or a raw `T[]`.
 */
export type IndexResponseLike<T> = IndexResponse<T> | PlainIndexResponse<T> | T[];
