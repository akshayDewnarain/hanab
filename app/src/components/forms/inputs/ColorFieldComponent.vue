<template>
    <div class="flex flex-col gap-1">
        <label :for="options.name" class="text-sm font-medium">
            {{ t(options.label) }}
            <span v-if="options.required" class="font-bold text-red-500">*</span>
        </label>

        <div class="flex items-center gap-2">
            <input
                :id="`${options.name}-picker`"
                v-model="pickerValue"
                class="size-10 shrink-0 cursor-pointer rounded border border-slate-300 bg-white p-0.5"
                type="color"
                @input="onPickerInput"
            />
            <input
                :id="options.name"
                v-model="value"
                :disabled="options.disabled"
                :name="options.name"
                :placeholder="t(options.placeholder ?? 'SKILL_COLOR_PLACEHOLDER')"
                :readonly="options.readonly"
                class="input-base min-w-0 flex-1 rounded px-3 py-2 font-mono text-sm uppercase"
                type="text"
                maxlength="7"
                @blur="$emit('blur')"
            />
        </div>

        <Collapse :duration="500" :max-height="'50px'" :show="!!error">
            <div v-if="error" class="ml-1 text-xs text-red-500">
                {{ t(error) }}
            </div>
        </Collapse>
    </div>
</template>

<script lang="ts" setup>
    import { computed, watch } from 'vue';
    import Collapse from '@/components/transitions/Collapse.vue';
    import type { ColorFieldOptions } from '@/modules/types/support/inputs/ColorFieldOptions.ts';
    import { useI18n } from 'vue-i18n';

    const props = defineProps<{
        options: ColorFieldOptions;
        error?: string;
        modelValue: string | null;
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', v: string | null): void;
        (e: 'blur'): void;
    }>();

    const { t } = useI18n();

    const value = computed({
        get: () => props.modelValue ?? '',
        set: (v: string) => emit('update:modelValue', v || null),
    });

    const pickerValue = computed({
        get: () => normalizeHex(value.value) || '#006a7c',
        set: (v: string) => {
            value.value = v.toUpperCase();
        },
    });

    function normalizeHex(raw: string): string | null {
        const v = raw.trim();
        if (/^#[0-9A-Fa-f]{6}$/.test(v)) {
            return v;
        }
        if (/^#[0-9A-Fa-f]{3}$/.test(v)) {
            const h = v.slice(1);
            return `#${h[0]}${h[0]}${h[1]}${h[1]}${h[2]}${h[2]}`.toUpperCase();
        }
        return null;
    }

    function onPickerInput(event: Event): void {
        const target = event.target as HTMLInputElement;
        value.value = target.value.toUpperCase();
    }

    watch(
        () => value.value,
        (v) => {
            const hex = normalizeHex(v);
            if (hex && hex !== v) {
                value.value = hex;
            }
        },
    );
</script>
