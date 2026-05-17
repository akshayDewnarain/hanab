import type Model from '@/modules/models/support/Model.ts';
import type { OptionMap } from '@/modules/types/support/inputs/OptionMap.ts';

/**
 * Configuration for loading select options from the API via a {@link Model} subclass.
 *
 * Used by select fields to:
 * - run index/list requests with optional query params
 * - map raw rows to {@link OptionMap} for the dropdown
 * - filter rows client-side and control translate/i18n behavior
 *
 * @property instance - Model class constructor (subclasses typically narrow constructor arity).
 * @property params - Query parameters forwarded to the model list request.
 * @property map - Either property names on each row or a function Row → {@link OptionMap}.
 * @property filter - Optional predicate to drop rows before they become options.
 * @property immediate - When false/undefined, loading may be deferred until open/focus (UI-dependent).
 * @property translate - When true, pass labels through i18n; when false/undefined, show raw API strings.
 */
export type RemoteFetchConfig<T = unknown> = {
    instance: new (...args: unknown[]) => Model<T>;
    params?: Record<string, unknown>;
    map?: { label: string; value: string } | ((row: unknown) => OptionMap);
    filter?: (row: unknown) => boolean;
    immediate?: boolean;
    translate?: boolean;
};
