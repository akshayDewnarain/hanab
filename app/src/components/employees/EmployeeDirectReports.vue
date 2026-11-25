<template>
    <section class="rounded-xl border border-list-border bg-list-surface px-5 py-4 shadow-sm">
        <h2 class="m-0 flex items-center gap-2 text-base font-semibold text-list-text">
            <Icon class="size-5 text-list-text-muted" icon="material-symbols:groups-outline-rounded" />
            {{ t('EMPLOYEE_DIRECT_REPORTS') }}
        </h2>
        <ul class="m-0 mt-3 grid list-none gap-1 p-0">
            <li v-for="person in reports" :key="person.id">
                <router-link
                    class="flex items-center gap-3 rounded-lg px-2 py-2 transition-colors duration-200 hover:bg-list-row-hover motion-reduce:transition-none"
                    :to="{ name: 'admin-employees-detail', params: { id: String(person.id) } }"
                >
                    <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full border border-list-border bg-list-canvas text-xs font-semibold text-list-text">
                        <img v-if="person.photo_url" :alt="name(person)" :src="person.photo_url" class="size-full object-cover" />
                        <span v-else>{{ person.initials }}</span>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-list-text">{{ name(person) }}</span>
                        <span v-if="person.role" class="block truncate text-xs text-list-text-muted">{{ person.role }}</span>
                    </span>
                </router-link>
            </li>
        </ul>
    </section>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import type { EmployeeDirectReport } from '@/modules/types/support/models/EmployeeRecord.ts';

    defineOptions({ name: 'EmployeeDirectReports' });

    defineProps<{
        reports: EmployeeDirectReport[];
    }>();

    const { t } = useI18n();

    function name(person: EmployeeDirectReport): string {
        return `${person.first_name} ${person.last_name}`.trim();
    }
</script>
