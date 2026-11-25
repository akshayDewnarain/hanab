<template>
    <PageLayout :label="t('HELP_SEARCH_RESULTS')">
        <div class="relative flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 overflow-y-auto">
                <div class="px-1 pt-1">
                    <router-link class="inline-flex items-center gap-1 text-sm font-medium text-emphasis" :to="{ name: 'admin-help' }">
                        <Icon class="size-4" icon="material-symbols:arrow-back-rounded" />
                        {{ t('HELP_BACK') }}
                    </router-link>
                    <h1 class="m-0 mt-3 text-lg font-semibold text-list-text">{{ t('HELP_SEARCH_RESULTS') }}</h1>
                </div>
                <UiStickySurface v-slot="{ scrolled }" class="px-1">
                    <div aria-hidden="true" class="pointer-events-none h-3"></div>
                    <form
                        class="rounded-xl border border-list-border bg-list-surface px-3 py-2.5 shadow-sm transition-[background-color,backdrop-filter] duration-200 ease-out motion-reduce:transition-none"
                        :style="scrolled ? frostStyle : undefined"
                        @submit.prevent="submit"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative min-w-[14rem] flex-1">
                                <Icon class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-list-text-muted" icon="material-symbols:search-rounded" />
                                <input
                                    ref="searchInput"
                                    v-model="term"
                                    class="help-search-input h-10 w-full rounded-lg border border-list-border bg-list-surface pr-9 pl-9 text-sm text-list-text"
                                    type="text"
                                    role="searchbox"
                                    autocomplete="off"
                                    :aria-label="t('HELP_SEARCH_PLACEHOLDER')"
                                    :placeholder="t('HELP_SEARCH_PLACEHOLDER')"
                                />
                                <button
                                    class="absolute top-1/2 right-2 inline-flex size-6 -translate-y-1/2 items-center justify-center rounded text-list-text-muted hover:text-list-text"
                                    :class="term ? '' : 'invisible'"
                                    type="button"
                                    :disabled="!term"
                                    :tabindex="term ? 0 : -1"
                                    :aria-label="t('HELP_CLEAR_SEARCH')"
                                    @click="clearTerm"
                                >
                                    <Icon class="size-4" icon="material-symbols:close-rounded" />
                                </button>
                            </div>
                            <div class="w-full sm:w-56">
                                <ChoiceDropdown
                                    :model-value="categoryId"
                                    :aria-label="t('HELP_FILTER_CATEGORY')"
                                    :options="categoryOptions"
                                    @update:model-value="setCategory"
                                />
                            </div>
                            <button class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand" type="submit">
                                <Icon class="size-4" icon="material-symbols:search-rounded" />
                                {{ t('HELP_SEARCH_ACTION') }}
                            </button>
                        </div>
                    </form>
                </UiStickySurface>
                <div class="flex flex-col gap-2 px-1 pt-4 pb-6">
                    <p v-if="failed" class="m-0 rounded-xl border border-dashed border-list-border bg-list-surface px-4 py-8 text-center text-sm text-list-text-muted">
                        {{ t('HELP_UNAVAILABLE') }}
                    </p>
                    <p v-else-if="loaded && !articles.length" class="m-0 rounded-xl border border-dashed border-list-border bg-list-surface px-4 py-8 text-center text-sm text-list-text-muted">
                        {{ t('HELP_NO_RESULTS') }}
                    </p>
                    <UiStaggerGrid v-if="articles.length" columns="1fr" gap="0.5rem">
                        <HelpArticleCard v-for="article in articles" :key="article.id" :article="article" />
                    </UiStaggerGrid>
                </div>
            </div>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { Icon } from '@iconify/vue';
    import { PageLayout, UiStaggerGrid, UiStickySurface } from '@freelance/vue-ui';
    import http from '@/utils/http';
    import ChoiceDropdown from '@/components/inputs/ChoiceDropdown.vue';
    import HelpArticleCard from '@/components/help/HelpArticleCard.vue';
    import type { HelpCard, HelpCategory } from '@/modules/types/help/Help.ts';

    const { t } = useI18n();
    const route = useRoute();
    const router = useRouter();
    const searchInput = ref<HTMLInputElement | null>(null);
    const term = ref(typeof route.query.q === 'string' ? route.query.q : '');
    let searchTimer: ReturnType<typeof setTimeout> | null = null;
    const categoryId = ref(typeof route.query.category_id === 'string' ? route.query.category_id : '');
    const articles = ref<HelpCard[]>([]);
    const categories = ref<HelpCategory[]>([]);
    const loaded = ref(false);
    const failed = ref(false);
    const frostStyle = {
        backgroundColor: 'color-mix(in srgb, var(--color-list-surface) 94%, transparent)',
        backdropFilter: 'blur(12px)',
    };
    const categoryOptions = computed(() => [
        { value: '', label: t('HELP_ALL_CATEGORIES') },
        ...categories.value.map((category) => ({ value: String(category.id), label: category.name })),
    ]);

    async function load(): Promise<void> {
        failed.value = false;
        const params: Record<string, string> = {};
        const typed = typeof route.query.q === 'string' ? route.query.q.trim() : '';
        const category = typeof route.query.category_id === 'string' ? route.query.category_id : '';
        if (typed) params.q = typed;
        if (category) params.category_id = category;
        try {
            const response = await http.get<{ data: HelpCard[] }>('help/search', { params });
            articles.value = response.data.data ?? [];
        } catch {
            articles.value = [];
            failed.value = true;
        } finally {
            loaded.value = true;
        }
    }

    function submit(): void {
        if (searchTimer) {
            clearTimeout(searchTimer);
            searchTimer = null;
        }
        if (!term.value.trim()) {
            void router.replace({ name: 'admin-help' });
            return;
        }
        const query: Record<string, string> = {};
        query.q = term.value.trim();
        if (categoryId.value) query.category_id = categoryId.value;
        const currentQuery = typeof route.query.q === 'string' ? route.query.q : '';
        const currentCategory = typeof route.query.category_id === 'string' ? route.query.category_id : '';
        if ((query.q ?? '') === currentQuery && (query.category_id ?? '') === currentCategory) {
            void load();
            return;
        }
        void router.replace({ name: 'admin-help-search', query });
    }

    watch(term, () => {
        if (searchTimer) clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            searchTimer = null;
            submit();
        }, 300);
    });

    function clearTerm(): void {
        term.value = '';
        submit();
    }

    function setCategory(value: string | number | null): void {
        categoryId.value = value == null ? '' : String(value);
        submit();
    }

    watch(
        () => [route.query.q, route.query.category_id],
        () => {
            const next = typeof route.query.q === 'string' ? route.query.q : '';
            const editing = document.activeElement === searchInput.value;
            if (!editing && next !== term.value) term.value = next;
            categoryId.value = typeof route.query.category_id === 'string' ? route.query.category_id : '';
            void load();
        },
    );

    onBeforeUnmount(() => {
        if (searchTimer) clearTimeout(searchTimer);
    });

    onMounted(async () => {
        const listed = await http.get<{ data: HelpCategory[] }>('help/categories');
        categories.value = listed.data.data ?? [];
        await load();
    });
</script>

<style scoped>
    .help-search-input::-webkit-search-cancel-button,
    .help-search-input::-webkit-search-decoration {
        -webkit-appearance: none;
        appearance: none;
        display: none;
    }
</style>
