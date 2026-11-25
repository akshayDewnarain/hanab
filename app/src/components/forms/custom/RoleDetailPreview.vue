<template>
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-start gap-3">
            <Icon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--color-primary)]" icon="material-symbols:badge-outline" />
            <div class="min-w-0 flex-1">
                <span class="text-sm font-semibold text-gray-800">{{ t('ROLE_DETAIL_PREVIEW_TITLE') }}</span>
                <p class="mt-1 text-xs leading-relaxed text-gray-500">{{ t('ROLE_DETAIL_PREVIEW_DESCRIPTION') }}</p>
            </div>
        </div>
        <div class="mt-4 rounded-md border border-dashed border-slate-200 bg-gradient-to-br from-slate-50 to-white px-4 py-6">
            <p class="text-base font-semibold text-slate-800">{{ name }}</p>
            <p class="mt-1 text-sm leading-6 text-slate-600">{{ description }}</p>
            <p class="mt-3 text-xs font-medium text-slate-500">{{ t('EMPLOYEE_ROLE_SORT') }}: {{ sortOrder }}</p>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

    const props = defineProps<{
        options: BaseInputFieldOptions;
        formData?: Record<string, unknown>;
    }>();

    const { t } = useI18n();
    const name = computed(() => {
        const value = typeof props.formData?.name === 'string' ? props.formData.name.trim() : '';
        return value || t('ROLE_DETAIL_PREVIEW_PLACEHOLDER');
    });
    const description = computed(() => {
        const value = typeof props.formData?.description === 'string' ? props.formData.description.trim() : '';
        return value || t('ROLE_DETAIL_PREVIEW_NO_DESCRIPTION');
    });
    const sortOrder = computed(() => {
        const value = props.formData?.sort_order;
        if (typeof value === 'number' && Number.isFinite(value)) return value;
        if (typeof value === 'string' && value.trim() !== '' && Number.isFinite(Number(value))) return Number(value);
        return 0;
    });
</script>
