<template>
    <router-view></router-view>
</template>

<script lang="ts" setup>
    import { setupDocumentTitle } from '@/composables/useDocumentTitle';
    import { useRouter } from 'vue-router';

    setupDocumentTitle(useRouter());
</script>

<style scoped></style>
