<template>
    <Transition
        :appear="_appear"
        enter-active-class="slide-in-enter-active"
        enter-from-class="slide-in-enter-from"
        enter-to-class="slide-in-enter-to"
        leave-active-class="slide-in-leave-active"
        leave-from-class="slide-in-leave-from"
        leave-to-class="slide-in-leave-to"
    >
        <slot />
    </Transition>
</template>

<script lang="ts" setup>
    import { defineComponent } from 'vue';

    defineComponent({
        name: 'SlideInRightToLeftComponent',
    });

    const props = defineProps<{
        appear?: boolean;
        enterFrom?: string;
        leaveFrom?: string;
        enterActive?: string;
        leaveActive?: string;
        enterTo?: string;
        leaveTo?: string;
    }>();

    const _appear = props.appear ?? true;
    const _enterFrom = props.enterFrom ?? 'translateX(100%)';
    const _leaveFrom = props.leaveFrom ?? 'translateX(0)';
    const _enterActive = props.enterActive ?? 'transform 0.5s ease-in-out';
    const _leaveActive = props.leaveActive ?? 'transform 0.5s ease-in-out';
    const _enterTo = props.enterTo ?? 'translateX(0)';
    const _leaveTo = props.leaveTo ?? 'translateX(100%)';
</script>

<style scoped>
    .slide-in-enter-from {
        transform: v-bind(_enterFrom);
        opacity: 0;
    }

    .slide-in-leave-to {
        transform: v-bind(_leaveTo);
        opacity: 0;
    }

    .slide-in-enter-to {
        transform: v-bind(_enterTo);
        opacity: 1;
    }

    .slide-in-leave-from {
        transform: v-bind(_leaveFrom);
        opacity: 1;
    }

    .slide-in-enter-active {
        transition: v-bind(_enterActive);
    }

    .slide-in-leave-active {
        transition: v-bind(_leaveActive);
    }
</style>
