<template>
    <div class="flex flex-col gap-1">
        <label :for="options.name" class="text-sm font-medium">
            {{ t(options.label) }}
            <span v-if="options.required" class="text-red-500 font-bold">*</span>
        </label>

        <input
            :id="options.name"
            v-model="value"
            :autocomplete="options.autocomplete"
            :disabled="options.disabled"
            :name="options.name"
            :placeholder="t(options.placeholder ?? 'GENERAL_PLACEHOLDER')"
            :readonly="options.readonly"
            :type="'text'"
            class="input-base rounded px-3 py-2 text-sm"
            @blur="$emit('blur')"
        />
        <Collapse :duration="500" :max-height="'50px'" :show="!!error">
            <div v-if="error" class="text-red-500 text-xs ml-1">
                {{ t(error) }}
            </div>
        </Collapse>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent } from 'vue';
    import type { InputFieldOptions } from '@/modules/types/support/inputs/InputFieldOptions.ts';
    import Collapse from '@/components/transitions/Collapse.vue';
    import { useI18n } from 'vue-i18n';

    defineComponent({
        name: 'FieldInput',
    });

    const props = defineProps<{
        options: InputFieldOptions;
        error?: string;
        modelValue: string | null;
    }>();

    const { t } = useI18n();

    const emit = defineEmits<{
        (e: 'update:modelValue', v: string | null): void;
        (e: 'blur'): void;
    }>();

    const value = computed({
        get: () => props.modelValue,
        set: (v: string | null) => emit('update:modelValue', v),
    });
</script>
