<template>
    <Transition
        appear
        enter-active-class="transform transition duration-300 ease-out"
        enter-from-class="-translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transform transition duration-200 ease-in"
        leave-to-class="-translate-y-full opacity-0"
    >
        <Topbar
            class="z-20! shadow-sm"
            :account="account"
            :account-actions="accountActions"
            :preferences="preferences"
            :labels="{
                search: t('GENERAL_SEARCH_PLACEHOLDER'),
                searchPlaceholder: t('GENERAL_SEARCH_PLACEHOLDER'),
                account: t('TOPBAR_ACCOUNT_MENU'),
            }"
            @account-action="onAccountAction"
            @preference-change="onPreferenceChange"
        >
            <template #search>
                <ExpertiseSearch presentation="field" shortcut />
            </template>
            <template #notifications>
                <NotificationBell
                    :items="notificationItems"
                    :unread-count="unreadCount"
                    :labels="notificationLabels"
                    view-all-href="/admin/messages"
                    @open="refreshMessages"
                    @select="onNotificationSelect"
                    @mark-read="markNotificationRead"
                    @mark-all-read="markAllNotificationsRead"
                    @dismiss="dismissNotification"
                />
            </template>
            <template #breadcrumbs>
                <BreadCrumb />
            </template>
            <template #account-action="{ action }">
                <Icon v-if="action.icon" :icon="action.icon" class="h-5 w-5 shrink-0" />
                <span>{{ action.label }}</span>
            </template>
        </Topbar>
    </Transition>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import type { AxiosError } from 'axios';
    import BreadCrumb from '@/components/navigation/BreadCrumb.vue';
    import ExpertiseSearch from '@/components/search/ExpertiseSearch.vue';
    import ConfirmationModal from '@/components/modals/content-modals/ConfirmationModal.vue';
    import {
        Topbar,
        NotificationBell,
        type NotificationLabels,
        type NotificationItem,
        type TopbarAccount,
        type TopbarAction,
        type TopbarPreference,
    } from '@freelance/vue-ui';
    import { APP_LOCALE_STORAGE_KEY, type AppLocale, useLocalStorage } from '@/composables/useLocalStorage';
    import { useModalService } from '@/composables/useModalService';
    import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget.ts';
    import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType.ts';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
    import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
    import { ModalVariant } from '@/modules/enums/support/modals/ModalVariant.ts';
    import { useAuthStore } from '@/stores/auth';
    import { useMessages } from '@/composables/useMessages';
    import { messageIcon } from '@/modules/messages/messagePresentation';
    import {
        readDismissedMessageIds,
        readSeenMessageIds,
        rememberDismissedMessageIds,
        rememberSeenMessageIds,
    } from '@/modules/messages/seenMessages';
    import { useThemeStore } from '@/stores/theme';
    import { isThemeAppearance, type ThemeAppearance } from '@/modules/support/theme/themeAppearance';
    import http from '@/utils/http';
    import { knowledgeBaseVisible } from '@/modules/knowledge/visibility';
    import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';

    defineOptions({ name: 'HanabTopbar' });

    const authStore = useAuthStore();
    const themeStore = useThemeStore();
    const router = useRouter();
    const { t, locale } = useI18n();
    const { getLocalStorageItem, setLocalStorageItem } = useLocalStorage();
    const modalService = useModalService();

    const localeOptions: { code: AppLocale; labelKey: string; flag: string }[] = [
        { code: 'nl', labelKey: 'APP_LANGUAGE_NL', flag: '🇳🇱' },
        { code: 'en', labelKey: 'APP_LANGUAGE_EN', flag: '🇬🇧' },
    ];

    const user = computed(() => authStore.user);

    const currentLocale = computed(() => (locale.value === 'en' ? 'en' : 'nl'));

    const roleLabel = computed(() => {
        const role = user.value?.role_name;
        if (!role) return '';
        const key = `GENERAL_${role.toUpperCase()}`;
        return t(key) !== key ? t(key) : role;
    });

    // The bell shows the latest in-app messages. Reactions stay on the messages page.
    const { feed, failed, isLoading, load: loadMessages } = useMessages();
    const seenMessageIds = ref<Set<string>>(new Set());
    const dismissedMessageIds = ref<Set<string>>(new Set());
    const seenReady = ref(false);
    let messagePoll: ReturnType<typeof setInterval> | null = null;

    function rememberSeen(ids: Iterable<string>): void {
        rememberSeenMessageIds(ids);
        seenMessageIds.value = new Set(ids);
    }

    function rememberDismissed(ids: Iterable<string>): void {
        rememberDismissedMessageIds(ids);
        dismissedMessageIds.value = new Set(ids);
    }

    const visibleMessages = computed(() => feed.value.filter((message) => !dismissedMessageIds.value.has(message.id)));

    const reviewItems = ref<NotificationItem[]>([]);

    const unreadCount = computed(() => {
        if (!seenReady.value) return 0;
        const messages = visibleMessages.value.filter((message) => !seenMessageIds.value.has(message.id)).length;
        const reviews = reviewItems.value.filter((item) => item.id && !seenMessageIds.value.has(item.id)).length;
        return messages + reviews;
    });

    const messageItems = computed<NotificationItem[]>(() =>
        visibleMessages.value.slice(0, 10).map((message) => ({
            id: message.id,
            title: message.title,
            body: message.text,
            unread: seenReady.value && !seenMessageIds.value.has(message.id),
            meta: t(`MESSAGE_CATEGORY_${message.category.toUpperCase()}`),
            timeLabel: formatNotificationTime(message.publishedAt ?? message.updatedAt),
            icon: messageIcon(message.type),
            tone: message.type === 'contract' ? 'info' : 'success',
            href: '/admin/messages',
            actionLabel: t('NOTIFICATIONS_OPEN'),
        })),
    );

    const notificationItems = computed(() => [
        ...reviewItems.value.map((item) => ({
            ...item,
            unread: seenReady.value && Boolean(item.id) && !seenMessageIds.value.has(item.id),
        })),
        ...messageItems.value,
    ]);

    function formatNotificationTime(value: string | null): string {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat(locale.value === 'nl' ? 'nl-NL' : 'en-GB', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    }

    function markNotificationRead(item: NotificationItem): void {
        const next = new Set(seenMessageIds.value);
        next.add(item.id);
        rememberSeen(next);
    }

    function markAllNotificationsRead(): void {
        const next = new Set(seenMessageIds.value);
        for (const message of visibleMessages.value) next.add(message.id);
        for (const item of reviewItems.value) if (item.id) next.add(item.id);
        rememberSeen(next);
    }

    function dismissNotification(item: NotificationItem): void {
        markNotificationRead(item);
        const next = new Set(dismissedMessageIds.value);
        next.add(item.id);
        rememberDismissed(next);
    }

    function onNotificationSelect(item: NotificationItem, event: MouseEvent): void {
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        if (item.id) markNotificationRead(item);
        if (item.href) void router.push(item.href);
    }

    async function refreshMessages(): Promise<void> {
        if (knowledgeBaseVisible && authStore.hasPermission('update knowledge articles')) {
            try {
                const response = await http.get<{ data: { overdue: Array<{ id: number; title: string }> } }>('knowledge/maintenance');
                reviewItems.value = (response.data.data.overdue ?? []).map((article) => ({
                    id: `knowledge-review-${article.id}`,
                    title: article.title,
                    body: t('KNOWLEDGE_REVIEW_NOTICE'),
                    unread: !seenMessageIds.value.has(`knowledge-review-${article.id}`),
                    meta: t('KNOWLEDGE_TITLE'),
                    icon: 'material-symbols:rate-review-outline-rounded',
                    tone: 'warning',
                    href: `/admin/knowledge/articles/${article.id}/edit`,
                    actionLabel: t('NOTIFICATIONS_OPEN'),
                }));
            } catch {
                reviewItems.value = [];
            }
        }
        await loadMessages();
        if (isLoading.value || failed.value) return;
        const stored = readSeenMessageIds();
        seenMessageIds.value = new Set(stored ?? []);
        dismissedMessageIds.value = new Set(readDismissedMessageIds());
        seenReady.value = true;
    }
    const notificationLabels = computed<NotificationLabels>(() => ({
        title: t('MOBILE_NOTIFICATIONS_TITLE'),
        trigger:
            unreadCount.value > 0
                ? t('NOTIFICATIONS_BELL_LABEL_UNREAD', { count: unreadCount.value })
                : t('NOTIFICATIONS_BELL_LABEL'),
        subtitle:
            unreadCount.value > 0
                ? t('NOTIFICATIONS_UNREAD_COUNT', { count: unreadCount.value })
                : t('NOTIFICATIONS_ALL_CAUGHT_UP'),
        empty: t('NOTIFICATIONS_EMPTY'),
        loading: t('NOTIFICATIONS_LOADING'),
        retry: t('NOTIFICATIONS_RETRY'),
        markRead: t('MOBILE_NOTIFICATION_MARK_READ'),
        markAllRead: t('NOTIFICATIONS_MARK_ALL_READ'),
        dismiss: t('NOTIFICATIONS_DISMISS'),
        open: t('NOTIFICATIONS_OPEN'),
        unread: t('NOTIFICATIONS_UNREAD'),
        viewAll: t('NOTIFICATIONS_VIEW_ALL'),
    }));

    const account = computed<TopbarAccount>(() => ({
        name: user.value?.name ?? '—',
        email: user.value?.email ?? '',
        role: roleLabel.value,
    }));

    const accountActions = computed<TopbarAction[]>(() => [
        { id: 'security', label: t('ACCOUNT_SECURITY'), icon: 'material-symbols:shield-lock-outline' },
        { id: 'logout', label: t('SIDEBAR_ITEM_LOGOUT'), icon: 'material-symbols:logout-rounded', danger: true },
    ]);

    const themeOptions: { id: ThemeAppearance; labelKey: string }[] = [
        { id: 'light', labelKey: 'APP_THEME_LIGHT' },
        { id: 'project', labelKey: 'APP_THEME_PROJECT' },
        { id: 'dark', labelKey: 'APP_THEME_DARK' },
    ];

    const preferences = computed<TopbarPreference[]>(() => [
        {
            id: 'language',
            label: t('APP_LANGUAGE'),
            icon: 'translate',
            value: currentLocale.value,
            options: localeOptions.map((option) => ({
                value: option.code,
                label: `${option.flag} ${t(option.labelKey)}`,
            })),
        },
        {
            id: 'theme',
            label: t('APP_THEME'),
            icon: 'palette',
            control: 'slider',
            value: themeStore.appearance,
            options: themeOptions.map((option) => ({
                value: option.id,
                label: t(option.labelKey),
            })),
        },
    ]);

    function onAccountAction(action: TopbarAction): void {
        if (action.id === 'security') void router.push({ name: 'admin-settings-security' });
        if (action.id === 'logout') confirmLogout();
    }

    function onPreferenceChange(id: string, value: string): void {
        if (id === 'language' && (value === 'nl' || value === 'en')) setLocale(value);
        if (id === 'theme' && isThemeAppearance(value)) themeStore.setAppearance(value);
    }

    function setLocale(code: AppLocale): void {
        locale.value = code;
        setLocalStorageItem(APP_LOCALE_STORAGE_KEY, code);
    }

    function syncLocaleFromStorage(): void {
        const stored = getLocalStorageItem(APP_LOCALE_STORAGE_KEY);
        if (stored === 'nl' || stored === 'en') {
            locale.value = stored;
            return;
        }
        setLocale('nl');
    }

    function confirmLogout(): void {
        modalService.open({
            title: 'GENERAL_WARNING_LOGOUT_MESSAGE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: ConfirmationModal,
            props: {
                message: 'GENERAL_WARNING_LOGOUT_MESSAGE',
                variant: ModalVariant.WARNING,
            },
            actions: [
                {
                    type: ModalResultType.DISMISS,
                    name: 'GENERAL_CANCEL',
                    variant: ModalActionType.DISMISS,
                    target: ModalActionTarget.CANCEL,
                },
                {
                    type: ModalResultType.ACCEPT,
                    name: 'GENERAL_ACCEPT',
                    variant: ModalActionType.PRIMARY,
                    target: ModalActionTarget.CONFIRM,
                },
            ],
            onAccept: () => {
                http.post('/logout', {}, { baseURL: import.meta.env.VITE_API_URL + '/api' })
                    .then(() => {
                        authStore.logout();
                        router.push('/login');
                    })
                    .catch((error: AxiosError) => {
                        console.error('Logout failed:', error);
                    });
            },
        });
    }

    onMounted(() => {
        syncLocaleFromStorage();
        void refreshMessages();
        messagePoll = setInterval(() => {
            void refreshMessages();
        }, 60_000);
    });

    onBeforeUnmount(() => {
        if (messagePoll) clearInterval(messagePoll);
    });
</script>
