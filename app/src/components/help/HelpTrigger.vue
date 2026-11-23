<template>
    <button
        type="button"
        class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-list-border bg-list-surface text-list-text transition-colors duration-200 hover:border-primary hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none"
        :aria-label="t('HELP_OPEN_CONTEXT')"
        @click="show(context)"
    >
        <Icon icon="material-symbols:help-outline-rounded" class="size-5" />
    </button>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { useHelpCenter } from '@/composables/useHelpCenter';

    defineProps<{ context: string }>();

    const { t } = useI18n();
    const { show } = useHelpCenter();
</script>
