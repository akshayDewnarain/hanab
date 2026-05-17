<template>
    <div
        class="flex text-sm w-fit items-center bg-[var(--color-warning-light)] text-[var(--color-warning)] rounded-full py-1 px-2 border border-[var(--color-warning)] shadow-md"
    >
        <Icon class="mr-2" icon="material-symbols:warning-rounded" />
        <span class="font-semibold"> {{ t(message) }} </span>
    </div>
</template>

<script lang="ts" setup>
    import { defineComponent } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';

    defineComponent({
        name: 'WarningBadge',
    });

    const { t } = useI18n();

    defineProps<{
        message: string;
    }>();
</script>
