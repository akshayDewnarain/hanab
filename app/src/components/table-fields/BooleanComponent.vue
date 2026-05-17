<template>
    <div class="flex justify-center items-center">
        <div v-if="value">
            <Icon class="w-6 h-6 text-[var(--color-success)]" icon="material-symbols:check-circle-outline-rounded" />
        </div>
        <div v-else>
            <Icon class="w-6 h-6 text-[var(--color-error)]" icon="material-symbols:cancel-outline-rounded" />
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { defineComponent, defineProps } from 'vue';
    import { Icon } from '@iconify/vue';

    defineComponent({
        name: 'BooleanComponent',
    });

    defineProps<{ value: boolean }>();
</script>
