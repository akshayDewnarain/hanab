<template>
    <BaseDetail :model="Skill">
        <template #header>
            <div class="flex pb-4 pt-2">
                <div
                    class="flex items-center gap-2 rounded border border-gray-200 bg-[var(--color-background)] px-3 py-1.5 shadow"
                >
                    <Icon class="size-6 text-white" icon="material-symbols:workspace-premium-outline" />
                    <span class="text-xl font-bold text-white">{{ t('SKILL_DETAIL_OVERVIEW') }}</span>
                </div>
            </div>
        </template>
    </BaseDetail>
</template>

<script setup lang="ts">
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import Skill from '@/models/employees/Skill.ts';
    import BaseDetail from '@/components/details/BaseDetail.vue';

    const { t } = useI18n();
</script>
