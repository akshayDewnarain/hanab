<template>
    <BaseDetail :model="Certificate">
        <template #header>
            <div class="flex pb-4 pt-2">
                <div class="bg-(--color-primary) rounded border border-gray-200 shadow px-2 py-1">
                    <span class="text-xl font-bold text-white">Certificate overview</span>
                </div>
            </div>
        </template>
    </BaseDetail>
</template>

<script setup lang="ts">
import Certificate from '@/models/employees/Certificate.ts';
import BaseDetail from '@/components/details/BaseDetail.vue';
</script>
