<template>
    <div ref="root" class="relative h-full min-h-[24rem]">
        <div class="h-full min-h-0">
            <div v-if="locations.length === 0" class="flex h-full flex-col items-center justify-center px-6 text-center">
                <p class="text-sm font-medium text-list-text">{{ t('DASHBOARD_MAP_EMPTY') }}</p>
                <p class="mt-1 max-w-sm text-sm text-list-text-muted">{{ t('DASHBOARD_MAP_EMPTY_HINT') }}</p>
                <button
                    v-if="canManageLocations"
                    type="button"
                    class="mt-3 inline-flex h-9 cursor-pointer items-center rounded-lg border border-list-border bg-list-surface px-3 text-sm font-medium text-list-text hover:border-primary hover:text-primary"
                    @click="emit('manage')"
                >
                    {{ t('DASHBOARD_MAP_MANAGE') }}
                </button>
            </div>
            <UiMap
                v-else
                :markers="markers"
                :max-zoom="18"
                :min-zoom="5"
                :reveal-padding="revealPadding"
                :selected-id="selectedId"
                :single-zoom="10"
                @marker-click="selectLocation"
            >
                <template #error>{{ t('DASHBOARD_MAP_ERROR') }}</template>
            </UiMap>
        </div>
        <OverlayPanel
            :close-label="t('DASHBOARD_MAP_CLOSE')"
            :label="selected?.name ?? t('DASHBOARD_MAP_PANEL')"
            :open="selected !== null"
            :side="useBottomSheet ? 'bottom' : 'end'"
            @close="selectedId = null"
        >
            <template #title>
                <h3 class="truncate text-sm font-semibold">{{ selected?.name }}</h3>
                <p class="text-xs text-list-text-muted">
                    {{ t('DASHBOARD_MAP_COUNT', { count: selected?.employee_count ?? 0 }) }}
                </p>
            </template>
            <template #close>
                <Icon class="size-[18px]" icon="material-symbols:close-rounded" />
            </template>
            <p v-if="panelLoading" class="text-sm text-list-text-muted">{{ t('DASHBOARD_MAP_LOADING') }}</p>
            <p v-else-if="colleagues.length === 0" class="text-sm text-list-text-muted">{{ t('DASHBOARD_MAP_NONE_HERE') }}</p>
            <div v-else class="flex flex-col gap-2">
                <p v-if="previewNote" class="text-xs text-list-text-muted">{{ previewNote }}</p>
                <EmployeeDirectoryCard
                    v-for="colleague in colleagues"
                    :key="String(colleague.id)"
                    compact
                    :employee="colleague"
                    @view="emit('view', Number(colleague.id))"
                />
            </div>
            <button
                v-if="selected"
                type="button"
                class="mt-3 inline-flex h-9 w-full cursor-pointer items-center justify-center rounded-lg border border-list-border text-sm font-medium text-list-text hover:border-primary hover:text-primary"
                @click="emit('directory', selected.id)"
            >
                {{ t('DASHBOARD_VIEW_LOCATION') }}
            </button>
        </OverlayPanel>
    </div>
</template>

