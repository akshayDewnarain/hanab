<template>
    <BaseDetail :model="EmployeeSkill">
        <template #header>
            <div class="flex pb-4 pt-2">
                <div class="bg-(--color-primary) rounded border border-gray-200 shadow px-2 py-1">
                    <span class="text-xl font-bold text-white">Employee skill overview</span>
                </div>
            </div>
        </template>
    </BaseDetail>
</template>

<script setup lang="ts">
import EmployeeSkill from '@/models/employees/EmployeeSkill.ts';
import BaseDetail from '@/components/details/BaseDetail.vue';
</script>
