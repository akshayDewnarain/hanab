<template>
    <router-link
        class="flex h-full flex-col rounded-xl border border-list-border bg-list-surface p-4 shadow-sm transition-colors duration-200 hover:border-primary motion-reduce:transition-none"
        :to="{ name: 'admin-knowledge-article', params: { slug: article.slug } }"
    >
        <p v-if="article.category" class="m-0 text-xs font-medium text-list-text-muted">{{ article.category.name }}</p>
        <h3 class="m-0 mt-1 text-base font-semibold text-list-text">{{ article.title }}</h3>
        <p v-if="article.summary || article.snippet" class="m-0 mt-2 line-clamp-3 text-sm leading-6 text-list-text-muted">
            {{ article.summary || article.snippet }}
        </p>
        <p class="m-0 mt-auto pt-3 text-xs text-list-text-muted">{{ article.author?.name }}</p>
    </router-link>
</template>

<script lang="ts" setup>
    import type { KnowledgeCard } from '@/modules/types/knowledge/Knowledge.ts';

    defineProps<{ article: KnowledgeCard }>();
</script>
