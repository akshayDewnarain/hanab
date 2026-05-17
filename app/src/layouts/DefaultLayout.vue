<template>
    <main class="h-full">
        <router-view></router-view>
    </main>
</template>

<script lang="ts" setup>
    defineOptions({
        name: 'default-layout',
    });
</script>

<style scoped></style>
