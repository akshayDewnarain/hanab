<template>
    <div class="flex h-full w-full min-h-0 flex-col">
        <!-- Header -->
        <div class="flex shrink-0 flex-col text-gray-600">
            <div class="flex items-center space-x-2">
                <Icon class="w-6 h-6" icon="material-symbols:database" />
                <span class="text-lg font-bold">
                    {{ t('GENERAL_SELECT_ENTITY', { entity: instance.getEntitySingular() }) }}
                </span>
            </div>
            <div>
                <span class="text-sm">
                    {{ t('GENERAL_SELECT_ENTITY_DESCRIPTION', { entity: instance.getEntity() }) }}
                </span>
            </div>
            <div class="py-2">
                <div class="relative flex w-full flex-row rounded bg-white shadow">
                    <input
                        v-model="searchQuery"
                        :placeholder="t('GENERAL_SEARCH_PLACEHOLDER')"
                        class="input-base"
                        type="text"
                        @input="handleInputChange"
                    />
                    <div
                        class="absolute right-0 flex h-full cursor-pointer items-center justify-center rounded-r border border-gray-200 bg-[var(--color-background)] px-2 transition-colors duration-200 ease-in hover:bg-[var(--color-highlight-dark)]"
                    >
                        <Icon class="h-4 w-4 text-white" icon="material-symbols:search" />
                    </div>
                </div>
            </div>
            <span class="font-bold">
                {{ t('GENERAL_SEARCH_RESULTS_COUNT', { count: meta.total }) }}
            </span>
        </div>

        <!-- Content -->
        <div class="flex min-h-0 w-full flex-1 flex-col overflow-hidden">
            <div class="min-h-0 flex-1 overflow-hidden">
                <SimpleListView
                    :attached-ids="localSelected"
                    :attached-ids-as-selected="true"
                    :columns="columns"
                    :items="items"
                    :show-action-bar="false"
                    :show-default-bulk-actions="false"
                    @update:selected-ids="setSelected"
                />
            </div>
            <ListViewPagination
                v-if="meta.total > 0"
                :current="meta.current_page"
                :per-page="meta.per_page"
                :total="meta.total"
                @update:currentPage="handlePageChange"
                @update:perPage="handlePerPageChange"
            />
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, onMounted, ref, watch } from 'vue';
    import type { EntityPickerProps } from '@/modules/types/support/modals/EntityPickerProps.ts';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { useDebounce } from '@/composables/useDebounce';
    import { useComponentState } from '@/composables/components/useComponentState';
    import SimpleListView from '@/components/list-views/SimpleListView.vue';
    import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';
    import ListViewPagination from '@/components/pagination/ListViewPagination.vue';
    import type { Meta } from '@/modules/types/support/responses/Meta.ts';
    import type { QueryParameters } from '@/modules/types/support/responses/QueryParameters.ts';

    defineComponent({
        name: 'EntityPicker',
    });

    const raw = withDefaults(
        defineProps<{
            props: EntityPickerProps;
        }>(),
        {},
    );

    const defaults = {
        includes: [] as string[],
        ids: [] as number[],
        lockCurrent: false,
        hideCurrent: false,
    };

    const props = computed<EntityPickerProps>(() => {
        return {
            ...defaults,
            ...raw.props,
        };
    });

    const { t } = useI18n();
    const { setLoading, setContent } = useComponentState();

    /** Index rows (API payloads), not necessarily full Model class instances. */
    const items = ref<unknown[]>([]);
    const searchQuery = ref('');
    const instance = new props.value.model();
    const columns = ref<TableColumnInterface[]>(instance.columns());
    const localSelected = ref<number[]>([...(props.value.ids ?? [])]);

    /** Snapshot rows for selected ids so getData works across paginated pages. */
    const selectedEntityCache = ref<Record<number, unknown>>({});

    const meta = ref<Meta>({
        current_page: 1,
        per_page: 15,
        total: 0,
    });

    function applyDefaultMetaFromItems(): void {
        const len = items.value.length;
        meta.value = {
            ...meta.value,
            current_page: 1,
            per_page: len > 0 ? len : meta.value.per_page,
            total: len,
        };
    }

    const handleInputChange = useDebounce(() => {
        meta.value.current_page = 1;
        fetchData();
    }, 500);

    function setSelected(ids: number[]): void {
        localSelected.value = [...ids];
        const cache = { ...selectedEntityCache.value };
        for (const item of items.value) {
            const id = (item as { id: number }).id;
            if (ids.includes(id)) {
                cache[id] = item;
            }
        }
        for (const id of Object.keys(cache).map(Number)) {
            if (!ids.includes(id)) {
                delete cache[id];
            }
        }
        selectedEntityCache.value = cache;
    }

    /**
     * @override
     * @see useModalComponent
     */
    function getData(): Record<string, unknown> {
        const byId = new Map<number, unknown>();
        for (const id of localSelected.value) {
            const cached = selectedEntityCache.value[id];
            if (cached) {
                byId.set(id, cached);
            }
        }
        for (const item of items.value) {
            const rowId = (item as { id: number }).id;
            if (localSelected.value.includes(rowId)) {
                byId.set(rowId, item);
            }
        }
        const selectedEntities = localSelected.value.map((id) => byId.get(id)).filter((e): e is object => e != null);
        return {
            model: props.value.model,
            selectedIds: [...localSelected.value],
            selectedEntities,
        };
    }

    /**
     * @override
     * @see useModalComponent
     */
    function isValidForAccept(): boolean {
        return true;
    }

    async function fetchData(): Promise<void> {
        setLoading(true);
        const params: QueryParameters = {
            page: meta.value.current_page,
            per_page: meta.value.per_page,
            search: searchQuery.value || undefined,
            include: props.value.includes,
        };
        try {
            const res = await instance.index(params);
            if (res.status !== 200) {
                return;
            }
            const body = res.data as { data?: unknown[]; meta?: Meta };
            items.value = body.data ?? [];
            if (body.meta) {
                meta.value = body.meta;
            } else {
                applyDefaultMetaFromItems();
            }
            const cache = { ...selectedEntityCache.value };
            for (const item of items.value) {
                const rowId = (item as { id: number }).id;
                if (localSelected.value.includes(rowId)) {
                    cache[rowId] = item;
                }
            }
            selectedEntityCache.value = cache;
        } catch {
            items.value = [];
            meta.value = { ...meta.value, total: 0 };
        } finally {
            setContent();
        }
    }

    function handlePageChange(page: number): void {
        meta.value.current_page = page;
        fetchData();
    }

    function handlePerPageChange(perPage: number): void {
        meta.value.per_page = perPage;
        meta.value.current_page = 1;
        fetchData();
    }

    watch(
        () => props.value.ids,
        (ids) => {
            const nextIds = ids ?? [];
            localSelected.value = [...nextIds];
            const cache = { ...selectedEntityCache.value };
            for (const id of Object.keys(cache).map(Number)) {
                if (!nextIds.includes(id)) {
                    delete cache[id];
                }
            }
            selectedEntityCache.value = cache;
        },
    );

    defineExpose({
        isValidForAccept,
        getData,
    });

    onMounted(() => {
        fetchData();
    });
</script>
