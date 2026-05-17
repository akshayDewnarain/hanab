<template>
    <CardListView
        :card-config="employeeCardConfig"
        :entity="'employees'"
        :includes="employeeListIncludes"
        :model="Employee"
        :selectable="false"
    />
</template>

<script setup lang="ts">
    import CardListView from '@/components/list-views/CardListView.vue';
    import Employee from '@/models/employees/Employee.ts';
    import { employeeCardConfig } from '@/config/list-views/employeeCardConfig.ts';

    defineOptions({
        name: 'EmployeeListView',
    });

    const employeeListIncludes = ['employeeRole', 'employeeLocation', 'skills', 'certificates'];
</script>
