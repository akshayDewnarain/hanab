<template>
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-start gap-3">
            <Icon
                class="mt-0.5 h-5 w-5 shrink-0 text-[var(--color-primary)]"
                icon="material-symbols:style-outline"
            />
            <div class="min-w-0 flex-1">
                <span class="text-sm font-semibold text-gray-800">{{ t('CERTIFICATE_DETAIL_TAB_PREVIEW') }}</span>
                <p class="mt-1 text-xs leading-relaxed text-gray-500">
                    {{ t('CERTIFICATE_DETAIL_PREVIEW_DESCRIPTION') }}
                </p>
            </div>
        </div>

        <div
            class="mt-4 flex flex-col items-center justify-center rounded-md border border-dashed border-slate-200/90 bg-gradient-to-br from-slate-50 to-white px-4 py-8"
        >
            <span
                class="inline-flex max-w-full items-center rounded-full border px-3 py-1.5 text-sm font-semibold shadow-sm"
                :style="chipStyle"
            >
                <span class="truncate">{{ displayText }}</span>
            </span>
            <p v-if="categoryLabel" class="mt-3 text-xs font-medium text-slate-600">
                {{ categoryLabel }}
            </p>
            <p class="mt-2 max-w-sm text-center text-xs text-slate-500">
                {{ t('CERTIFICATE_DETAIL_PREVIEW_CAPTION') }}
            </p>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
    import { relationRowChipStyle } from '@/modules/utils/groupedRelationPicker.ts';
    import type { CategoryGroupedRelationRow } from '@/modules/types/support/forms/CategoryGroupedRelationRow.ts';

    const props = defineProps<{
        options: BaseInputFieldOptions;
        formData?: Record<string, unknown>;
    }>();

    const { t } = useI18n();

    const CATEGORY_LABEL_KEYS: Record<string, string> = {
        safety: 'CERTIFICATE_CATEGORY_SAFETY',
        technical: 'CERTIFICATE_CATEGORY_TECHNICAL',
        compliance: 'CERTIFICATE_CATEGORY_COMPLIANCE',
    };

    const previewRow = computed((): CategoryGroupedRelationRow => ({
        id: 0,
        name: typeof props.formData?.name === 'string' ? props.formData.name : '',
        code: typeof props.formData?.code === 'string' ? props.formData.code : null,
        category: typeof props.formData?.category === 'string' ? props.formData.category : null,
        label_background_color:
            typeof props.formData?.label_background_color === 'string'
                ? props.formData.label_background_color
                : null,
        label_text_color:
            typeof props.formData?.label_text_color === 'string' ? props.formData.label_text_color : null,
        label_border_color:
            typeof props.formData?.label_border_color === 'string'
                ? props.formData.label_border_color
                : null,
    }));

    const displayText = computed(() => {
        const label = previewRow.value.code?.trim() || previewRow.value.name?.trim();
        return label || t('CERTIFICATE_DETAIL_PREVIEW_PLACEHOLDER');
    });

    const chipStyle = computed(() => relationRowChipStyle(previewRow.value));

    const categoryLabel = computed(() => {
        const cat = previewRow.value.category;
        if (!cat) {
            return '';
        }
        const key = CATEGORY_LABEL_KEYS[cat];
        return key ? t(key) : cat;
    });
</script>
