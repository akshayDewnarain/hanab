<template>
    <PageLayout :label="article?.title || t('KNOWLEDGE_TITLE')" class="overflow-y-auto">
        <div class="mx-auto grid w-full max-w-5xl gap-6 lg:grid-cols-[minmax(0,42rem)_16rem]">
            <article>
                <router-link class="text-sm font-medium text-primary" :to="{ name: 'admin-knowledge' }">{{ t('KNOWLEDGE_BACK') }}</router-link>
                <p v-if="missing" class="mt-4 text-sm text-list-text">{{ t('KNOWLEDGE_UNAVAILABLE') }}</p>
                <template v-else-if="article">
                    <p v-if="article.category" class="mt-4 mb-0 text-sm text-list-text-muted">
                        <router-link class="text-primary" :to="{ name: 'admin-knowledge-category', params: { slug: article.category.slug } }">
                            {{ article.category.name }}
                        </router-link>
                    </p>
                    <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
                        <h1 class="m-0 text-3xl font-semibold tracking-tight text-list-text">{{ article.title }}</h1>
                        <router-link
                            v-if="article.can_edit"
                            class="inline-flex h-9 items-center rounded-lg border border-list-border px-3 text-sm font-medium"
                            :to="{ name: 'admin-knowledge-edit', params: { id: article.id } }"
                        >
                            {{ t('KNOWLEDGE_EDIT') }}
                        </router-link>
                    </div>
                    <p v-if="article.summary" class="mt-3 text-base text-list-text-muted">{{ article.summary }}</p>
                    <p class="mt-2 text-xs text-list-text-muted">
                        <span v-if="article.author">{{ article.author.name }} · </span>
                        {{ t('KNOWLEDGE_UPDATED', { date: updated }) }}
                    </p>
                    <div v-if="article.tags.length" class="mt-3 flex flex-wrap gap-2">
                        <router-link
                            v-for="tag in article.tags"
                            :key="tag.slug"
                            class="rounded-full border border-list-border px-2.5 py-0.5 text-xs font-medium text-list-text"
                            :to="{ name: 'admin-knowledge-search', query: { tag: tag.slug } }"
                        >
                            {{ tag.name }}
                        </router-link>
                    </div>
                    <UiRichTextContent class="knowledge-body mt-6 max-w-[42rem]" :document="article.content" />
                    <section v-if="article.attachments.length" class="mt-8">
                        <h2 class="m-0 text-sm font-semibold">{{ t('KNOWLEDGE_ATTACHMENTS') }}</h2>
                        <ul class="mt-2 list-none p-0">
                            <li v-for="file in article.attachments" :key="file.id">
                                <button type="button" class="cursor-pointer text-sm font-medium text-primary" @click="download(file.id, file.filename)">
                                    {{ file.filename }}
                                </button>
                            </li>
                        </ul>
                    </section>
                    <section class="mt-8">
                        <h2 class="m-0 text-sm font-semibold">{{ t('KNOWLEDGE_FEEDBACK') }}</h2>
                        <div class="mt-2 flex gap-2">
                            <button type="button" class="h-9 rounded-lg border border-list-border px-3 text-sm" :class="article.feedback.mine === true ? 'border-primary text-primary' : ''" @click="vote(true)">
                                {{ t('KNOWLEDGE_HELPFUL') }}
                            </button>
                            <button type="button" class="h-9 rounded-lg border border-list-border px-3 text-sm" :class="article.feedback.mine === false ? 'border-primary text-primary' : ''" @click="vote(false)">
                                {{ t('KNOWLEDGE_NOT_HELPFUL') }}
                            </button>
                        </div>
                    </section>
                    <section v-if="article.related.length" class="mt-8">
                        <h2 class="m-0 text-sm font-semibold">{{ t('KNOWLEDGE_RELATED') }}</h2>
                        <div class="mt-3 grid gap-3">
                            <KnowledgeArticleCard v-for="item in article.related" :key="item.id" :article="item" />
                        </div>
                    </section>
                    <section v-if="article.colleagues.length" class="mt-8">
                        <h2 class="m-0 text-sm font-semibold">{{ t('KNOWLEDGE_COLLEAGUES') }}</h2>
                        <ul class="mt-2 list-none p-0">
                            <li v-for="person in article.colleagues" :key="person.id">
                                <router-link class="text-sm font-medium text-primary" :to="{ name: 'admin-employees-detail', params: { id: person.id } }">
                                    {{ person.name }}
                                </router-link>
                                <span v-if="person.role" class="text-sm text-list-text-muted"> · {{ person.role }}</span>
                            </li>
                        </ul>
                    </section>
                </template>
            </article>
            <aside v-if="headings.length" class="lg:pt-16">
                <h2 class="m-0 text-xs font-semibold tracking-wide text-list-text-muted uppercase">{{ t('KNOWLEDGE_CONTENTS') }}</h2>
                <ul class="mt-2 list-none p-0">
                    <li v-for="heading in headings" :key="heading.id" :class="heading.level === 3 ? 'pl-3' : ''">
                        <a class="text-sm text-list-text hover:text-primary" :href="`#${heading.id}`">{{ heading.text }}</a>
                    </li>
                </ul>
            </aside>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { computed, ref, watch } from 'vue';
    import { useRoute } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { PageLayout } from '@freelance/vue-ui';
    import { UiRichTextContent, richTextHeadings } from '@freelance/vue-ui/editor';
    import http from '@/utils/http';
    import KnowledgeArticleCard from '@/components/knowledge/KnowledgeArticleCard.vue';
    import type { KnowledgeArticle } from '@/modules/types/knowledge/Knowledge.ts';

    const { t, locale } = useI18n();
    const route = useRoute();
    const article = ref<KnowledgeArticle | null>(null);
    const missing = ref(false);
    const headings = computed(() => (article.value ? richTextHeadings(article.value.content) : []));
    const updated = computed(() => {
        const value = article.value?.updated_at;
        if (!value) return '';
        return new Date(value).toLocaleDateString(locale.value);
    });

    async function load(): Promise<void> {
        missing.value = false;
        article.value = null;
        try {
            const response = await http.get<{ data: KnowledgeArticle }>(`knowledge/articles/slug/${route.params.slug}`);
            article.value = response.data.data;
        } catch {
            missing.value = true;
        }
    }

    async function vote(helpful: boolean): Promise<void> {
        if (!article.value) return;
        await http.post(`knowledge/articles/${article.value.id}/feedback`, { helpful });
        article.value.feedback.mine = helpful;
    }

    async function download(id: number, filename: string): Promise<void> {
        const response = await http.get(`knowledge/attachments/${id}`, { responseType: 'blob' });
        const url = URL.createObjectURL(response.data);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();
        URL.revokeObjectURL(url);
    }

    watch(() => route.params.slug, () => void load(), { immediate: true });
</script>
