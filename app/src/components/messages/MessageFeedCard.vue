<template>
    <article class="flex h-full flex-col rounded-xl border border-list-border bg-list-surface p-4 text-list-text">
        <header class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-list-canvas text-list-text">
                    <Icon :icon="messageIcon(message.type)" class="size-5" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                    <h2 class="m-0 text-sm font-semibold">{{ t(messageKickerKey(message.type)) }}</h2>
                    <p class="m-0 mt-0.5 text-xs text-list-text-muted">{{ meta }}</p>
                </div>
            </div>
            <span class="shrink-0 rounded-full bg-list-canvas px-2.5 py-1 text-xs font-medium text-list-text-muted">
                {{ t(`MESSAGE_CATEGORY_${message.category.toUpperCase()}`) }}
            </span>
        </header>
        <h3 class="m-0 mt-4 text-base font-semibold">{{ message.title }}</h3>
        <p class="mt-1 line-clamp-3 text-sm text-list-text-muted">{{ message.text }}</p>
        <footer class="mt-4 flex items-center justify-between gap-3 border-t border-list-border pt-3">
            <div class="flex flex-wrap gap-1" role="group" :aria-label="t('MESSAGE_REACTIONS')">
                <button
                    v-for="choice in choices"
                    :key="choice.id"
                    type="button"
                    class="inline-flex min-h-8 cursor-pointer items-center gap-1 rounded-full px-2 text-sm"
                    :class="message.reaction === choice.id ? 'bg-primary/10 font-semibold' : 'hover:bg-list-row-hover'"
                    :aria-pressed="message.reaction === choice.id"
                    :aria-label="message.reaction === choice.id ? t('MESSAGE_REACTION_REMOVE') : choice.label"
                    @click="emit('react', message.id, message.reaction === choice.id ? null : choice.id)"
                >
                    <span aria-hidden="true">{{ choice.id }}</span>
                    <span v-if="countFor(choice.id)" class="text-xs text-list-text-muted">{{ countFor(choice.id) }}</span>
                </button>
            </div>
            <button type="button" class="cursor-pointer text-sm font-semibold text-list-text" @click="emit('open', message)">
                {{ t('MESSAGES_READ_MORE') }}
            </button>
        </footer>
    </article>
</template>

<script lang="ts" setup>
    import type { FeedMessage } from '@/composables/useMessages';
    import { formatMessageDate, messageIcon, messageKickerKey, messageSource } from '@/modules/messages/messagePresentation';
    import { Icon } from '@iconify/vue';
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';

    defineOptions({ name: 'MessageFeedCard' });

    const props = defineProps<{
        message: FeedMessage;
        choices: { id: string; label: string }[];
    }>();

    const emit = defineEmits<{
        react: [id: string, emoji: string | null];
        open: [message: FeedMessage];
    }>();

    const { t, locale } = useI18n();
    const meta = computed(() => {
        const date = formatMessageDate(props.message.publishedAt ?? props.message.updatedAt, locale.value);
        const source = messageSource(props.message, t);
        return date ? `${date} · ${source}` : source;
    });

    function countFor(emoji: string): number {
        return props.message.reactions.find((item) => item.emoji === emoji)?.count ?? 0;
    }
</script>
