<template>
    <InformationPanel
        v-model:expanded="expanded"
        :title="t('CERTIFICATE_DETAIL_HELP_TITLE')"
        icon="material-symbols:lightbulb-outline-rounded"
    >
        <p class="mb-3">{{ t('CERTIFICATE_DETAIL_HELP_INTRO') }}</p>
        <ul class="list-inside list-disc space-y-1.5 text-slate-600">
            <li>{{ t('CERTIFICATE_DETAIL_HELP_TIP_CODE') }}</li>
            <li>{{ t('CERTIFICATE_DETAIL_HELP_TIP_COLORS') }}</li>
            <li>{{ t('CERTIFICATE_DETAIL_HELP_TIP_CATEGORY') }}</li>
            <li>{{ t('CERTIFICATE_DETAIL_HELP_TIP_EXPIRY') }}</li>
            <li>{{ t('CERTIFICATE_DETAIL_HELP_TIP_ACTIVE') }}</li>
        </ul>
    </InformationPanel>
</template>

<script lang="ts" setup>
    import { ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import InformationPanel from '@/components/feedback/InformationPanel.vue';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

    defineProps<{
        options: BaseInputFieldOptions;
    }>();

    const { t } = useI18n();
    const expanded = ref(true);
</script>
