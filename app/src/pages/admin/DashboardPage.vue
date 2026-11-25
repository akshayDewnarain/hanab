<template>
    <PageLayout :label="t('DASHBOARD_TITLE')">
        <div class="relative flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 overflow-y-auto">
                <div class="flex min-h-full flex-col gap-3 px-1 pt-1 pb-3">
                    <header>
                        <h1 class="m-0 text-lg font-semibold text-list-text">{{ t('DASHBOARD_TITLE') }}</h1>
                        <p v-if="welcome" class="m-0 mt-1 text-sm text-list-text">{{ welcome }}</p>
                        <p class="m-0 mt-1 text-sm text-list-text-muted">{{ t('DASHBOARD_INTRO') }}</p>
                    </header>

                    <div
                        v-if="error"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-list-border bg-list-surface px-4 py-3"
                    >
                        <p class="m-0 text-sm text-list-text">{{ t('DASHBOARD_ERROR') }}</p>
                        <button
                            type="button"
                            class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand"
                            @click="load"
                        >
                            {{ t('DASHBOARD_RETRY') }}
                        </button>
                    </div>

                    <section class="grid grid-cols-1 items-start gap-3 sm:grid-cols-2 xl:grid-cols-4" :aria-label="t('DASHBOARD_TITLE')">
                        <template v-if="loading">
                            <Skeleton v-for="index in 4" :key="index" class="h-[6.75rem] rounded-xl" />
                        </template>
                        <template v-else-if="overview">
                            <UiStatCard
                                :description="t('DASHBOARD_ACTIVE_EMPLOYEES_HINT')"
                                interactive
                                :label="t('DASHBOARD_ACTIVE_EMPLOYEES')"
                                :value="overview.active_employees"
                                @select="openColleagues({ 'filter[is_active]': '1' })"
                            >
                                <template #icon>
                                    <Icon class="size-5" icon="material-symbols:groups-outline-rounded" />
                                </template>
                                <template #trailing>
                                    <Icon class="ml-auto size-5 shrink-0 text-list-text-muted" icon="material-symbols:chevron-right-rounded" />
                                </template>
                            </UiStatCard>
                            <UiStatCard
                                v-if="showQuality"
                                :description="t('DASHBOARD_WITHOUT_SKILLS_HINT')"
                                interactive
                                :label="t('DASHBOARD_WITHOUT_SKILLS')"
                                :value="overview.without_skills ?? 0"
                                @select="openColleagues({ 'filter[missing_skill]': '1', 'filter[is_active]': '1' })"
                            >
                                <template #icon>
                                    <Icon class="size-5" icon="material-symbols:verified-outline-rounded" />
                                </template>
                                <template #trailing>
                                    <Icon class="ml-auto size-5 shrink-0 text-list-text-muted" icon="material-symbols:chevron-right-rounded" />
                                </template>
                            </UiStatCard>
                            <UiStatCard
                                v-if="showQuality"
                                :description="t('DASHBOARD_WITHOUT_CERTIFICATES_HINT')"
                                interactive
                                :label="t('DASHBOARD_WITHOUT_CERTIFICATES')"
                                :value="overview.without_certificates ?? 0"
                                @select="openColleagues({ 'filter[missing_certificate]': '1', 'filter[is_active]': '1' })"
                            >
                                <template #icon>
                                    <Icon class="size-5" icon="material-symbols:workspace-premium-outline-rounded" />
                                </template>
                                <template #trailing>
                                    <Icon class="ml-auto size-5 shrink-0 text-list-text-muted" icon="material-symbols:chevron-right-rounded" />
                                </template>
                            </UiStatCard>
                            <UiStatCard
                                :description="t('DASHBOARD_LOCATIONS_HINT')"
                                interactive
                                :label="t('DASHBOARD_LOCATIONS')"
                                :value="overview.locations"
                                @select="focusMap"
                            >
                                <template #icon>
                                    <Icon class="size-5" icon="material-symbols:location-on-outline-rounded" />
                                </template>
                                <template #trailing>
                                    <Icon class="ml-auto size-5 shrink-0 text-list-text-muted" icon="material-symbols:chevron-right-rounded" />
                                </template>
                            </UiStatCard>
                        </template>
                    </section>

                    <section class="grid min-h-0 flex-1 grid-cols-1 items-start gap-3 xl:grid-rows-[minmax(0,1fr)]" :class="workspaceClass">
                        <DashboardTasks v-if="showTasks" class="self-start" />

                        <section id="dashboard-map" class="flex min-h-[22rem] flex-col self-start overflow-hidden rounded-xl border border-list-border bg-list-surface shadow-sm xl:h-full xl:min-h-0 xl:self-stretch">
                            <header class="shrink-0 border-b border-list-border px-3 py-2">
                                <h2 class="m-0 text-sm font-semibold text-list-text">{{ t('DASHBOARD_MAP_TITLE') }}</h2>
                            </header>
                            <div class="min-h-0 flex-1">
                                <Skeleton v-if="loading" class="h-full min-h-[18rem] rounded-none" />
                                <DashboardLocationMap
                                    v-else
                                    class="h-full"
                                    :locations="overview?.work_locations ?? []"
                                    @directory="openLocationDirectory"
                                    @manage="openLocations"
                                    @view="openEmployee"
                                />
                            </div>
                        </section>

                        <div class="flex flex-col gap-3 self-start">
                            <DashboardQuickActions />
                            <section v-if="showQuality" class="rounded-xl border border-list-border bg-list-surface px-3 py-2 shadow-sm">
                                <h2 class="m-0 text-sm font-semibold text-list-text">{{ t('DASHBOARD_COMPLETENESS') }}</h2>
                                <div v-if="loading" class="mt-2 flex flex-col gap-1.5">
                                    <Skeleton v-for="index in 5" :key="index" class="h-7 rounded-lg" />
                                </div>
                                <div v-else-if="!overview || overview.active_employees === 0" class="mt-2">
                                    <p class="m-0 text-sm font-medium text-list-text">{{ t('DASHBOARD_NO_EMPLOYEES') }}</p>
                                    <p class="m-0 mt-1 text-sm text-list-text-muted">{{ t('DASHBOARD_NO_EMPLOYEES_HINT') }}</p>
                                </div>
                                <div v-else class="mt-1.5 flex flex-col">
                                    <button
                                        v-for="metric in metrics"
                                        :key="metric.key"
                                        type="button"
                                        class="flex h-8 cursor-pointer items-center gap-2 rounded-lg px-1 text-left hover:bg-list-row-hover"
                                        @click="openColleagues(metric.query)"
                                    >
                                        <span class="w-[6.75rem] shrink-0 truncate text-xs font-medium text-list-text" :title="t(metric.label)">{{ t(metric.label) }}</span>
                                        <span class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-list-canvas" role="progressbar" :aria-valuenow="metric.percent" aria-valuemin="0" aria-valuemax="100" :aria-label="t(metric.label)">
                                            <span class="block h-full rounded-full bg-primary" :style="{ width: `${metric.percent}%` }" />
                                        </span>
                                        <span class="w-9 shrink-0 text-right text-xs font-semibold text-list-text tabular-nums">{{ metric.percent }}%</span>
                                    </button>
                                </div>
                            </section>
                        </div>
                    </section>
                </div>

                <div class="px-1 pb-10">
                    <section class="grid grid-cols-1 items-start gap-3" :class="workspaceClass">
                        <div v-if="showTasks" class="hidden xl:block" />
                        <DashboardActivity />
                        <DashboardProfile />
                    </section>
                </div>
            </div>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import { PageLayout, UiStatCard } from '@freelance/vue-ui';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import DashboardActivity from '@/components/dashboard/DashboardActivity.vue';
    import DashboardLocationMap, { type DashboardWorkLocation } from '@/components/dashboard/DashboardLocationMap.vue';
    import DashboardProfile from '@/components/dashboard/DashboardProfile.vue';
    import DashboardQuickActions from '@/components/dashboard/DashboardQuickActions.vue';
    import DashboardTasks from '@/components/dashboard/DashboardTasks.vue';
    import Skeleton from '@/components/skeleton/Skeleton.vue';
    import { useAuthStore } from '@/stores/auth';
    import http from '@/utils/http';

    defineOptions({ name: 'DashboardPage' });

    type PeopleOverview = {
        active_employees: number;
        locations: number;
        locations_without_coordinates: number;
        quality?: boolean;
        work_locations: DashboardWorkLocation[];
        without_skills?: number;
        without_certificates?: number;
        without_location?: number;
        without_email?: number;
        without_phone?: number;
        with_skills?: number;
        with_certificates?: number;
        with_location?: number;
        with_email?: number;
        with_phone?: number;
        skills_percent?: number;
        certificates_percent?: number;
        location_percent?: number;
        email_percent?: number;
        phone_percent?: number;
    };

    const { t } = useI18n();
    const router = useRouter();
    const auth = useAuthStore();
    const loading = ref(true);
    const error = ref(false);
    const overview = ref<PeopleOverview | null>(null);
    let requestId = 0;

    const showQuality = computed(() => overview.value?.quality === true);
    const showTasks = computed(() => auth.hasPermission('update employees') || auth.hasPermission('update employee_locations'));
    const workspaceClass = computed(() => {
        if (showTasks.value) {
            return 'xl:grid-cols-[minmax(14rem,0.72fr)_minmax(0,1.9fr)_minmax(16rem,0.9fr)]';
        }
        return 'xl:grid-cols-[minmax(0,1.7fr)_minmax(16rem,0.9fr)]';
    });
    const welcome = computed(() => {
        const name = auth.user?.name?.trim();
        return name ? t('DASHBOARD_WELCOME', { name }) : '';
    });

    const metrics = computed(() => {
        const data = overview.value;
        if (!data?.quality) return [];
        return [
            { key: 'skills', label: 'DASHBOARD_METRIC_SKILLS', filled: data.with_skills ?? 0, percent: data.skills_percent ?? 0, query: { 'filter[missing_skill]': '1', 'filter[is_active]': '1' } },
            { key: 'certificates', label: 'DASHBOARD_METRIC_CERTIFICATES', filled: data.with_certificates ?? 0, percent: data.certificates_percent ?? 0, query: { 'filter[missing_certificate]': '1', 'filter[is_active]': '1' } },
            { key: 'location', label: 'DASHBOARD_METRIC_LOCATION', filled: data.with_location ?? 0, percent: data.location_percent ?? 0, query: { 'filter[missing_location]': '1', 'filter[is_active]': '1' } },
            { key: 'email', label: 'DASHBOARD_METRIC_EMAIL', filled: data.with_email ?? 0, percent: data.email_percent ?? 0, query: { 'filter[missing_business_email]': '1', 'filter[is_active]': '1' } },
            { key: 'phone', label: 'DASHBOARD_METRIC_PHONE', filled: data.with_phone ?? 0, percent: data.phone_percent ?? 0, query: { 'filter[missing_business_phone]': '1', 'filter[is_active]': '1' } },
        ];
    });

    function openColleagues(query: Record<string, string>): void {
        void router.push({ name: 'admin-employees-overview', query });
    }

    function openLocationDirectory(id: number): void {
        openColleagues({ 'filter[employee_location_id]': String(id), 'filter[is_active]': '1' });
    }

    function openLocations(): void {
        void router.push({ name: 'admin-employee-locations-overview' });
    }

    function focusMap(): void {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.getElementById('dashboard-map')?.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
    }

    function openEmployee(id: number): void {
        void router.push({ name: 'admin-employees-detail', params: { id: String(id) } });
    }

    async function load(): Promise<void> {
        const current = ++requestId;
        loading.value = overview.value === null;
        error.value = false;
        try {
            const response = await http.get<{ data: PeopleOverview }>('dashboard/people-overview');
            if (current !== requestId) return;
            overview.value = response.data.data;
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
