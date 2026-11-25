<template>
    <PageLayout :label="category?.name || t('HELP_CATEGORY')" class="overflow-y-auto">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
            <router-link class="inline-flex items-center gap-1 text-sm font-medium text-emphasis" :to="{ name: 'admin-help' }">
                <Icon class="size-4" icon="material-symbols:arrow-back-rounded" />
                {{ t('HELP_BACK') }}
            </router-link>
            <p v-if="missing" class="m-0 text-sm text-list-text">{{ t('HELP_UNAVAILABLE') }}</p>
            <template v-else-if="category">
                <header class="overflow-hidden rounded-xl border border-list-border bg-list-surface shadow-[0_1px_2px_color-mix(in_srgb,var(--color-list-shadow-tint)_7%,transparent)]">
                    <span class="block h-1" :class="helpBarClass(category.slug)" aria-hidden="true"></span>
                    <div class="flex items-start gap-3 p-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg" :class="helpTileClass(category.slug)">
                            <Icon class="size-5" :icon="category.icon || 'material-symbols:menu-book-outline-rounded'" />
                        </span>
                        <div class="min-w-0">
                            <h1 class="m-0 text-lg font-semibold text-list-text">{{ category.name }}</h1>
                            <p v-if="category.description" class="m-0 mt-1 text-sm text-list-text-muted">{{ category.description }}</p>
                            <p class="m-0 mt-1 text-xs font-medium text-list-text-muted">{{ t('HELP_ARTICLE_COUNT', { count: articles.length }) }}</p>
                        </div>
                    </div>
                </header>
                <p v-if="!articles.length" class="m-0 rounded-xl border border-dashed border-list-border bg-list-surface px-4 py-8 text-center text-sm text-list-text-muted">
                    {{ t('HELP_EMPTY') }}
                </p>
                <UiStaggerGrid v-if="articles.length" columns="1fr" gap="0.75rem">
                    <HelpArticleCard v-for="article in articles" :key="article.id" :article="article" :show-category="false" />
                </UiStaggerGrid>
            </template>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { onMounted, ref, watch } from 'vue';
    import { useRoute } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { Icon } from '@iconify/vue';
    import { PageLayout, UiStaggerGrid } from '@freelance/vue-ui';
    import http from '@/utils/http';
    import HelpArticleCard from '@/components/help/HelpArticleCard.vue';
    import { helpBarClass, helpTileClass } from '@/modules/help/appearance';
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
            articles.value = [];
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
