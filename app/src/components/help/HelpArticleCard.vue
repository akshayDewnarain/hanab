<template>
    <router-link
        class="border border-list-border bg-list-surface shadow-sm transition-colors duration-200 hover:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none"
        :class="compact ? 'flex items-center gap-3 rounded-xl px-3 py-2.5' : 'block rounded-xl p-4'"
        :to="{ name: 'admin-help-article', params: { slug: article.slug } }"
    >
        <span
            v-if="compact"
            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-light text-primary"
            aria-hidden="true"
        >
            <Icon icon="material-symbols:menu-book-outline-rounded" class="size-5" />
        </span>
        <span class="min-w-0" :class="compact ? 'flex-1' : 'block'">
            <span v-if="article.category && !compact" class="m-0 block text-xs font-medium text-list-text-muted">{{ article.category.name }}</span>
            <span class="m-0 block truncate font-semibold text-list-text" :class="compact ? 'text-sm' : 'mt-1 text-base'">{{ article.title }}</span>
            <span v-if="compact" class="m-0 block truncate text-xs text-list-text-muted">{{ compactMeta }}</span>
            <span v-else-if="article.snippet || article.summary" class="m-0 mt-2 block text-sm text-list-text-muted">{{ article.snippet || article.summary }}</span>
        </span>
        <Icon v-if="compact" icon="material-symbols:chevron-right-rounded" class="size-5 shrink-0 text-list-text-muted" aria-hidden="true" />
    </router-link>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { Icon } from '@iconify/vue';
    import type { HelpCard } from '@/modules/types/help/Help.ts';

    const props = defineProps<{ article: HelpCard; compact?: boolean }>();

    const compactMeta = computed(() => {
        const summary = props.article.summary || props.article.snippet || '';
        return [props.article.category?.name, summary].filter(Boolean).join(' · ');
    });
</script>
