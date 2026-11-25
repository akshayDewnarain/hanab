<template>
    <PageLayout :label="t('HELP_TITLE')">
        <div v-if="loading" class="flex flex-1 items-center justify-center" role="status">
            <span class="size-10 animate-spin rounded-full border-2 border-list-border border-t-primary motion-reduce:animate-none" />
            <span class="sr-only">{{ t('HELP_LOADING') }}</span>
        </div>
        <div v-else class="relative flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 overflow-y-auto">
                <header class="flex flex-wrap items-start justify-between gap-4 px-1 pt-1">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary-light text-emphasis">
                            <Icon class="size-5" icon="material-symbols:menu-book-outline-rounded" />
                        </span>
                        <div class="min-w-0">
                            <h1 class="m-0 text-lg font-semibold text-list-text">{{ t('HELP_TITLE') }}</h1>
                            <p class="m-0 mt-1 max-w-2xl text-sm text-list-text-muted">{{ t('HELP_INTRO') }}</p>
                            <p v-if="publishedCount > 0" class="m-0 mt-1 text-xs font-medium text-list-text-muted">
                                {{ t('HELP_ARTICLE_COUNT', { count: publishedCount }) }}
                            </p>
                        </div>
                    </div>
                    <div v-if="canManage || canCreate" class="flex shrink-0 flex-wrap items-center gap-2">
                        <router-link v-if="canManage" class="access-action" :to="{ name: 'admin-help-manage' }">
                            <Icon class="size-4" icon="material-symbols:tune-rounded" />
                            {{ t('HELP_MANAGE') }}
                        </router-link>
                        <router-link
                            v-if="canCreate"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand"
                            :to="{ name: 'admin-help-create' }"
                        >
                            <Icon class="size-4" icon="material-symbols:add-rounded" />
                            {{ t('HELP_NEW') }}
                        </router-link>
                    </div>
                </header>

                <UiStickySurface v-slot="{ scrolled }" class="px-1">
                    <div aria-hidden="true" class="pointer-events-none h-3"></div>
                    <form
                        class="rounded-xl border border-list-border bg-list-surface px-3 py-2.5 shadow-sm transition-[background-color,backdrop-filter,box-shadow] duration-200 ease-out motion-reduce:transition-none"
                        :style="scrolled ? frostStyle : undefined"
                        @submit.prevent="search"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative min-w-[14rem] flex-1">
                                <Icon class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-list-text-muted" icon="material-symbols:search-rounded" />
                                <input
                                    v-model="term"
                                    class="help-search-input h-10 w-full rounded-lg border border-list-border bg-list-surface pr-9 pl-9 text-sm text-list-text"
                                    type="text"
                                    role="searchbox"
                                    autocomplete="off"
                                    :placeholder="t('HELP_SEARCH_PLACEHOLDER')"
                                    :aria-label="t('HELP_SEARCH_PLACEHOLDER')"
                                />
                                <button
                                    class="absolute top-1/2 right-2 inline-flex size-6 -translate-y-1/2 items-center justify-center rounded text-list-text-muted hover:text-list-text"
                                    :class="term ? '' : 'invisible'"
                                    type="button"
                                    :disabled="!term"
                                    :tabindex="term ? 0 : -1"
                                    :aria-label="t('HELP_CLEAR_SEARCH')"
                                    @click="term = ''"
                                >
                                    <Icon class="size-4" icon="material-symbols:close-rounded" />
                                </button>
                            </div>
                            <div class="w-full sm:w-56">
                                <ChoiceDropdown
                                    :model-value="categoryFilter"
                                    :aria-label="t('HELP_FILTER_CATEGORY')"
                                    :options="categoryOptions"
                                    @update:model-value="categoryFilter = String($event ?? '')"
                                />
                            </div>
                            <button class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand" type="submit">
                                <Icon class="size-4" icon="material-symbols:search-rounded" />
                                {{ t('HELP_SEARCH_ACTION') }}
                            </button>
                        </div>
                    </form>
                </UiStickySurface>

                <div class="flex flex-col gap-6 px-1 pt-4 pb-6">
                    <section v-if="started.length">
                        <h2 class="m-0 text-sm font-semibold text-list-text">{{ t('HELP_GETTING_STARTED') }}</h2>
                        <UiStaggerGrid class="mt-3" columns="repeat(auto-fill, minmax(min(100%, 20rem), 1fr))" gap="0.5rem">
                            <HelpArticleCard v-for="article in started" :key="article.id" compact :article="article" />
                        </UiStaggerGrid>
                    </section>

                    <section>
                        <h2 class="m-0 text-sm font-semibold text-list-text">{{ t('HELP_BROWSE') }}</h2>
                        <p v-if="!visibleCategories.length" class="m-0 mt-3 rounded-xl border border-dashed border-list-border bg-list-surface px-4 py-8 text-center text-sm text-list-text-muted">
                            {{ t('HELP_EMPTY') }}
                        </p>
                        <UiStaggerGrid v-else class="mt-3">
                            <UiKnowledgeCategoryCard
                                v-for="category in visibleCategories"
                                :key="category.id"
                                :accent-class="helpBarClass(category.slug)"
                                :description="category.description || ''"
                                :icon="category.icon || 'material-symbols:menu-book-outline-rounded'"
                                :meta="t('HELP_ARTICLE_COUNT', { count: category.article_count })"
                                :tile-class="helpTileClass(category.slug)"
                                :title="category.name"
                            >
                                <router-link
                                    v-for="guide in category.previews || []"
                                    :key="guide.id"
                                    class="rounded-lg px-1 py-1 transition-colors duration-200 hover:bg-list-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none"
                                    :to="{ name: 'admin-help-article', params: { slug: guide.slug } }"
                                >
                                    <UiKnowledgeGuideItem :meta="guideMeta(guide)" :title="guide.title" />
                                </router-link>
                                <template #footer>
                                    <router-link
                                        v-if="category.article_count > 0"
                                        class="inline-flex items-center gap-1 text-sm font-semibold text-emphasis"
                                        :to="{ name: 'admin-help-category', params: { slug: category.slug } }"
                                    >
                                        {{ t('HELP_VIEW_CATEGORY') }}
                                        <Icon class="size-4" icon="material-symbols:arrow-forward-rounded" />
                                    </router-link>
                                    <router-link
                                        v-else-if="canCreate"
                                        class="inline-flex items-center gap-1 text-sm font-semibold text-emphasis"
                                        :to="{ name: 'admin-help-create' }"
                                    >
                                        <Icon class="size-4" icon="material-symbols:add-rounded" />
                                        {{ t('HELP_ADD_GUIDE') }}
                                    </router-link>
                                </template>
                            </UiKnowledgeCategoryCard>
                        </UiStaggerGrid>
                    </section>

                    <section v-if="recent.length">
                        <h2 class="m-0 text-sm font-semibold text-list-text">{{ t('HELP_RECENT') }}</h2>
                        <UiStaggerGrid class="mt-3" columns="repeat(auto-fill, minmax(min(100%, 18rem), 1fr))" gap="0.5rem">
                            <HelpArticleCard v-for="article in recent" :key="article.id" compact :article="article" />
                        </UiStaggerGrid>
                    </section>
                </div>
            </div>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { Icon } from '@iconify/vue';
    import { PageLayout, UiKnowledgeCategoryCard, UiKnowledgeGuideItem, UiStaggerGrid, UiStickySurface } from '@freelance/vue-ui';
    import http from '@/utils/http';
    import { useAuthStore } from '@/stores/auth';
    import ChoiceDropdown from '@/components/inputs/ChoiceDropdown.vue';
    import HelpArticleCard from '@/components/help/HelpArticleCard.vue';
    import { helpBarClass, helpIsRecent, helpTileClass } from '@/modules/help/appearance';
    import type { HelpCard, HelpCategory } from '@/modules/types/help/Help.ts';

    const { t, locale } = useI18n();
    const router = useRouter();
    const auth = useAuthStore();
    const loading = ref(true);
    const term = ref('');
    const categoryFilter = ref('');
    const categories = ref<HelpCategory[]>([]);
    const started = ref<HelpCard[]>([]);
    const recent = ref<HelpCard[]>([]);
    const canCreate = computed(() => auth.hasPermission('create help articles'));
    const canManage = computed(() => auth.hasPermission('update help articles') || auth.hasPermission('publish help articles'));
    const frostStyle = {
        backgroundColor: 'color-mix(in srgb, var(--color-list-surface) 94%, transparent)',
        backdropFilter: 'blur(12px)',
    };
    const publishedCount = computed(() => categories.value.reduce((sum, category) => sum + category.article_count, 0));
    const visibleCategories = computed(() =>
        categories.value.filter((category) => {
            if (categoryFilter.value && String(category.id) !== categoryFilter.value) return false;
            if (category.article_count > 0) return true;
            return canManage.value;
        }),
    );
    const categoryOptions = computed(() => [
        { value: '', label: t('HELP_ALL_CATEGORIES') },
        ...categories.value.map((category) => ({ value: String(category.id), label: category.name })),
    ]);
    const featuredIds = computed(() => new Set(started.value.map((article) => article.id)));

    function guideMeta(guide: HelpCard): string {
        if (helpIsRecent(guide.updated_at)) return t('HELP_RECENT_BADGE');
        if (!guide.updated_at) return '';
        const date = new Date(guide.updated_at);
        if (Number.isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat(locale.value === 'nl' ? 'nl-NL' : 'en-GB', { day: 'numeric', month: 'short' }).format(date);
    }

    let searchTimer: ReturnType<typeof setTimeout> | null = null;

    function search(): void {
        if (searchTimer) {
            clearTimeout(searchTimer);
            searchTimer = null;
        }
        const query: Record<string, string> = {};
        const typed = term.value.trim();
        if (!typed) return;
        query.q = typed;
        if (categoryFilter.value) query.category_id = categoryFilter.value;
        void router.push({ name: 'admin-help-search', query });
    }

    watch(term, () => {
        if (searchTimer) clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            searchTimer = null;
            search();
        }, 300);
    });

    onBeforeUnmount(() => {
        if (searchTimer) clearTimeout(searchTimer);
    });

    onMounted(async () => {
        try {
            const response = await http.get<{ data: { categories: HelpCategory[]; getting_started: HelpCard[]; recent: HelpCard[] } }>('help');
            categories.value = response.data.data.categories ?? [];
            started.value = response.data.data.getting_started ?? [];
            recent.value = (response.data.data.recent ?? []).filter((article) => !featuredIds.value.has(article.id));
        } finally {
            loading.value = false;
        }
    });
</script>

<style scoped>
    .access-action {
        display: inline-flex;
        height: 2.25rem;
        align-items: center;
        gap: 0.375rem;
        border-radius: 0.5rem;
        border: 1px solid var(--color-list-border);
        background: var(--color-list-surface);
        padding: 0 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--color-list-text);
    }

    .access-action:hover {
        border-color: color-mix(in srgb, var(--color-emphasis) 35%, var(--color-list-border));
    }

    .help-search-input::-webkit-search-cancel-button,
    .help-search-input::-webkit-search-decoration {
        -webkit-appearance: none;
        appearance: none;
        display: none;
    }
</style>
