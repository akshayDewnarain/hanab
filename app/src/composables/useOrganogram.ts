import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import http from '@/utils/http';
import { useAuthStore } from '@/stores/auth';
import type {
    OrganogramEdge,
    OrganogramGraph,
    OrganogramNode,
    OrganogramResponse,
    OrganogramView,
} from '@/modules/support/organogram/types';
import { ancestorIds, hiddenByCollapse, initialCollapsed, layoutOrganogram } from '@/composables/useOrganogramLayout';

export type LaidOutOrganogramNode = OrganogramNode & {
    x: number;
    y: number;
    width: number;
    height: number;
    collapsed: boolean;
    expandable: boolean;
    highlighted: boolean;
    connected: boolean;
    showAvatar: boolean;
    showLocation: boolean;
    compact: boolean;
};

type Preference = {
    view: OrganogramView;
    locationId: string;
    levelId: string;
    minimap: boolean;
    showAvatars: boolean;
    showLocations: boolean;
    showLegend: boolean;
    compact: boolean;
};

type Option = { id: number; name: string; color?: string };

const STORAGE_KEY = 'hanab.organogram.preferences';

function readPreference(): Preference {
    const fallback: Preference = {
        view: 'management',
        locationId: '',
        levelId: '',
        minimap: true,
        showAvatars: true,
        showLocations: true,
        showLegend: false,
        compact: false,
    };
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return fallback;
        }
        const parsed = JSON.parse(raw) as Partial<Preference>;
        const view = parsed.view === 'location' || parsed.view === 'roles' ? parsed.view : 'management';
        return {
            view,
            locationId: typeof parsed.locationId === 'string' ? parsed.locationId : '',
            levelId: typeof parsed.levelId === 'string' ? parsed.levelId : '',
            minimap: parsed.minimap !== false,
            showAvatars: parsed.showAvatars !== false,
            showLocations: parsed.showLocations !== false,
            showLegend: parsed.showLegend === true,
            compact: parsed.compact === true,
        };
    } catch {
        return fallback;
    }
}

function childCounts(edges: OrganogramEdge[]): Map<string, number> {
    const counts = new Map<string, number>();
    for (const edge of edges) {
        counts.set(edge.source, (counts.get(edge.source) ?? 0) + 1);
    }
    return counts;
}

function locationKey(locationId: number | null | undefined): string {
    return typeof locationId === 'number' && Number.isFinite(locationId) ? String(locationId) : 'none';
}

