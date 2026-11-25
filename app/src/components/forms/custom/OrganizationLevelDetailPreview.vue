<template>
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-start gap-3">
            <Icon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--color-primary)]" icon="material-symbols:palette-outline" />
            <div class="min-w-0 flex-1">
                <span class="text-sm font-semibold text-gray-800">{{ t('LEVEL_DETAIL_PREVIEW_TITLE') }}</span>
                <p class="mt-1 text-xs leading-relaxed text-gray-500">{{ t('LEVEL_DETAIL_PREVIEW_DESCRIPTION') }}</p>
            </div>
        </div>
        <div class="mt-4 flex justify-center rounded-md border border-dashed border-slate-200 bg-gradient-to-br from-slate-50 to-white px-4 py-8">
            <div class="w-full max-w-xs overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm" :style="{ borderTop: `4px solid ${color}` }">
                <div class="px-3 py-3">
                    <p class="text-[11px] font-semibold tracking-wide text-slate-500 uppercase">{{ t('ORGANIZATION_LEVEL') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ name }}</p>
                </div>
            </div>
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
    const HEX = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
    const name = computed(() => {
        const value = typeof props.formData?.name === 'string' ? props.formData.name.trim() : '';
        return value || t('LEVEL_DETAIL_PREVIEW_PLACEHOLDER');
    });
    const color = computed(() => {
        const value = typeof props.formData?.color === 'string' ? props.formData.color.trim() : '';
        return HEX.test(value) ? value : '#8B95A4';
    });
</script>
