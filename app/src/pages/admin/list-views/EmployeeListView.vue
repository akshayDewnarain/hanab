<template>
    <div class="h-full min-h-0">
        <CardListView
            directory-switcher
            :card-config="employeeCardConfig"
            :entity="'employees'"
            :includes="employeeListIncludes"
            :is-mobile-layout="isMobileLayout"
            :mobile-filters-open="mobileFiltersOpen"
            :model="Employee"
            :presentation="presentation"
            :selectable="false"
            @update:mobileFiltersOpen="$emit('update:mobileFiltersOpen', $event)"
            @update:presentation="setPresentation"
        />
    </div>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import CardListView from '@/components/list-views/CardListView.vue';
    import Employee from '@/models/employees/Employee.ts';
    import { employeeCardConfig } from '@/config/list-views/employeeCardConfig.ts';

    defineOptions({
        name: 'EmployeeListView',
    });

    defineProps<{
        mobileFiltersOpen?: boolean;
        isMobileLayout?: boolean;
    }>();

    defineEmits<{
        (e: 'update:mobileFiltersOpen', value: boolean): void;
    }>();

    const VIEW_KEY = 'hanab.employees.view';
    const stored = typeof localStorage !== 'undefined' ? localStorage.getItem(VIEW_KEY) : null;
    const presentation = ref<'directory' | 'cards'>(stored === 'cards' ? 'cards' : 'directory');

    function setPresentation(value: 'directory' | 'cards'): void {
        presentation.value = value;
        localStorage.setItem(VIEW_KEY, value);
    }

    const employeeListIncludes = ['employeeRole', 'employeeLocation', 'skills', 'certificates'];
</script>
