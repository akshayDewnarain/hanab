<template>
    <PageLayout :label="t('HELP_MANAGE')" class="overflow-y-auto">
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="m-0 text-2xl font-semibold">{{ t('HELP_MANAGE') }}</h1>
                <router-link class="inline-flex h-9 items-center rounded-lg bg-primary px-3 text-sm font-semibold text-white" :to="{ name: 'admin-help-create' }">
                    {{ t('HELP_NEW') }}
                </router-link>
            </div>
            <label class="text-sm">
                <span class="text-list-text-muted">{{ t('HELP_FILTER_STATUS') }}</span>
                <select v-model="status" class="mt-1 h-9 rounded-lg border border-list-border px-2" @change="load">
                    <option value="">{{ t('HELP_FILTER_STATUS') }}</option>
                    <option value="draft">{{ t('HELP_STATUS_DRAFT') }}</option>
                    <option value="published">{{ t('HELP_STATUS_PUBLISHED') }}</option>
                    <option value="archived">{{ t('HELP_STATUS_ARCHIVED') }}</option>
                </select>
            </label>
            <div class="overflow-hidden rounded-xl border border-list-border bg-list-surface">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-list-border text-list-text-muted">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('HELP_ARTICLE_TITLE') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('HELP_CATEGORY') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('HELP_FILTER_STATUS') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('HELP_CONTEXT') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="article in articles" :key="article.id" class="border-b border-list-border last:border-0">
                            <td class="px-4 py-3">
                                <router-link class="font-medium text-primary" :to="{ name: 'admin-help-edit', params: { id: article.id } }">{{ article.title }}</router-link>
                            </td>
                            <td class="px-4 py-3">{{ article.category?.name }}</td>
                            <td class="px-4 py-3">{{ t(statusKey(article.status)) }}</td>
                            <td class="px-4 py-3">{{ article.context_key }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { PageLayout } from '@freelance/vue-ui';
    import http from '@/utils/http';
    import type { HelpCard } from '@/modules/types/help/Help.ts';

    const { t } = useI18n();
    const status = ref('');
    const articles = ref<HelpCard[]>([]);

    function statusKey(value: string): string {
        if (value === 'published') return 'HELP_STATUS_PUBLISHED';
        if (value === 'archived') return 'HELP_STATUS_ARCHIVED';
        return 'HELP_STATUS_DRAFT';
    }

    async function load(): Promise<void> {
        const response = await http.get<{ data: HelpCard[] }>('help/articles', { params: { status: status.value || undefined } });
        articles.value = response.data.data ?? [];
    }

    onMounted(load);
</script>
