<template>
    <BaseEdge :id="id" :path="path" :style="style" />
</template>

<script setup lang="ts">
    import { BaseEdge, getSmoothStepPath, type EdgeProps } from '@vue-flow/core';
    import { computed } from 'vue';

    const props = defineProps<EdgeProps>();
    const path = computed(() => getSmoothStepPath({ ...props, borderRadius: 8, offset: 24 })[0]);
    const crossLocation = computed(() => Boolean(props.data?.crossLocation));
    const style = computed(() => ({
        stroke: crossLocation.value ? 'var(--color-list-text, #334155)' : 'var(--color-sidebar-text-muted, #64748b)',
        strokeWidth: 1.5,
        strokeDasharray: crossLocation.value ? '7 5' : undefined,
    }));
</script>
