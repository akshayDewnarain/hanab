<template>
    <div class="contents">
        <template v-if="!loading">
            <slot></slot>
        </template>
        <template v-else>
            <slot name="loading"></slot>
        </template>
    </div>
</template>

<script lang="ts" setup>

    defineProps<{
        loading: boolean;
    }>();
</script>
