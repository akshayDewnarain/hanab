<template>
    <BaseDetail :model="Skill" icon="badge" :title="t('SKILL_DETAIL_OVERVIEW')" />
</template>

<script setup lang="ts">
    import { useI18n } from 'vue-i18n';
    import Skill from '@/models/employees/Skill.ts';
    import BaseDetail from '@/components/details/BaseDetail.vue';

    const { t } = useI18n();
</script>
