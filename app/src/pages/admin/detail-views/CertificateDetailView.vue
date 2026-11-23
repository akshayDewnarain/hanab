<template>
    <BaseDetail :model="Certificate" icon="badge" :title="t('CERTIFICATE_DETAIL_OVERVIEW')" />
</template>

<script setup lang="ts">
    import { useI18n } from 'vue-i18n';
    import Certificate from '@/models/employees/Certificate.ts';
    import BaseDetail from '@/components/details/BaseDetail.vue';

    const { t } = useI18n();
</script>
