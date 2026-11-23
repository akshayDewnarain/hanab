<template>
    <div class="organogram-float">
        <div class="organogram-float-bar">
            <HelpTrigger :context="view === 'location' ? 'organogram.location' : 'organogram.management'" />
            <div class="organogram-pop">
                <button type="button" class="organogram-chip" :aria-expanded="viewOpen" aria-haspopup="menu" @click="toggle('view')">
                    {{ viewLabel }}
                    <Icon icon="material-symbols:expand-more-rounded" />
                </button>
                <div v-if="viewOpen" class="organogram-popover" role="menu">
                    <button
                        v-for="item in views"
                        :key="item.id"
                        type="button"
                        role="menuitemradio"
                        :aria-checked="view === item.id"
                        :class="{ 'is-active': view === item.id }"
                        @click="chooseView(item.id)"
                    >
                        {{ t(item.label) }}
                    </button>
                </div>
            </div>

            <div class="organogram-pop">
                <button type="button" class="organogram-chip organogram-icon-chip" :aria-expanded="searchOpen" :aria-label="t('ORGANOGRAM_SEARCH')" @click="toggle('search')">
                    <Icon icon="material-symbols:search-rounded" />
                </button>
                <form v-if="searchOpen" class="organogram-popover organogram-search" @submit.prevent="emit('search')">
                    <input
                        ref="searchInput"
                        :value="query"
                        type="search"
                        :placeholder="t('ORGANOGRAM_SEARCH')"
                        @input="emit('update:query', ($event.target as HTMLInputElement).value)"
                    />
                    <ul v-if="matches.length">
                        <li v-for="match in matches.slice(0, 8)" :key="match.id">
                            <button type="button" @click="emit('focus', match)">{{ match.name }}</button>
                        </li>
                    </ul>
                    <p v-else-if="query.trim() && notice" class="organogram-popover-note">
                        {{ notice === 'filtered' ? t('ORGANOGRAM_HIDDEN_BY_FILTER') : t('ORGANOGRAM_NO_MATCH') }}
                        <button v-if="notice === 'filtered'" type="button" @click="emit('reveal')">{{ t('ORGANOGRAM_CLEAR_FILTERS') }}</button>
                    </p>
                </form>
            </div>

            <div class="organogram-pop">
                <button type="button" class="organogram-chip organogram-icon-chip" :aria-expanded="filtersOpen" :aria-label="t('ORGANOGRAM_FILTERS')" @click="toggle('filters')">
                    <Icon icon="material-symbols:filter-alt-outline-rounded" />
                    <span v-if="filtersActive" class="organogram-filter-dot" />
                </button>
                <div v-if="filtersOpen" class="organogram-popover organogram-filters">
                    <label>
                        {{ t('ORGANOGRAM_ALL_LOCATIONS') }}
                        <select :value="locationId" @change="emit('update:locationId', ($event.target as HTMLSelectElement).value)">
                            <option value="">{{ t('ORGANOGRAM_ALL_LOCATIONS') }}</option>
                            <option v-for="location in locations" :key="location.id" :value="String(location.id)">{{ location.name }}</option>
                        </select>
                    </label>
                    <div v-if="view === 'location' && places.length" class="organogram-places">
                        <p>{{ t('ORGANOGRAM_LOCATIONS') }}</p>
                        <button v-for="place in places" :key="place.id" type="button" @click="emit('focus-group', place.id)">
                            <span>{{ place.name || t('ORGANOGRAM_NO_LOCATION') }}</span>
                            <span>{{ place.count }}</span>
                        </button>
                        <p class="organogram-popover-note">{{ t('ORGANOGRAM_CROSS_HINT') }}</p>
                    </div>
                    <label>
                        {{ t('ORGANOGRAM_ALL_LEVELS') }}
                        <select :value="levelId" @change="emit('update:levelId', ($event.target as HTMLSelectElement).value)">
                            <option value="">{{ t('ORGANOGRAM_ALL_LEVELS') }}</option>
                            <option value="none">{{ t('ORGANOGRAM_UNCLASSIFIED') }}</option>
                            <option v-for="level in levels" :key="level.id" :value="String(level.id)">{{ level.name }}</option>
                        </select>
                    </label>
                    <ul v-if="legend.length" class="organogram-legend">
                        <li v-for="level in legend" :key="level.id">
                            <span :style="{ background: level.color }" />
                            {{ level.name }}
                        </li>
                    </ul>
                </div>
            </div>

            <ExpandableCardActions
                placement="inline"
                size="md"
                :actions="moreActions"
                :trigger-label="t('ORGANOGRAM_MORE')"
                @action="onMore"
            />
        </div>

        <div v-if="settingsOpen" class="organogram-popover organogram-settings">
            <p>{{ t('ORGANOGRAM_SETTINGS') }}</p>
            <label><input :checked="showAvatars" type="checkbox" @change="emit('update:showAvatars', ($event.target as HTMLInputElement).checked)" /> {{ t('ORGANOGRAM_SHOW_AVATARS') }}</label>
            <label><input :checked="showLocations" type="checkbox" @change="emit('update:showLocations', ($event.target as HTMLInputElement).checked)" /> {{ t('ORGANOGRAM_SHOW_LOCATIONS') }}</label>
            <label><input :checked="showLegend" type="checkbox" @change="emit('update:showLegend', ($event.target as HTMLInputElement).checked)" /> {{ t('ORGANOGRAM_SHOW_LEGEND') }}</label>
            <label><input :checked="compact" type="checkbox" @change="emit('update:compact', ($event.target as HTMLInputElement).checked)" /> {{ t('ORGANOGRAM_COMPACT') }}</label>
            <label><input :checked="showMinimap" type="checkbox" @change="emit('update:showMinimap', ($event.target as HTMLInputElement).checked)" /> {{ t('ORGANOGRAM_MINIMAP') }}</label>
        </div>

        <ul v-if="showLegend && !filtersOpen" class="organogram-legend organogram-legend-pin">
            <li v-for="level in legend" :key="level.id">
                <span :style="{ background: level.color }" />
                {{ level.name }}
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
    import { Icon } from '@iconify/vue';
    import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import ExpandableCardActions, { type ExpandableCardAction } from '@/components/ui/ExpandableCardActions.vue';
    import HelpTrigger from '@/components/help/HelpTrigger.vue';
    import type { OrganogramNode, OrganogramView } from '@/modules/support/organogram/types';

    const props = defineProps<{
        view: OrganogramView;
        query: string;
        locationId: string;
        levelId: string;
        locations: { id: number; name: string }[];
        places: { id: string; name: string; count: number }[];
        levels: { id: number; name: string; color?: string }[];
        matches: OrganogramNode[];
        notice: 'none' | 'filtered' | null;
        canEdit: boolean;
        showAvatars: boolean;
        showLocations: boolean;
        showLegend: boolean;
        showMinimap: boolean;
        compact: boolean;
        settingsOpen: boolean;
    }>();

    const emit = defineEmits<{
        'update:view': [OrganogramView];
        'update:query': [string];
        'update:locationId': [string];
        'update:levelId': [string];
        'update:showAvatars': [boolean];
        'update:showLocations': [boolean];
        'update:showLegend': [boolean];
        'update:showMinimap': [boolean];
        'update:compact': [boolean];
        'update:settingsOpen': [boolean];
        search: [];
        focus: [OrganogramNode];
        reveal: [];
        expand: [];
        collapse: [];
        edit: [];
        reset: [];
        list: [];
        'fit-location': [];
        'focus-group': [string];
    }>();

    const { t } = useI18n();
    const viewOpen = ref(false);
    const searchOpen = ref(false);
    const filtersOpen = ref(false);
    const searchInput = ref<HTMLInputElement | null>(null);
    const views: { id: OrganogramView; label: string }[] = [
        { id: 'management', label: 'ORGANOGRAM_VIEW_MANAGEMENT' },
        { id: 'location', label: 'ORGANOGRAM_VIEW_LOCATION' },
        { id: 'roles', label: 'ORGANOGRAM_VIEW_ROLES' },
    ];

    const viewLabel = computed(() => t(views.find((item) => item.id === props.view)?.label ?? 'ORGANOGRAM_VIEW_MANAGEMENT'));
    const filtersActive = computed(() => props.locationId !== '' || props.levelId !== '');
    const legend = computed(() => props.levels.filter((level) => level.color));
    const moreActions = computed<ExpandableCardAction[]>(() => {
        const actions: ExpandableCardAction[] = [
            { key: 'expand', icon: 'material-symbols:unfold-more-rounded', label: t('ORGANOGRAM_EXPAND_ALL') },
            { key: 'collapse', icon: 'material-symbols:unfold-less-rounded', label: t('ORGANOGRAM_COLLAPSE_ALL') },
        ];
        if (props.view === 'location' && props.locationId !== '') {
            actions.push({
                key: 'fit-location',
                icon: 'material-symbols:center-focus-weak-rounded',
                label: t('ORGANOGRAM_FIT_LOCATION'),
            });
        }
        actions.push({ key: 'reset', icon: 'material-symbols:restart-alt-rounded', label: t('ORGANOGRAM_RESET') });
        if (props.canEdit) {
            actions.push({ key: 'edit', icon: 'material-symbols:edit-outline-rounded', label: t('ORGANOGRAM_EDIT') });
        }
        actions.push({ key: 'settings', icon: 'material-symbols:tune-rounded', label: t('ORGANOGRAM_SETTINGS') });
        actions.push({ key: 'list', icon: 'material-symbols:format-list-bulleted-rounded', label: t('ORGANOGRAM_LIST') });
        return actions;
    });

    function toggle(panel: 'view' | 'search' | 'filters'): void {
        viewOpen.value = panel === 'view' ? !viewOpen.value : false;
        searchOpen.value = panel === 'search' ? !searchOpen.value : false;
        filtersOpen.value = panel === 'filters' ? !filtersOpen.value : false;
        if (searchOpen.value) {
            void nextTick(() => searchInput.value?.focus());
        }
    }

    function chooseView(next: OrganogramView): void {
        emit('update:view', next);
        viewOpen.value = false;
    }

    function onMore(key: string): void {
        if (key === 'expand') emit('expand');
        if (key === 'collapse') emit('collapse');
        if (key === 'edit') emit('edit');
        if (key === 'reset') emit('reset');
        if (key === 'fit-location') emit('fit-location');
        if (key === 'list') emit('list');
        if (key === 'settings') emit('update:settingsOpen', !props.settingsOpen);
    }

    function onDocumentClick(event: MouseEvent): void {
        const target = event.target as Node | null;
        const root = (event.currentTarget as Document).querySelector('.organogram-float');
        if (root && target && root.contains(target)) {
            return;
        }
        viewOpen.value = false;
        searchOpen.value = false;
        filtersOpen.value = false;
        emit('update:settingsOpen', false);
    }

    function onKey(event: KeyboardEvent): void {
        if (event.key === 'Escape') {
            viewOpen.value = false;
            searchOpen.value = false;
            filtersOpen.value = false;
            emit('update:settingsOpen', false);
        }
    }

    onMounted(() => {
        document.addEventListener('click', onDocumentClick);
        document.addEventListener('keydown', onKey);
    });

    onUnmounted(() => {
        document.removeEventListener('click', onDocumentClick);
        document.removeEventListener('keydown', onKey);
    });
</script>
