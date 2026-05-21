<template>
    <Transition
        appear
        enter-active-class="transform transition duration-300 ease-out"
        enter-from-class="-translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transform transition duration-200 ease-in"
        leave-to-class="-translate-y-full opacity-0"
    >
        <nav class="relative z-20 h-14 flex-shrink-0 border-b border-slate-200/80 bg-white/95 shadow-sm backdrop-blur-sm">
            <div class="relative flex h-full w-full items-center justify-between gap-4 px-4">
                <!-- Left -->
                <div class="flex min-w-0 items-center">
                    <BreadCrumb />
                </div>

                <!-- Center search -->
                <div class="pointer-events-none absolute left-1/2 hidden -translate-x-1/2 px-2 md:pointer-events-auto md:block">
                    <label class="relative block w-72 lg:w-80">
                        <span class="sr-only">{{ t('GENERAL_SEARCH_PLACEHOLDER') }}</span>
                        <Icon
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            icon="material-symbols:search-rounded"
                        />
                        <input
                            :placeholder="t('GENERAL_SEARCH_PLACEHOLDER')"
                            class="w-full rounded-full border border-slate-200 bg-slate-50/80 py-2 pl-10 pr-4 text-sm text-slate-700 shadow-inner transition focus:border-[var(--color-primary)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/20"
                            type="search"
                        />
                    </label>
                </div>

                <!-- Right: user + menu -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <AdvancedDropdown
                        :align="'right'"
                        :show-caret="false"
                        customDropdownClass="!w-72 !overflow-visible !rounded-xl !border-slate-200/90 !p-0 !shadow-xl"
                    >
                        <template #trigger="{ onToggleDropdown }">
                            <button
                                :aria-label="t('TOPBAR_ACCOUNT_MENU')"
                                class="group flex cursor-pointer items-center gap-2.5 rounded-full border border-slate-200/90 bg-white py-1 pl-1 pr-2.5 shadow-sm transition hover:border-[var(--color-primary)]/30 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)]/40"
                                type="button"
                                @click="onToggleDropdown"
                            >
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[var(--color-background)] to-[var(--color-highlight-dark)] text-sm font-semibold tracking-wide text-white shadow-inner"
                                >
                                    {{ userInitials }}
                                </span>
                                <span class="hidden min-w-0 flex-col items-start text-left sm:flex">
                                    <span class="max-w-[9rem] truncate text-sm font-semibold leading-tight text-slate-800">
                                        {{ user?.name ?? '—' }}
                                    </span>
                                    <span
                                        v-if="roleLabel"
                                        class="max-w-[9rem] truncate text-xs font-medium text-[var(--color-primary)]"
                                    >
                                        {{ roleLabel }}
                                    </span>
                                </span>
                                <Icon
                                    class="h-5 w-5 shrink-0 text-slate-400 transition group-hover:text-[var(--color-primary)]"
                                    icon="material-symbols:keyboard-arrow-down-rounded"
                                />
                            </button>
                        </template>

                        <template #dropdown-content>
                            <div class="relative w-72 overflow-visible rounded-xl">
                                <div
                                    class="border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white px-4 py-3.5"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[var(--color-background)] to-[var(--color-highlight-dark)] text-sm font-bold text-white shadow-md"
                                        >
                                            {{ userInitials }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-slate-900">
                                                {{ user?.name ?? '—' }}
                                            </p>
                                            <p class="truncate text-xs text-slate-500">{{ user?.email ?? '' }}</p>
                                            <span
                                                v-if="roleLabel"
                                                class="mt-1.5 inline-flex rounded-full bg-[var(--color-primary)]/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[var(--color-primary)]"
                                            >
                                                {{ roleLabel }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="relative px-2 py-2">
                                    <button
                                        :aria-expanded="languageMenuOpen"
                                        :class="[
                                            'flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2.5 text-left text-sm transition',
                                            languageMenuOpen
                                                ? 'bg-slate-100 text-[var(--color-primary)]'
                                                : 'text-slate-700 hover:bg-slate-50',
                                        ]"
                                        type="button"
                                        @click.stop="toggleLanguageMenu"
                                    >
                                        <Icon
                                            class="h-5 w-5 shrink-0 text-slate-500"
                                            icon="material-symbols:translate-rounded"
                                        />
                                        <span class="min-w-0 flex-1 font-medium">{{ t('APP_LANGUAGE') }}</span>
                                        <span class="truncate text-xs text-slate-500">{{ activeLocaleLabel }}</span>
                                        <Icon
                                            class="h-5 w-5 shrink-0 text-slate-400"
                                            icon="material-symbols:chevron-left-rounded"
                                        />
                                    </button>

                                    <!-- Flyout opens to the left; main panel width stays fixed -->
                                    <Transition
                                        enter-active-class="transition duration-150 ease-out"
                                        enter-from-class="opacity-0 translate-x-2"
                                        enter-to-class="opacity-100 translate-x-0"
                                        leave-active-class="transition duration-100 ease-in"
                                        leave-from-class="opacity-100 translate-x-0"
                                        leave-to-class="opacity-0 translate-x-2"
                                    >
                                        <div
                                            v-show="languageMenuOpen"
                                            class="absolute right-full top-0 z-10 mr-1.5 flex w-44 flex-col rounded-lg border border-slate-200/90 bg-white py-1.5 shadow-lg"
                                            role="menu"
                                            :aria-label="t('APP_LANGUAGE')"
                                        >
                                            <button
                                                v-for="option in localeOptions"
                                                :key="option.code"
                                                :aria-current="currentLocale === option.code ? 'true' : undefined"
                                                :class="[
                                                    'mx-1.5 flex cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-2 text-left text-sm transition',
                                                    currentLocale === option.code
                                                        ? 'bg-[var(--color-primary)]/10 font-medium text-[var(--color-primary)]'
                                                        : 'text-slate-700 hover:bg-slate-50',
                                                ]"
                                                role="menuitem"
                                                type="button"
                                                @click.stop="selectLocale(option.code)"
                                            >
                                                <span aria-hidden="true" class="text-base leading-none">{{
                                                    option.flag
                                                }}</span>
                                                <span class="min-w-0 flex-1">{{ t(option.labelKey) }}</span>
                                                <Icon
                                                    v-if="currentLocale === option.code"
                                                    class="h-4 w-4 shrink-0"
                                                    icon="material-symbols:check-rounded"
                                                />
                                            </button>
                                        </div>
                                    </Transition>
                                </div>

                                <div class="border-t border-slate-100 px-2 py-2">
                                    <button
                                        class="flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-red-600 transition hover:bg-red-50"
                                        type="button"
                                        @click="confirmLogout"
                                    >
                                        <Icon class="h-5 w-5 shrink-0" icon="material-symbols:logout-rounded" />
                                        <span>{{ t('SIDEBAR_ITEM_LOGOUT') }}</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </AdvancedDropdown>
                </div>
            </div>
        </nav>
    </Transition>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import type { AxiosError } from 'axios';
    import BreadCrumb from '@/components/navigation/BreadCrumb.vue';
    import ConfirmationModal from '@/components/modals/content-modals/ConfirmationModal.vue';
    import AdvancedDropdown from '@/components/inputs/AdvancedDropdown.vue';
    import {
        APP_LOCALE_STORAGE_KEY,
        type AppLocale,
        useLocalStorage,
    } from '@/composables/useLocalStorage';
    import { useModalService } from '@/composables/useModalService';
    import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget.ts';
    import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType.ts';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
    import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
    import { ModalVariant } from '@/modules/enums/support/modals/ModalVariant.ts';
    import { useAuthStore } from '@/stores/auth';
    import http from '@/utils/http';
    import { computed, defineComponent, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';

    defineComponent({ name: 'Topbar' });

    const authStore = useAuthStore();
    const router = useRouter();
    const { t, locale } = useI18n();
    const { getLocalStorageItem, setLocalStorageItem } = useLocalStorage();
    const modalService = useModalService();

    const localeOptions: { code: AppLocale; labelKey: string; flag: string }[] = [
        { code: 'nl', labelKey: 'APP_LANGUAGE_NL', flag: '🇳🇱' },
        { code: 'en', labelKey: 'APP_LANGUAGE_EN', flag: '🇬🇧' },
    ];

    const user = computed(() => authStore.user);
    const languageMenuOpen = ref(false);

    const currentLocale = computed(() => (locale.value === 'en' ? 'en' : 'nl'));

    const activeLocaleLabel = computed(() => {
        const active = localeOptions.find((o) => o.code === currentLocale.value);
        return active ? t(active.labelKey) : '';
    });

    const userInitials = computed(() => {
        const name = user.value?.name?.trim();
        if (!name) return '?';
        const parts = name.split(/\s+/).filter(Boolean);
        if (parts.length >= 2) {
            return `${parts[0]![0] ?? ''}${parts[parts.length - 1]![0] ?? ''}`.toUpperCase();
        }
        return name.slice(0, 2).toUpperCase();
    });

    const roleLabel = computed(() => {
        const role = user.value?.role_name;
        if (!role) return '';
        const key = `GENERAL_${role.toUpperCase()}`;
        return t(key) !== key ? t(key) : role;
    });

    function closeLanguageMenu(): void {
        languageMenuOpen.value = false;
    }

    function toggleLanguageMenu(): void {
        languageMenuOpen.value = !languageMenuOpen.value;
    }

    function setLocale(code: AppLocale): void {
        locale.value = code;
        setLocalStorageItem(APP_LOCALE_STORAGE_KEY, code);
    }

    function selectLocale(code: AppLocale): void {
        setLocale(code);
        closeLanguageMenu();
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
    });
</script>
