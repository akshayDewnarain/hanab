<template>
    <div class="flex flex-col w-full h-full">
        <ListView :entity="'employee-skills'" :model="EmployeeSkill" />
    </div>
</template>

<script setup lang="ts">
import { defineComponent } from 'vue';
import ListView from '@/components/list-views/ListView.vue';
import EmployeeSkill from '@/models/employees/EmployeeSkill.ts';

defineComponent({
    name: 'EmployeeSkillListView',
});
</script>
