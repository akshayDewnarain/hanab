<template>
    <Transition
        appear
        enter-active-class="opacity-enter-active"
        enter-from-class="opacity-enter-from"
        enter-to-class="opacity-enter-to"
        leave-active-class="opacity-leave-active"
        leave-from-class="opacity-leave-from"
        leave-to-class="opacity-leave-to"
    >
        <slot />
    </Transition>
</template>

<script lang="ts" setup>
    import { defineComponent } from 'vue';

    defineComponent({
        name: 'OpacityFadeComponent',
    });
</script>

<style scoped>
    .opacity-enter-active,
    .opacity-leave-active {
        transition: opacity 0.5s ease;
    }

    .opacity-enter-from,
    .opacity-leave-to {
        opacity: 0;
    }

    .opacity-enter-to,
    .opacity-leave-from {
        opacity: 1;
    }
</style>
