<template>
    <div class="relative flex h-full min-h-0 flex-col bg-list-canvas">
        <div class="min-h-0 flex-1 overflow-y-auto">
        <div class="px-3 pt-3 sm:px-4 sm:pt-4">
            <EmployeeDirectoryHeader
                :show-search="false"
                :presentation="presentation"
                :query="query"
                :suggestions="suggestions"
                @clear="emit('clear')"
                @search="emit('search')"
                @suggest="emit('suggest', $event)"
                @update:presentation="emit('update:presentation', $event)"
                @update:query="emit('update:query', $event)"
            >
                <template #actions>
                    <slot name="actions" />
                </template>
            </EmployeeDirectoryHeader>
        </div>

        <UiStickySurface v-slot="{ scrolled }" class="px-3 pb-4 sm:px-4">
            <div aria-hidden="true" class="pointer-events-none h-3 bg-list-canvas"></div>
            <section
                class="rounded-xl border border-list-border bg-list-surface px-4 py-3 shadow-sm transition-[background-color,backdrop-filter] duration-200 ease-out motion-reduce:transition-none"
                :style="scrolled ? frostStyle : undefined"
            >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <form class="flex min-w-0 flex-1 items-center gap-2" @submit.prevent="submitSearch">
                        <label class="sr-only" for="employee-cards-search">{{ t('DIRECTORY_SEARCH_LABEL') }}</label>
                        <div class="relative min-w-0 flex-1">
                            <Icon
                                class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-list-text-muted"
                                icon="material-symbols:search-rounded"
                            />
                            <input
                                id="employee-cards-search"
                                :value="query"
                                type="text"
                                autocomplete="off"
                                :placeholder="t('DIRECTORY_SEARCH_PLACEHOLDER')"
                                class="input-base h-9 w-full rounded-lg border-list-border bg-list-surface pr-9 pl-10 text-sm shadow-sm transition-[border-color,box-shadow] duration-200 ease-out placeholder:text-list-text-muted focus:border-primary focus:ring-2 focus:ring-primary/15 motion-reduce:transition-none"
                                @blur="searchFocused = false"
                                @focus="searchFocused = true"
                                @input="emit('update:query', ($event.target as HTMLInputElement).value)"
                            />
                            <button
                                v-if="query"
                                type="button"
                                class="absolute top-1/2 right-2 inline-flex size-6 -translate-y-1/2 cursor-pointer items-center justify-center rounded text-list-text-muted hover:text-list-text"
                                :aria-label="t('DIRECTORY_CLEAR')"
                                :title="t('DIRECTORY_CLEAR')"
                                @click="emit('clear')"
                            >
                                <Icon class="size-4" icon="material-symbols:close-rounded" />
                            </button>
                        </div>
                        <button
                            type="submit"
                            class="inline-flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-lg bg-primary text-on-brand shadow-sm transition-colors duration-200 ease-out hover:bg-[var(--color-primary-hover)] motion-reduce:transition-none"
                            :aria-label="t('DIRECTORY_SEARCH_ACTION')"
                            :title="t('DIRECTORY_SEARCH_ACTION')"
                        >
                            <Icon class="size-5" icon="material-symbols:arrow-forward-rounded" />
                        </button>
                    </form>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border border-list-border bg-list-surface px-3 text-sm font-semibold text-list-text transition-colors duration-200 ease-out hover:border-primary hover:text-primary motion-reduce:transition-none"
                            :aria-expanded="filtersOpen"
                            aria-controls="employee-cards-filters"
                            @click="filtersOpen = true"
                        >
                            <Icon class="size-4" icon="material-symbols:filter-alt-outline-rounded" />
                            {{ t('DIRECTORY_FILTERS') }}
                            <span v-if="filterCount > 0" class="tabular-nums text-primary">({{ filterCount }})</span>
                        </button>
                        <label class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-list-border bg-list-surface px-2.5 text-sm text-list-text-muted">
                            <Icon class="size-4" icon="material-symbols:sort-rounded" />
                            <span class="sr-only">{{ t('DIRECTORY_SORT') }}</span>
                            <select
                                class="cursor-pointer bg-transparent text-sm font-medium text-list-text outline-none"
                                :value="sortValue"
                                @change="emit('sort', ($event.target as HTMLSelectElement).value)"
                            >
                                <option value="relevance">{{ t('DIRECTORY_SORT_RELEVANCE') }}</option>
                                <option value="last_name">{{ t('DIRECTORY_SORT_NAME') }}</option>
                            </select>
                        </label>
                        <HelpTrigger context="employees.directory" />
                    </div>
                </div>

                <Collapsible :open="searchFocused && suggestions.length > 0">
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-list-border pt-3">
                        <span class="text-xs font-medium text-list-text-muted">{{ t('DIRECTORY_SUGGESTIONS') }}</span>
                        <button
                            v-for="suggestion in suggestions"
                            :key="suggestion"
                            type="button"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold transition-colors duration-200 ease-out motion-reduce:transition-none"
                            :class="
                                suggestion.toLowerCase() === query.trim().toLowerCase()
                                    ? 'border-primary bg-primary-light text-emphasis'
                                    : 'border-list-border bg-list-canvas text-list-text hover:border-primary'
                            "
                            @mousedown.prevent
                            @click="emit('suggest', suggestion)"
                        >
                            <Icon class="size-3.5" icon="material-symbols:search-rounded" />
                            {{ suggestion }}
                        </button>
                    </div>
                </Collapsible>
            </section>
        </UiStickySurface>

        <div class="flex flex-col gap-3 px-3 sm:px-4" :style="{ paddingBottom: `${pagerSpace}px` }">
            <div v-if="chips.length" class="flex flex-wrap items-center gap-1.5">
                <button
                    v-for="chip in chips"
                    :key="chip.key"
                    type="button"
                    class="inline-flex cursor-pointer items-center gap-1 rounded-full border border-list-border bg-list-surface px-2 py-0.5 text-xs font-medium text-list-text transition-colors duration-200 ease-out hover:border-primary motion-reduce:transition-none"
                    @click="emit('remove-filter', chip.key)"
                >
                    {{ chip.label }}
                    <Icon class="size-3.5" icon="material-symbols:close-rounded" />
                    <span class="sr-only">{{ t('FILTER_RESET') }}</span>
                </button>
                <button
                    type="button"
                    class="inline-flex cursor-pointer items-center px-1 text-xs font-semibold text-primary hover:underline"
                    @click="emit('reset-filters')"
                >
                    {{ t('FILTER_RESET') }}
                </button>
            </div>

            <div class="min-w-0">
                <p class="text-sm font-semibold text-list-text">{{ t('DIRECTORY_RESULT_COUNT', { count: total }) }}</p>
                <p v-if="query" class="truncate text-xs text-list-text-muted">
                    {{ t('DIRECTORY_RESULTS_HEADING', { query }) }}
                </p>
                <p v-if="relatedTerms.length" class="truncate text-xs text-list-text-muted">
                    {{ t('DIRECTORY_ALSO_SEARCHED', { terms: relatedTerms.join(' · ') }) }}
                </p>
            </div>

            <div
                v-if="isLoading"
                class="grid justify-start gap-4"
                style="grid-template-columns: repeat(auto-fill, minmax(min(100%, 17.5rem), 22rem))"
            >
                <div
                    v-for="index in 8"
                    :key="`card-skeleton-${index}`"
                    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <Skeleton class="h-12 w-full rounded-none" />
                    <div class="flex flex-col items-center gap-3 px-4 pt-5 pb-6">
                        <Skeleton class="size-32 rounded-full" />
                        <Skeleton class="h-9 w-full rounded-lg" />
                        <Skeleton class="h-3 w-full" />
                        <Skeleton class="h-3 w-5/6" />
                    </div>
                </div>
            </div>

            <div
                v-else-if="items.length"
                class="grid justify-start gap-4"
                style="grid-template-columns: repeat(auto-fill, minmax(min(100%, 17.5rem), 22rem))"
            >
                <CardListItem
                    v-for="item in items"
                    :key="getItemId(item)"
                    :card-config="cardConfig"
                    :columns-by-name="columnsByName"
                    :convert-to-string="convertToString"
                    :disable-navigation="disableNavigation"
                    :fields="fields"
                    :get-column-value="getColumnValue"
                    :item="itemAsRecord(item)"
                    @click="emit('view', getItemId(item))"
                />
            </div>

            <div v-else class="flex flex-col items-center justify-center rounded-xl border border-dashed border-list-border bg-list-surface px-6 py-16 text-center">
                <p class="text-sm font-medium text-list-text">{{ t(cardConfig.emptyStateKey ?? 'NO_RESULTS') }}</p>
            </div>

        </div>
        </div>

        <div ref="pager" class="pointer-events-none absolute inset-x-0 bottom-0 z-20">
            <div aria-hidden="true" class="h-3 bg-list-canvas"></div>
            <div class="px-3 sm:px-4">
                <section
                    class="pointer-events-auto rounded-xl border border-list-border px-4 py-3 shadow-sm [&>footer]:border-0 [&>footer]:bg-transparent [&>footer]:p-0"
                    :style="frostStyle"
                >
                    <ListViewPagination
                        :current="currentPage"
                        :labels="paginationLabels"
                        :options="pageSizeOptions"
                        :per-page="perPage"
                        :total="total"
                        @update:current="emit('page', $event)"
                        @update:per-page="emit('per-page', $event)"
                    />
                </section>
            </div>
            <div aria-hidden="true" class="h-3 bg-list-canvas"></div>
        </div>

        <div
            class="fixed inset-0 z-40"
            :class="filtersOpen ? '' : 'pointer-events-none'"
            :inert="filtersOpen ? undefined : true"
        >
            <button
                type="button"
                class="absolute inset-0 bg-black/40 transition-opacity duration-200 ease-out motion-reduce:transition-none"
                :class="filtersOpen ? 'opacity-100' : 'opacity-0'"
                :tabindex="filtersOpen ? 0 : -1"
                :aria-label="t('DIRECTORY_SHOW_RESULTS')"
                @click="filtersOpen = false"
            />
            <div
                id="employee-cards-filters"
                class="absolute inset-y-0 right-0 flex w-full max-w-[22rem] transition-transform duration-200 ease-out motion-reduce:transition-none"
                :class="filtersOpen ? 'translate-x-0' : 'translate-x-full'"
            >
                <Drawer
                    class="h-full w-full max-w-none border-l shadow-xl"
                    :close-label="t('DIRECTORY_SHOW_RESULTS')"
                    :label="t('DIRECTORY_FILTERS')"
                    :open="filtersOpen"
                    side="end"
                    @close="filtersOpen = false"
                >
                    <template #title>
                        <h2 class="truncate text-sm font-semibold">{{ t('DIRECTORY_FILTERS') }}</h2>
                    </template>
                    <template #close>
                        <Icon class="size-[18px]" icon="material-symbols:close-rounded" />
                    </template>
                    <QuickFilters
                        layout="sidebar"
                        :discovery="discovery"
                        :entity="entity"
                        :filters="filters"
                        :model="model"
                        @update:filters="emit('filters', $event)"
                        @update:mounted="emit('mounted-filters', $event)"
                    />
                    <template #footer>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="inline-flex h-9 cursor-pointer items-center justify-center rounded-lg border border-list-border px-3 text-sm font-medium text-list-text transition-colors duration-200 ease-out hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 motion-reduce:transition-none"
                                :disabled="filterCount === 0"
                                @click="emit('reset-filters')"
                            >
                                {{ t('FILTER_RESET') }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-9 flex-1 cursor-pointer items-center justify-center rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand transition-colors duration-200 ease-out hover:bg-[var(--color-primary-hover)] motion-reduce:transition-none"
                                @click="filtersOpen = false"
                            >
                                {{ t('DIRECTORY_SHOW_RESULTS') }}
                            </button>
                        </div>
                    </template>
                </Drawer>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { onBeforeUnmount, onMounted, ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import { Collapsible, Drawer, ListViewPagination, UiStickySurface } from '@freelance/vue-ui';
    import { useI18n } from 'vue-i18n';
    import EmployeeDirectoryHeader from '@/components/employees/EmployeeDirectoryHeader.vue';
    import CardListItem from '@/components/list-views/CardListItem.vue';
    import QuickFilters from '@/components/filters/list-views/QuickFilters.vue';
    import HelpTrigger from '@/components/help/HelpTrigger.vue';
    import Skeleton from '@/components/skeleton/Skeleton.vue';
    import type { CardListConfig, CardListFieldConfig } from '@/modules/types/support/list-views/CardListConfig.ts';
    import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';
    import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';

    defineOptions({ name: 'EmployeeCardsWorkspace' });

    defineProps<{
        presentation: 'directory' | 'cards';
        query: string;
        suggestions: readonly string[];
        sortValue: string;
        filterCount: number;
        chips: { key: string; label: string }[];
        relatedTerms: string[];
        isLoading: boolean;
        items: unknown[];
        total: number;
        currentPage: number;
        perPage: number;
        pageSizeOptions: number[];
        paginationLabels: {
            showing: string;
            to: string;
            of: string;
            results: string;
            perPage: string;
            previous: string;
            next: string;
            pagination: string;
        };
        cardConfig: CardListConfig;
        fields: CardListFieldConfig[];
        columnsByName: Record<string, TableColumnInterface>;
        disableNavigation: boolean;
        entity: string;
        model: ModelConstructor<unknown>;
        filters: Record<string, unknown>;
        discovery: { name: string; icon: string; label: string }[];
        getColumnValue: (item: unknown, column: TableColumnInterface) => unknown;
        convertToString: (value: unknown) => string;
        getItemId: (item: unknown) => number;
    }>();

    const emit = defineEmits<{
        'update:presentation': [value: 'directory' | 'cards'];
        'update:query': [value: string];
        search: [];
        clear: [];
        suggest: [value: string];
        sort: [value: string];
        page: [value: number];
        'per-page': [value: number];
        'remove-filter': [key: string];
        'reset-filters': [];
        filters: [value: Record<string, unknown>];
        'mounted-filters': [value: boolean];
        view: [id: number];
    }>();

    const { t } = useI18n();
    const filtersOpen = ref(false);
    const searchFocused = ref(false);
    const frostStyle = {
        backgroundColor: 'color-mix(in srgb, var(--color-list-surface) 94%, transparent)',
        backdropFilter: 'blur(12px)',
    };
    const pager = ref<HTMLElement | null>(null);
    const pagerSpace = ref(0);
    let pagerObserver: ResizeObserver | null = null;

    function syncPagerSpace(): void {
        pagerSpace.value = Math.ceil(pager.value?.getBoundingClientRect().height ?? 0);
    }

    onMounted(() => {
        if (typeof ResizeObserver === 'undefined' || !pager.value) return;
        pagerObserver = new ResizeObserver(() => syncPagerSpace());
        pagerObserver.observe(pager.value);
        syncPagerSpace();
    });

    onBeforeUnmount(() => {
        pagerObserver?.disconnect();
        pagerObserver = null;
    });

    function submitSearch(): void {
        searchFocused.value = false;
        emit('search');
    }

    function itemAsRecord(item: unknown): Record<string, unknown> {
        return item as Record<string, unknown>;
    }
</script>
