<template>
    <section class="rounded-xl border border-list-border bg-list-surface shadow-sm">
        <header class="border-b border-list-border px-3 py-2">
            <h2 class="text-sm font-semibold text-list-text">{{ t('DASHBOARD_QUICK_TITLE') }}</h2>
        </header>
        <div class="grid grid-cols-2 gap-1 p-1.5">
            <button
                v-for="action in actions"
                :key="action.key"
                type="button"
                class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-lg px-2 text-left text-xs font-medium text-list-text transition-colors duration-200 hover:bg-list-row-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none"
                @click="action.run()"
            >
                <Icon class="size-3.5 shrink-0 text-emphasis" :icon="action.icon" />
                <span class="truncate">{{ t(action.label) }}</span>
            </button>
        </div>
    </section>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { useAuthStore } from '@/stores/auth';

    defineOptions({ name: 'DashboardQuickActions' });

    const { t } = useI18n();
    const router = useRouter();
    const auth = useAuthStore();

    const actions = computed(() => {
        const items: { key: string; label: string; icon: string; run: () => void }[] = [
            { key: 'search', label: 'DASHBOARD_QUICK_SEARCH', icon: 'material-symbols:search-rounded', run: () => void router.push({ name: 'admin-employees-overview' }) },
            { key: 'organogram', label: 'DASHBOARD_QUICK_ORGANOGRAM', icon: 'material-symbols:account-tree-outline-rounded', run: () => void router.push({ name: 'admin-organogram' }) },
            { key: 'locations', label: 'DASHBOARD_QUICK_LOCATIONS', icon: 'material-symbols:location-on-outline-rounded', run: () => void router.push({ name: 'admin-employee-locations-overview' }) },
            { key: 'messages', label: 'DASHBOARD_QUICK_MESSAGES', icon: 'material-symbols:forum-outline-rounded', run: () => void router.push({ name: 'admin-messages' }) },
        ];
        if (auth.hasPermission('create employees')) {
            items.push({
                key: 'add',
                label: 'DASHBOARD_QUICK_ADD',
                icon: 'material-symbols:person-add-outline-rounded',
                run: () => void router.push({ name: 'admin-employees-overview', query: { create: '1' } }),
            });
        }
        if (auth.hasPermission('import employees')) {
            items.push({
                key: 'import',
                label: 'DASHBOARD_QUICK_IMPORT',
                icon: 'material-symbols:upload-rounded',
                run: () => void router.push({ name: 'admin-data-transfer' }),
            });
        }
        return items;
    });
</script>
