<template>
    <BaseDetail :model="EmployeeRole" icon="badge" :title="t('EMPLOYEE_ROLE_DETAIL_OVERVIEW')" />
</template>

<script setup lang="ts">
    import { useI18n } from 'vue-i18n';
    import EmployeeRole from '@/models/employees/EmployeeRole.ts';
    import BaseDetail from '@/components/details/BaseDetail.vue';

    const { t } = useI18n();
</script>
