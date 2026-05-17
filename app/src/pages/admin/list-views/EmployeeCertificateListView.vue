<template>
    <div class="flex flex-col w-full h-full">
        <ListView :entity="'employee-certificates'" :model="EmployeeCertificate" />
    </div>
</template>

<script setup lang="ts">
import { defineComponent } from 'vue';
import ListView from '@/components/list-views/ListView.vue';
import EmployeeCertificate from '@/models/employees/EmployeeCertificate.ts';

defineComponent({
    name: 'EmployeeCertificateListView',
});
</script>
