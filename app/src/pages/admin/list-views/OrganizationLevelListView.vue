<template>
    <div class="flex flex-col w-full h-full">
        <ListView :entity="'organization-levels'" :model="OrganizationLevel" />
    </div>
</template>

<script setup lang="ts">
    import { defineComponent } from 'vue';
    import ListView from '@/components/list-views/ListView.vue';
    import OrganizationLevel from '@/models/employees/OrganizationLevel.ts';

    defineComponent({
        name: 'OrganizationLevelListView',
    });
</script>
