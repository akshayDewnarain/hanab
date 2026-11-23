<template>
    <EmployeeCardsWorkspace
        v-if="cardWorkspace"
        :card-config="cardConfig"
        :chips="activeFilterChips"
        :columns-by-name="columnsByName"
        :convert-to-string="convertToString"
        :current-page="meta.current_page"
        :disable-navigation="disableRowNavigation"
        :discovery="discoverySections"
        :entity="entity"
        :fields="resolvedFields"
        :filter-count="filterCount"
        :filters="filters"
        :get-column-value="getColumnValue"
        :get-item-id="getItemId"
        :is-loading="isLoading"
        :items="items"
        :model="model"
        :page-size-options="pageSizeOptions"
        :pagination-labels="paginationLabels"
        :per-page="meta.per_page"
        :presentation="presentation"
        :query="searchQuery"
        :related-terms="relatedTerms"
        :sort-value="sortQuery || (searchQuery ? 'relevance' : 'last_name')"
        :suggestions="suggestions"
        :total="meta.total"
        @clear="clearDirectorySearch"
        @filters="applyFilters"
        @mounted-filters="onQuickFiltersMounted"
        @page="handlePageChange"
        @per-page="handlePerPageChange"
        @remove-filter="removeFilter"
        @reset-filters="resetFilters"
        @search="runDirectorySearch"
        @sort="onCardsSort"
        @suggest="useSuggestion"
        @update:presentation="emit('update:presentation', $event)"
        @update:query="onDirectoryQuery"
        @view="onCardClick"
    >
        <template #actions>
            <EmployeeListActions
                :can-create="canCreate"
                :custom-actions="customActions"
                :display-table-configuration="displayTableConfiguration"
                :filters="filters"
                :refresh="fetchData"
                :search="searchQuery"
                :transfer-key="modelInstance.transferKey()"
                @create="openModal"
                @custom="handleCustomAction"
                @refresh="fetchData"
                @settings="openTableConfiguration"
            />
        </template>
    </EmployeeCardsWorkspace>
    <div
        v-else
        class="flex w-full flex-col"
        :class="[
            props.isMobileLayout ? 'h-full min-h-0' : 'h-full',
            props.directorySwitcher ? 'gap-3 bg-list-canvas p-3 sm:p-4' : 'p-0 sm:h-full sm:p-4',
        ]"
    >
        <EmployeeDirectoryHeader
            v-if="props.directorySwitcher"
            :presentation="presentation"
            :query="searchQuery"
            :suggestions="suggestions"
            @clear="clearDirectorySearch"
            @search="runDirectorySearch"
            @suggest="useSuggestion"
            @update:presentation="emit('update:presentation', $event)"
            @update:query="onDirectoryQuery"
        >
            <template #actions>
                <EmployeeListActions
                    :can-create="canCreate"
                    :custom-actions="customActions"
                    :display-table-configuration="displayTableConfiguration"
                    :filters="filters"
                    :refresh="fetchData"
                    :search="searchQuery"
                    :transfer-key="modelInstance.transferKey()"
                    @create="openModal"
                    @custom="handleCustomAction"
                    @refresh="fetchData"
                    @settings="openTableConfiguration"
                />
            </template>
        </EmployeeDirectoryHeader>

        <!-- Filters and results -->
        <div
            class="flex min-h-0 flex-1 overflow-hidden"
            :class="[
                props.directorySwitcher
                    ? 'gap-3 lg:flex-row-reverse'
                    : props.isMobileLayout
                      ? ''
                      : 'sm:rounded-xl sm:border sm:border-slate-200/90 sm:bg-white sm:shadow-sm',
            ]"
        >
            <!-- Cards -->
            <div
                class="flex min-w-0 flex-1 flex-col"
                :class="props.directorySwitcher ? 'overflow-hidden rounded-xl border border-list-border bg-list-surface shadow-sm' : ''"
            >
                <div
                    v-if="props.directorySwitcher"
                    class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-list-border px-3 py-2.5"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-list-text">
                            {{ t('DIRECTORY_RESULT_COUNT', { count: meta.total }) }}
                        </p>
                        <p v-if="searchQuery" class="truncate text-xs text-list-text-muted">
                            {{ t('DIRECTORY_RESULTS_HEADING', { query: searchQuery }) }}
                        </p>
                        <p v-if="relatedTerms.length" class="truncate text-xs text-list-text-muted">
                            {{ t('DIRECTORY_ALSO_SEARCHED', { terms: relatedTerms.join(' · ') }) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-lg border border-list-border px-2 text-xs font-semibold text-list-text lg:hidden"
                            @click="emit('update:mobileFiltersOpen', true)"
                        >
                            <Icon class="size-4" icon="material-symbols:filter-alt-outline-rounded" />
                            {{ t('DIRECTORY_FILTERS') }}
                            <span v-if="filterCount > 0" class="tabular-nums text-emphasis">{{ filterCount }}</span>
                        </button>
                        <label class="inline-flex items-center gap-1.5 text-sm text-list-text-muted">
                            <Icon class="size-4" icon="material-symbols:sort-rounded" />
                            <span class="sr-only">{{ t('DIRECTORY_SORT') }}</span>
                            <select
                                class="cursor-pointer bg-transparent text-sm font-medium text-list-text outline-none"
                                :value="sortQuery || (searchQuery ? 'relevance' : 'last_name')"
                                @change="onDirectorySort"
                            >
                                <option value="relevance">{{ t('DIRECTORY_SORT_RELEVANCE') }}</option>
                                <option value="last_name">{{ t('DIRECTORY_SORT_NAME') }}</option>
                            </select>
                        </label>
                    </div>
                </div>
                <div v-if="props.directorySwitcher && activeFilterChips.length" class="flex shrink-0 flex-wrap gap-1.5 px-3 pt-2">
                    <button
                        v-for="chip in activeFilterChips"
                        :key="chip.key"
                        type="button"
                        class="inline-flex cursor-pointer items-center gap-1 rounded-full border border-list-border bg-list-surface px-2 py-0.5 text-xs font-medium text-list-text transition-colors duration-200 ease-out hover:border-primary motion-reduce:transition-none"
                        @click="removeFilter(chip.key)"
                    >
                        {{ chip.label }}
                        <Icon class="size-3.5" icon="material-symbols:close-rounded" />
                        <span class="sr-only">{{ t('FILTER_RESET') }}</span>
                    </button>
                </div>
                <div v-if="!props.directorySwitcher" class="flex justify-end p-2 lg:hidden">
                    <ImportExportActions
                        :filters="filters"
                        :refresh="fetchData"
                        :search="searchQuery"
                        :transfer-key="modelInstance.transferKey()"
                    />
                </div>
                <div class="relative min-h-0 flex-1 overflow-hidden">
                    <div class="h-full overflow-auto" :class="props.directorySwitcher ? 'bg-list-surface' : 'bg-transparent sm:bg-slate-50/80'">
                        <Transition mode="out-in" name="card-fade">
                            <div
                                v-if="isLoading && presentation === 'directory'"
                                key="loading-directory"
                                class="flex flex-col gap-2 p-3"
                            >
                                <Skeleton v-for="index in 6" :key="`directory-skeleton-${index}`" class="h-16 w-full rounded-xl" />
                            </div>
                            <div
                                v-else-if="isLoading"
                                key="loading"
                                class="grid grid-cols-1 gap-3 p-2 sm:grid-cols-2 sm:gap-5 sm:p-5 lg:grid-cols-3 xl:grid-cols-4"
                            >
                                <div
                                    v-for="index in 8"
                                    :key="`skeleton-${index}`"
                                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                                >
                                    <Skeleton class="h-12 w-full rounded-none" />
                                    <div class="flex flex-col items-center gap-3 px-4 pb-6 pt-5">
                                        <Skeleton class="size-32 rounded-full" />
                                        <Skeleton class="h-9 w-full rounded-lg" />
                                        <Skeleton class="h-3 w-full" />
                                        <Skeleton class="h-3 w-5/6" />
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else-if="items.length"
                                key="content"
                                :class="
                                    presentation === 'directory'
                                        ? 'flex flex-col gap-3 p-3'
                                        : 'grid grid-cols-1 gap-3 p-2 sm:grid-cols-2 sm:gap-5 sm:p-5 lg:grid-cols-3 xl:grid-cols-4'
                                "
                            >
                                <template v-if="presentation === 'directory'">
                                    <EmployeeDirectoryCard
                                        v-for="item in items"
                                        :key="getItemId(item)"
                                        :employee="itemAsRecord(item)"
                                        :query="searchQuery"
                                        @view="navigateToDetail(getItemId(item))"
                                    />
                                </template>
                                <template v-else>
                                    <CardListItem
                                        v-for="item in items"
                                        :key="getItemId(item)"
                                        :card-config="cardConfig"
                                        :columns-by-name="columnsByName"
                                        :convert-to-string="convertToString"
                                        :disable-navigation="disableRowNavigation"
                                        :fields="resolvedFields"
                                        :get-column-value="getColumnValue"
                                        :item="itemAsRecord(item)"
                                        @click="onCardClick(getItemId(item))"
                                        @view="onCardClick(getItemId(item))"
                                    />
                                </template>
                            </div>

                            <div
                                v-else
                                key="empty"
                                class="flex flex-col items-center justify-center px-6 py-20 text-center"
                            >
                                <div class="mb-4 flex size-16 items-center justify-center rounded-full bg-slate-100">
                                    <Icon
                                        class="size-8 text-slate-300"
                                        icon="material-symbols:groups-outline-rounded"
                                    />
                                </div>
                                <p class="text-sm font-medium text-slate-600">
                                    {{ t(cardConfig.emptyStateKey ?? 'NO_RESULTS') }}
                                </p>
                            </div>
                        </Transition>
                    </div>
                </div>
                <div v-if="props.directorySwitcher" class="shrink-0">
                    <DirectoryPagination
                        :current="meta.current_page"
                        :labels="paginationLabels"
                        :options="pageSizeOptions"
                        :per-page="meta.per_page"
                        :total="meta.total"
                        @update:current="handlePageChange"
                        @update:per-page="handlePerPageChange"
                    />
                </div>
            </div>

            <!-- Filter column (inside list view) -->
            <div
                class="hidden h-full min-h-0 shrink-0 flex-col lg:flex"
                :class="props.directorySwitcher ? 'w-64 gap-0 bg-transparent p-0 xl:w-72' : 'w-72 gap-3 bg-slate-50/90 p-3 lg:w-80'"
            >
                <!-- Actions card -->
                <section
                    v-if="!props.directorySwitcher"
                    class="w-full shrink-0 space-y-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm"
                >
                    <button
                        v-if="enableDefaultActions"
                        type="button"
                        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-[var(--color-primary)] bg-[var(--color-primary)] px-3 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[var(--color-highlight-dark)]"
                        @click="openModal"
                    >
                        <Icon class="size-[18px]" icon="material-symbols:add-2-rounded" />
                        {{ t('MODAL_CREATE') }}
                    </button>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="displayTableConfiguration"
                            type="button"
                            class="inline-flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
                            :title="t('TABLE_SETTINGS')"
                            @click="openTableConfiguration"
                        >
                            <Icon class="size-[18px]" icon="material-symbols:tools-wrench-outline-rounded" />
                            <span class="truncate">{{ t('TABLE_SETTINGS') }}</span>
                        </button>
                        <button
                            type="button"
                            class="inline-flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
                            :title="t('TABLE_REFRESH')"
                            @click="fetchData"
                        >
                            <Icon class="size-[18px]" icon="material-symbols:refresh-rounded" />
                            <span class="truncate">{{ t('TABLE_REFRESH') }}</span>
                        </button>
                    </div>

                    <div
                        v-for="(customAction, index) in props.customActions || []"
                        :key="index"
                        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-100"
                        @click="handleCustomAction(customAction)"
                    >
                        <Icon :icon="customAction.icon" class="size-[18px]" />
                        {{ t(customAction.label) }}
                    </div>

                    <ImportExportActions
                        class="hidden lg:block"
                        :filters="filters"
                        :refresh="fetchData"
                        :search="searchQuery"
                        :transfer-key="modelInstance.transferKey()"
                    />

                    <slot name="header-actions" />
                </section>

                <!-- Filters card -->
                <section
                    class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-xl border shadow-sm"
                    :class="props.directorySwitcher ? 'border-list-border bg-list-surface' : 'border-slate-200 bg-white'"
                >
                    <div
                        v-if="props.directorySwitcher"
                        class="flex shrink-0 items-center justify-between gap-2 border-b border-list-border px-3 py-2.5"
                    >
                        <h2 class="inline-flex items-center gap-2 text-sm font-semibold text-list-text">
                            <Icon class="size-5 text-list-text-muted" icon="material-symbols:filter-alt-outline-rounded" />
                            {{ t('DIRECTORY_FILTERS') }}
                        </h2>
                        <button
                            type="button"
                            class="inline-flex size-8 cursor-pointer items-center justify-center rounded-lg text-list-text-muted hover:bg-list-row-hover hover:text-list-text disabled:cursor-not-allowed disabled:opacity-40"
                            :aria-label="t('FILTER_RESET')"
                            :title="t('FILTER_RESET')"
                            :disabled="filterCount === 0"
                            @click="resetFilters"
                        >
                            <Icon class="size-[18px]" icon="material-symbols:refresh-rounded" />
                        </button>
                    </div>
                    <div v-else-if="presentation !== 'directory'" class="shrink-0 space-y-3 border-b border-slate-100 px-4 pb-4 pt-4">
                        <label class="sr-only" for="card-list-sidebar-search">{{
                            t('GENERAL_SEARCH_PLACEHOLDER')
                        }}</label>
                        <div class="relative">
                            <input
                                id="card-list-sidebar-search"
                                v-model="searchQuery"
                                :placeholder="t('GENERAL_SEARCH_PLACEHOLDER')"
                                class="input-base w-full rounded-lg border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm shadow-sm"
                                type="text"
                                @input="handleInputChange"
                            />
                            <Icon
                                class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
                                icon="material-symbols:search-rounded"
                            />
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-medium tabular-nums text-slate-600">
                                {{ t('CARD_LIST_RESULTS_COUNT', { count: meta.total }) }}
                            </p>
                            <button
                                v-if="filterCount > 0"
                                type="button"
                                class="inline-flex shrink-0 cursor-pointer items-center gap-1 text-xs font-semibold text-[var(--color-primary)] hover:underline"
                                @click="resetFilters"
                            >
                                <Icon class="size-3.5" icon="material-symbols:filter-alt-off-outline-rounded" />
                                {{ t('FILTER_RESET') }}
                            </button>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-x-hidden overflow-y-auto px-3 py-3">
                        <QuickFilters
                            :key="quickFilterKey"
                            layout="sidebar"
                            :discovery="props.directorySwitcher ? discoverySections : undefined"
                            :entity="props.entity"
                            :filters="filters"
                            :model="props.model"
                            @update:filters="applyFilters"
                            @update:mounted="onQuickFiltersMounted"
                        />
                    </div>
                </section>

                <!-- Pagination card (pinned to bottom of sidebar) -->
                <section
                    v-if="!props.directorySwitcher"
                    class="mt-auto w-full shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <ListViewPagination
                        compact
                        :current="meta.current_page"
                        :per-page="meta.per_page"
                        :total="meta.total"
                        @update:currentPage="handlePageChange"
                        @update:perPage="handlePerPageChange"
                    />
                </section>
            </div>
        </div>
        <MobileFiltersOverlay
            :open="props.mobileFiltersOpen"
            @close="emit('update:mobileFiltersOpen', false)"
        >
            <template #title>{{ props.directorySwitcher ? t('DIRECTORY_FILTERS') : t('FILTER_QUICK_FILTERS') }}</template>

            <div class="space-y-4 p-4">
                <section v-if="!props.directorySwitcher" class="space-y-3 rounded border border-slate-200 bg-white p-3 shadow-sm">
                    <label class="sr-only" for="mobile-card-list-filter-search">{{ t('GENERAL_SEARCH_PLACEHOLDER') }}</label>
                    <div class="relative">
                        <input
                            id="mobile-card-list-filter-search"
                            v-model="searchQuery"
                            :placeholder="t('GENERAL_SEARCH_PLACEHOLDER')"
                            class="input-base w-full rounded-lg border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm shadow-sm"
                            type="text"
                            @input="handleInputChange"
                        />
                        <Icon
                            class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
                            icon="material-symbols:search-rounded"
                        />
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-medium tabular-nums text-slate-600">
                            {{ t('CARD_LIST_RESULTS_COUNT', { count: meta.total }) }}
                        </p>
                        <button
                            v-if="filterCount > 0"
                            type="button"
                            class="inline-flex shrink-0 cursor-pointer items-center gap-1 text-xs font-semibold text-[var(--color-primary)] hover:underline"
                            @click="resetFilters"
                        >
                            <Icon class="size-3.5" icon="material-symbols:filter-alt-off-outline-rounded" />
                            {{ t('FILTER_RESET') }}
                        </button>
                    </div>
                </section>

                <section class="min-h-[30vh] rounded border border-list-border bg-list-surface p-3 shadow-sm">
                    <div v-if="props.directorySwitcher" class="mb-2 flex justify-end">
                        <button
                            type="button"
                            class="inline-flex cursor-pointer items-center gap-1 text-xs font-semibold text-emphasis disabled:opacity-40"
                            :disabled="filterCount === 0"
                            @click="resetFilters"
                        >
                            <Icon class="size-3.5" icon="material-symbols:refresh-rounded" />
                            {{ t('FILTER_RESET') }}
                        </button>
                    </div>
                    <QuickFilters
                        :key="quickFilterKey"
                        layout="sidebar"
                        :discovery="props.directorySwitcher ? discoverySections : undefined"
                        :entity="props.entity"
                        :filters="filters"
                        :model="props.model"
                        @update:filters="applyFilters"
                        @update:mounted="onQuickFiltersMounted"
                    />
                </section>
            </div>

            <template #footer>
                <div class="space-y-3">
                    <div v-if="!props.directorySwitcher" class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                        <ListViewPagination
                            compact
                            :current="meta.current_page"
                            :per-page="meta.per_page"
                            :total="meta.total"
                            @update:currentPage="handlePageChange"
                            @update:perPage="handlePerPageChange"
                        />
                    </div>
                    <button
                        type="button"
                        class="w-full rounded-lg bg-[var(--color-primary)] px-4 py-2.5 text-sm font-semibold text-white"
                        @click="emit('update:mobileFiltersOpen', false)"
                    >
                        {{ t('GENERAL_DONE') }}
                    </button>
                </div>
            </template>
        </MobileFiltersOverlay>
    </div>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { Icon } from '@iconify/vue';
    import { ListViewPagination as DirectoryPagination } from '@freelance/vue-ui';
    import { useListView } from '@/composables/useListView.ts';
    import { useModalService } from '@/composables/useModalService';
    import { useAuthStore } from '@/stores/auth';
    import { getColumnFilterKey } from '@/modules/utils/tableColumnFilters';
    import http from '@/utils/http';
    import type { ListViewActionType } from '@/modules/types/support/list-views/ListViewActionType.ts';
    import type { UseListViewHelperProps } from '@/modules/types/support/list-views/UseListViewHelperProps.ts';
    import type { CardListConfig } from '@/modules/types/support/list-views/CardListConfig.ts';
    import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';
    import { resolveCardFields } from '@/modules/utils/cardListView.ts';
    import CardListItem from '@/components/list-views/CardListItem.vue';
    import EmployeeDirectoryCard from '@/components/employees/EmployeeDirectoryCard.vue';
    import EmployeeDirectoryHeader from '@/components/employees/EmployeeDirectoryHeader.vue';
    import EmployeeCardsWorkspace from '@/components/employees/EmployeeCardsWorkspace.vue';
    import EmployeeListActions from '@/components/employees/EmployeeListActions.vue';
    import ListViewPagination from '@/components/pagination/ListViewPagination.vue';
    import QuickFilters from '@/components/filters/list-views/QuickFilters.vue';
    import ImportExportActions from '@/components/import-export/ImportExportActions.vue';
    import Skeleton from '@/components/skeleton/Skeleton.vue';
import MobileFiltersOverlay from '@/components/mobile/MobileFiltersOverlay.vue';

    defineOptions({
        name: 'CardListView',
    });

    const props = withDefaults(
        defineProps<
            UseListViewHelperProps & {
                cardConfig: CardListConfig;
                isMobileLayout?: boolean;
                mobileFiltersOpen?: boolean;
                directorySwitcher?: boolean;
                presentation?: 'directory' | 'cards';
            }
        >(),
        {
            enableDefaultActions: true,
            displayHeader: false,
            displayTableConfiguration: true,
            showDefaultBulkActions: true,
            selectable: false,
            disableRowNavigation: false,
            useURLQueries: true,
            isMobileLayout: false,
            mobileFiltersOpen: false,
            directorySwitcher: false,
            presentation: 'cards',
        },
    );

    const emit = defineEmits<{
        (e: 'update:mobileFiltersOpen', value: boolean): void;
        (e: 'update:presentation', value: 'directory' | 'cards'): void;
    }>();

    const { t } = useI18n();
    const router = useRouter();
    const modalService = useModalService();

    const {
        isLoading,
        quickFilterKey,
        filters,
        searchQuery,
        items,
        meta,
        visibleColumns,
        filterCount,
        modelInstance,
        handleInputChange,
        fetchData,
        applyFilters,
        sortQuery,
        resetFilters,
        removeFilter,
        setListSort,
        openTableConfiguration,
        handlePageChange,
        handlePerPageChange,
        navigateToDetail,
        openModal,
        onQuickFiltersMounted,
        getItemId,
        getColumnValue,
        convertToString,
    } = useListView(props);

    const auth = useAuthStore();
    const canCreate = computed(() => props.enableDefaultActions && auth.hasPermission(`create ${props.entity}`));
    const cardWorkspace = computed(() => props.directorySwitcher && props.presentation === 'cards');
    const pageSizeOptions = [10, 25, 50, 100];
    const paginationLabels = computed(() => ({
        showing: t('PAGINATION_SHOWING'),
        to: t('PAGINATION_TO'),
        of: t('PAGINATION_OF'),
        results: t('PAGINATION_RESULTS'),
        perPage: t('PAGINATION_PER_PAGE'),
        previous: t('PAGINATION_PREV'),
        next: t('PAGINATION_NEXT'),
        pagination: t('PAGINATION_PAGE_LABEL'),
    }));

    const discoverySections = [
        { name: 'skills', icon: 'material-symbols:verified-outline-rounded', label: 'DIRECTORY_FILTER_EXPERTISE' },
        { name: 'certificates', icon: 'material-symbols:workspace-premium-outline-rounded', label: 'DIRECTORY_FILTER_CERTIFICATES' },
        { name: 'employee_location', icon: 'material-symbols:location-on-outline-rounded', label: 'DIRECTORY_FILTER_LOCATION' },
        { name: 'employee_role', icon: 'material-symbols:work-outline', label: 'DIRECTORY_FILTER_ROLE' },
        { name: 'is_active', icon: 'material-symbols:check-circle-outline-rounded', label: 'DIRECTORY_FILTER_STATUS' },
    ];

    const booleanFilters = new Set([
        'is_active',
        'missing_skill',
        'missing_certificate',
        'missing_location',
        'missing_email',
        'missing_phone',
    ]);

    const extraFilterLabels: Record<string, string> = {
        missing_skill: 'DASHBOARD_ATTENTION_SKILLS',
        missing_certificate: 'DASHBOARD_ATTENTION_CERTIFICATES',
        missing_location: 'DASHBOARD_ATTENTION_LOCATION',
        missing_email: 'DASHBOARD_ATTENTION_EMAIL',
        missing_phone: 'DASHBOARD_ATTENTION_PHONE',
        employee_location_id: 'DIRECTORY_FILTER_LOCATION',
        employee_role_id: 'DIRECTORY_FILTER_ROLE',
    };

    function relatedName(relation: 'employee_location' | 'employee_role', id: unknown): string {
        const wanted = String(id);
        for (const item of items.value) {
            const record = item as Record<string, unknown>;
            const related = record[relation];
            if (!related || typeof related !== 'object' || !('id' in related)) continue;
            if (String((related as { id?: unknown }).id) !== wanted) continue;
            const name = (related as { name?: unknown }).name;
            if (typeof name === 'string' && name !== '') return name;
        }
        return wanted;
    }

    function isEnabledFlag(value: unknown): boolean {
        return value === true || value === 1 || value === '1' || value === 'true';
    }

    const activeFilterChips = computed(() => {
        const columns = modelInstance.columns();
        const chips: { key: string; label: string }[] = [];
        for (const [key, value] of Object.entries(filters.value)) {
            if (value === undefined || value === null || value === '' || (Array.isArray(value) && value.length === 0)) {
                continue;
            }
            const column = columns.find((item) => getColumnFilterKey(item) === key);
            const section = discoverySections.find((item) => item.name === column?.name);
            const extra = extraFilterLabels[key];
            const label = extra ? t(extra) : section ? t(section.label) : column ? t(column.label) : key;
            if (Array.isArray(value)) {
                chips.push({ key, label: `${label} (${value.length})` });
            } else if (booleanFilters.has(key) || typeof value === 'boolean') {
                chips.push({ key, label: `${label}: ${isEnabledFlag(value) ? t('DIRECTORY_FILTER_TRUE') : t('DIRECTORY_FILTER_FALSE')}` });
            } else if (key === 'employee_location_id') {
                chips.push({ key, label: `${label}: ${relatedName('employee_location', value)}` });
            } else if (key === 'employee_role_id') {
                chips.push({ key, label: `${label}: ${relatedName('employee_role', value)}` });
            } else {
                chips.push({ key, label: `${label}: ${value}` });
            }
        }
        return chips;
    });

    const suggestions = ref<string[]>([]);

    const relatedTerms = computed(() => {
        const names = new Set<string>();
        for (const item of items.value) {
            const matches = (item as { expertise_matches?: { matched_on?: string; name?: string }[] }).expertise_matches;
            if (!Array.isArray(matches)) {
                continue;
            }
            for (const match of matches) {
                if (match?.matched_on === 'synonym' && match.name) {
                    names.add(match.name);
                }
            }
        }
        return [...names].slice(0, 4);
    });

    function onDirectoryQuery(value: string): void {
        searchQuery.value = value;
        handleInputChange();
    }

    function runDirectorySearch(): void {
        meta.value.current_page = 1;
        void fetchData();
    }

    function clearDirectorySearch(): void {
        searchQuery.value = '';
        runDirectorySearch();
    }

    function useSuggestion(value: string): void {
        searchQuery.value = value;
        runDirectorySearch();
    }

    function onDirectorySort(event: Event): void {
        const value = (event.target as HTMLSelectElement).value;
        setListSort(value === 'relevance' ? '' : value);
    }

    function onCardsSort(value: string): void {
        setListSort(value === 'relevance' ? '' : value);
    }

    onMounted(() => {
        if (!props.directorySwitcher) {
            return;
        }
        http.get<{ data?: { name?: string }[] }>('skills', {
            params: { per_page: 6, sort: 'name', 'filter[is_active]': 1 },
        })
            .then((response) => {
                suggestions.value = (response.data.data ?? [])
                    .map((skill) => skill.name)
                    .filter((name): name is string => Boolean(name));
            })
            .catch(() => {
                suggestions.value = [];
            });
    });

    const resolvedFields = computed(() => resolveCardFields(props.cardConfig, visibleColumns.value));

    const columnsByName = computed((): Record<string, TableColumnInterface> => {
        const map: Record<string, TableColumnInterface> = {};
        for (const col of visibleColumns.value) {
            map[col.name] = col;
        }
        for (const col of modelInstance.columns()) {
            if (!map[col.name]) {
                map[col.name] = col;
            }
        }
        return map;
    });

    function itemAsRecord(item: unknown): Record<string, unknown> {
        return item as Record<string, unknown>;
    }

    function handleCustomAction(customAction: ListViewActionType): void {
        if (customAction.action) {
            customAction.action();
        } else if (customAction.modal) {
            modalService.open(customAction.modal);
        } else if (customAction.to) {
            router.push(customAction.to);
        }
    }

    function onCardClick(id: number): void {
        if (props.disableRowNavigation) {
            return;
        }
        navigateToDetail(id);
    }
</script>

<style scoped>
    .card-fade-enter-active,
    .card-fade-leave-active {
        transition: opacity 0.2s ease;
    }

    .card-fade-enter-from,
    .card-fade-leave-to {
        opacity: 0;
    }
</style>
