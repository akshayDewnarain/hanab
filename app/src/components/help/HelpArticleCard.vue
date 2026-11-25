<template>
    <router-link
        class="block rounded-xl border border-list-border bg-list-surface px-3 py-2.5 shadow-[0_1px_2px_color-mix(in_srgb,var(--color-list-shadow-tint)_6%,transparent)] transition-[border-color,background-color,box-shadow] duration-200 hover:border-[color-mix(in_srgb,var(--color-emphasis)_28%,var(--color-list-border))] hover:bg-list-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none"
        :to="{ name: 'admin-help-article', params: { slug: article.slug } }"
    >
        <UiKnowledgeGuideItem :icon="icon" :meta="meta" :title="article.title" />
        <p v-if="!compact && (article.snippet || article.summary)" class="m-0 mt-1 line-clamp-2 pl-6 text-sm leading-5 text-list-text-muted">
            {{ article.snippet || article.summary }}
        </p>
    </router-link>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { UiKnowledgeGuideItem } from '@freelance/vue-ui';
    import { helpIsRecent } from '@/modules/help/appearance';
    import type { HelpCard } from '@/modules/types/help/Help.ts';

    const props = defineProps<{ article: HelpCard; compact?: boolean; showCategory?: boolean }>();
    const { t, locale } = useI18n();

    const icon = computed(() => 'material-symbols:menu-book-outline-rounded');

    const meta = computed(() => {
        const parts: string[] = [];
        if (props.showCategory !== false && props.article.category?.name) parts.push(props.article.category.name);
        if (helpIsRecent(props.article.updated_at)) parts.push(t('HELP_RECENT_BADGE'));
        else if (props.article.updated_at) parts.push(formatted(props.article.updated_at));
        return parts.join(' · ');
    });

    function formatted(value: string): string {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat(locale.value === 'nl' ? 'nl-NL' : 'en-GB', { day: 'numeric', month: 'short' }).format(date);
    }
</script>
