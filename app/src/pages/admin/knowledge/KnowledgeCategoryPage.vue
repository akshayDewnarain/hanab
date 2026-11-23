<template>
    <PageLayout :label="category?.name || t('KNOWLEDGE_CATEGORY')" class="overflow-y-auto">
        <div class="mx-auto flex w-full max-w-[84rem] flex-col gap-4">
            <router-link class="text-sm font-medium text-primary" :to="{ name: 'admin-knowledge' }">{{ t('KNOWLEDGE_BACK') }}</router-link>
            <p v-if="missing" class="m-0 text-sm text-list-text">{{ t('KNOWLEDGE_UNAVAILABLE') }}</p>
            <template v-else-if="category">
                <header>
                    <h1 class="m-0 text-2xl font-semibold text-list-text">{{ category.name }}</h1>
                    <p v-if="category.description" class="m-0 mt-1 max-w-2xl text-sm text-list-text-muted">{{ category.description }}</p>
                </header>
                <p v-if="!articles.length" class="m-0 text-sm text-list-text-muted">{{ t('KNOWLEDGE_EMPTY_HOME') }}</p>
                <div v-else class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <KnowledgeArticleCard v-for="article in articles" :key="article.id" :article="article" />
                </div>
            </template>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { ref, watch } from 'vue';
    import { useRoute } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { PageLayout } from '@freelance/vue-ui';
    import http from '@/utils/http';
    import KnowledgeArticleCard from '@/components/knowledge/KnowledgeArticleCard.vue';
    import type { KnowledgeCard, KnowledgeCategory } from '@/modules/types/knowledge/Knowledge.ts';

    const { t } = useI18n();
    const route = useRoute();
    const category = ref<KnowledgeCategory | null>(null);
    const articles = ref<KnowledgeCard[]>([]);
    const missing = ref(false);

    async function load(): Promise<void> {
        missing.value = false;
        const listed = await http.get<{ data: KnowledgeCategory[] }>('knowledge/categories');
        const match = (listed.data.data ?? []).find((item) => item.slug === route.params.slug);
        if (!match) {
            category.value = null;
            missing.value = true;
            return;
        }
        category.value = match;
        const results = await http.get<{ data: KnowledgeCard[] }>('knowledge/search', { params: { category_id: match.id } });
        articles.value = results.data.data ?? [];
    }

    watch(() => route.params.slug, () => void load(), { immediate: true });
</script>