<script lang="ts" setup>
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { Icon } from '@iconify/vue';
    import { OverlayPanel } from '@freelance/vue-ui';
    import { UiMap } from '@freelance/vue-ui/map';
    import '@freelance/vue-ui/map.css';
    import '@freelance/vue-ui/leaflet.css';
    import { useI18n } from 'vue-i18n';
    import EmployeeDirectoryCard from '@/components/employees/EmployeeDirectoryCard.vue';
    import { useViewport } from '@/composables/useViewport';
    import { useAuthStore } from '@/stores/auth';
    import http from '@/utils/http';

    defineOptions({ name: 'DashboardLocationMap' });

    export type DashboardWorkLocation = {
        id: number;
        name: string;
        latitude: number;
        longitude: number;
        employee_count: number;
    };

    const props = defineProps<{
        locations: DashboardWorkLocation[];
    }>();

    const emit = defineEmits<{
        view: [id: number];
        directory: [id: number];
        manage: [];
    }>();

    const { t } = useI18n();
    const { isMobile } = useViewport();
    const auth = useAuthStore();
    const root = ref<HTMLElement | null>(null);
    const mapWidth = ref(0);
    const mapHeight = ref(0);
    let mapResizeObserver: ResizeObserver | null = null;
    const canManageLocations = computed(() => auth.hasPermission('update employee_locations'));
    const previewLimit = 8;
    const selectedId = ref<number | null>(null);
    const colleagues = ref<Record<string, unknown>[]>([]);
    const panelLoading = ref(false);
    let colleagueRequest = 0;

    const markers = computed(() => {
        const seen = new Map<string, number>();
        return props.locations
            .filter((location) => Number.isFinite(location.latitude) && Number.isFinite(location.longitude))
            .map((location) => {
                const key = `${location.latitude.toFixed(5)}:${location.longitude.toFixed(5)}`;
                const overlap = seen.get(key) ?? 0;
                seen.set(key, overlap + 1);
                const angle = overlap * (Math.PI / 3);
                const shift = overlap * 0.002;
                return {
                    id: location.id,
                    latitude: location.latitude + (overlap ? Math.sin(angle) * shift : 0),
                    longitude: location.longitude + (overlap ? Math.cos(angle) * shift : 0),
                    label: location.name,
                    count: location.employee_count,
                };
            });
    });

    const selected = computed(() => props.locations.find((location) => location.id === selectedId.value) ?? null);

    const useBottomSheet = computed(() => isMobile.value || (mapWidth.value > 0 && mapWidth.value < 720));

    const revealPadding = computed(() => {
        if (selectedId.value === null) return null;
        if (useBottomSheet.value) {
            const sheet = Math.round((mapHeight.value || 416) * 0.7) + 40;
            return { top: 16, right: 16, bottom: sheet, left: 48 };
        }
        const panel = Math.min(320, Math.max(0, mapWidth.value - 72));
        return { top: 24, right: 12 + panel + 44, bottom: 56, left: 56 };
    });

    const previewNote = computed(() => {
        const total = selected.value?.employee_count ?? 0;
        if (total <= colleagues.value.length) return '';
        return t('DASHBOARD_MAP_PREVIEW', { shown: colleagues.value.length, count: total });
    });

    async function loadColleagues(id: number): Promise<void> {
        const current = ++colleagueRequest;
        panelLoading.value = true;
        try {
            const response = await http.get<{ data: Record<string, unknown>[] }>('employees', {
                params: {
                    filter: { employee_location_id: id, is_active: 1 },
                    include: ['employeeRole', 'employeeLocation', 'skills', 'certificates'],
                    per_page: previewLimit,
                    page: 1,
                },
            });
            if (current !== colleagueRequest) return;
            colleagues.value = response.data.data ?? [];
        } catch {
            if (current !== colleagueRequest) return;
            colleagues.value = [];
        } finally {
            if (current === colleagueRequest) panelLoading.value = false;
        }
    }

    function selectLocation(id: string | number): void {
        const next = Number(id);
        selectedId.value = next;
        void loadColleagues(next);
    }

    onMounted(() => {
        const element = root.value;
        if (!element) return;
        mapResizeObserver = new ResizeObserver(() => {
            mapWidth.value = element.clientWidth;
            mapHeight.value = element.clientHeight;
        });
        mapResizeObserver.observe(element);
    });

    onBeforeUnmount(() => {
        mapResizeObserver?.disconnect();
        mapResizeObserver = null;
    });

    watch(
        () => props.locations,
        () => {
            if (selectedId.value !== null && !props.locations.some((location) => location.id === selectedId.value)) {
                selectedId.value = null;
            }
        },
    );
</script>
