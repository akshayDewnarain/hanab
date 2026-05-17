<template>
    <transition name="collapse-y">
        <div v-if="show" :class="customClasses">
            <slot></slot>
        </div>
    </transition>
</template>

<script lang="ts" setup>
    import { defineComponent } from 'vue';

    defineComponent({
        name: 'CollapseTransition',
    });
    import { onMounted, ref } from 'vue';

    const height = ref('50px');

    const props = defineProps<{
        show: boolean;
        customClasses?: string | string[] | Record<string, boolean>;
        maxHeight?: string;
        duration?: number;
    }>();

    onMounted(() => {
        height.value = props.maxHeight ? props.maxHeight : '50px';
    });
</script>

<style scoped>
    .collapse-y-enter-active,
    .collapse-y-leave-active {
        transition:
            max-height 0.5s ease,
            opacity 0.5s ease;
    }

    .collapse-y-enter-from,
    .collapse-y-leave-to {
        max-height: 0;
        opacity: 0;
    }

    .collapse-y-enter-to,
    .collapse-y-leave-from {
        max-height: v-bind(height);
        opacity: 1;
    }
</style>