export function useOrganogram() {
    const router = useRouter();
    const auth = useAuthStore();
    const { t, locale } = useI18n();
    const preference = readPreference();

    const view = ref<OrganogramView>(preference.view);
    const locationId = ref(preference.locationId);
    const levelId = ref(preference.levelId);
    const showMinimap = ref(preference.minimap);
    const showAvatars = ref(preference.showAvatars);
    const showLocations = ref(preference.showLocations);
    const showLegend = ref(preference.showLegend);
    const compact = ref(preference.compact);
    const showList = ref(false);
    const settingsOpen = ref(false);
    const query = ref('');
    const locations = ref<Option[]>([]);
    const levels = ref<Option[]>([]);
    const graph = ref<OrganogramGraph>({ nodes: [], edges: [] });
    const laidOut = ref<LaidOutOrganogramNode[]>([]);
    const collapsed = ref<Set<string>>(new Set());
    const highlightedId = ref<string | null>(null);
    const isLoading = ref(false);
    const failed = ref(false);
    const searchNotice = ref<'none' | 'filtered' | null>(null);
    const fitRequest = ref(0);
    const focusRequest = ref(0);
    const focusId = ref<string | null>(null);

    let layoutToken = 0;
    let loadedView: OrganogramView | null = null;

    const canEdit = computed(() => auth.hasPermission('update employees'));

    const visibleGraph = computed(() => {
        const hidden = hiddenByCollapse(graph.value.edges, collapsed.value);
        const nodes = graph.value.nodes.filter((node) => !hidden.has(node.id));
        const ids = new Set(nodes.map((node) => node.id));
        const edges = graph.value.edges.filter((edge) => ids.has(edge.source) && ids.has(edge.target));
        return { nodes, edges };
    });

    const matches = computed(() => {
        const term = query.value.trim().toLowerCase();
        if (term === '') {
            return [];
        }
        return graph.value.nodes.filter((node) => {
            const haystack = [node.name, node.role, node.employeeNumber, node.people?.map((person) => person.name).join(' ')]
                .filter((value): value is string => Boolean(value))
                .join(' ')
                .toLowerCase();
            return haystack.includes(term);
        });
    });

    async function loadOptions(): Promise<void> {
        try {
            const [locationResponse, levelResponse] = await Promise.all([
                http.get<{ data: Option[] }>('employee-locations', { params: { per_page: 200 } }),
                http.get<{ data: Option[] }>('organization-levels', { params: { per_page: 200 } }),
            ]);
            locations.value = Array.isArray(locationResponse.data.data) ? locationResponse.data.data : [];
            levels.value = Array.isArray(levelResponse.data.data) ? levelResponse.data.data : [];
        } catch {
            locations.value = [];
            levels.value = [];
        }
    }

    async function load(): Promise<void> {
        isLoading.value = true;
        failed.value = false;
        searchNotice.value = null;
        try {
            const params: Record<string, string> = {
                view: view.value === 'roles' ? 'roles' : 'management',
            };
            if (locationId.value !== '') {
                params.location_id = locationId.value;
            }
            if (levelId.value !== '') {
                params.organization_level_id = levelId.value;
            }
            const response = await http.get<OrganogramResponse>('organogram', { params });
            const nodes = response.data.data?.nodes ?? [];
            const edges = response.data.data?.edges ?? [];
            if (loadedView !== view.value) {
                loadedView = view.value;
                collapsed.value = initialCollapsed(nodes, edges);
            } else {
                const present = new Set(nodes.map((node) => node.id));
                collapsed.value = new Set([...collapsed.value].filter((id) => present.has(id)));
            }
            graph.value = { nodes, edges };
        } catch {
            failed.value = true;
            graph.value = { nodes: [], edges: [] };
        } finally {
            isLoading.value = false;
        }
    }

    async function arrange(shouldFit: boolean): Promise<void> {
        const token = ++layoutToken;
        const current = visibleGraph.value;
        const counts = childCounts(graph.value.edges);
        const linked = new Set<string>();
        for (const edge of current.edges) {
            linked.add(edge.source);
            linked.add(edge.target);
        }
        const grouping = view.value === 'location';
        const locationById = new Map(current.nodes.map((node) => [node.id, node.locationId ?? null]));
        const elsewhere = new Set<string>();
        if (grouping) {
            for (const edge of current.edges) {
                if (edge.type === 'reports_to' && locationKey(locationById.get(edge.source)) !== locationKey(locationById.get(edge.target))) {
                    elsewhere.add(edge.target);
                }
            }
        }
        const prepared = current.nodes.map((node) => ({
            ...node,
            reportsElsewhere: elsewhere.has(node.id) && node.managerNote !== 'inactive' && node.context !== true,
        }));
        const laid = await layoutOrganogram(prepared, current.edges, {
            compact: compact.value,
            groupByLocation: grouping,
            locationOrder: locations.value.map((item) => item.id),
            unassignedLabel: view.value === 'roles' ? '' : t('ORGANOGRAM_UNASSIGNED_SECTION'),
            noLocationLabel: t('ORGANOGRAM_NO_LOCATION'),
        });
        if (token !== layoutToken) {
            return;
        }
        const locationCounts = new Map<string, number>();
        for (const node of graph.value.nodes) {
            if (node.type !== 'employee') {
                continue;
            }
            const key = locationKey(node.locationId);
            locationCounts.set(key, (locationCounts.get(key) ?? 0) + 1);
        }
        laidOut.value = laid.map((node) => ({
            ...node,
            memberCount: node.groupKind === 'location' ? (locationCounts.get(node.id.slice('location-'.length)) ?? node.memberCount) : node.memberCount,
            collapsed: collapsed.value.has(node.id),
            expandable: node.type !== 'group' && (counts.get(node.id) ?? 0) > 0,
            highlighted: node.id === highlightedId.value,
            connected: linked.has(node.id),
            showAvatar: showAvatars.value,
            showLocation: showLocations.value,
            compact: compact.value,
        }));
        if (shouldFit) {
            fitRequest.value += 1;
        }
    }

    function toggleNode(id: string): void {
        const next = new Set(collapsed.value);
        if (next.has(id)) {
            next.delete(id);
        } else {
            next.add(id);
        }
        collapsed.value = next;
    }

    function expandAll(): void {
        collapsed.value = new Set();
    }

    function collapseAll(): void {
        const counts = childCounts(graph.value.edges);
        collapsed.value = new Set([...counts.keys()]);
    }

    function openEmployee(employeeId: number): void {
        void router.push({ name: 'admin-employees-detail', params: { id: String(employeeId) } });
    }

    function editHierarchy(): void {
        const selected = graph.value.nodes.find((node) => node.id === highlightedId.value && node.employeeId);
        if (selected?.employeeId) {
            openEmployee(selected.employeeId);
            return;
        }
        void router.push({ name: 'admin-employees-overview' });
    }

    function focusGroup(id: string): void {
        focusId.value = id;
        focusRequest.value += 1;
    }

    async function focusNode(node: OrganogramNode): Promise<void> {
        const ancestors = ancestorIds(graph.value.edges, node.id);
        const next = new Set(collapsed.value);
        for (const ancestor of ancestors) {
            next.delete(ancestor);
        }
        collapsed.value = next;
        highlightedId.value = node.id;
        searchNotice.value = null;
        await arrange(false);
    }

    function search(): void {
        const term = query.value.trim();
        if (term === '') {
            searchNotice.value = null;
            highlightedId.value = null;
            return;
        }
        const found = matches.value[0];
        if (found) {
            void focusNode(found);
            return;
        }
        searchNotice.value = locationId.value !== '' || levelId.value !== '' ? 'filtered' : 'none';
    }

    async function revealSearch(): Promise<void> {
        const term = query.value.trim();
        locationId.value = '';
        levelId.value = '';
        await load();
        const found = graph.value.nodes.find((node) => node.name.toLowerCase().includes(term.toLowerCase()));
        if (found) {
            await focusNode(found);
            return;
        }
        searchNotice.value = 'none';
    }

    const fitNext = ref(true);

    watch([view, locationId, levelId, showMinimap, showAvatars, showLocations, showLegend, compact], () => {
        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                view: view.value,
                locationId: locationId.value,
                levelId: levelId.value,
                minimap: showMinimap.value,
                showAvatars: showAvatars.value,
                showLocations: showLocations.value,
                showLegend: showLegend.value,
                compact: compact.value,
            }),
        );
    });

    watch([showAvatars, showLocations, compact, locale], () => {
        void arrange(false);
    });

    watch(locations, () => {
        if (view.value === 'location') {
            void arrange(false);
        }
    });

    watch([view, locationId, levelId], () => {
        fitNext.value = true;
        void load();
    });

    watch(visibleGraph, () => {
        const fit = fitNext.value;
        fitNext.value = false;
        void arrange(fit);
    });

    return {
        view,
        locationId,
        levelId,
        showMinimap,
        showAvatars,
        showLocations,
        showLegend,
        compact,
        showList,
        settingsOpen,
        unassignedCount: computed(() => {
            if (view.value === 'roles') {
                return 0;
            }
            const linked = new Set<string>();
            for (const edge of graph.value.edges) {
                linked.add(edge.source);
                linked.add(edge.target);
            }
            return graph.value.nodes.filter(
                (node) => node.type === 'employee' && !node.context && !node.managerNote && !linked.has(node.id),
            ).length;
        }),
        places: computed(() => {
            const order = new Map(locations.value.map((item, index) => [item.id, index]));
            const groups = new Map<string, { id: string; name: string; count: number; order: number }>();
            for (const node of graph.value.nodes) {
                if (node.type !== 'employee') {
                    continue;
                }
                const key = locationKey(node.locationId);
                const existing = groups.get(key);
                if (existing) {
                    existing.count += 1;
                    continue;
                }
                const numericId = key === 'none' ? null : Number(key);
                groups.set(key, {
                    id: `location-${key}`,
                    name: node.location?.trim() || '',
                    count: 1,
                    order: numericId == null ? 100_000 : (order.get(numericId) ?? 90_000),
                });
            }
            return [...groups.values()]
                .sort((left, right) => left.order - right.order || left.name.localeCompare(right.name))
                .map(({ id, name, count }) => ({ id, name, count }));
        }),
        query,
        locations,
        levels,
        graph,
        laidOut,
        visibleEdges: computed(() => {
            const locationById = new Map(graph.value.nodes.map((node) => [node.id, node.locationId ?? null]));
            const grouping = view.value === 'location';
            return visibleGraph.value.edges.map((edge) => ({
                ...edge,
                crossLocation: grouping && edge.type === 'reports_to' && locationKey(locationById.get(edge.source)) !== locationKey(locationById.get(edge.target)),
            }));
        }),
        isLoading,
        failed,
        searchNotice,
        matches,
        highlightedId,
        fitRequest,
        focusRequest,
        focusId,
        canEdit,
        loadOptions,
        load,
        toggleNode,
        expandAll,
        collapseAll,
        openEmployee,
        editHierarchy,
        focusNode,
        focusGroup,
        search,
        revealSearch,
    };
}
