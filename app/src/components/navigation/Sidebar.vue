<template>
    <div ref="asideRef" class="h-full shrink-0">
        <Sidebar
            v-model:expanded="sidebarExpanded"
            :items="navigationItems"
            :footer-items="footerItems"
            :navigation-label="t('SIDEBAR_NAVIGATION')"
            :footer-label="t('SIDEBAR_ACTIONS')"
            :collapse-label="t('SIDEBAR_COLLAPSE')"
            :expand-label="t('SIDEBAR_EXPAND')"
            @select="onSelect"
        >
            <template #brand="{ expanded }">
                <RouterLink
                    to="/admin/employees"
                    class="flex items-center justify-center rounded-sm border border-sidebar-logo-border bg-sidebar-logo-background p-1 shadow-sm"
                >
                    <img
                        alt="Hanab"
                        :class="expanded ? 'h-9 w-auto max-w-full object-contain' : 'h-10 w-10 object-contain'"
                        :src="expanded ? '/hanab-logo-long.png' : '/hanab-logo.png'"
                    />
                </RouterLink>
            </template>
            <template #icon="{ item }">
                <Icon v-if="item.icon" :icon="item.icon" />
            </template>
        </Sidebar>
    </div>
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
                    class="fixed top-0 h-screen bg-list-surface text-text shadow-2xl pointer-events-auto overflow-y-auto small-scrollbar"
                >
                    <div
                        class="flex items-center justify-between border-b border-sidebar-border-highlight bg-sidebar-background px-4 py-3"
                    >
                        <h3 class="text-base font-semibold text-[var(--color-sidebar-item-text)]">
                            {{ t('SETTINGS') }}
                        </h3>
                        <button
                            :aria-label="t('GENERAL_CLOSE')"
                            class="rounded p-2 hover:bg-[var(--color-sidebar-item-highlight-dark)]"
                            type="button"
                            @click="toggleSettings"
                        >
                            <Icon
                                class="h-5 w-5 text-[var(--color-sidebar-icon)]"
                                icon="material-symbols:close-rounded"
                            />
                        </button>
                    </div>

                    <!-- Settings links -->
                    <nav class="p-3 flex flex-col gap-1">
                        <SettingSideBarItem
                            v-for="item in visibleSettings"
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
    import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import type { SidebarItemInterface } from '@/modules/types/support/navigation/SidebarItemInterface';
    import { Sidebar, type SidebarItem } from '@freelance/vue-ui';
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
    import { knowledgeBaseVisible } from '@/modules/knowledge/visibility';

    defineOptions({ name: 'HanabSidebar' });

    const sidebarExpanded = ref(true);
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
            label: 'SIDEBAR_ITEM_COLLEAGUES',
            to: '/admin/employees',
            icon: 'material-symbols:badge-outline-rounded',
        },
        {
            label: 'SIDEBAR_ITEM_ORGANOGRAM',
            to: '/admin/organogram',
            icon: 'material-symbols:account-tree-outline-rounded',
        },
        {
            label: 'SIDEBAR_ITEM_MESSAGES',
            to: '/admin/messages',
            icon: 'material-symbols:forum-outline-rounded',
        },
        {
            label: 'KNOWLEDGE_TITLE',
            to: '/admin/knowledge',
            icon: 'material-symbols:menu-book-outline-rounded',
        },
        {
            label: 'EMPLOYEE_ROLE',
            to: '/admin/employee-roles',
            icon: 'material-symbols:workspace-premium-outline-rounded',
            permissionEntity: 'employee_roles',
        },
        {
            label: 'ORGANIZATION_LEVEL',
            to: '/admin/organization-levels',
            icon: 'material-symbols:layers-outline-rounded',
            permissionEntity: 'organization_levels',
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
            permissionEntity: 'skills',
        },
        {
            label: 'GENERAL_CERTIFICATE',
            to: '/admin/certificates',
            icon: 'material-symbols:workspace-premium-outline',
            permissionEntity: 'certificates',
        },
    ];

    const bottomSidebarItems: SidebarItemInterface[] = [
        //TODO add more?
    ];

    const settingsSideBarItems: SidebarItemInterface[] = [
        {
            label: 'ACCOUNT_PROFILE',
            to: '/admin/account',
            icon: 'material-symbols:account-circle-outline',
        },
        {
            label: 'ACCOUNT_SECURITY',
            to: '/admin/settings/security',
            icon: 'material-symbols:shield-lock-outline',
        },
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

    const visibleSettings = computed(() => settingsSideBarItems.filter((item) => {
        if (item.to === '/admin/settings/users') return authStore.canViewEntity('users');
        if (item.to === '/admin/settings/access-control') return authStore.canViewEntity('roles');
        return true;
    }));

    onMounted(() => {
        try {
            const stored = localStorage.getItem('isSidebarOpen');
            if (stored !== null) sidebarExpanded.value = stored !== 'false';
        } catch {
            // Keep navigation usable when browser storage is unavailable.
        }
    });

    watch(sidebarExpanded, (expanded) => {
        try {
            localStorage.setItem('isSidebarOpen', JSON.stringify(expanded));
        } catch {
            // Persistence is optional.
        }
    });

    function toSharedItem(item: SidebarItemInterface): SidebarItem {
        const target = router.resolve(item.to);
        const path = currentRoute.path.replace(/\/+$/, '') || '/';
        const targetPath = target.path.replace(/\/+$/, '') || '/';
        return {
            id: item.to,
            label: t(item.label),
            icon: item.icon,
            href: target.href,
            active:
                !(item.inactiveWhenPathStartsWith && path.startsWith(item.inactiveWhenPathStartsWith)) &&
                (item.activeExact ? path === targetPath : path === targetPath || path.startsWith(`${targetPath}/`)),
        };
    }

    const navigationItems = computed(() => getSidebarItems().map(toSharedItem));
    const footerItems = computed<SidebarItem[]>(() => [
        ...getBottomSidebarItems().map(toSharedItem),
        toSharedItem({
            label: 'HELP_TITLE',
            to: '/admin/help',
            icon: 'material-symbols:help-outline-rounded',
        }),
        {
            id: 'settings',
            label: t('SIDEBAR_ITEM_SETTINGS'),
            icon: 'material-symbols:settings-rounded',
            active: isInSettingsSection.value,
        },
        { id: 'logout', label: t('SIDEBAR_ITEM_LOGOUT'), icon: 'material-symbols:logout-rounded' },
    ]);

    function onSelect(item: SidebarItem, event: MouseEvent): void {
        if (event.defaultPrevented) return;
        if (item.id === 'settings') {
            toggleSettings();
        } else if (item.id === 'logout') {
            logout();
        } else if (
            item.href &&
            event.button === 0 &&
            !event.metaKey &&
            !event.ctrlKey &&
            !event.shiftKey &&
            !event.altKey
        ) {
            event.preventDefault();
            void router.push(item.id);
        }
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
            .filter((item) => item.to !== '/admin/knowledge' || knowledgeBaseVisible)
            .map((item) => {
                if (item.permission) {
                    return {
                        ...item,
                        visible: authStore.hasPermission(item.permission),
                    };
                }

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
        () => sidebarExpanded.value,
        async () => {
            await nextTick();
            measurePanelLeft();
        },
    );
</script>
