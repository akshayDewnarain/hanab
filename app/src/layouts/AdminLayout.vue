<template>
    <MobileLayout v-if="isMobile" />
    <main v-else class="flex h-screen max-h-screen w-full max-w-full overflow-hidden">
        <Sidebar />
        <div class="flex h-full min-h-0 min-w-0 w-full flex-col overflow-hidden bg-gray-200">
            <Topbar />
            <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
                <div class="h-full min-h-0 min-w-0 overflow-hidden">
                    <router-view></router-view>
                </div>
            </div>
            <ModalContainer />
        </div>
    </main>
</template>

<script lang="ts" setup>
    import Sidebar from '@/components/navigation/Sidebar.vue';
    import Topbar from '@/components/navigation/Topbar.vue';
    import ModalContainer from '@/components/modals/ModalContainer.vue';
    import MobileLayout from '@/layouts/MobileLayout.vue';
    import { useViewport } from '@/composables/useViewport';

    const { isMobile } = useViewport();

    defineOptions({
        name: 'admin-layout',
    });
</script>

<style scoped></style>
