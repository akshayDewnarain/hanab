<template>
    <PageLayout class="!overflow-hidden" :label="t('DASHBOARD_TITLE')">
        <div class="min-h-0 flex-1 overflow-auto">
            <header class="mb-4">
                <h1 class="text-lg font-semibold text-list-text">{{ t('DASHBOARD_TITLE') }}</h1>
                <p class="mt-1 text-sm text-list-text-muted">{{ t('DASHBOARD_INTRO') }}</p>
            </header>

            <div
                v-if="error"
                class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-list-border bg-list-surface px-4 py-3"
            >
                <p class="text-sm text-list-text">{{ t('DASHBOARD_ERROR') }}</p>
                <button
                    type="button"
                    class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand"
                    @click="load"
                >
                    {{ t('DASHBOARD_RETRY') }}
                </button>
            </div>

            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" :aria-label="t('DASHBOARD_TITLE')">
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

            <div class="mt-3 grid grid-cols-1 items-start gap-3 xl:grid-cols-[minmax(0,1.7fr)_minmax(18rem,22rem)]">
                <section id="dashboard-map" class="overflow-hidden rounded-xl border border-list-border bg-list-surface shadow-sm">
                    <header class="border-b border-list-border px-4 py-3">
                        <h2 class="text-sm font-semibold text-list-text">{{ t('DASHBOARD_MAP_TITLE') }}</h2>
                    </header>
                    <div class="h-[26rem] lg:h-[32rem]">
                        <Skeleton v-if="loading" class="h-full rounded-none" />
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

                <div class="flex flex-col gap-3">
                    <section v-if="showQuality" class="rounded-xl border border-list-border bg-list-surface p-4 shadow-sm">
                        <h2 class="text-sm font-semibold text-list-text">{{ t('DASHBOARD_COMPLETENESS') }}</h2>
                        <div v-if="loading" class="mt-3 flex flex-col gap-3">
                            <Skeleton v-for="index in 5" :key="index" class="h-10 rounded-lg" />
                        </div>
                        <div v-else-if="!overview || overview.active_employees === 0" class="mt-3">
                            <p class="text-sm font-medium text-list-text">{{ t('DASHBOARD_NO_EMPLOYEES') }}</p>
                            <p class="mt-1 text-sm text-list-text-muted">{{ t('DASHBOARD_NO_EMPLOYEES_HINT') }}</p>
                        </div>
                        <div v-else class="mt-3 flex flex-col gap-3">
                            <UiProgress
                                v-for="metric in metrics"
                                :key="metric.key"
                                :caption="t('DASHBOARD_COMPLETENESS_DETAIL', { percent: metric.percent, filled: metric.filled, total: overview.active_employees })"
                                interactive
                                :label="t(metric.label)"
                                :percent="metric.percent"
                                @select="openColleagues(metric.query)"
                            />
                        </div>
                    </section>

                    <section v-if="showQuality" class="rounded-xl border border-list-border bg-list-surface p-4 shadow-sm">
                        <h2 class="text-sm font-semibold text-list-text">{{ t('DASHBOARD_NEEDS_ATTENTION') }}</h2>
                        <p v-if="attention.length === 0" class="mt-3 text-sm text-list-text-muted">{{ t('DASHBOARD_ATTENTION_CLEAR') }}</p>
                        <ul v-else class="mt-2 flex list-none flex-col p-0">
                            <li v-for="item in attention" :key="item.key">
                                <button
                                    type="button"
                                    class="flex w-full cursor-pointer items-center gap-2 rounded-lg px-2 py-2 text-left transition-colors duration-200 hover:bg-list-row-hover motion-reduce:transition-none"
                                    @click="openColleagues(item.query)"
                                >
                                    <Icon class="size-5 shrink-0 text-list-text-muted" :icon="item.icon" />
                                    <span class="min-w-0 flex-1 text-sm text-list-text">{{ t(item.label) }}</span>
                                    <span class="text-sm font-semibold tabular-nums text-list-text">{{ item.count }}</span>
                                    <Icon class="size-4 shrink-0 text-list-text-muted" icon="material-symbols:chevron-right-rounded" />
                                </button>
                            </li>
                        </ul>
                    </section>

                    <section v-else-if="!loading" class="rounded-xl border border-list-border bg-list-surface p-4 shadow-sm">
                        <h2 class="text-sm font-semibold text-list-text">{{ t('DASHBOARD_FIND_TITLE') }}</h2>
                        <p class="mt-1 text-sm text-list-text-muted">{{ t('DASHBOARD_FIND_HINT') }}</p>
                        <button
                            type="button"
                            class="mt-3 inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand"
                            @click="openColleagues({})"
                        >
                            {{ t('DASHBOARD_FIND_ACTION') }}
                            <Icon class="size-4" icon="material-symbols:arrow-forward-rounded" />
                        </button>
                    </section>
                </div>
            </div>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import { PageLayout, UiProgress, UiStatCard } from '@freelance/vue-ui';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import DashboardLocationMap, { type DashboardWorkLocation } from '@/components/dashboard/DashboardLocationMap.vue';
    import Skeleton from '@/components/skeleton/Skeleton.vue';
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
    const loading = ref(true);
    const error = ref(false);
    const overview = ref<PeopleOverview | null>(null);
    let requestId = 0;

    const showQuality = computed(() => overview.value?.quality === true);

    const metrics = computed(() => {
        const data = overview.value;
        if (!data?.quality) return [];
        const rows: { key: string; label: string; filled: number; percent: number; query: Record<string, string> }[] = [
            { key: 'skills', label: 'DASHBOARD_SKILLS_REGISTERED', filled: data.with_skills ?? 0, percent: data.skills_percent ?? 0, query: { 'filter[missing_skill]': '1', 'filter[is_active]': '1' } },
            { key: 'certificates', label: 'DASHBOARD_CERTIFICATES_REGISTERED', filled: data.with_certificates ?? 0, percent: data.certificates_percent ?? 0, query: { 'filter[missing_certificate]': '1', 'filter[is_active]': '1' } },
            { key: 'location', label: 'DASHBOARD_LOCATION_ASSIGNED', filled: data.with_location ?? 0, percent: data.location_percent ?? 0, query: { 'filter[missing_location]': '1', 'filter[is_active]': '1' } },
            { key: 'email', label: 'DASHBOARD_EMAIL_REGISTERED', filled: data.with_email ?? 0, percent: data.email_percent ?? 0, query: { 'filter[missing_email]': '1', 'filter[is_active]': '1' } },
            { key: 'phone', label: 'DASHBOARD_PHONE_REGISTERED', filled: data.with_phone ?? 0, percent: data.phone_percent ?? 0, query: { 'filter[missing_phone]': '1', 'filter[is_active]': '1' } },
        ];
        return rows;
    });

    const attention = computed(() => {
        const data = overview.value;
        if (!data?.quality) return [];
        const rows: { key: string; label: string; icon: string; count: number; query: Record<string, string> }[] = [
            { key: 'skills', label: 'DASHBOARD_ATTENTION_SKILLS', icon: 'material-symbols:verified-outline-rounded', count: data.without_skills ?? 0, query: { 'filter[missing_skill]': '1', 'filter[is_active]': '1' } },
            { key: 'certificates', label: 'DASHBOARD_ATTENTION_CERTIFICATES', icon: 'material-symbols:workspace-premium-outline-rounded', count: data.without_certificates ?? 0, query: { 'filter[missing_certificate]': '1', 'filter[is_active]': '1' } },
            { key: 'location', label: 'DASHBOARD_ATTENTION_LOCATION', icon: 'material-symbols:location-on-outline-rounded', count: data.without_location ?? 0, query: { 'filter[missing_location]': '1', 'filter[is_active]': '1' } },
            { key: 'email', label: 'DASHBOARD_ATTENTION_EMAIL', icon: 'material-symbols:mail-outline-rounded', count: data.without_email ?? 0, query: { 'filter[missing_email]': '1', 'filter[is_active]': '1' } },
            { key: 'phone', label: 'DASHBOARD_ATTENTION_PHONE', icon: 'material-symbols:call-outline-rounded', count: data.without_phone ?? 0, query: { 'filter[missing_phone]': '1', 'filter[is_active]': '1' } },
        ];
        return rows.filter((item) => item.count > 0);
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
        document.getElementById('dashboard-map')?.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'nearest' });
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
