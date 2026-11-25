<template>
    <MobileLayout v-if="isMobile" />
    <main v-else class="flex h-dvh max-h-dvh w-full max-w-full overflow-hidden">
        <Sidebar />
        <div class="flex h-full min-h-0 min-w-0 w-full flex-col overflow-hidden bg-list-canvas">
            <Topbar />
            <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
                <div class="h-full min-h-0 min-w-0 overflow-hidden">
                    <router-view v-slot="{ Component, route }">
                        <UiPageTransition v-if="isHelpRoute(route)">
                            <component :is="Component" :key="route.path" />
                        </UiPageTransition>
                        <component :is="Component" v-else />
                    </router-view>
                </div>
            </div>
            <ModalContainer />
            <HelpDrawer />
        </div>
    </main>
</template>

<script lang="ts" setup>
    import Sidebar from '@/components/navigation/Sidebar.vue';
    import Topbar from '@/components/navigation/Topbar.vue';
    import ModalContainer from '@/components/modals/ModalContainer.vue';
    import MobileLayout from '@/layouts/MobileLayout.vue';
    import HelpDrawer from '@/components/help/HelpDrawer.vue';
    import { UiPageTransition } from '@freelance/vue-ui';
    import { useViewport } from '@/composables/useViewport';
    import type { RouteLocationNormalizedLoaded } from 'vue-router';

    function isHelpRoute(route: RouteLocationNormalizedLoaded): boolean {
        return String(route.name ?? '').startsWith('admin-help');
    }

    const { isMobile } = useViewport();

    defineOptions({
        name: 'admin-layout',
    });
</script>

<style scoped></style>
