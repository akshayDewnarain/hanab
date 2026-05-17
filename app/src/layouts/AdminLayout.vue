<template>
    <main class="flex h-full w-full overflow-hidden">
        <Sidebar />
        <div class="flex flex-col h-full w-full bg-gray-200 overflow-hidden">
            <Topbar />
            <div class="flex flex-col flex-grow overflow-hidden">
                <router-view></router-view>
            </div>
            <ModalContainer />
        </div>
    </main>
</template>

<script lang="ts" setup>
    import Sidebar from '@/components/navigation/Sidebar.vue';
    import Topbar from '@/components/navigation/Topbar.vue';
    import ModalContainer from '@/components/modals/ModalContainer.vue'

    defineOptions({
        name: 'admin-layout',
    });
</script>

<style scoped></style>
