<template>
    <div class="flex flex-col gap-2">
        <label :for="options.name" class="text-sm font-medium">
            {{ t(options.label) }}
            <span v-if="options.required" class="text-red-500 font-bold">*</span>
        </label>
        <div class="flex items-center gap-2">
            <input :value="safeColor" type="color" class="h-9 w-12 cursor-pointer rounded border" @input="setColor(($event.target as HTMLInputElement).value)" />
            <input
                :id="options.name"
                :value="modelValue ?? ''"
                type="text"
                maxlength="7"
                class="input-base rounded px-3 py-2 text-sm uppercase"
                :placeholder="t(options.placeholder ?? 'ORGANIZATION_LEVEL_COLOR_PLACEHOLDER')"
                @input="setColor(($event.target as HTMLInputElement).value)"
                @blur="$emit('blur')"
            />
        </div>
        <div class="w-52 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm" :style="{ borderTop: `4px solid ${safeColor}` }">
            <div class="flex items-center gap-2 px-3 py-2">
                <span class="grid size-8 place-items-center rounded-full text-xs font-semibold text-white" :style="{ background: safeColor }">CA</span>
                <span>
                    <span class="block text-sm font-semibold">{{ t('ORGANIZATION_LEVEL_PREVIEW') }}</span>
                    <span class="block text-xs text-slate-500">{{ safeColor }}</span>
                </span>
            </div>
        </div>
        <p v-if="error" class="ml-1 text-xs text-red-500">{{ t(error) }}</p>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import type { InputFieldOptions } from '@/modules/types/support/inputs/InputFieldOptions.ts';

    const props = defineProps<{
        options: InputFieldOptions;
        error?: string;
        modelValue: string | null;
    }>();

    const emit = defineEmits<{
        'update:modelValue': [value: string | null];
        blur: [];
    }>();

    const { t } = useI18n();
    const safeColor = computed(() => {
        const value = props.modelValue ?? '';
        return /^#[0-9A-Fa-f]{6}$/.test(value) ? value : '#8B95A4';
    });

    function setColor(value: string): void {
        emit('update:modelValue', value.trim());
    }
</script>
