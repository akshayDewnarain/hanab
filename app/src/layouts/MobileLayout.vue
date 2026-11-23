<template>
    <main class="flex h-dvh max-h-dvh w-full flex-col overflow-hidden bg-list-canvas text-text">
        <header
            class="shrink-0 bg-gradient-to-r from-sidebar-background-gradient-from via-sidebar-background to-sidebar-background-gradient-to px-4 py-3 text-sidebar-item-text"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-sidebar-text-muted">Hanab Mobile</p>
                    <h1 class="mt-1 text-lg font-semibold">{{ pageTitle }}</h1>
                    <RouterLink
                        v-if="isStandalone"
                        class="mt-1 inline-flex text-sm font-semibold text-sidebar-item-text underline"
                        to="/admin/employees"
                    >
                        {{ t('PAGE_TITLE_EMPLOYEES') }}
                    </RouterLink>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <ExpertiseSearch presentation="field" shortcut />
                    <RouterLink
                        to="/admin/messages"
                        :aria-label="t('MESSAGES_TITLE')"
                        class="relative inline-flex size-9 items-center justify-center rounded-lg border border-sidebar-border-highlight bg-sidebar-hover-background text-sidebar-icon transition hover:brightness-110"
                    >
                        <Icon class="size-5" icon="material-symbols:notifications-outline-rounded" />
                    </RouterLink>
                    <button
                        v-if="!isStandalone"
                        type="button"
                        class="inline-flex size-9 items-center justify-center rounded-lg border border-sidebar-border-highlight bg-sidebar-hover-background text-sidebar-icon transition hover:brightness-110"
                        @click="openFilters"
                    >
                        <Icon class="size-5" icon="material-symbols:filter-alt-outline" />
                    </button>
                </div>
            </div>
        </header>

        <div class="min-h-0 flex-1 overflow-hidden">
            <router-view v-if="isStandalone" />
            <EmployeeListView
                v-else
                class="h-full"
                is-mobile-layout
                :mobile-filters-open="isFiltersOpen"
                @update:mobileFiltersOpen="isFiltersOpen = $event"
            />
        </div>

        <nav
            class="shrink-0 border-t border-sidebar-border-highlight bg-gradient-to-r from-highlight-dark via-primary to-primary px-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] pt-2 text-on-brand backdrop-blur"
        >
            <ul class="grid grid-cols-3 gap-2">
                <li>
                    <RouterLink
                        to="/admin/employees"
                        class="flex flex-col items-center gap-1 rounded-lg px-2 py-2 text-on-brand/80"
                        active-class="bg-primary/35 text-on-brand shadow-sm ring-1 ring-on-brand/20"
                    >
                        <Icon class="size-5" icon="material-symbols:groups-rounded" />
                        <span class="text-[11px] font-semibold">{{ t('PAGE_TITLE_EMPLOYEES') }}</span>
                    </RouterLink>
                </li>
                <li>
                    <RouterLink
                        to="/admin/organogram"
                        class="flex flex-col items-center gap-1 rounded-lg px-2 py-2 text-on-brand/80"
                        active-class="bg-primary/35 text-on-brand shadow-sm ring-1 ring-on-brand/20"
                    >
                        <Icon class="size-5" icon="material-symbols:account-tree-outline-rounded" />
                        <span class="text-[11px] font-semibold">{{ t('ORGANOGRAM_TITLE') }}</span>
                    </RouterLink>
                </li>
                <li>
                    <RouterLink
                        to="/admin/messages"
                        class="flex flex-col items-center gap-1 rounded-lg px-2 py-2 text-on-brand/80"
                        active-class="bg-primary/35 text-on-brand shadow-sm ring-1 ring-on-brand/20"
                    >
                        <Icon class="size-5" icon="material-symbols:forum-outline-rounded" />
                        <span class="text-[11px] font-semibold">{{ t('MESSAGES_TITLE') }}</span>
                    </RouterLink>
                </li>
            </ul>
        </nav>
        <HelpDrawer />
    </main>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { computed, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRoute } from 'vue-router';
    import ExpertiseSearch from '@/components/search/ExpertiseSearch.vue';
    import EmployeeListView from '@/pages/admin/list-views/EmployeeListView.vue';
    import HelpDrawer from '@/components/help/HelpDrawer.vue';

    const { t } = useI18n();
    const route = useRoute();
    const standaloneNames = ['admin-data-transfer', 'admin-organogram', 'admin-messages'];
    const isStandalone = computed(() => standaloneNames.includes(String(route.name)) || String(route.name).startsWith('admin-help'));
    const pageTitle = computed(() => {
        if (route.name === 'admin-data-transfer') return t('DATA_TRANSFER_TITLE');
        if (route.name === 'admin-organogram') return t('ORGANOGRAM_TITLE');
        if (route.name === 'admin-messages') return t('MESSAGES_TITLE');
        if (String(route.name).startsWith('admin-help')) return t('HELP_TITLE');
        return t('PAGE_TITLE_EMPLOYEES');
    });
    const isFiltersOpen = ref(false);

    function openFilters(): void {
        isFiltersOpen.value = true;
    }

    defineOptions({
        name: 'mobile-layout',
    });
</script>
