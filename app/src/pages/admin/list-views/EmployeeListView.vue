<template>
    <div class="h-full min-h-0">
        <CardListView
            :card-config="employeeCardConfig"
            :entity="'employees'"
            :includes="employeeListIncludes"
            :is-mobile-layout="isMobileLayout"
            :mobile-filters-open="mobileFiltersOpen"
            :model="Employee"
            :selectable="false"
            @update:mobileFiltersOpen="$emit('update:mobileFiltersOpen', $event)"
        />
    </div>
</template>

<script setup lang="ts">
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

    const employeeListIncludes = ['employeeRole', 'employeeLocation', 'skills', 'certificates'];
</script>
