<template>
    <button
        v-if="canCreate"
        type="button"
        class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg bg-[var(--color-primary)] px-3 text-sm font-semibold text-on-brand shadow-sm transition-colors duration-200 ease-out hover:bg-[var(--color-primary-hover)] motion-reduce:transition-none"
        @click="emit('create')"
    >
        <Icon class="size-[18px]" icon="material-symbols:add-rounded" />
        {{ t('MODAL_CREATE') }}
    </button>
    <button
        v-if="displayTableConfiguration"
        type="button"
        class="inline-flex size-9 cursor-pointer items-center justify-center rounded-lg border border-list-border bg-list-surface text-list-text transition-colors duration-200 ease-out hover:border-primary hover:text-primary motion-reduce:transition-none"
        :aria-label="t('TABLE_SETTINGS')"
        :title="t('TABLE_SETTINGS')"
        @click="emit('settings')"
    >
        <Icon class="size-[18px]" icon="material-symbols:settings-outline-rounded" />
    </button>
    <button
        type="button"
        class="inline-flex size-9 cursor-pointer items-center justify-center rounded-lg border border-list-border bg-list-surface text-list-text transition-colors duration-200 ease-out hover:border-primary hover:text-primary motion-reduce:transition-none"
        :aria-label="t('TABLE_REFRESH')"
        :title="t('TABLE_REFRESH')"
        @click="emit('refresh')"
    >
        <Icon class="size-[18px]" icon="material-symbols:refresh-rounded" />
    </button>
    <button
        v-for="(customAction, index) in customActions"
        :key="index"
        type="button"
        class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border border-list-border bg-list-surface px-2.5 text-sm font-medium text-list-text"
        @click="emit('custom', customAction)"
    >
        <Icon class="size-[18px]" :icon="customAction.icon" />
        <span class="hidden sm:inline">{{ t(customAction.label) }}</span>
    </button>
    <ImportExportActions
        compact
        :filters="filters"
        :refresh="refresh"
        :search="search"
        :transfer-key="transferKey ?? null"
    />
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import ImportExportActions from '@/components/import-export/ImportExportActions.vue';
    import type { ListViewActionType } from '@/modules/types/support/list-views/ListViewActionType.ts';

    defineOptions({ name: 'EmployeeListActions' });

    defineProps<{
        canCreate: boolean;
        displayTableConfiguration: boolean;
        customActions?: ListViewActionType[];
        filters: Record<string, unknown>;
        refresh: () => void;
        search: string;
        transferKey?: string | null;
    }>();

    const emit = defineEmits<{
        create: [];
        settings: [];
        refresh: [];
        custom: [action: ListViewActionType];
    }>();

    const { t } = useI18n();
</script>
