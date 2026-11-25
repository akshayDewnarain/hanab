<template>
    <PageLayout :label="t('KNOWLEDGE_MANAGE')" class="overflow-y-auto">
        <div class="mx-auto flex w-full max-w-[84rem] flex-col gap-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="m-0 text-2xl font-semibold">{{ t('KNOWLEDGE_MANAGE') }}</h1>
                <router-link class="inline-flex h-9 items-center rounded-lg bg-primary px-3 text-sm font-semibold text-white" :to="{ name: 'admin-knowledge-create' }">
                    {{ t('KNOWLEDGE_NEW_ARTICLE') }}
                </router-link>
            </div>
            <div class="flex flex-wrap gap-2">
                <ChoiceDropdown
                    v-model="status"
                    class="max-w-xs"
                    :options="[
                        { value: '', label: t('KNOWLEDGE_FILTER_STATUS') },
                        { value: 'draft', label: t('KNOWLEDGE_STATUS_DRAFT') },
                        { value: 'published', label: t('KNOWLEDGE_STATUS_PUBLISHED') },
                        { value: 'archived', label: t('KNOWLEDGE_STATUS_ARCHIVED') },
                    ]"
                    @update:model-value="load"
                />
            </div>
            <section class="rounded-xl border border-list-border bg-list-surface">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-list-text-muted">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('KNOWLEDGE_ARTICLE_TITLE') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('KNOWLEDGE_CATEGORY') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('KNOWLEDGE_FILTER_STATUS') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('KNOWLEDGE_VISIBILITY') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('KNOWLEDGE_AUTHOR') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="article in articles" :key="article.id" class="border-t border-list-border">
                            <td class="px-4 py-3">
                                <router-link class="font-medium text-primary" :to="{ name: 'admin-knowledge-edit', params: { id: article.id } }">
                                    {{ article.title }}
                                </router-link>
                            </td>
                            <td class="px-4 py-3">{{ article.category?.name || '—' }}</td>
                            <td class="px-4 py-3">{{ t(statusKey(article.status)) }}</td>
                            <td class="px-4 py-3">{{ article.visibility === 'private' ? t('KNOWLEDGE_VISIBILITY_PRIVATE') : t('KNOWLEDGE_VISIBILITY_AUTHENTICATED') }}</td>
                            <td class="px-4 py-3">{{ article.author?.name }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
            <section v-if="canManageCategories" class="rounded-xl border border-list-border bg-list-surface p-4">
                <h2 class="m-0 text-sm font-semibold">{{ t('KNOWLEDGE_CATEGORY') }}</h2>
                <form class="mt-3 grid gap-2 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_8rem_6rem_auto_auto]" @submit.prevent="createCategory">
                    <input v-model="draft.name" required class="h-9 rounded-lg border border-list-border px-3 text-sm" :placeholder="t('KNOWLEDGE_CATEGORY')" :aria-label="t('KNOWLEDGE_CATEGORY')" />
                    <ChoiceDropdown v-model="draft.parent_id" :aria-label="t('KNOWLEDGE_PARENT')" :options="parentOptions()" />
                    <input v-model="draft.icon" class="h-9 rounded-lg border border-list-border px-3 text-sm" :placeholder="t('KNOWLEDGE_ICON')" :aria-label="t('KNOWLEDGE_ICON')" />
                    <input v-model.number="draft.sort_order" type="number" min="0" class="h-9 rounded-lg border border-list-border px-3 text-sm" :aria-label="t('KNOWLEDGE_SORT')" />
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="draft.is_active" type="checkbox" />
                        {{ t('KNOWLEDGE_ACTIVE') }}
                    </label>
                    <button type="submit" class="h-9 rounded-lg border border-list-border px-3 text-sm">{{ t('KNOWLEDGE_ADD_CATEGORY') }}</button>
                </form>
                <ul class="mt-4 list-none p-0">
                    <li v-for="category in categories" :key="category.id" class="grid items-center gap-2 border-t border-list-border py-2 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_8rem_6rem_auto_auto]">
                        <input v-model="category.name" class="h-9 rounded-lg border border-list-border px-3 text-sm" :aria-label="t('KNOWLEDGE_CATEGORY')" />
                        <ChoiceDropdown v-model="category.parent_id" :aria-label="t('KNOWLEDGE_PARENT')" :options="parentOptions(category.id)" />
                        <input v-model="category.icon" class="h-9 rounded-lg border border-list-border px-3 text-sm" :aria-label="t('KNOWLEDGE_ICON')" />
                        <input v-model.number="category.sort_order" type="number" min="0" class="h-9 rounded-lg border border-list-border px-3 text-sm" :aria-label="t('KNOWLEDGE_SORT')" />
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="category.is_active" type="checkbox" />
                            {{ t('KNOWLEDGE_ACTIVE') }}
                        </label>
                        <button type="button" class="h-9 rounded-lg border border-list-border px-3 text-sm" @click="saveCategory(category)">{{ t('KNOWLEDGE_SAVE_CATEGORY') }}</button>
                    </li>
                </ul>
            </section>
            <section v-if="maintenance" class="grid gap-4 md:grid-cols-3">
                <div>
                    <h2 class="text-sm font-semibold">{{ t('KNOWLEDGE_OVERDUE') }}</h2>
                    <p v-if="!maintenance.overdue.length" class="text-sm text-list-text-muted">{{ t('KNOWLEDGE_NONE') }}</p>
                    <ul v-else class="list-none p-0 text-sm">
                        <li v-for="article in maintenance.overdue" :key="article.id">
                            <router-link class="text-primary" :to="{ name: 'admin-knowledge-edit', params: { id: article.id } }">{{ article.title }}</router-link>
                        </li>
                    </ul>
                </div>
                <div>
                    <h2 class="text-sm font-semibold">{{ t('KNOWLEDGE_DRAFTS') }}</h2>
                    <p v-if="!maintenance.drafts.length" class="text-sm text-list-text-muted">{{ t('KNOWLEDGE_NONE') }}</p>
                    <ul v-else class="list-none p-0 text-sm">
                        <li v-for="article in maintenance.drafts" :key="article.id">
                            <router-link class="text-primary" :to="{ name: 'admin-knowledge-edit', params: { id: article.id } }">{{ article.title }}</router-link>
                        </li>
                    </ul>
                </div>
                <div>
                    <h2 class="text-sm font-semibold">{{ t('KNOWLEDGE_NEGATIVE') }}</h2>
                    <p v-if="!maintenance.negative.length" class="text-sm text-list-text-muted">{{ t('KNOWLEDGE_NONE') }}</p>
                    <ul v-else class="list-none p-0 text-sm">
                        <li v-for="article in maintenance.negative" :key="article.id">
                            <router-link class="text-primary" :to="{ name: 'admin-knowledge-edit', params: { id: article.id } }">{{ article.title }}</router-link>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { PageLayout } from '@freelance/vue-ui';
    import ChoiceDropdown from '@/components/inputs/ChoiceDropdown.vue';
    import http from '@/utils/http';
    import { useAuthStore } from '@/stores/auth';
    import type { KnowledgeCard, KnowledgeCategory } from '@/modules/types/knowledge/Knowledge.ts';

    const { t } = useI18n();
    const auth = useAuthStore();
    const status = ref('');
    const articles = ref<KnowledgeCard[]>([]);
    const categories = ref<KnowledgeCategory[]>([]);
    const maintenance = ref<{ overdue: KnowledgeCard[]; drafts: KnowledgeCard[]; negative: KnowledgeCard[] } | null>(null);
    const canManageCategories = computed(() => auth.hasPermission('manage knowledge categories'));
    const draft = ref({ name: '', parent_id: null as number | null, icon: '', sort_order: 0, is_active: true });

    function parentOptions(excludeId?: number) {
        return [
            { value: null, label: t('KNOWLEDGE_PARENT') },
            ...categories.value
                .filter((category) => category.id !== excludeId)
                .map((category) => ({ value: category.id, label: category.name })),
        ];
    }

    function statusKey(value: string): string {
        if (value === 'published') return 'KNOWLEDGE_STATUS_PUBLISHED';
        if (value === 'archived') return 'KNOWLEDGE_STATUS_ARCHIVED';
        return 'KNOWLEDGE_STATUS_DRAFT';
    }

    function categoryPayload(category: { name: string; parent_id: number | null; icon: string | null; sort_order: number; is_active?: boolean }) {
        const icon = category.icon?.trim() ?? '';
        return {
            name: category.name,
            parent_id: category.parent_id,
            icon: icon === '' ? null : icon,
            sort_order: Number(category.sort_order) || 0,
            is_active: category.is_active !== false,
        };
    }

    async function loadCategories(): Promise<void> {
        if (!canManageCategories.value) return;
        const response = await http.get<{ data: KnowledgeCategory[] }>('knowledge/categories');
        categories.value = response.data.data ?? [];
    }

    async function createCategory(): Promise<void> {
        await http.post('knowledge/categories', categoryPayload(draft.value));
        draft.value = { name: '', parent_id: null, icon: '', sort_order: 0, is_active: true };
        await loadCategories();
    }

    async function saveCategory(category: KnowledgeCategory): Promise<void> {
        await http.put(`knowledge/categories/${category.id}`, categoryPayload(category));
        await loadCategories();
    }

    async function load(): Promise<void> {
        const response = await http.get<{ data: KnowledgeCard[] }>('knowledge/articles', { params: { status: status.value || undefined } });
        articles.value = response.data.data ?? [];
    }

    onMounted(async () => {
        await load();
        const response = await http.get<{ data: { overdue: KnowledgeCard[]; drafts: KnowledgeCard[]; negative: KnowledgeCard[] } }>('knowledge/maintenance');
        maintenance.value = response.data.data;
        await loadCategories();
    });
</script>
