<template>
    <div class="flex flex-col w-full h-full">
        <ListView :entity="'employee-locations'" :model="EmployeeLocation" />
    </div>
</template>

<script setup lang="ts">
import { defineComponent } from 'vue';
import ListView from '@/components/list-views/ListView.vue';
import EmployeeLocation from '@/models/employees/EmployeeLocation.ts';

defineComponent({
    name: 'EmployeeLocationListView',
});
</script>
