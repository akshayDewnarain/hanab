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
    import { defineProps } from 'vue';

    defineProps<{
        loading: boolean;
    }>();
</script>
