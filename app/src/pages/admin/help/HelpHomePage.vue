<template>
    <PageLayout :label="t('HELP_TITLE')" class="overflow-y-auto">
        <div class="mx-auto flex w-full max-w-[84rem] flex-col gap-6">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="m-0 text-2xl font-semibold text-list-text">{{ t('HELP_TITLE') }}</h1>
                    <p class="m-0 mt-1 max-w-2xl text-sm text-list-text-muted">{{ t('HELP_INTRO') }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <router-link
                        v-if="canManage"
                        class="inline-flex h-9 items-center rounded-lg border border-list-border px-3 text-sm font-medium"
                        :to="{ name: 'admin-help-manage' }"
                    >
                        {{ t('HELP_MANAGE') }}
                    </router-link>
                    <router-link
                        v-if="canCreate"
                        class="inline-flex h-9 items-center rounded-lg bg-primary px-3 text-sm font-semibold text-white"
                        :to="{ name: 'admin-help-create' }"
                    >
                        {{ t('HELP_NEW') }}
                    </router-link>
                </div>
            </header>
            <form class="flex gap-2" @submit.prevent="search">
                <input
                    v-model="term"
                    class="h-11 min-w-0 flex-1 rounded-xl border border-list-border bg-list-surface px-4 text-sm"
                    type="search"
                    :placeholder="t('HELP_SEARCH_PLACEHOLDER')"
                    :aria-label="t('HELP_SEARCH_PLACEHOLDER')"
                />
                <button type="submit" class="h-11 rounded-xl bg-primary px-4 text-sm font-semibold text-white">{{ t('HELP_SEARCH_ACTION') }}</button>
            </form>
            <section v-if="categories.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <router-link
                    v-for="category in categories"
                    :key="category.id"
                    class="rounded-xl border border-list-border bg-list-surface p-4 shadow-sm transition-colors duration-200 hover:border-primary motion-reduce:transition-none"
                    :to="{ name: 'admin-help-category', params: { slug: category.slug } }"
                >
                    <h2 class="m-0 flex items-center gap-2 text-base font-semibold">
                        <Icon class="size-5 text-primary" :icon="category.icon || 'material-symbols:menu-book-outline-rounded'" />
                        {{ category.name }}
                    </h2>
                    <p v-if="category.description" class="m-0 mt-2 text-sm text-list-text-muted">{{ category.description }}</p>
                    <p class="m-0 mt-3 text-xs text-list-text-muted">{{ t('HELP_ARTICLE_COUNT', { count: category.article_count }) }}</p>
                </router-link>
            </section>
            <section>
                <h2 class="m-0 text-sm font-semibold">{{ t('HELP_GETTING_STARTED') }}</h2>
                <p v-if="!started.length" class="m-0 mt-3 text-sm text-list-text-muted">{{ t('HELP_EMPTY') }}</p>
                <div v-else class="mt-3 grid gap-3 md:grid-cols-2">
                    <HelpArticleCard v-for="article in started" :key="article.id" :article="article" />
                </div>
            </section>
            <section v-if="recent.length">
                <h2 class="m-0 text-sm font-semibold">{{ t('HELP_RECENT') }}</h2>
                <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <HelpArticleCard v-for="article in recent" :key="article.id" :article="article" />
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
    import HelpArticleCard from '@/components/help/HelpArticleCard.vue';
    import type { HelpCard, HelpCategory } from '@/modules/types/help/Help.ts';

    const { t } = useI18n();
    const router = useRouter();
    const auth = useAuthStore();
    const term = ref('');
    const categories = ref<HelpCategory[]>([]);
    const started = ref<HelpCard[]>([]);
    const recent = ref<HelpCard[]>([]);
    const canCreate = computed(() => auth.hasPermission('create help articles'));
    const canManage = computed(() => auth.hasPermission('update help articles'));

    function search(): void {
        void router.push({ name: 'admin-help-search', query: term.value.trim() ? { q: term.value.trim() } : {} });
    }

    onMounted(async () => {
        const response = await http.get<{ data: { categories: HelpCategory[]; getting_started: HelpCard[]; recent: HelpCard[] } }>('help');
        categories.value = response.data.data.categories ?? [];
        started.value = response.data.data.getting_started ?? [];
        recent.value = response.data.data.recent ?? [];
    });
</script>
