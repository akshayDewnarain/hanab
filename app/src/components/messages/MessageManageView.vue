<template>
    <section class="grid gap-4">
        <div class="flex flex-wrap gap-2" role="tablist" :aria-label="t('MESSAGES_MANAGE')">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="h-9 cursor-pointer rounded-full px-4 text-sm font-semibold"
                :class="status === tab.id ? 'bg-list-text text-list-surface' : 'border border-list-border bg-list-surface text-list-text'"
                :aria-selected="status === tab.id"
                role="tab"
                @click="status = tab.id"
            >
                {{ tab.label }}
            </button>
        </div>

        <p v-if="failed" role="alert" class="text-sm">{{ t('MESSAGES_ERROR') }}</p>
        <p v-else-if="rows.length === 0" role="status" class="rounded-xl border border-list-border bg-list-surface px-4 py-8 text-center text-sm text-list-text-muted">
            {{ t('MESSAGE_MANAGE_EMPTY') }}
        </p>
        <div v-else class="overflow-hidden rounded-xl border border-list-border bg-list-surface">
            <div
                class="hidden gap-3 border-b border-list-border px-4 py-2 text-xs font-semibold text-list-text-muted md:grid md:grid-cols-[minmax(0,1.4fr)_0.7fr_0.6fr_0.8fr_0.8fr_auto]"
            >
                <span>{{ t('MESSAGES_COLUMN_TITLE') }}</span>
                <span>{{ t('MESSAGES_COLUMN_CATEGORY') }}</span>
                <span>{{ t('MESSAGES_COLUMN_STATUS') }}</span>
                <span>{{ t('MESSAGES_COLUMN_UPDATED') }}</span>
                <span>{{ t('MESSAGES_COLUMN_AUTHOR') }}</span>
                <span>{{ t('MESSAGES_COLUMN_ACTIONS') }}</span>
            </div>
            <ul class="m-0 list-none p-0">
                <li
                    v-for="message in rows"
                    :key="message.id"
                    class="grid gap-2 border-b border-list-border px-4 py-3 text-sm last:border-b-0 md:grid-cols-[minmax(0,1.4fr)_0.7fr_0.6fr_0.8fr_0.8fr_auto] md:items-center"
                >
                    <p class="m-0 truncate font-semibold">{{ message.title }}</p>
                    <p class="m-0 text-list-text-muted">{{ t(`MESSAGE_CATEGORY_${message.category.toUpperCase()}`) }}</p>
                    <p class="m-0">{{ t(message.status === 'draft' ? 'MESSAGE_STATUS_DRAFT' : 'MESSAGE_STATUS_PUBLISHED') }}</p>
                    <p class="m-0 text-list-text-muted">{{ formatMessageDate(message.updatedAt ?? message.publishedAt, locale) }}</p>
                    <p class="m-0 truncate text-list-text-muted">{{ message.authorName || '—' }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="cursor-pointer font-semibold" @click="emit('view', message)">{{ t('MESSAGE_ACTION_VIEW') }}</button>
                        <button
                            v-if="message.type === 'contract' && canUpdate"
                            type="button"
                            class="cursor-pointer font-semibold"
                            @click="emit('edit', message)"
                        >
                            {{ t('MESSAGE_ACTION_EDIT') }}
                        </button>
                        <button
                            v-if="message.status === 'draft' && canPublish"
                            type="button"
                            class="cursor-pointer font-semibold"
                            @click="emit('publish', message)"
                        >
                            {{ t('MESSAGE_PUBLISH') }}
                        </button>
                        <button
                            v-if="message.status === 'draft' && canDelete"
                            type="button"
                            class="cursor-pointer font-semibold text-red-600"
                            @click="emit('remove', message)"
                        >
                            {{ t('MESSAGE_DELETE') }}
                        </button>
                    </div>
                </li>
            </ul>
        </div>
    </section>
</template>

<script lang="ts" setup>
    import type { FeedMessage } from '@/composables/useMessages';
    import { formatMessageDate } from '@/modules/messages/messagePresentation';
    import { computed, ref } from 'vue';
    import { useI18n } from 'vue-i18n';

    defineOptions({ name: 'MessageManageView' });

    const props = defineProps<{
        drafts: FeedMessage[];
        published: FeedMessage[];
        canPublish: boolean;
        canUpdate: boolean;
        canDelete: boolean;
        failed: boolean;
    }>();

    const emit = defineEmits<{
        view: [message: FeedMessage];
        edit: [message: FeedMessage];
        publish: [message: FeedMessage];
        remove: [message: FeedMessage];
    }>();

    const { t, locale } = useI18n();
    const status = ref<'draft' | 'published'>('draft');
    const tabs = computed(() => [
        { id: 'draft' as const, label: t('MESSAGES_DRAFTS') },
        { id: 'published' as const, label: t('MESSAGE_STATUS_PUBLISHED') },
    ]);
    const rows = computed(() => (status.value === 'draft' ? props.drafts : props.published));
</script>
