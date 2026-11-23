<template>
    <PageLayout :label="t('HELP_SEARCH_RESULTS')" class="overflow-y-auto">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
            <router-link class="text-sm font-medium text-primary" :to="{ name: 'admin-help' }">{{ t('HELP_BACK') }}</router-link>
            <form class="flex gap-2" @submit.prevent="submit">
                <input v-model="term" class="h-11 min-w-0 flex-1 rounded-xl border border-list-border bg-list-surface px-4 text-sm" type="search" :aria-label="t('HELP_SEARCH_PLACEHOLDER')" />
                <button type="submit" class="h-11 rounded-xl bg-primary px-4 text-sm font-semibold text-white">{{ t('HELP_SEARCH_ACTION') }}</button>
            </form>
            <p v-if="loaded && !articles.length" class="m-0 text-sm text-list-text-muted">{{ t('HELP_NO_RESULTS') }}</p>
            <HelpArticleCard v-for="article in articles" :key="article.id" :article="article" />
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { onMounted, ref, watch } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { PageLayout } from '@freelance/vue-ui';
    import http from '@/utils/http';
    import HelpArticleCard from '@/components/help/HelpArticleCard.vue';
    import type { HelpCard } from '@/modules/types/help/Help.ts';

    const { t } = useI18n();
    const route = useRoute();
    const router = useRouter();
    const term = ref(typeof route.query.q === 'string' ? route.query.q : '');
    const articles = ref<HelpCard[]>([]);
    const loaded = ref(false);

    async function load(): Promise<void> {
        const response = await http.get<{ data: HelpCard[] }>('help/search', { params: { q: term.value.trim() } });
        articles.value = response.data.data ?? [];
        loaded.value = true;
    }

    function submit(): void {
        void router.replace({ name: 'admin-help-search', query: term.value.trim() ? { q: term.value.trim() } : {} });
    }

    watch(() => route.query.q, (value) => {
        term.value = typeof value === 'string' ? value : '';
        void load();
    });

    onMounted(load);
</script>
