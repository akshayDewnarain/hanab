<template>
    <div class="flex items-center justify-center">
        <span class="badge-secondary">{{ formattedDate }}</span>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineProps } from 'vue';
    import dayjs from 'dayjs';

    const props = defineProps<{
        value: string;
        options?: Record<string, string>;
    }>();

    const formattedDate = computed(() => {
        const format = props.options?.format ?? 'DD MMM YYYY HH:mm';
        return dayjs(props.value).format(format);
    });
</script>
