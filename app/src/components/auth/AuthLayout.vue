<template>
    <UiAuthLayout :width="width">
        <template #backdrop>
            <div class="absolute inset-0 scale-105 bg-[url('/background-cover.png')] bg-cover bg-center"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-[var(--color-primary)]/70 via-[var(--color-primary)]/35 to-black/25 backdrop-blur-[3px]"></div>
            <div class="absolute inset-0 bg-black/20 backdrop-blur-sm"></div>
        </template>
        <template #brand>
            <img alt="Hanab" class="h-8 w-auto max-w-40 object-contain" src="/hanab-logo-full.png" />
        </template>
        <template v-if="$slots.progress" #progress>
            <slot name="progress" />
        </template>
        <slot />
    </UiAuthLayout>
</template>

<script lang="ts" setup>
    import { UiAuthLayout, type AuthLayoutWidth } from '@freelance/vue-ui';

    defineOptions({ name: 'AuthLayout' });

    withDefaults(defineProps<{ width?: AuthLayoutWidth }>(), { width: 'compact' });
</script>
