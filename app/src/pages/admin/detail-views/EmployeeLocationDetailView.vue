<template>
    <BaseDetail :model="EmployeeLocation" icon="info" :title="t('EMPLOYEE_LOCATION_DETAIL_OVERVIEW')" />
</template>

<script setup lang="ts">
    import { useI18n } from 'vue-i18n';
    import EmployeeLocation from '@/models/employees/EmployeeLocation.ts';
    import BaseDetail from '@/components/details/BaseDetail.vue';

    const { t } = useI18n();
</script>
