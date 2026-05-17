<template>
    <div class="flex flex-col w-full h-full">
        <ListView :entity="'certificates'" :model="Certificate" />
    </div>
</template>

<script setup lang="ts">
import { defineComponent } from 'vue';
import ListView from '@/components/list-views/ListView.vue';
import Certificate from '@/models/employees/Certificate.ts';

defineComponent({
    name: 'CertificateListView',
});
</script>
