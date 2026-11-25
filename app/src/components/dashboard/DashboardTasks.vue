<template>
    <section class="rounded-xl border border-list-border bg-list-surface shadow-sm">
        <header class="border-b border-list-border px-3 py-2">
            <h2 class="text-sm font-semibold text-list-text">{{ t('DASHBOARD_NEEDS_ATTENTION') }}</h2>
        </header>
        <div class="px-1.5 py-1.5">
            <div v-if="loading" class="flex flex-col gap-1.5 px-1.5 py-1">
                <Skeleton v-for="index in 4" :key="index" class="h-8 rounded-lg" />
            </div>
            <div v-else-if="error" class="flex items-center justify-between gap-3 px-1.5 py-2">
                <p class="text-sm text-list-text">{{ t('DASHBOARD_TASKS_ERROR') }}</p>
                <button type="button" class="cursor-pointer text-sm font-semibold text-emphasis" @click="load">{{ t('DASHBOARD_RETRY') }}</button>
            </div>
            <p v-else-if="actions.length === 0" class="px-1.5 py-2 text-sm text-list-text-muted">{{ t('DASHBOARD_TASKS_EMPTY') }}</p>
            <ul v-else class="m-0 flex list-none flex-col p-0">
                <li v-for="action in actions" :key="action.key">
                    <button
                        type="button"
                        class="flex h-9 w-full cursor-pointer items-center gap-2 rounded-lg px-1.5 text-left transition-colors duration-200 hover:bg-list-row-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none"
                        :aria-label="t(action.title_key, { count: action.count })"
                        @click="open(action)"
                    >
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-md bg-primary-light text-emphasis">
                            <Icon class="size-3.5" :icon="action.icon" />
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm text-list-text">{{ t(compactTitle(action.key)) }}</span>
                        <span class="shrink-0 text-sm font-semibold tabular-nums text-list-text">{{ action.count }}</span>
                        <Icon class="size-4 shrink-0 text-list-text-muted" icon="material-symbols:chevron-right-rounded" />
                    </button>
                </li>
            </ul>
        </div>
    </section>
</template>

<script lang="ts" setup>
    import { onMounted, ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import Skeleton from '@/components/skeleton/Skeleton.vue';
    import { dashboardRoute, type DashboardAction } from '@/modules/dashboard/dashboardTargets';
    import http from '@/utils/http';

    defineOptions({ name: 'DashboardTasks' });

    const compactTitles: Record<string, string> = {
        missing_skills: 'DASHBOARD_TASK_SKILLS_COMPACT',
        missing_certificates: 'DASHBOARD_TASK_CERTIFICATES_COMPACT',
        missing_business_phone: 'DASHBOARD_TASK_PHONE_COMPACT',
        missing_business_email: 'DASHBOARD_TASK_EMAIL_COMPACT',
        missing_location: 'DASHBOARD_TASK_LOCATION_COMPACT',
        locations_without_coordinates: 'DASHBOARD_TASK_MAP_COMPACT',
    };

    function compactTitle(key: string): string {
        return compactTitles[key] ?? 'DASHBOARD_NEEDS_ATTENTION';
    }

    const { t, te } = useI18n();
    const router = useRouter();
    const loading = ref(true);
    const error = ref(false);
    const actions = ref<DashboardAction[]>([]);
    let requestId = 0;

    function open(action: DashboardAction): void {
        const name = dashboardRoute(action.target.name);
        if (!name) return;
        void router.push({ name, query: action.target.query });
    }

    async function load(): Promise<void> {
        const current = ++requestId;
        loading.value = actions.value.length === 0;
        error.value = false;
        try {
            const response = await http.get<{ data: DashboardAction[] }>('dashboard/actions');
            if (current !== requestId) return;
            actions.value = (response.data.data ?? []).filter((action) => te(action.title_key));
        } catch {
            if (current !== requestId) return;
            error.value = true;
        } finally {
            if (current === requestId) loading.value = false;
        }
    }

    onMounted(() => {
        void load();
    });
</script>
