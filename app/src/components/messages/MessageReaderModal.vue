<template>
    <section v-if="message" class="flex max-h-[80vh] flex-col bg-list-surface text-list-text">
        <header class="flex items-start justify-between gap-3 border-b border-list-border px-4 py-3">
            <div>
                <p class="m-0 text-xs font-semibold uppercase tracking-wide text-list-text-muted">
                    {{ t(messageKickerKey(message.type)) }}
                </p>
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
        <footer
            v-if="message.status !== 'draft'"
            class="flex flex-wrap gap-1 border-t border-list-border px-4 py-2.5"
            role="group"
            :aria-label="t('MESSAGE_REACTIONS')"
        >
            <button
                v-for="choice in choices"
                :key="choice.id"
                type="button"
                class="inline-flex min-h-8 cursor-pointer items-center gap-1 rounded-full px-2 text-sm"
                :class="message.reaction === choice.id ? 'bg-primary/10 font-semibold' : 'hover:bg-list-row-hover'"
                :aria-pressed="message.reaction === choice.id"
                :aria-label="message.reaction === choice.id ? t('MESSAGE_REACTION_REMOVE') : choice.label"
                @click="react(message.id, message.reaction === choice.id ? null : choice.id)"
            >
                <span aria-hidden="true">{{ choice.id }}</span>
                <span class="text-xs text-list-text-muted">{{ countFor(choice.id) }}</span>
            </button>
        </footer>
    </section>
</template>

<script lang="ts" setup>
    import type { FeedMessage } from '@/composables/useMessages';
    import { useMessages } from '@/composables/useMessages';
    import { formatMessageDate, messageKickerKey, messageSource } from '@/modules/messages/messagePresentation';
    import { UiRichTextContent } from '@freelance/vue-ui/editor';
    import { Icon } from '@iconify/vue';
    import { computed, inject } from 'vue';
    import { useI18n } from 'vue-i18n';

    defineOptions({ name: 'MessageReaderModal' });

    const props = defineProps<{
        props: { id: string };
    }>();

    const dismissModal = inject<() => void>('dismissModal', () => undefined);
    const { t, locale } = useI18n();
    const { feed, managed, draftItems, react } = useMessages();
    const choices = [
        { id: '👍', label: '👍' },
        { id: '🎉', label: '🎉' },
        { id: '👏', label: '👏' },
    ];

    const message = computed(() => [...feed.value, ...managed.value, ...draftItems.value].find((item) => item.id === props.props.id));
    const meta = computed(() => {
        if (!message.value) return '';
        const date = formatMessageDate(message.value.publishedAt ?? message.value.updatedAt, locale.value);
        const source = messageSource(message.value, t);
        return date ? `${date} · ${source}` : source;
    });

    function countFor(emoji: string): number {
        return message.value?.reactions.find((item) => item.emoji === emoji)?.count ?? 0;
    }
</script>
