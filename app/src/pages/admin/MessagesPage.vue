<template>
    <PageLayout class="h-full" :label="t('MESSAGES_TITLE')">
        <div class="flex h-full min-h-0 flex-col gap-4 overflow-auto">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <Icon icon="material-symbols:article-outline" class="size-6 text-list-text" aria-hidden="true" />
                    <h1 class="text-lg font-semibold text-list-text">{{ t('MESSAGES_TITLE') }}</h1>
                    <HelpTrigger context="messages.compose" />
                    <span
                        v-if="unreadCount > 0"
                        class="rounded-full bg-primary px-2 py-0.5 text-xs font-semibold text-on-brand"
                    >
                        {{ t('MESSAGES_UNREAD', { count: unreadCount }) }}
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="managing"
                        type="button"
                        class="inline-flex h-10 cursor-pointer items-center rounded-full border border-list-border bg-list-surface px-4 text-sm font-semibold text-list-text"
                        @click="managing = false"
                    >
                        {{ t('MESSAGES_BACK') }}
                    </button>
                    <button
                        v-else-if="canManage"
                        type="button"
                        class="inline-flex h-10 cursor-pointer items-center rounded-full border border-list-border bg-list-surface px-4 text-sm font-semibold text-list-text"
                        @click="openManage"
                    >
                        {{ t('MESSAGES_MANAGE') }}
                    </button>
                    <button
                        v-if="canCreate"
                        type="button"
                        class="inline-flex h-10 cursor-pointer items-center gap-1 rounded-full bg-primary px-4 text-sm font-semibold text-on-brand"
                        @click="openCompose(null)"
                    >
                        <Icon icon="material-symbols:add" class="size-4" />
                        {{ t('MESSAGES_NEW') }}
                    </button>
                </div>
            </header>

            <MessageManageView
                v-if="managing"
                :drafts="draftItems"
                :published="managed"
                :can-publish="canPublish"
                :can-update="canUpdate"
                :can-delete="canDelete"
                :failed="manageFailed"
                @view="openReader"
                @edit="openCompose"
                @publish="confirmPublish"
                @remove="confirmDelete"
            />

            <template v-else>
                <div class="flex flex-wrap gap-2" role="tablist" :aria-label="t('MESSAGES_TITLE')">
                    <button
                        v-for="filter in filters"
                        :key="filter.id"
                        type="button"
                        class="h-9 cursor-pointer rounded-full px-4 text-sm font-semibold"
                        :class="category === filter.id ? 'bg-list-text text-list-surface' : 'border border-list-border bg-list-surface text-list-text'"
                        :aria-selected="category === filter.id"
                        role="tab"
                        @click="selectCategory(filter.id)"
                    >
                        {{ filter.label }}
                    </button>
                </div>

                <section class="rounded-xl border border-list-border bg-list-surface px-4 py-3">
                    <h2 class="m-0 text-sm font-semibold text-list-text">{{ t('MESSAGES_PREFERENCES') }}</h2>
                    <p class="m-0 mt-1 text-sm text-list-text-muted">{{ t('MESSAGES_PUSH_NOTIFICATIONS_HINT') }}</p>
                    <div class="mt-3 flex flex-wrap gap-4">
                        <label v-for="item in preferenceItems" :key="item.key" class="flex items-center gap-2 text-sm text-list-text">
                            <ToggleSwitch
                                :model-value="item.value"
                                :disabled="item.locked"
                                :label="item.label"
                                @update:model-value="item.save($event)"
                            />
                            <span>{{ item.label }}</span>
                        </label>
                    </div>
                </section>

                <p v-if="isLoading && feed.length === 0" role="status" class="text-sm text-list-text-muted">{{ t('MESSAGES_LOADING') }}</p>
                <p v-else-if="failed" role="alert" class="flex items-center gap-3 text-sm text-list-text">
                    {{ t('MESSAGES_ERROR') }}
                    <button type="button" class="cursor-pointer font-semibold underline" @click="load('feed')">{{ t('MESSAGES_RETRY') }}</button>
                </p>
                <p
                    v-else-if="filtered.length === 0"
                    role="status"
                    class="rounded-xl border border-list-border bg-list-surface px-4 py-10 text-center text-sm text-list-text-muted"
                >
                    {{ category === 'all' ? t('MESSAGES_EMPTY') : t('MESSAGES_EMPTY_FILTER') }}
                </p>
                <template v-else>
                    <div class="grid grid-cols-1 items-start gap-3 md:grid-cols-2">
                        <MessageFeedCard
                            v-for="message in visible"
                            :key="message.id"
                            :message="message"
                            :choices="reactionChoices"
                            :class="message.category === 'company' ? 'md:col-span-2' : ''"
                            @react="react"
                            @open="openReader"
                        />
                    </div>
                    <button
                        v-if="visible.length < filtered.length"
                        type="button"
                        class="h-10 cursor-pointer self-center rounded-full border border-list-border bg-list-surface px-4 text-sm font-semibold text-list-text"
                        @click="visibleCount += pageSize"
                    >
                        {{ t('MESSAGES_LOAD_MORE') }}
                    </button>
                </template>
            </template>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { PageLayout, ToggleSwitch } from '@freelance/vue-ui';
    import { Icon } from '@iconify/vue';
    import HelpTrigger from '@/components/help/HelpTrigger.vue';
    import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import MessageComposeModal from '@/components/messages/MessageComposeModal.vue';
    import MessageFeedCard from '@/components/messages/MessageFeedCard.vue';
    import MessageManageView from '@/components/messages/MessageManageView.vue';
    import MessageReaderModal from '@/components/messages/MessageReaderModal.vue';
    import ConfirmationModal from '@/components/modals/content-modals/ConfirmationModal.vue';
    import { type FeedMessage, useMessages } from '@/composables/useMessages';
    import { useModalService } from '@/composables/useModalService';
    import { useToastService } from '@/composables/useToastService';
    import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget';
    import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType';
    import { ModalType } from '@/modules/enums/support/modals/ModalType';
    import { ModalVariant } from '@/modules/enums/support/modals/ModalVariant';
    import { readSeenMessageIds } from '@/modules/messages/seenMessages';

    defineOptions({ name: 'MessagesPage' });

    const { t } = useI18n();
    const modalService = useModalService();
    const toast = useToastService();
    const {
        feed,
        managed,
        draftItems,
        canPublish,
        canCreate,
        canUpdate,
        canDelete,
        preference,
        failed,
        manageFailed,
        isLoading,
        load,
        react,
        publish,
        removeDraft,
        savePreference,
    } = useMessages();

    const category = ref<'all' | 'company' | 'people' | 'milestones'>('all');
    const managing = ref(false);
    const visibleCount = ref(8);
    const pageSize = 8;
    const seenVersion = ref(0);
    const canManage = computed(() => canPublish.value || canCreate.value || canUpdate.value || canDelete.value);
    const reactionChoices = computed(() => [
        { id: '👍', label: t('MESSAGE_REACTION_THUMBS') },
        { id: '🎉', label: t('MESSAGE_REACTION_PARTY') },
        { id: '👏', label: t('MESSAGE_REACTION_CLAP') },
    ]);
    const filters = computed(() => [
        { id: 'all' as const, label: t('MESSAGES_FILTER_ALL') },
        { id: 'company' as const, label: t('MESSAGES_FILTER_COMPANY') },
        { id: 'people' as const, label: t('MESSAGE_CATEGORY_PEOPLE') },
        { id: 'milestones' as const, label: t('MESSAGE_CATEGORY_MILESTONES') },
    ]);
    const filtered = computed(() => (category.value === 'all' ? feed.value : feed.value.filter((message) => message.category === category.value)));
    const visible = computed(() => filtered.value.slice(0, visibleCount.value));
    const unreadCount = computed(() => {
        seenVersion.value;
        const seen = readSeenMessageIds();
        if (!seen) return 0;
        const ids = new Set(seen);
        return feed.value.filter((message) => !ids.has(message.id)).length;
    });
    const preferenceItems = computed(() => [
        {
            key: 'push_notifications',
            label: t('MESSAGES_PUSH_NOTIFICATIONS'),
            value: preference.value.push_notifications,
            locked: false,
            save: (value: boolean) => void savePreference({ ...preference.value, push_notifications: value }),
        },
        {
            key: 'people',
            label: t('MESSAGE_CATEGORY_PEOPLE'),
            value: preference.value.people,
            locked: false,
            save: (value: boolean) => void savePreference({ ...preference.value, people: value }),
        },
        {
            key: 'milestones',
            label: t('MESSAGE_CATEGORY_MILESTONES'),
            value: preference.value.milestones,
            locked: false,
            save: (value: boolean) => void savePreference({ ...preference.value, milestones: value }),
        },
        {
            key: 'company',
            label: t('MESSAGE_CATEGORY_COMPANY'),
            value: true,
            locked: preference.value.company_locked,
            save: () => undefined,
        },
    ]);

    function selectCategory(next: 'all' | 'company' | 'people' | 'milestones'): void {
        category.value = next;
        visibleCount.value = pageSize;
    }

    function bumpSeen(): void {
        seenVersion.value += 1;
    }

    function openCompose(message: FeedMessage | null): void {
        if (message && message.type !== 'contract') return;
        void modalService.open({
            title: message ? 'MESSAGE_COMPOSE_EDIT' : 'MESSAGE_COMPOSE_TITLE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: MessageComposeModal,
            bare: true,
            disableDefaultPadding: true,
            widthClass: 'w-full max-w-xl',
            panelClass: '!h-auto !rounded-2xl !border-list-border !bg-list-surface !text-list-text',
            props: { message, canPublish: canPublish.value },
        });
    }

    function openReader(message: FeedMessage): void {
        void modalService.open({
            title: 'MESSAGES_TITLE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: MessageReaderModal,
            bare: true,
            disableDefaultPadding: true,
            widthClass: 'w-full max-w-xl',
            panelClass: '!h-auto !rounded-2xl !border-list-border !bg-list-surface !text-list-text',
            props: { id: message.id },
        });
    }

    async function confirmPublish(message: FeedMessage): Promise<void> {
        const confirmed = await modalService.open({
            title: 'MESSAGE_PUBLISH_CONFIRM_TITLE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: ConfirmationModal,
            props: { message: 'MESSAGE_PUBLISH_CONFIRM', variant: ModalVariant.INFO },
            actions: [
                {
                    type: ModalResultType.DISMISS,
                    name: 'GENERAL_CANCEL',
                    variant: ModalActionType.DISMISS,
                    target: ModalActionTarget.CANCEL,
                },
                {
                    type: ModalResultType.ACCEPT,
                    name: 'MESSAGE_COMPOSE_PUBLISH',
                    variant: ModalActionType.PRIMARY,
                    target: ModalActionTarget.CONFIRM,
                },
            ],
        });
        if (confirmed.type !== ModalResultType.ACCEPT) return;
        try {
            await publish(message.id);
            toast.success('MESSAGE_PUBLISHED_TOAST');
        } catch {
            toast.error('MESSAGE_SAVE_ERROR');
        }
    }

    async function confirmDelete(message: FeedMessage): Promise<void> {
        const confirmed = await modalService.open({
            title: 'MESSAGE_DELETE_CONFIRM_TITLE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: ConfirmationModal,
            props: { message: 'MESSAGE_DELETE_CONFIRM', variant: ModalVariant.WARNING },
            actions: [
                {
                    type: ModalResultType.DISMISS,
                    name: 'GENERAL_CANCEL',
                    variant: ModalActionType.DISMISS,
                    target: ModalActionTarget.CANCEL,
                },
                {
                    type: ModalResultType.ACCEPT,
                    name: 'MESSAGE_DELETE',
                    variant: ModalActionType.PRIMARY,
                    target: ModalActionTarget.CONFIRM,
                },
            ],
        });
        if (confirmed.type !== ModalResultType.ACCEPT) return;
        try {
            await removeDraft(message.id);
            toast.success('MESSAGE_DELETED_TOAST');
        } catch {
            toast.error('MESSAGE_SAVE_ERROR');
        }
    }

    function openManage(): void {
        managing.value = true;
        void load('manage');
    }

    onMounted(() => {
        window.addEventListener('hanab-messages-seen', bumpSeen);
        void load('feed');
    });

    onBeforeUnmount(() => {
        window.removeEventListener('hanab-messages-seen', bumpSeen);
    });
</script>
