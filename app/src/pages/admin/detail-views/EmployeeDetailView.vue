<template>
    <BaseDetail :model="Employee">
        <template #header>
            <div class="flex pb-4 pt-2">
                <div class="bg-[var(--color-background)] rounded border border-gray-200 shadow px-2 py-1">
                    <span class="text-xl font-bold text-white">Employee overview</span>
                </div>
            </div>
        </template>
    </BaseDetail>
</template>

<script setup lang="ts">

import Employee from '@/models/employees/Employee.ts';
import BaseDetail from '@/components/details/BaseDetail.vue';
</script>

