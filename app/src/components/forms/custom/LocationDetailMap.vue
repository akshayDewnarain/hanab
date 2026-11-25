<template>
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-start gap-3">
            <Icon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--color-primary)]" icon="material-symbols:map-outline" />
            <div class="min-w-0 flex-1">
                <span class="text-sm font-semibold text-gray-800">{{ t('LOCATION_DETAIL_MAP_TITLE') }}</span>
                <p class="mt-1 text-xs leading-relaxed text-gray-500">{{ t('LOCATION_DETAIL_MAP_DESCRIPTION') }}</p>
            </div>
        </div>
        <div class="mt-4 overflow-hidden rounded-md border border-slate-200 bg-slate-50">
            <UiMap
                v-if="marker"
                style="height: 18rem"
                :markers="[marker]"
                :max-zoom="18"
                :min-zoom="4"
                :single-zoom="14"
            >
                <template #error>{{ t('LOCATION_DETAIL_MAP_ERROR') }}</template>
            </UiMap>
            <div v-else class="flex h-72 flex-col items-center justify-center px-6 text-center">
                <p class="text-sm font-medium text-slate-700">{{ t('LOCATION_DETAIL_MAP_EMPTY') }}</p>
                <p class="mt-1 max-w-sm text-xs leading-relaxed text-slate-500">{{ t('LOCATION_DETAIL_MAP_EMPTY_HINT') }}</p>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { UiMap } from '@freelance/vue-ui/map';
    import '@freelance/vue-ui/map.css';
    import '@freelance/vue-ui/leaflet.css';
    import { isMapCoordinate } from '@freelance/vue-ui/map';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

    const props = defineProps<{
        options: BaseInputFieldOptions;
        formData?: Record<string, unknown>;
    }>();

    const { t } = useI18n();

    function coordinate(value: unknown): number | null {
        if (typeof value === 'number' && Number.isFinite(value)) return value;
        if (typeof value === 'string' && value.trim() !== '') {
            const parsed = Number(value.trim().replace(',', '.'));
            return Number.isFinite(parsed) ? parsed : null;
        }
        return null;
    }

    const marker = computed(() => {
        const latitude = coordinate(props.formData?.latitude);
        const longitude = coordinate(props.formData?.longitude);
        if (latitude === null || longitude === null || !isMapCoordinate(latitude, longitude)) return null;
        const name = typeof props.formData?.name === 'string' ? props.formData.name.trim() : '';
        return { id: 'location', latitude, longitude, label: name || t('EMPLOYEE_LOCATION') };
    });
</script>
