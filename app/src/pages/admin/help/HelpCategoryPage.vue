<template>
    <PageLayout :label="category?.name || t('HELP_CATEGORY')" class="overflow-y-auto">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
            <router-link class="text-sm font-medium text-primary" :to="{ name: 'admin-help' }">{{ t('HELP_BACK') }}</router-link>
            <p v-if="missing" class="m-0 text-sm">{{ t('HELP_UNAVAILABLE') }}</p>
            <template v-else-if="category">
                <h1 class="m-0 text-2xl font-semibold">{{ category.name }}</h1>
                <p v-if="category.description" class="m-0 text-sm text-list-text-muted">{{ category.description }}</p>
                <p v-if="!articles.length" class="m-0 text-sm text-list-text-muted">{{ t('HELP_EMPTY') }}</p>
                <HelpArticleCard v-for="article in articles" :key="article.id" :article="article" />
            </template>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { onMounted, ref, watch } from 'vue';
    import { useRoute } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { PageLayout } from '@freelance/vue-ui';
    import http from '@/utils/http';
    import HelpArticleCard from '@/components/help/HelpArticleCard.vue';
    import type { HelpCard, HelpCategory } from '@/modules/types/help/Help.ts';

    const { t } = useI18n();
    const route = useRoute();
    const category = ref<HelpCategory | null>(null);
    const articles = ref<HelpCard[]>([]);
    const missing = ref(false);

    async function load(): Promise<void> {
        missing.value = false;
        const listed = await http.get<{ data: HelpCategory[] }>('help/categories');
        const match = (listed.data.data ?? []).find((item) => item.slug === route.params.slug);
        if (!match) {
            category.value = null;
            missing.value = true;
            return;
        }
        category.value = match;
        const results = await http.get<{ data: HelpCard[] }>('help/search', { params: { category_id: match.id } });
        articles.value = results.data.data ?? [];
    }

    watch(() => route.params.slug, () => void load());
    onMounted(load);
</script>
