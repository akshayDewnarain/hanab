<template>
    <div
        class="flex w-full flex-col p-0 sm:h-full sm:p-4"
        :class="props.isMobileLayout ? 'h-full min-h-0' : 'h-full'"
    >
        <!-- Single list-view surface: cards + integrated filter column -->
        <div
            class="flex min-h-0 flex-1 overflow-hidden"
            :class="
                props.isMobileLayout
                    ? ''
                    : 'sm:rounded-xl sm:border sm:border-slate-200/90 sm:bg-white sm:shadow-sm'
            "
        >
            <!-- Cards -->
            <div class="flex min-w-0 flex-1 flex-col">
                <div class="relative min-h-0 flex-1 overflow-hidden">
                    <div class="h-full overflow-auto bg-transparent sm:bg-slate-50/80">
                        <Transition mode="out-in" name="card-fade">
                            <div
                                v-if="isLoading"
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
                                class="grid grid-cols-1 gap-3 p-2 sm:grid-cols-2 sm:gap-5 sm:p-5 lg:grid-cols-3 xl:grid-cols-4"
                            >
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
            </div>

            <!-- Filter column (inside list view) -->
            <div class="hidden h-full min-h-0 w-72 shrink-0 flex-col gap-3 bg-slate-50/90 p-3 lg:flex lg:w-80">
                <!-- Actions card -->
                <section class="w-full shrink-0 space-y-3 rounded border border-gray-200 bg-white p-3 shadow-md">
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

                    <slot name="header-actions" />
                </section>

                <!-- Filters card -->
                <section
                    class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded border border-gray-200 bg-white shadow-md"
                >
                    <div class="shrink-0 space-y-3 border-b border-slate-100 px-4 pb-4 pt-4">
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

                    <div class="min-h-0 flex-1 overflow-x-hidden overflow-y-auto px-4 py-3">
                        <QuickFilters
                            :key="quickFilterKey"
                            layout="sidebar"
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
                    class="mt-auto w-full shrink-0 overflow-hidden rounded border border-gray-200 bg-white shadow-md"
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
            <template #title>{{ t('FILTER_QUICK_FILTERS') }}</template>

            <div class="space-y-4 p-4">
                <section class="space-y-3 rounded border border-slate-200 bg-white p-3 shadow-sm">
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

                <section class="min-h-[30vh] rounded border border-slate-200 bg-white p-3 shadow-sm">
                    <QuickFilters
                        :key="quickFilterKey"
                        layout="sidebar"
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
                    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
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
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { Icon } from '@iconify/vue';
    import { useListView } from '@/composables/useListView.ts';
    import { useModalService } from '@/composables/useModalService';
    import type { ListViewActionType } from '@/modules/types/support/list-views/ListViewActionType.ts';
    import type { UseListViewHelperProps } from '@/modules/types/support/list-views/UseListViewHelperProps.ts';
    import type { CardListConfig } from '@/modules/types/support/list-views/CardListConfig.ts';
    import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';
    import { resolveCardFields } from '@/modules/utils/cardListView.ts';
    import CardListItem from '@/components/list-views/CardListItem.vue';
    import ListViewPagination from '@/components/pagination/ListViewPagination.vue';
    import QuickFilters from '@/components/filters/list-views/QuickFilters.vue';
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
        },
    );

    const emit = defineEmits<{
        (e: 'update:mobileFiltersOpen', value: boolean): void;
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
        resetFilters,
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
