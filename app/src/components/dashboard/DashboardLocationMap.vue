<template>
    <div ref="root" class="relative h-full min-h-0">
        <div class="flex h-full min-h-0 flex-col">
            <nav
                v-if="locations.length > 0"
                class="flex shrink-0 flex-wrap gap-1.5 border-b border-list-border px-3 py-2"
                :aria-label="t('DASHBOARD_MAP_LIST')"
            >
                <button
                    v-for="location in locations"
                    :key="location.id"
                    type="button"
                    class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-full border px-2.5 text-xs font-medium transition-colors duration-200 motion-reduce:transition-none"
                    :class="selectedId === location.id ? 'border-primary bg-primary-light text-emphasis' : 'border-list-border text-list-text hover:border-primary'"
                    :aria-pressed="selectedId === location.id"
                    @click="selectLocation(location.id)"
                >
                    {{ location.name }}
                    <span class="text-list-text-muted tabular-nums">{{ location.employee_count }}</span>
                </button>
            </nav>
            <div class="min-h-0 flex-1">
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
                    :center="[52.15, 5.29]"
                    :fit-max-zoom="12"
                    :fit-padding="36"
                    :markers="markers"
                    :max-zoom="18"
                    :min-zoom="5"
                    :reveal-padding="revealPadding"
                    :scroll-wheel-zoom="false"
                    :selected-id="selectedId"
                    :single-zoom="10"
                    :zoom="7"
                    @marker-click="selectLocation"
                >
                    <template #error>{{ t('DASHBOARD_MAP_ERROR') }}</template>
                </UiMap>
            </div>
        </div>
        <aside
            v-if="selected"
            class="absolute z-30 flex min-h-0 flex-col overflow-hidden border border-list-border bg-list-surface text-list-text shadow-md"
            :class="useBottomSheet ? 'inset-x-2 bottom-2 max-h-[58%] rounded-xl' : 'top-2 right-2 bottom-2 w-60 rounded-xl'"
            role="dialog"
            :aria-label="selected.name"
        >
            <header class="flex shrink-0 items-center gap-2 border-b border-list-border px-2.5 py-2">
                <div class="min-w-0 flex-1">
                    <h3 class="truncate text-sm font-semibold">{{ selected.name }}</h3>
                    <p class="text-xs text-list-text-muted">{{ t('DASHBOARD_MAP_COUNT', { count: selected.employee_count }) }}</p>
                </div>
                <button
                    type="button"
                    class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-lg text-list-text-muted hover:bg-list-row-hover hover:text-list-text"
                    :aria-label="t('DASHBOARD_MAP_CLOSE')"
                    @click="selectedId = null"
                >
                    <Icon class="size-4" icon="material-symbols:close-rounded" />
                </button>
            </header>
            <div class="min-h-0 flex-1 overflow-auto px-1.5 py-1.5">
                <p v-if="panelLoading" class="px-1.5 py-2 text-sm text-list-text-muted">{{ t('DASHBOARD_MAP_LOADING') }}</p>
                <p v-else-if="colleagues.length === 0" class="px-1.5 py-2 text-sm text-list-text-muted">{{ t('DASHBOARD_MAP_NONE_HERE') }}</p>
                <ul v-else class="m-0 flex list-none flex-col p-0">
                    <li v-for="colleague in colleagueRows" :key="colleague.id">
                        <button
                            type="button"
                            class="flex h-9 w-full cursor-pointer items-center gap-2 rounded-lg px-1.5 text-left hover:bg-list-row-hover"
                            @click="emit('view', colleague.id)"
                        >
                            <span class="flex size-6 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-light text-[10px] font-bold text-emphasis">
                                <img v-if="colleague.photo" :alt="colleague.name" class="size-full object-cover" :src="colleague.photo" />
                                <span v-else>{{ colleague.initials }}</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm leading-4 font-medium text-list-text">{{ colleague.name }}</span>
                                <span v-if="colleague.role" class="block truncate text-[11px] leading-4 text-list-text-muted">{{ colleague.role }}</span>
                            </span>
                            <Icon class="size-4 shrink-0 text-list-text-muted" icon="material-symbols:chevron-right-rounded" />
                        </button>
                    </li>
                </ul>
                <p v-if="previewNote" class="px-1.5 pt-1 text-[11px] text-list-text-muted">{{ previewNote }}</p>
            </div>
            <div class="shrink-0 border-t border-list-border p-1.5">
                <button
                    type="button"
                    class="inline-flex h-8 w-full cursor-pointer items-center justify-center gap-1 rounded-lg bg-primary-light text-xs font-semibold text-emphasis hover:brightness-95"
                    @click="emit('directory', selected.id)"
                >
                    {{ t('DASHBOARD_VIEW_COLLEAGUES') }}
                    <Icon class="size-3.5" icon="material-symbols:arrow-forward-rounded" />
                </button>
            </div>
        </aside>
    </div>
</template>

<script lang="ts" setup>
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { Icon } from '@iconify/vue';
    import { UiMap } from '@freelance/vue-ui/map';
    import '@freelance/vue-ui/map.css';
    import '@freelance/vue-ui/leaflet.css';
    import { useI18n } from 'vue-i18n';
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
    const previewLimit = 6;
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
        return { top: 16, right: 252, bottom: 16, left: 48 };
    });

    const colleagueRows = computed(() => colleagues.value.map((colleague) => {
        const first = textOf(colleague.first_name);
        const last = textOf(colleague.last_name);
        const role = colleague.employee_role;
        const image = colleague.image;
        const photo = image && typeof image === 'object'
            ? textOf((image as { thumb_url?: unknown; url?: unknown }).thumb_url) || textOf((image as { url?: unknown }).url)
            : '';
        const stored = textOf(colleague.initials);
        return {
            id: Number(colleague.id),
            name: [first, last].filter(Boolean).join(' '),
            role: role && typeof role === 'object' ? textOf((role as { name?: unknown }).name) : '',
            initials: stored || `${first.charAt(0)}${last.charAt(0)}`.toUpperCase(),
            photo,
        };
    }));

    function textOf(value: unknown): string {
        return typeof value === 'string' ? value.trim() : '';
    }

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

    function onKeydown(event: KeyboardEvent): void {
        if (event.key === 'Escape') selectedId.value = null;
    }

    onMounted(() => {
        window.addEventListener('keydown', onKeydown);
        const element = root.value;
        if (!element) return;
        mapResizeObserver = new ResizeObserver(() => {
            mapWidth.value = element.clientWidth;
            mapHeight.value = element.clientHeight;
        });
        mapResizeObserver.observe(element);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('keydown', onKeydown);
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
