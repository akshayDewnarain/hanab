import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';
import type { ListViewActionType } from '@/modules/types/support/list-views/ListViewActionType.ts';

/**
 * Configuration passed into {@link import('@/composables/useListViewHelper.ts').useListViewHelper useListViewHelper} and {@link import('@/components/list-views/ListView.vue').default ListView}.
 *
 * @property entity - Stable entity key (e.g. for routes, storage keys, and labels).
 * @property model - API model constructor used for columns, `index`, and bulk destroy.
 * @property detailRouteName - When set, row navigation uses this route name instead of `admin-{entity}-detail`.
 * @property includes - Optional relationship names forwarded to list API `include`.
 * @property enableDefaultActions - When false, default header actions may be hidden by the consumer.
 * @property displayHeader - Toggle list header chrome (search, actions).
 * @property displayTableConfiguration - When true, show table column configuration entry point.
 * @property showDefaultBulkActions - When true, show built-in bulk actions when `selectable` is enabled.
 * @property selectable - When true, rows can be selected for bulk operations.
 * @property disableRowNavigation - When true, clicking a row does not navigate to detail.
 * @property useURLQueries - When true, sync pagination/search/filters with the route query string.
 * @property alwaysShowPagination - When true, show pagination even when the list is empty.
 * @property staticFilters - Non-editable filters always merged into API requests (e.g. parent id for embedded lists).
 * @property customActions - Extra header buttons (route, modal, or callback); see {@link import('@/modules/types/support/list-views/ListViewActionType.ts').ListViewActionType ListViewActionType}.
 */
export interface UseListViewHelperProps {
    entity: string;
    model: ModelConstructor<unknown>;
    detailRouteName?: string;
    includes?: string[];
    enableDefaultActions?: boolean;
    displayHeader?: boolean;
    displayTableConfiguration?: boolean;
    showDefaultBulkActions?: boolean;
    selectable?: boolean;
    disableRowNavigation?: boolean;
    useURLQueries?: boolean;
    alwaysShowPagination?: boolean;
    staticFilters?: Record<string, unknown>;
    customActions?: ListViewActionType[];
}
