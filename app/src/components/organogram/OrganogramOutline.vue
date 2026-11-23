<template>
    <ul class="organogram-outline">
        <li v-for="node in nodes" :key="node.id">
            <button type="button" @click="emit('open', node)">{{ node.name }}</button>
            <span v-if="node.role"> · {{ node.role }}</span>
            <span v-if="node.location"> · {{ node.location }}</span>
            <OrganogramOutline v-if="childrenOf(node.id).length" :nodes="childrenOf(node.id)" :edges="edges" @open="emit('open', $event)" />
        </li>
    </ul>
</template>

<script setup lang="ts">
    import type { OrganogramEdge, OrganogramNode } from '@/modules/support/organogram/types';

    const props = defineProps<{
        nodes: OrganogramNode[];
        edges: OrganogramEdge[];
    }>();

    const emit = defineEmits<{ open: [OrganogramNode] }>();

    function childrenOf(id: string): OrganogramNode[] {
        const childIds = new Set(props.edges.filter((edge) => edge.source === id).map((edge) => edge.target));
        return props.nodes.filter((node) => childIds.has(node.id));
    }
</script>
