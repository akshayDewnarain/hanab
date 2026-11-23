<template>
    <PageLayout class="h-full" :label="t('EXPERTISE_SEARCH_TITLE')">
        <div class="flex h-full min-h-0 flex-col gap-3">
            <header class="shrink-0">
                <h1 class="text-lg font-semibold text-list-text">{{ t('EXPERTISE_SEARCH_TITLE') }}</h1>
                <p class="mt-1 text-sm text-list-text-muted">{{ t('EXPERTISE_SEARCH_INTRO') }}</p>
            </header>
            <ExpertiseSearch presentation="page" class="min-h-0 flex-1" />
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { PageLayout } from '@freelance/vue-ui';
    import { useI18n } from 'vue-i18n';
    import ExpertiseSearch from '@/components/search/ExpertiseSearch.vue';

    defineOptions({ name: 'ExpertiseSearchPage' });

    const { t } = useI18n();
</script>
