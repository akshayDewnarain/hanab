<template>
    <div class="flex flex-col w-full h-full">
        <ListView :entity="'employee-roles'" :model="EmployeeRole" />
    </div>
</template>

<script setup lang="ts">
import { defineComponent } from 'vue';
import ListView from '@/components/list-views/ListView.vue';
import EmployeeRole from '@/models/employees/EmployeeRole.ts';

defineComponent({
    name: 'EmployeeRoleListView',
});
</script>
