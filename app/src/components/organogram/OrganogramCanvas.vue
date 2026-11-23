<template>
    <div ref="root" class="organogram-canvas">
        <VueFlow
            id="organogram"
            :nodes="flowNodes"
            :edges="flowEdges"
            :node-types="nodeTypes"
            :edge-types="edgeTypes"
            :min-zoom="0.15"
            :max-zoom="2"
            :nodes-draggable="false"
            :nodes-connectable="false"
            :edges-updatable="false"
            :pan-on-drag="true"
            :zoom-on-scroll="true"
            :zoom-on-pinch="true"
            :prevent-scrolling="true"
            fit-view-on-init
        >
            <Background variant="dots" :gap="20" :size="1.4" :color="dotColor" />
            <MiniMap
                v-if="showMinimap"
                pannable
                zoomable
                :width="188"
                :height="112"
                :node-color="minimapColor"
                :node-stroke-color="minimapStroke"
                :node-border-radius="4"
                :node-stroke-width="1"
                :mask-color="minimapMaskColor"
                mask-stroke-color="transparent"
                :mask-border-radius="8"
                :offset-scale="10"
                :aria-label="minimapLabel"
            />
        </VueFlow>
        <slot />
        <p v-if="empty" class="organogram-empty">{{ emptyLabel }}</p>
        <div class="organogram-controls" role="group" :aria-label="controlsLabel">
            <button type="button" :aria-label="zoomInLabel" @click="zoomIn()">
                <Icon icon="material-symbols:add-rounded" />
            </button>
            <button type="button" :aria-label="zoomOutLabel" @click="zoomOut()">
                <Icon icon="material-symbols:remove-rounded" />
            </button>
            <button type="button" :aria-label="fitLabel" @click="fit()">
                <Icon icon="material-symbols:filter-center-focus" />
            </button>
            <button type="button" :aria-label="minimapLabel" :aria-pressed="showMinimap" @click="emit('toggle-minimap')">
                <Icon icon="material-symbols:menu-book-outline-rounded" />
            </button>
            <button type="button" :aria-label="fullscreenLabel" @click="toggleFullscreen()">
                <Icon :icon="isFullscreen ? 'material-symbols:fullscreen-exit-rounded' : 'material-symbols:fullscreen-rounded'" />
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { Background } from '@vue-flow/background';
    import { MiniMap } from '@vue-flow/minimap';
    import { VueFlow, useVueFlow, type Edge, type Node, type NodeTypesObject } from '@vue-flow/core';
    import { Icon } from '@iconify/vue';
    import { computed, markRaw, nextTick, onMounted, ref, watch } from 'vue';
    import '@vue-flow/core/dist/style.css';
    import '@vue-flow/core/dist/theme-default.css';
    import '@vue-flow/minimap/dist/style.css';
    import EmployeeNode from '@/components/organogram/EmployeeNode.vue';
    import LocationGroup from '@/components/organogram/LocationGroup.vue';
    import ReportingEdge from '@/components/organogram/ReportingEdge.vue';
    import RoleNode from '@/components/organogram/RoleNode.vue';
    import type { LaidOutOrganogramNode } from '@/composables/useOrganogram';
    import type { OrganogramEdge } from '@/modules/support/organogram/types';

    const props = defineProps<{
        nodes: LaidOutOrganogramNode[];
        edges: OrganogramEdge[];
        showMinimap: boolean;
        fitRequest: number;
        focusRequest: number;
        focusId: string | null;
        resetRequest: number;
        empty: boolean;
        emptyLabel: string;
        controlsLabel: string;
        zoomInLabel: string;
        zoomOutLabel: string;
        fitLabel: string;
        minimapLabel: string;
        fullscreenLabel: string;
    }>();

    const emit = defineEmits<{ 'toggle-minimap': [] }>();

    const nodeTypes: NodeTypesObject = {
        employee: markRaw(EmployeeNode) as NodeTypesObject[string],
        role: markRaw(RoleNode) as NodeTypesObject[string],
        group: markRaw(LocationGroup) as NodeTypesObject[string],
    };
    const edgeTypes = {
        reporting: markRaw(ReportingEdge),
    };

    const root = ref<HTMLElement | null>(null);
    const isFullscreen = ref(false);
    const dotColor = ref('#c5ced8');
    const minimapNodeColor = ref('#d5dbe3');
    const minimapGroupColor = ref('#e7eef5');
    const minimapMaskColor = ref('rgba(241, 245, 249, 0.92)');
    const { zoomIn, zoomOut, fitView, setViewport, setCenter } = useVueFlow({ id: 'organogram' });

    const flowNodes = computed<Node[]>(() =>
        props.nodes.map((node) => ({
            id: node.id,
            type: node.type,
            position: { x: node.x, y: node.y },
            data: node,
            draggable: false,
            connectable: false,
            selectable: node.type !== 'group',
            zIndex: node.type === 'group' ? 0 : 10,
            class: node.type === 'group' ? 'organogram-group-node' : undefined,
            width: node.width,
            height: node.height,
            style: node.type === 'group' ? { width: `${node.width}px`, height: `${node.height}px` } : undefined,
        })),
    );

    const flowEdges = computed<Edge[]>(() =>
        props.edges.map((edge) => ({
            id: edge.id,
            source: edge.source,
            target: edge.target,
            type: 'reporting',
            data: { crossLocation: edge.crossLocation === true },
        })),
    );

    function minimapColor(node: Node): string {
        return node.type === 'group' ? minimapGroupColor.value : minimapNodeColor.value;
    }

    function minimapStroke(node: Node): string {
        return node.type === 'group' ? 'rgba(100, 116, 139, 0.55)' : 'transparent';
    }

    function fit(): void {
        void fitView({ padding: 0.16, duration: 200, maxZoom: 1 });
    }

    function readableFit(): void {
        const element = root.value;
        if (!element || props.nodes.length === 0) {
            fit();
            return;
        }
        const groups = props.nodes.filter((node) => node.groupKind === 'location');
        if (groups.length >= 2) {
            const minX = Math.min(...groups.map((node) => node.x));
            const maxX = Math.max(...groups.map((node) => node.x + node.width));
            const top = Math.min(...groups.map((node) => node.y));
            const above = props.nodes.filter((node) => node.type === 'employee' && node.y + node.height <= top + 1);
            const minY = above.reduce((value, node) => Math.min(value, node.y), top);
            const width = Math.max(1, maxX - minX);
            const zoom = Math.min(1, Math.max(0.72, (element.clientWidth * 0.88) / width));
            const centerY = minY - 36 + element.clientHeight / (2 * zoom);
            void setCenter(minX + width / 2, centerY, { zoom, duration: 220 });
            return;
        }
        let minX = Infinity;
        let minY = Infinity;
        let maxX = -Infinity;
        let maxY = -Infinity;
        for (const node of props.nodes) {
            minX = Math.min(minX, node.x);
            minY = Math.min(minY, node.y);
            maxX = Math.max(maxX, node.x + node.width);
            maxY = Math.max(maxY, node.y + node.height);
        }
        const zoom = Math.min(1, (element.clientWidth * 0.86) / Math.max(1, maxX - minX), (element.clientHeight * 0.86) / Math.max(1, maxY - minY));
        if (zoom >= 0.62) {
            fit();
            return;
        }
        const anchor = groups[0] ?? props.nodes.find((node) => node.type !== 'group');
        if (!anchor) {
            fit();
            return;
        }
        void setCenter(anchor.x + anchor.width / 2, anchor.y + Math.min(element.clientHeight, 280), { zoom: 0.72, duration: 220 });
    }

    function reset(): void {
        void setViewport({ x: 48, y: 48, zoom: 1 });
    }

    function toggleFullscreen(): void {
        if (!root.value) {
            return;
        }
        if (document.fullscreenElement === root.value) {
            void document.exitFullscreen();
            return;
        }
        void root.value.requestFullscreen();
    }

    function focusHighlighted(): void {
        const node = props.nodes.find((item) => item.highlighted);
        if (!node) {
            return;
        }
        void setCenter(node.x + node.width / 2, node.y + node.height / 2, { zoom: 1, duration: 250 });
    }

    watch(
        () => props.fitRequest,
        () => {
            void nextTick(() => readableFit());
        },
    );

    watch(
        () => props.focusRequest,
        (value) => {
            if (!value || !props.focusId) {
                return;
            }
            const id = props.focusId;
            void nextTick(() => {
                void fitView({ nodes: [id], padding: 0.2, duration: 250, maxZoom: 1 });
            });
        },
    );

    watch(
        () => props.resetRequest,
        (value) => {
            if (value > 0) {
                reset();
            }
        },
    );

    watch(
        () => props.nodes.map((node) => `${node.id}:${node.highlighted}`).join('|'),
        () => {
            void nextTick(() => focusHighlighted());
        },
    );

    onMounted(() => {
        const theme = document.documentElement.dataset.theme;
        const dark = theme === 'dark';
        dotColor.value = dark ? '#475569' : '#c5ced8';
        minimapNodeColor.value = dark ? '#64748b' : '#d5dbe3';
        minimapGroupColor.value = dark ? '#334155' : '#e7eef5';
        minimapMaskColor.value = dark ? 'rgba(51, 65, 85, 0.72)' : 'rgba(241, 245, 249, 0.92)';
        document.addEventListener('fullscreenchange', () => {
            isFullscreen.value = document.fullscreenElement === root.value;
        });
    });
</script>
