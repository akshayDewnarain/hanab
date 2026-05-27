<template>
    <main class="flex h-dvh max-h-dvh w-full flex-col overflow-hidden bg-slate-100">
        <header
            class="shrink-0 bg-gradient-to-r from-[var(--color-sidebar-background)] via-[var(--color-secondary)] to-[var(--color-sidebar-item-highlight-dark)] px-4 py-3 text-white"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-white/70">Hanab Mobile</p>
                    <h1 class="mt-1 text-lg font-semibold">{{ t('PAGE_TITLE_EMPLOYEES') }}</h1>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        aria-label="Notifications"
                        class="relative inline-flex size-9 items-center justify-center rounded-lg border border-white/35 bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/20"
                        @click="openNotifications"
                    >
                        <Icon class="size-5" icon="material-symbols:notifications-outline-rounded" />
                        <span
                            class="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full bg-[var(--color-secondary)] text-[10px] font-bold leading-none text-white ring-2 ring-[var(--color-sidebar-background)]"
                        >
                            2
                        </span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex size-9 items-center justify-center rounded-lg border border-white/35 bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/20"
                        @click="openFilters"
                    >
                        <Icon class="size-5" icon="material-symbols:filter-alt-outline" />
                    </button>
                </div>
            </div>
        </header>

        <div class="min-h-0 flex-1 overflow-hidden">
            <EmployeeListView
                class="h-full"
                is-mobile-layout
                :mobile-filters-open="isFiltersOpen"
                @update:mobileFiltersOpen="isFiltersOpen = $event"
            />
        </div>

        <nav
            class="shrink-0 border-t border-white/20 bg-gradient-to-r from-[var(--color-highlight-dark)] via-[var(--color-primary)] to-[var(--color-primary)] px-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] pt-2 backdrop-blur"
        >
            <ul class="grid grid-cols-3 gap-2">
                <li>
                    <div
                        class="flex flex-col items-center gap-1 rounded-lg bg-[var(--color-primary)]/35 px-2 py-2 text-white shadow-sm ring-1 ring-white/20"
                    >
                        <Icon class="size-5" icon="material-symbols:groups-rounded" />
                        <span class="text-[11px] font-semibold">Employees</span>
                    </div>
                </li>
                <li>
                    <div class="flex flex-col items-center gap-1 rounded-lg px-2 py-2 text-white/80">
                        <Icon class="size-5" icon="material-symbols:space-dashboard-outline-rounded" />
                        <span class="text-[11px] font-medium">Dashboard</span>
                    </div>
                </li>
                <li>
                    <div class="flex flex-col items-center gap-1 rounded-lg px-2 py-2 text-white/80">
                        <Icon class="size-5" icon="material-symbols:settings-outline-rounded" />
                        <span class="text-[11px] font-medium">Settings</span>
                    </div>
                </li>
            </ul>
        </nav>

        <MobileNotificationsOverlay :open="isNotificationsOpen" @close="isNotificationsOpen = false" />
    </main>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import EmployeeListView from '@/pages/admin/list-views/EmployeeListView.vue';
    import MobileNotificationsOverlay from '@/components/mobile/MobileNotificationsOverlay.vue';

    const { t } = useI18n();
    const isFiltersOpen = ref(false);
    const isNotificationsOpen = ref(false);

    function openFilters(): void {
        isNotificationsOpen.value = false;
        isFiltersOpen.value = true;
    }

    function openNotifications(): void {
        isFiltersOpen.value = false;
        isNotificationsOpen.value = true;
    }

    defineOptions({
        name: 'mobile-layout',
    });
</script>
