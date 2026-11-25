<template>
    <article
        class="flex h-full flex-col rounded-xl border bg-list-surface p-4 text-list-text shadow-sm transition-[border-color,box-shadow] duration-200 ease-out motion-reduce:transition-none hover:shadow-md"
        :class="unread ? 'border-primary/35' : 'border-list-border'"
    >
        <header class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-primary-light text-emphasis">
                    <Icon :icon="messageIcon(message.type)" class="size-5" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                    <h2 class="m-0 flex items-center gap-2 text-base font-semibold">
                        <span class="truncate">{{ message.title }}</span>
                        <span v-if="unread" class="size-2 shrink-0 rounded-full bg-primary" :title="t('MESSAGES_NEW_BADGE')">
                            <span class="sr-only">{{ t('MESSAGES_NEW_BADGE') }}</span>
                        </span>
                    </h2>
                    <p class="m-0 mt-0.5 text-xs text-list-text-muted">{{ meta }}</p>
                </div>
            </div>
            <span class="shrink-0 rounded-full bg-list-canvas px-2.5 py-1 text-xs font-medium text-list-text-muted">
                {{ categoryLabel }}
            </span>
        </header>
        <p class="mt-3 line-clamp-3 flex-1 text-sm leading-6 text-list-text">{{ message.text }}</p>
        <footer class="mt-4 flex items-center justify-between gap-3 border-t border-list-border pt-3">
            <UiReactionControl
                :options="reactionOptions"
                :model-value="selectedReaction"
                :counts="reactionCounts"
                default-id="like"
                :labels="reactionLabels"
                @select="onSelect"
            />
            <button type="button" class="cursor-pointer text-sm font-semibold text-primary hover:underline" @click="emit('open', message)">
                {{ t('MESSAGES_READ_MORE') }}
            </button>
        </footer>
    </article>
</template>

<script lang="ts" setup>
    import type { FeedMessage } from '@/composables/useMessages';
    import { formatMessageDate, messageIcon, messageSource } from '@/modules/messages/messagePresentation';
    import { emojiForReactionId, MESSAGE_REACTIONS, reactionIdForEmoji } from '@/modules/messages/reactions';
    import { UiReactionControl } from '@freelance/vue-ui';
    import { Icon } from '@iconify/vue';
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';

    defineOptions({ name: 'MessageFeedCard' });

    const props = defineProps<{
        message: FeedMessage;
        unread?: boolean;
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
    const categoryLabel = computed(() =>
        props.message.category === 'company' ? t('MESSAGES_FILTER_COMPANY') : t(`MESSAGE_CATEGORY_${props.message.category.toUpperCase()}`),
    );
    const reactionOptions = computed(() =>
        MESSAGE_REACTIONS.map((item) => ({ id: item.id, emoji: item.emoji, label: t(item.labelKey) })),
    );
    const reactionCounts = computed(() =>
        MESSAGE_REACTIONS.map((item) => ({
            id: item.id,
            count: props.message.reactions.find((reaction) => reaction.emoji === item.emoji)?.count ?? 0,
        })),
    );
    const reactionTotal = computed(() => reactionCounts.value.reduce((sum, item) => sum + item.count, 0));
    const reactionLabels = computed(() => ({
        open: t('MESSAGE_REACTION_OPEN'),
        remove: t('MESSAGE_REACTION_REMOVE'),
        reactions: t('MESSAGE_REACTIONS_VIEW'),
        count: reactionTotal.value === 1 ? t('MESSAGE_REACTION_COUNT_ONE') : t('MESSAGE_REACTION_COUNT', { count: reactionTotal.value }),
    }));
    const selectedReaction = computed(() => reactionIdForEmoji(props.message.reaction));

    function onSelect(id: string | null): void {
        emit('react', props.message.id, emojiForReactionId(id));
    }
</script>
