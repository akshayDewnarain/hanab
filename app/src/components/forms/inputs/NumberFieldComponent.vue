<template>
    <div class="flex flex-col gap-1">
        <label :for="props.options.name" class="text-sm font-medium">
            {{ t(props.options.label) }}
            <span v-if="props.options.required" class="text-red-500 font-bold">*</span>
        </label>

        <input
            :id="props.options.name"
            v-model.number="value"
            :autocomplete="props.options.autocomplete"
            :disabled="props.options.disabled"
            :max="props.options.max ?? undefined"
            :min="props.options.min ?? undefined"
            :name="props.options.name"
            :step="props.options.step === undefined ? 'any' : props.options.step"
            :placeholder="t(props.options.placeholder ?? 'GENERAL_PLACEHOLDER')"
            :readonly="props.options.readonly"
            class="input-base rounded px-3 py-2 text-sm appearance-none"
            type="number"
            @blur="$emit('blur')"
        />

        <Collapse :duration="500" :max-height="'50px'" :show="!!error">
            <div v-if="error" class="text-red-500 text-xs ml-1">{{ t(error) }}</div>
        </Collapse>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent } from 'vue';
    import type { NumberFieldOptions } from '@/modules/types/support/inputs/NumberFieldOptions.ts';
    import Collapse from '@/components/transitions/Collapse.vue';
    import { useI18n } from 'vue-i18n';

    defineComponent({
        name: 'NumberFieldComponent',
    });

    const emit = defineEmits<{
        (e: 'update:modelValue', v: number | null): void;
        (e: 'blur'): void;
    }>();

    const props = defineProps<{
        options: NumberFieldOptions;
        error?: string;
        modelValue: string | number | null;
    }>();

    const { t } = useI18n();

    function coerceNumber(v: unknown): number | null {
        if (v === '' || v === null || typeof v === 'undefined') return null;
        const n = typeof v === 'number' ? v : Number(v);
        return Number.isNaN(n) ? null : n;
    }

    const value = computed({
        get: () => props.modelValue,
        set: (v) => emit('update:modelValue', coerceNumber(v)),
    });
</script>
