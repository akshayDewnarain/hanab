<template>
    <PageLayout :label="t('KNOWLEDGE_SEARCH_RESULTS')" class="overflow-y-auto">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
            <router-link class="text-sm font-medium text-primary" :to="{ name: 'admin-knowledge' }">{{ t('KNOWLEDGE_BACK') }}</router-link>
            <form class="flex gap-2" @submit.prevent="run">
                <input v-model="term" class="h-11 min-w-0 flex-1 rounded-xl border border-list-border bg-list-surface px-4 text-sm" type="search" :aria-label="t('KNOWLEDGE_SEARCH_PLACEHOLDER')" />
                <button type="submit" class="h-11 rounded-xl bg-primary px-4 text-sm font-semibold text-white">{{ t('KNOWLEDGE_SEARCH_ACTION') }}</button>
            </form>
            <p v-if="loaded && !articles.length" class="m-0 text-sm text-list-text-muted">{{ t('KNOWLEDGE_NO_RESULTS') }}</p>
            <div class="flex flex-col gap-3">
                <KnowledgeArticleCard v-for="article in articles" :key="article.id" :article="article" />
            </div>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { onMounted, ref, watch } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { PageLayout } from '@freelance/vue-ui';
    import http from '@/utils/http';
    import KnowledgeArticleCard from '@/components/knowledge/KnowledgeArticleCard.vue';
    import type { KnowledgeCard } from '@/modules/types/knowledge/Knowledge.ts';

    const { t } = useI18n();
    const route = useRoute();
    const router = useRouter();
    const term = ref(typeof route.query.q === 'string' ? route.query.q : '');
    const articles = ref<KnowledgeCard[]>([]);
    const loaded = ref(false);

    async function load(): Promise<void> {
        const response = await http.get<{ data: KnowledgeCard[] }>('knowledge/search', {
            params: {
                q: term.value,
                tag: typeof route.query.tag === 'string' ? route.query.tag : undefined,
            },
        });
        articles.value = response.data.data ?? [];
        loaded.value = true;
    }

    function run(): void {
        void router.replace({ name: 'admin-knowledge-search', query: term.value.trim() ? { q: term.value.trim() } : {} });
    }

    watch(() => route.query.q, () => {
        term.value = typeof route.query.q === 'string' ? route.query.q : '';
        void load();
    });
    onMounted(() => void load());
</script>
