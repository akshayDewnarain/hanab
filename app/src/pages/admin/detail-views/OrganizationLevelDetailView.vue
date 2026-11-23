<template>
    <BaseDetail :model="OrganizationLevel" icon="info" :title="t('ORGANIZATION_LEVEL')" />
</template>

<script setup lang="ts">
    import { useI18n } from 'vue-i18n';
    import OrganizationLevel from '@/models/employees/OrganizationLevel.ts';
    import BaseDetail from '@/components/details/BaseDetail.vue';

    const { t } = useI18n();
</script>
