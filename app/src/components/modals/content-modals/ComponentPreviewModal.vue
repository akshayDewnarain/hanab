<template>
    <component :is="previewComponent" v-if="previewComponent && model" :model="model" />
    <div v-else class="p-4 text-center text-gray-500">No preview component available</div>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import type { Component } from 'vue';

    const props = defineProps<{
        props?: Record<string, unknown>;
    }>();

    const model = computed(() => {
        return (props.props?.model as Record<string, unknown> | undefined) || null;
    });

    const previewComponent = computed(() => {
        return props.props?.previewComponent as Component | undefined;
    });
</script>
