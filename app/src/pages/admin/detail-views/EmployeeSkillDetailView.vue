<template>
    <BaseDetail :model="EmployeeSkill" icon="badge" :title="t('EMPLOYEE_SKILL_DETAIL_OVERVIEW')" />
</template>

<script setup lang="ts">
    import { useI18n } from 'vue-i18n';
    import EmployeeSkill from '@/models/employees/EmployeeSkill.ts';
    import BaseDetail from '@/components/details/BaseDetail.vue';

    const { t } = useI18n();
</script>
