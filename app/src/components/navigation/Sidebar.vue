<template>
    <Transition
        appear
        enter-active-class="transition-[width,opacity] duration-300 ease-out overflow-hidden"
        enter-from-class="!w-0 opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-[width,opacity] duration-200 ease-in overflow-hidden"
        leave-to-class="!w-0 opacity-0"
    >
        <aside
            ref="asideRef"
            :class="sidebarCollapsed ? 'w-44' : 'w-14'"
            class="relative bg-[var(--color-background)] small-scrollbar isolate z-10 flex h-full flex-shrink-0 flex-col gap-2 transition-all duration-200 ease-in-out"
        >
            <div class="bg-[var(--color-background)] relative flex h-full flex-1 flex-col justify-between">
                <div class="flex flex-col items-center my-2">
                    <div class="w-full my-1 items-center relative group" @click="goToEmployees()">
                        <div
                            class="flex items-center mx-2 gap-1 text-white rounded-md hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in relative cursor-pointer"
                        >
                            <img alt="Logo" class="w-10 h-10 p-0.5 bg-gray-50 rounded-sm border shadow-sm" src="/cesta-ca-logo.png" />
                            <transition mode="out-in" name="fade">
                                <span v-if="sidebarCollapsed" class="block truncate text-white font-bold">{{
                                    'cest-ca'
                                }}</span>
                            </transition>
                        </div>
                        <div
                            v-if="!sidebarCollapsed"
                            class="absolute left-full top-1/2 transform -translate-y-1/2 ml-2 hidden group-hover:block opacity-0 group-hover:opacity-100 transition-opacity duration-200 ease-in bg-gray-800 text-white text-sm font-medium py-1 px-2 rounded shadow-lg whitespace-nowrap"
                        >
                            {{ 'test' }}
                            <div
                                class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-full w-0 h-0 border-y-6 border-y-transparent border-r-6 border-r-gray-800"
                            ></div>
                        </div>
                    </div>

                    <SidebarItem
                        v-for="item in getSidebarItems()"
                        :key="item.label"
                        :active-exact="item.activeExact"
                        :collapsed="sidebarCollapsed"
                        :icon="item.icon"
                        :inactive-when-path-starts-with="item.inactiveWhenPathStartsWith"
                        :label="item.label"
                        :to="item.to"
                    />
                </div>
                <div class="flex flex-col items-center my-2">
                    <SidebarItem
                        v-for="item in getBottomSidebarItems()"
                        :key="item.label"
                        :collapsed="sidebarCollapsed"
                        :icon="item.icon"
                        :label="item.label"
                        :to="item.to"
                    />
                    <div class="w-full my-1 items-center relative group">
                        <div
                            v-if="isInSettingsSection"
                            class="absolute left-0 top-0 bottom-0 w-1 bg-white rounded"
                        ></div>
                        <div
                            :class="[
                                'flex items-center mx-2 gap-1 text-white rounded-md hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in relative cursor-pointer',
                                isInSettingsSection ? 'bg-[var(--color-highlight-dark)]' : '',
                            ]"
                            @click="toggleSettings"
                        >
                            <div class="flex items-center p-2 rounded-md">
                                <Icon class="w-6 h-6" icon="material-symbols:settings-rounded" />
                            </div>
                            <transition mode="out-in" name="fade">
                                <span v-if="sidebarCollapsed" class="font-bold text-base">{{
                                    t('SIDEBAR_ITEM_SETTINGS')
                                }}</span>
                            </transition>
                        </div>
                        <div
                            v-if="!sidebarCollapsed"
                            class="absolute left-full top-1/2 transform -translate-y-1/2 ml-2 hidden group-hover:block opacity-0 group-hover:opacity-100 transition-opacity duration-200 ease-in bg-gray-800 text-white text-sm font-medium py-1 px-2 rounded shadow-lg whitespace-nowrap"
                        >
                            {{ t('SIDEBAR_ITEM_SETTINGS') }}
                            <div
                                class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-full w-0 h-0 border-y-6 border-y-transparent border-r-6 border-r-gray-800"
                            ></div>
                        </div>
                    </div>
                    <div class="w-full my-1 items-center relative group">
                        <div
                            class="flex items-center mx-2 gap-1 text-white rounded-md hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in relative cursor-pointer"
                            @click="logout()"
                        >
                            <div class="flex items-center p-2 rounded-md">
                                <Icon class="w-6 h-6" icon="material-symbols:logout-rounded" />
                            </div>
                            <transition mode="out-in" name="fade">
                                <span v-if="sidebarCollapsed" class="font-bold text-base">{{
                                    t('SIDEBAR_ITEM_LOGOUT')
                                }}</span>
                            </transition>
                        </div>
                        <div
                            v-if="!sidebarCollapsed"
                            class="absolute left-full top-1/2 transform -translate-y-1/2 ml-2 hidden group-hover:block opacity-0 group-hover:opacity-100 transition-opacity duration-200 ease-in bg-gray-800 text-white text-sm font-medium py-1 px-2 rounded shadow-lg whitespace-nowrap"
                        >
                            {{ t('SIDEBAR_ITEM_LOGOUT') }}
                            <div
                                class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-full w-0 h-0 border-y-6 border-y-transparent border-r-6 border-r-gray-800"
                            ></div>
                        </div>
                    </div>
                    <div class="w-full">
                        <div class="min-h-px bg-white mx-1"></div>
                    </div>
                    <div class="w-full my-1 items-center relative">
                        <div class="w-full my-1 items-center relative" @click="toggleSideBar">
                            <div
                                class="flex items-center mx-2 gap-1 text-white rounded-md hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in cursor-pointer"
                            >
                                <div
                                    :class="{
                                        'transform rotate-180': sidebarCollapsed,
                                        'transform rotate-0': !sidebarCollapsed,
                                    }"
                                    class="flex items-center p-2 rounded-md transition-transform duration-200 ease-in-out"
                                >
                                    <Icon
                                        class="text-white w-6 h-6"
                                        icon="material-symbols:keyboard-double-arrow-right-rounded"
                                    />
                                </div>
                                <span v-if="sidebarCollapsed" class="font-bold text-base">{{
                                    t('SIDEBAR_COLLAPSE')
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </Transition>
    <Teleport to="body">
        <div v-if="settingsOpen" class="fixed inset-0 z-[200] pointer-events-none">
            <!-- click-away overlay that starts at the sidebar’s right edge -->
            <Transition
                appear
                enter-active-class="transition-[width,opacity] duration-300 ease-out overflow-hidden"
                enter-from-class="!w-0 opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-[width,opacity] duration-200 ease-in overflow-hidden"
                leave-to-class="!w-0 opacity-0"
            >
                <div
                    v-if="settingsLoaded"
                    :style="{ left: panelLeft + 'px' }"
                    class="fixed inset-0 bg-black/20 backdrop-blur-sm pointer-events-auto"
                    @click="toggleSettings"
                ></div>
            </Transition>

            <Transition
                appear
                enter-active-class="transition-[width,opacity] duration-300 ease-out overflow-hidden"
                enter-from-class="!w-0 opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-[width,opacity] duration-200 ease-in overflow-hidden"
                leave-to-class="!w-0 opacity-0"
            >
                <aside
                    v-if="settingsLoaded"
                    :style="{ left: panelLeft + 'px', width: '20rem' }"
                    class="fixed top-0 h-screen bg-white shadow-2xl pointer-events-auto overflow-y-auto small-scrollbar"
                >
                    <div
                        class="flex items-center bg-[var(--color-background)] justify-between px-4 py-3 border-b border-gray-200"
                    >
                        <h3 class="text-base font-semibold text-white">{{ t('SETTINGS') }}</h3>
                        <button
                            aria-label="Close"
                            class="p-2 rounded hover:bg-[var(--color-highlight-dark)]"
                            type="button"
                            @click="toggleSettings"
                        >
                            <Icon class="w-5 h-5 text-white" icon="material-symbols:close-rounded" />
                        </button>
                    </div>

                    <!-- Settings links -->
                    <nav class="p-3 flex flex-col gap-1">
                        <SettingSideBarItem
                            v-for="item in settingsSideBarItems"
                            :key="item.label"
                            :icon="item.icon"
                            :label="item.label"
                            :to="item.to"
                            @click="toggleSettings"
                        />
                    </nav>
                </aside>
            </Transition>
        </div>
    </Teleport>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import type { SidebarItemInterface } from '@/modules/types/support/navigation/SidebarItemInterface';
    import SidebarItem from '@/components/navigation/SidebarItem.vue';
    import { Icon } from '@iconify/vue';
    import http from '@/utils/http';
    import { useRoute, useRouter } from 'vue-router';
    import { useModalService } from '@/composables/useModalService';
    import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget.ts';
    import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType.ts';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
    import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
    import { ModalVariant } from '@/modules/enums/support/modals/ModalVariant.ts';
    import ConfirmationModal from '@/components/modals/content-modals/ConfirmationModal.vue';
    import { AxiosError } from 'axios';
    import { useI18n } from 'vue-i18n';
    import SettingSideBarItem from '@/components/navigation/SettingSideBarItem.vue';
    import { useAuthStore } from '@/stores/auth';

    defineComponent({
        name: 'Sidebar',
    });

    const sidebarCollapsed = ref(false);
    const router = useRouter();
    const currentRoute = useRoute();

    const authStore = useAuthStore();
    const _modalService = useModalService();
    const { t } = useI18n();

    const settingsOpen = ref(false);
    const settingsLoaded = ref(false);
    const asideRef = ref<HTMLElement | null>(null);
    const panelLeft = ref(0);

    const isInSettingsSection = computed(() => {
        return currentRoute.path.startsWith('/admin/settings/');
    });

    function measurePanelLeft() {
        const rect = asideRef.value?.getBoundingClientRect();
        panelLeft.value = rect ? Math.round(rect.right) : 0;
    }

    let ro: ResizeObserver | null = null;

    function onAsideTransitionEnd(e: TransitionEvent) {
        if (e.propertyName === 'width') measurePanelLeft();
    }

    const baseSidebarItems: SidebarItemInterface[] = [
        {
            label: 'SIDEBAR_ITEM_DASHBOARD',
            to: '/admin/dashboard',
            icon: 'material-symbols:monitoring',
        },
        {
            label: 'GENERAL_EMPLOYEE',
            to: '/admin/employees',
            icon: 'material-symbols:badge-outline-rounded',
        },
        {
            label: 'EMPLOYEE_ROLE',
            to: '/admin/employee-roles',
            icon: 'material-symbols:workspace-premium-outline-rounded',
        },
        {
            label: 'EMPLOYEE_LOCATION',
            to: '/admin/employee-locations',
            icon: 'material-symbols:location-on-outline-rounded',
        },
        {
            label: 'GENERAL_SKILL',
            to: '/admin/skills',
            icon: 'material-symbols:verified-outline-rounded',
        },
        {
            label: 'GENERAL_CERTIFICATE',
            to: '/admin/certificates',
            icon: 'material-symbols:workspace-premium-outline',
        },
    ];

    const bottomSidebarItems: SidebarItemInterface[] = [
        //TODO add more?
    ];

    const settingsSideBarItems: SidebarItemInterface[] = [
        {
            label: 'ACCESS_CONTROL',
            to: '/admin/settings/access-control',
            icon: 'material-symbols:shield-person-outline',
        },
        {
            label: 'USERS',
            to: '/admin/settings/users',
            icon: 'material-symbols:group-outline-rounded',
        },
    ];

    onMounted(() => {
        sidebarCollapsed.value = JSON.parse(localStorage.getItem('isSidebarOpen') || 'true');
    });

    function toggleSideBar(): void {
        sidebarCollapsed.value = !sidebarCollapsed.value;
        localStorage.setItem('isSidebarOpen', JSON.stringify(sidebarCollapsed.value));
    }

    function goToEmployees(): void {
        router.push('/admin/employees');
    }

    function getBottomSidebarItems(): SidebarItemInterface[] {
        const items: SidebarItemInterface[] = [];

        if (import.meta.env.VITE_APP_ENVIRONMENT === 'dev') {
            items.push({
                label: 'SIDEBAR_ITEM_DEVELOPMENT',
                to: '/admin/development',
                icon: 'material-symbols:code-rounded',
            });
        }

        return [...items, ...bottomSidebarItems];
    }

    function getSidebarItems(): SidebarItemInterface[] {
        const items: SidebarItemInterface[] = [];

        return [...baseSidebarItems, ...items]
            .map((item) => {
                if (!item.permissionEntity) {
                    return item;
                }

                return {
                    ...item,
                    visible: authStore.canViewEntity(item.permissionEntity),
                };
            })
            .filter((item) => item.visible !== false);
    }

    function updatePanelLeft() {
        const rect = asideRef.value?.getBoundingClientRect();
        panelLeft.value = rect ? Math.round(rect.right) : 0;
    }

    function toggleSettings() {
        if (settingsOpen.value) {
            settingsLoaded.value = false;
            setTimeout(() => {
                settingsOpen.value = false;
                nextTick(updatePanelLeft);
            }, 200);
        } else {
            settingsOpen.value = true;
            settingsLoaded.value = true;
            nextTick(updatePanelLeft);
        }
    }

    function logout() {
        _modalService.open({
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
                        router.push('/login');
                    })
                    .catch((error: AxiosError) => {
                        console.error('Logout failed:', error);
                    });
            },
        });
    }

    onMounted(() => {
        measurePanelLeft();

        if ('ResizeObserver' in window) {
            ro = new ResizeObserver(() => measurePanelLeft());
            if (asideRef.value) ro.observe(asideRef.value);
        }

        asideRef.value?.addEventListener('transitionend', onAsideTransitionEnd);

        window.addEventListener('resize', measurePanelLeft);
    });

    onBeforeUnmount(() => {
        ro?.disconnect();
        ro = null;
        asideRef.value?.removeEventListener('transitionend', onAsideTransitionEnd);
        window.removeEventListener('resize', measurePanelLeft);
    });

    watch(
        () => sidebarCollapsed.value,
        async () => {
            await nextTick();
            measurePanelLeft();
        },
    );
</script>
