<template>
    <PageLayout class="!overflow-hidden" :label="t('MESSAGES_TITLE')">
        <div class="flex h-full min-h-0 flex-col overflow-hidden">
            <header class="mb-3 flex shrink-0 flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="inline-flex items-center gap-2 text-lg font-semibold text-list-text">
                        <Icon class="size-5 shrink-0 text-list-text-muted" icon="material-symbols:forum-outline-rounded" aria-hidden="true" />
                        {{ t('MESSAGES_TITLE') }}
                        <HelpTrigger context="messages.compose" />
                    </h1>
                    <p class="mt-1 text-sm text-list-text-muted">{{ t('MESSAGES_INTRO') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        v-if="!managing && unreadCount > 0"
                        class="inline-flex h-9 items-center rounded-full bg-primary-light px-3 text-xs font-semibold text-emphasis"
                    >
                        {{ t('MESSAGES_UNREAD', { count: unreadCount }) }}
                    </span>
                    <button
                        v-if="managing"
                        type="button"
                        class="inline-flex h-9 cursor-pointer items-center rounded-lg border border-list-border bg-list-surface px-3 text-sm font-semibold text-list-text"
                        @click="managing = false"
                    >
                        {{ t('MESSAGES_BACK') }}
                    </button>
                    <button
                        v-else-if="canManage"
                        type="button"
                        class="inline-flex h-9 cursor-pointer items-center rounded-lg border border-list-border bg-list-surface px-3 text-sm font-semibold text-list-text"
                        @click="openManage"
                    >
                        {{ t('MESSAGES_MANAGE') }}
                    </button>
                    <button
                        v-if="canCreate"
                        type="button"
                        class="inline-flex h-9 cursor-pointer items-center gap-1 rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand"
                        @click="openCompose(null)"
                    >
                        <Icon icon="material-symbols:add" class="size-4" />
                        {{ t('MESSAGES_NEW') }}
                    </button>
                </div>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto">
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
                    <UiStickySurface v-slot="{ scrolled }" class="pb-3">
                        <div aria-hidden="true" class="pointer-events-none h-3"></div>
                        <section
                            class="rounded-xl border border-list-border bg-list-surface px-3 py-2.5 shadow-sm transition-[background-color,backdrop-filter,box-shadow] duration-200 ease-out motion-reduce:transition-none"
                            :style="scrolled ? frostStyle : undefined"
                        >
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                                <form class="flex min-w-0 flex-1 items-center" @submit.prevent>
                                    <label class="sr-only" for="messages-search">{{ t('MESSAGES_SEARCH') }}</label>
                                    <div class="relative min-w-0 flex-1">
                                        <Icon class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-list-text-muted" icon="material-symbols:search-rounded" />
                                        <input
                                            id="messages-search"
                                            v-model="query"
                                            type="search"
                                            autocomplete="off"
                                            :placeholder="t('MESSAGES_SEARCH')"
                                            class="h-9 w-full rounded-lg border border-list-border bg-list-surface pr-3 pl-10 text-sm text-list-text outline-none placeholder:text-list-text-muted focus:border-primary focus:ring-2 focus:ring-primary/15"
                                        />
                                    </div>
                                </form>
                                <div class="flex flex-wrap items-center gap-2">
                                    <label class="inline-flex h-9 items-center gap-2 rounded-lg border border-list-border bg-list-surface px-2.5 text-sm text-list-text">
                                        <span>{{ t('MESSAGES_UNREAD_ONLY') }}</span>
                                        <ToggleSwitch v-model="unreadOnly" :label="t('MESSAGES_UNREAD_ONLY')" />
                                    </label>
                                    <ChoiceDropdown
                                        v-model="sort"
                                        :aria-label="t('MESSAGES_SORT')"
                                        :full-width="false"
                                        :options="sortOptions"
                                    />
                                    <router-link
                                        class="inline-flex size-9 items-center justify-center rounded-lg border border-list-border bg-list-surface text-list-text hover:border-primary hover:text-primary"
                                        :to="{ name: 'admin-account', hash: '#message-preferences' }"
                                        :aria-label="t('MESSAGE_PREFERENCES_TITLE')"
                                        :title="t('MESSAGE_PREFERENCES_TITLE')"
                                    >
                                        <Icon class="size-4" icon="material-symbols:tune-rounded" />
                                    </router-link>
                                </div>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1.5" role="tablist" :aria-label="t('MESSAGES_TITLE')">
                                <button
                                    v-for="filter in filters"
                                    :key="filter.id"
                                    type="button"
                                    class="h-8 cursor-pointer rounded-full px-3 text-xs font-semibold"
                                    :class="category === filter.id ? 'bg-list-text text-list-surface' : 'border border-list-border bg-list-surface text-list-text hover:border-primary'"
                                    :aria-selected="category === filter.id"
                                    role="tab"
                                    @click="selectCategory(filter.id)"
                                >
                                    {{ filter.label }}
                                </button>
                            </div>
                        </section>
                    </UiStickySurface>

                    <p v-if="isLoading && feed.length === 0" role="status" class="text-sm text-list-text-muted">{{ t('MESSAGES_LOADING') }}</p>
                    <p v-else-if="failed" role="alert" class="flex items-center gap-3 text-sm text-list-text">
                        {{ t('MESSAGES_ERROR') }}
                        <button type="button" class="cursor-pointer font-semibold underline" @click="load('feed')">{{ t('MESSAGES_RETRY') }}</button>
                    </p>
                    <p
                        v-else-if="filtered.length === 0"
                        role="status"
                        class="rounded-xl border border-dashed border-list-border bg-list-surface px-4 py-10 text-center text-sm text-list-text-muted"
                    >
                        {{ emptyLabel }}
                    </p>
                    <template v-else>
                        <div class="grid grid-cols-1 items-stretch gap-3 md:grid-cols-2">
                            <MessageFeedCard
                                v-for="message in visible"
                                :key="message.id"
                                :message="message"
                                :unread="isUnread(message.id)"
                                @react="onReact"
                                @open="openReader"
                            />
                        </div>
                        <button
                            v-if="visible.length < filtered.length"
                            type="button"
                            class="mt-3 h-9 cursor-pointer rounded-lg border border-list-border bg-list-surface px-4 text-sm font-semibold text-list-text"
                            @click="visibleCount += pageSize"
                        >
                            {{ t('MESSAGES_LOAD_MORE') }}
                        </button>
                    </template>
                </template>
            </div>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { PageLayout, ToggleSwitch, UiStickySurface } from '@freelance/vue-ui';
    import { Icon } from '@iconify/vue';
    import HelpTrigger from '@/components/help/HelpTrigger.vue';
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import ChoiceDropdown from '@/components/inputs/ChoiceDropdown.vue';
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
        failed,
        manageFailed,
        isLoading,
        load,
        react,
        publish,
        removeDraft,
    } = useMessages();

    const category = ref<'all' | 'company' | 'people' | 'milestones'>('all');
    const query = ref('');
    const unreadOnly = ref(false);
    const sort = ref<'newest' | 'oldest'>('newest');
    const managing = ref(false);
    const visibleCount = ref(8);
    const pageSize = 8;
    const seenVersion = ref(0);
    const frostStyle = {
        backgroundColor: 'color-mix(in srgb, var(--color-list-surface) 94%, transparent)',
        backdropFilter: 'blur(12px)',
    };
    const canManage = computed(() => canPublish.value || canCreate.value || canUpdate.value || canDelete.value);
    const filters = computed(() => [
        { id: 'all' as const, label: t('MESSAGES_FILTER_ALL') },
        { id: 'company' as const, label: t('MESSAGES_FILTER_COMPANY') },
        { id: 'people' as const, label: t('MESSAGE_CATEGORY_PEOPLE') },
        { id: 'milestones' as const, label: t('MESSAGE_CATEGORY_MILESTONES') },
    ]);
    const sortOptions = computed(() => [
        { value: 'newest' as const, label: t('MESSAGES_SORT_NEWEST') },
        { value: 'oldest' as const, label: t('MESSAGES_SORT_OLDEST') },
    ]);
    const seenIds = computed(() => {
        seenVersion.value;
        return new Set(readSeenMessageIds() ?? []);
    });
    const filtered = computed(() => {
        const needle = query.value.trim().toLowerCase();
        const rows = feed.value.filter((message) => {
            if (category.value !== 'all' && message.category !== category.value) return false;
            if (unreadOnly.value && !isUnread(message.id)) return false;
            if (!needle) return true;
            const haystack = [message.title, message.text, message.subjectName, message.authorName].join(' ').toLowerCase();
            return haystack.includes(needle);
        });
        return rows.sort((left, right) => {
            const leftTime = Date.parse(left.publishedAt ?? left.updatedAt ?? '') || 0;
            const rightTime = Date.parse(right.publishedAt ?? right.updatedAt ?? '') || 0;
            return sort.value === 'oldest' ? leftTime - rightTime : rightTime - leftTime;
        });
    });
    const visible = computed(() => filtered.value.slice(0, visibleCount.value));
    const unreadCount = computed(() => feed.value.filter((message) => isUnread(message.id)).length);
    const emptyLabel = computed(() => {
        if (unreadOnly.value && feed.value.length > 0) return t('MESSAGES_ALL_READ');
        if (query.value.trim()) return t('MESSAGES_NONE_FOUND');
        return category.value === 'all' ? t('MESSAGES_EMPTY') : t('MESSAGES_EMPTY_FILTER');
    });

    function isUnread(id: string): boolean {
        return !seenIds.value.has(id);
    }

    function selectCategory(next: 'all' | 'company' | 'people' | 'milestones'): void {
        category.value = next;
        visibleCount.value = pageSize;
    }

    function bumpSeen(): void {
        seenVersion.value += 1;
    }

    async function onReact(id: string, emoji: string | null): Promise<void> {
        try {
            await react(id, emoji);
        } catch {
            toast.error('MESSAGE_REACTION_FAILED');
        }
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
                { type: ModalResultType.DISMISS, name: 'GENERAL_CANCEL', variant: ModalActionType.DISMISS, target: ModalActionTarget.CANCEL },
                { type: ModalResultType.ACCEPT, name: 'MESSAGE_COMPOSE_PUBLISH', variant: ModalActionType.PRIMARY, target: ModalActionTarget.CONFIRM },
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
                { type: ModalResultType.DISMISS, name: 'GENERAL_CANCEL', variant: ModalActionType.DISMISS, target: ModalActionTarget.CANCEL },
                { type: ModalResultType.ACCEPT, name: 'MESSAGE_DELETE', variant: ModalActionType.PRIMARY, target: ModalActionTarget.CONFIRM },
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

    watch([query, unreadOnly, sort], () => {
        visibleCount.value = pageSize;
    });

    onMounted(() => {
        window.addEventListener('hanab-messages-seen', bumpSeen);
        void load('feed');
    });

    onBeforeUnmount(() => {
        window.removeEventListener('hanab-messages-seen', bumpSeen);
    });
</script>
