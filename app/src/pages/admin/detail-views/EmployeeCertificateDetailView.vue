<template>
    <BaseDetail :model="EmployeeCertificate" icon="note" :title="t('EMPLOYEE_CERTIFICATE_DETAIL_OVERVIEW')" />
</template>

<script setup lang="ts">
    import { useI18n } from 'vue-i18n';
    import EmployeeCertificate from '@/models/employees/EmployeeCertificate.ts';
    import BaseDetail from '@/components/details/BaseDetail.vue';

    const { t } = useI18n();
</script>
