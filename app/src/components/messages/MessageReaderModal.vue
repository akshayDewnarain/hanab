<template>
    <section v-if="message" class="flex max-h-[80vh] flex-col bg-list-surface text-list-text">
        <header class="flex items-start justify-between gap-3 border-b border-list-border px-4 py-3">
            <div>
                <p class="m-0 text-xs font-semibold text-list-text-muted">{{ categoryLabel }}</p>
                <h2 class="m-0 mt-1 text-lg font-semibold">{{ message.title }}</h2>
                <p class="m-0 mt-1 text-xs text-list-text-muted">{{ meta }}</p>
            </div>
            <button
                type="button"
                class="inline-flex size-8 cursor-pointer items-center justify-center rounded-lg text-list-text-muted hover:bg-list-row-hover"
                :aria-label="t('MESSAGES_CLOSE')"
                @click="dismissModal"
            >
                <Icon icon="material-symbols:close-rounded" class="size-5" />
            </button>
        </header>
        <div class="overflow-y-auto px-4 py-3">
            <UiRichTextContent v-if="message.document" :document="message.document" />
            <p v-else class="m-0 text-sm leading-6 whitespace-pre-wrap">{{ message.text }}</p>
        </div>
        <footer v-if="message.status !== 'draft'" class="border-t border-list-border px-4 py-2.5">
            <UiReactionControl
                :options="reactionOptions"
                :model-value="selectedReaction"
                :counts="reactionCounts"
                default-id="like"
                :labels="reactionLabels"
                @select="onSelect"
            />
        </footer>
    </section>
</template>

<script lang="ts" setup>
    import { useMessages } from '@/composables/useMessages';
    import { useToastService } from '@/composables/useToastService';
    import { formatMessageDate, messageSource } from '@/modules/messages/messagePresentation';
    import { emojiForReactionId, MESSAGE_REACTIONS, reactionIdForEmoji } from '@/modules/messages/reactions';
    import { UiReactionControl } from '@freelance/vue-ui';
    import { readSeenMessageIds, rememberSeenMessageIds } from '@/modules/messages/seenMessages';
    import { UiRichTextContent } from '@freelance/vue-ui/editor';
    import { Icon } from '@iconify/vue';
    import { computed, inject, watch } from 'vue';
    import { useI18n } from 'vue-i18n';

    defineOptions({ name: 'MessageReaderModal' });

    const props = defineProps<{
        props: { id: string };
    }>();

    const dismissModal = inject<() => void>('dismissModal', () => undefined);
    const toast = useToastService();
    const { t, locale } = useI18n();
    const { feed, managed, draftItems, react } = useMessages();

    const message = computed(() => [...feed.value, ...managed.value, ...draftItems.value].find((item) => item.id === props.props.id));
    const categoryLabel = computed(() => {
        if (!message.value) return '';
        return message.value.category === 'company'
            ? t('MESSAGES_FILTER_COMPANY')
            : t(`MESSAGE_CATEGORY_${message.value.category.toUpperCase()}`);
    });

    watch(
        () => message.value?.id,
        (id) => {
            const current = message.value;
            if (!id || !current || current.status === 'draft') return;
            const seen = new Set(readSeenMessageIds() ?? []);
            if (seen.has(id)) return;
            seen.add(id);
            rememberSeenMessageIds(seen);
        },
        { immediate: true },
    );
    const meta = computed(() => {
        if (!message.value) return '';
        const date = formatMessageDate(message.value.publishedAt ?? message.value.updatedAt, locale.value);
        const source = messageSource(message.value, t);
        return date ? `${date} · ${source}` : source;
    });

    const reactionOptions = computed(() =>
        MESSAGE_REACTIONS.map((item) => ({ id: item.id, emoji: item.emoji, label: t(item.labelKey) })),
    );
    const reactionCounts = computed(() =>
        MESSAGE_REACTIONS.map((item) => ({
            id: item.id,
            count: message.value?.reactions.find((reaction) => reaction.emoji === item.emoji)?.count ?? 0,
        })),
    );
    const reactionTotal = computed(() => reactionCounts.value.reduce((sum, item) => sum + item.count, 0));
    const reactionLabels = computed(() => ({
        open: t('MESSAGE_REACTION_OPEN'),
        remove: t('MESSAGE_REACTION_REMOVE'),
        reactions: t('MESSAGE_REACTIONS_VIEW'),
        count: reactionTotal.value === 1 ? t('MESSAGE_REACTION_COUNT_ONE') : t('MESSAGE_REACTION_COUNT', { count: reactionTotal.value }),
    }));
    const selectedReaction = computed(() => reactionIdForEmoji(message.value?.reaction));

    async function onSelect(id: string | null): Promise<void> {
        if (!message.value) return;
        try {
            await react(message.value.id, emojiForReactionId(id));
        } catch {
            toast.error('MESSAGE_REACTION_FAILED');
        }
    }
</script>
