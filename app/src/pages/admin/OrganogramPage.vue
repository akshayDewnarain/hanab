<template>
    <section class="organogram-page" :aria-label="t('ORGANOGRAM_TITLE')">
        <OrganogramCanvas
            :nodes="laidOut"
            :edges="visibleEdges"
            :show-minimap="showMinimap"
            :fit-request="fitRequest"
            :focus-request="focusRequest"
            :focus-id="focusId"
            :reset-request="resetRequest"
            :controls-label="t('ORGANOGRAM_CONTROLS')"
            :zoom-in-label="t('ORGANOGRAM_ZOOM_IN')"
            :zoom-out-label="t('ORGANOGRAM_ZOOM_OUT')"
            :fit-label="t('ORGANOGRAM_FIT')"
            :minimap-label="showMinimap ? t('ORGANOGRAM_HIDE_MINIMAP') : t('ORGANOGRAM_MINIMAP')"
            :fullscreen-label="t('ORGANOGRAM_FULLSCREEN')"
            @toggle-minimap="showMinimap = !showMinimap"
        >
            <OrganogramFloatingToolbar
                :view="view"
                :query="query"
                :location-id="locationId"
                :level-id="levelId"
                :locations="locations"
                :places="places"
                :levels="levels"
                :matches="matches"
                :notice="searchNotice"
                :can-edit="canEdit"
                :show-avatars="showAvatars"
                :show-locations="showLocations"
                :show-legend="showLegend"
                :show-minimap="showMinimap"
                :compact="compact"
                :settings-open="settingsOpen"
                @update:view="view = $event"
                @update:query="query = $event"
                @update:location-id="locationId = $event"
                @update:level-id="levelId = $event"
                @update:show-avatars="showAvatars = $event"
                @update:show-locations="showLocations = $event"
                @update:show-legend="showLegend = $event"
                @update:show-minimap="showMinimap = $event"
                @update:compact="compact = $event"
                @update:settings-open="settingsOpen = $event"
                @search="search"
                @focus="focusNode"
                @reveal="revealSearch"
                @expand="expandAll"
                @collapse="collapseAll"
                @edit="editHierarchy"
                @reset="resetRequest += 1"
                @fit-location="focusGroup(`location-${locationId}`)"
                @focus-group="focusGroup"
                @list="showList = !showList"
            />
            <div v-if="showSpinner" class="organogram-overlay">
                <div class="organogram-loading" role="status">
                    <span class="organogram-spinner" />
                    <p>{{ t('ORGANOGRAM_LOADING') }}</p>
                </div>
            </div>
            <div v-else-if="failed" class="organogram-overlay">
                <div class="organogram-state" role="alert">
                    <Icon icon="material-symbols:cloud-off-outline-rounded" />
                    <h2>{{ t('ORGANOGRAM_ERROR') }}</h2>
                    <p>{{ t('ORGANOGRAM_ERROR_HINT') }}</p>
                    <button type="button" class="organogram-state-action" @click="load()">{{ t('ORGANOGRAM_RETRY') }}</button>
                </div>
            </div>
            <div v-else-if="laidOut.length === 0" class="organogram-overlay">
                <div class="organogram-state">
                    <Icon icon="material-symbols:account-tree-outline-rounded" />
                    <h2>{{ filtersActive ? t('ORGANOGRAM_EMPTY_FILTERED') : t('ORGANOGRAM_EMPTY') }}</h2>
                    <p>{{ filtersActive ? t('ORGANOGRAM_EMPTY_FILTERED_BODY') : t('ORGANOGRAM_EMPTY_BODY') }}</p>
                    <button v-if="filtersActive" type="button" class="organogram-state-action" @click="clearChartFilters">
                        {{ t('ORGANOGRAM_CLEAR_FILTERS') }}
                    </button>
                </div>
            </div>
            <p v-else-if="isLoading" class="organogram-status" role="status">{{ t('ORGANOGRAM_LOADING') }}</p>
            <p v-else-if="unassignedCount > 0" class="organogram-unassigned">
                {{ t('ORGANOGRAM_UNASSIGNED', { count: unassignedCount }) }}
            </p>
            <aside v-if="showList" class="organogram-list-panel">
                <OrganogramOutline :nodes="roots" :edges="graph.edges" @open="openOutline" />
            </aside>
        </OrganogramCanvas>
    </section>
</template>

<script setup lang="ts">
    import { Icon } from '@iconify/vue';
    import { computed, onMounted, provide, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { organogramActionsKey } from '@/components/organogram/actions';
    import OrganogramCanvas from '@/components/organogram/OrganogramCanvas.vue';
    import OrganogramFloatingToolbar from '@/components/organogram/OrganogramFloatingToolbar.vue';
    import OrganogramOutline from '@/components/organogram/OrganogramOutline.vue';
    import { useOrganogram } from '@/composables/useOrganogram';
    import type { OrganogramNode } from '@/modules/support/organogram/types';
    import '@/css/organogram.css';

    defineOptions({ name: 'OrganogramPage' });

    const { t } = useI18n();
    const resetRequest = ref(0);
    const {
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
        query,
        locations,
        levels,
        graph,
        laidOut,
        visibleEdges,
        isLoading,
        failed,
        searchNotice,
        matches,
        fitRequest,
        focusRequest,
        focusId,
        canEdit,
        unassignedCount,
        places,
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
    } = useOrganogram();

    const filtersActive = computed(() => locationId.value !== '' || levelId.value !== '');
    const showSpinner = computed(
        () => (isLoading.value && laidOut.value.length === 0) || (!failed.value && graph.value.nodes.length > 0 && laidOut.value.length === 0),
    );

    function clearChartFilters(): void {
        locationId.value = '';
        levelId.value = '';
    }

    const roots = computed(() => {
        const targets = new Set(graph.value.edges.map((edge) => edge.target));
        return graph.value.nodes.filter((node) => !targets.has(node.id));
    });

    provide(organogramActionsKey, { openEmployee, toggleNode, focusGroup });

    function openOutline(node: OrganogramNode): void {
        if (node.employeeId) {
            openEmployee(node.employeeId);
            return;
        }
        const person = node.people?.[0];
        if (person) {
            openEmployee(person.id);
        }
    }

    onMounted(async () => {
        await loadOptions();
        await load();
    });
</script>
