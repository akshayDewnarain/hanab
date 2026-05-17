/**
 * Minimal paginated list envelope: only a `data` array (no `links` / `meta`).
 *
 * Used when an index endpoint returns rows in a Laravel-like `data` wrapper without full pagination metadata.
 */
export type PlainIndexResponse<T> = {
    data: T[];
};
