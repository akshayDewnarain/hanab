<template>
    <PageLayout :label="t('KNOWLEDGE_TITLE')" class="overflow-y-auto">
        <div class="mx-auto flex w-full max-w-[84rem] flex-col gap-6">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="m-0 text-2xl font-semibold text-list-text">{{ t('KNOWLEDGE_TITLE') }}</h1>
                    <p class="m-0 mt-1 max-w-2xl text-sm text-list-text-muted">{{ t('KNOWLEDGE_INTRO') }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <router-link
                        v-if="canManage"
                        class="inline-flex h-9 items-center rounded-lg border border-list-border px-3 text-sm font-medium text-list-text"
                        :to="{ name: 'admin-knowledge-manage' }"
                    >
                        {{ t('KNOWLEDGE_MANAGE') }}
                    </router-link>
                    <router-link
                        v-if="canCreate"
                        class="inline-flex h-9 items-center rounded-lg bg-primary px-3 text-sm font-semibold text-white"
                        :to="{ name: 'admin-knowledge-create' }"
                    >
                        {{ t('KNOWLEDGE_NEW_ARTICLE') }}
                    </router-link>
                </div>
            </header>
            <form class="flex gap-2" @submit.prevent="search">
                <input
                    v-model="term"
                    class="h-11 min-w-0 flex-1 rounded-xl border border-list-border bg-list-surface px-4 text-sm text-list-text"
                    type="search"
                    :placeholder="t('KNOWLEDGE_SEARCH_PLACEHOLDER')"
                    :aria-label="t('KNOWLEDGE_SEARCH_PLACEHOLDER')"
                />
                <button type="submit" class="h-11 rounded-xl bg-primary px-4 text-sm font-semibold text-white">
                    {{ t('KNOWLEDGE_SEARCH_ACTION') }}
                </button>
            </form>
            <section v-if="categories.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <router-link
                    v-for="category in categories"
                    :key="category.id"
                    class="rounded-xl border border-list-border bg-list-surface p-4 shadow-sm transition-colors duration-200 hover:border-primary motion-reduce:transition-none"
                    :to="{ name: 'admin-knowledge-category', params: { slug: category.slug } }"
                >
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="m-0 flex items-center gap-2 text-base font-semibold text-list-text">
                            <Icon class="size-5 text-primary" :icon="category.icon || 'material-symbols:folder-outline-rounded'" />
                            {{ category.name }}
                        </h2>
                        <span class="text-xs text-list-text-muted">{{ t('KNOWLEDGE_ARTICLE_COUNT', { count: category.article_count }) }}</span>
                    </div>
                    <p v-if="category.description" class="m-0 mt-2 text-sm text-list-text-muted">{{ category.description }}</p>
                </router-link>
            </section>
            <section v-if="featured.length">
                <h2 class="m-0 text-sm font-semibold text-list-text">{{ t('KNOWLEDGE_FEATURED') }}</h2>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <KnowledgeArticleCard v-for="article in featured" :key="article.id" :article="article" />
                </div>
            </section>
            <section>
                <h2 class="m-0 text-sm font-semibold text-list-text">{{ t('KNOWLEDGE_RECENT') }}</h2>
                <p v-if="!recent.length" class="m-0 mt-3 text-sm text-list-text-muted">{{ t('KNOWLEDGE_EMPTY_HOME') }}</p>
                <div v-else class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <KnowledgeArticleCard v-for="article in recent" :key="article.id" :article="article" />
                </div>
            </section>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { Icon } from '@iconify/vue';
    import { PageLayout } from '@freelance/vue-ui';
    import http from '@/utils/http';
    import { useAuthStore } from '@/stores/auth';
    import KnowledgeArticleCard from '@/components/knowledge/KnowledgeArticleCard.vue';
    import type { KnowledgeCard, KnowledgeCategory } from '@/modules/types/knowledge/Knowledge.ts';

    const { t } = useI18n();
    const router = useRouter();
    const auth = useAuthStore();
    const term = ref('');
    const categories = ref<KnowledgeCategory[]>([]);
    const featured = ref<KnowledgeCard[]>([]);
    const recent = ref<KnowledgeCard[]>([]);
    const canCreate = computed(() => auth.hasPermission('create knowledge articles'));
    const canManage = computed(() => auth.hasPermission('update knowledge articles'));

    function search(): void {
        void router.push({ name: 'admin-knowledge-search', query: term.value.trim() ? { q: term.value.trim() } : {} });
    }

    onMounted(async () => {
        const response = await http.get<{ data: { categories: KnowledgeCategory[]; featured: KnowledgeCard[]; recent: KnowledgeCard[] } }>('knowledge');
        categories.value = response.data.data.categories ?? [];
        featured.value = response.data.data.featured ?? [];
        recent.value = response.data.data.recent ?? [];
    });
</script>
