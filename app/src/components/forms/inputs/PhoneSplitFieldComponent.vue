<template>
    <div class="flex flex-col gap-1">
        <label :for="`${options.name}-national`" class="text-sm font-medium">
            {{ t(options.label) }}
            <span v-if="options.required" class="text-red-500 font-bold">*</span>
        </label>

        <div class="flex flex-wrap gap-2">
            <input
                :id="`${options.name}-country`"
                v-model="countryCode"
                :autocomplete="options.autocomplete ? `${options.autocomplete} country` : 'tel-country-code'"
                :disabled="options.disabled"
                :name="`${options.name}_country_code`"
                class="input-base w-24 shrink-0 rounded px-3 py-2 text-sm"
                maxlength="8"
                placeholder="+31"
                :readonly="options.readonly"
                type="text"
                @blur="$emit('blur')"
            />
            <input
                :id="`${options.name}-national`"
                v-model="nationalNumber"
                :autocomplete="options.autocomplete ? `${options.autocomplete} national` : 'tel-national-number'"
                :disabled="options.disabled"
                :name="`${options.name}_national_number`"
                class="input-base min-w-0 flex-1 rounded px-3 py-2 text-sm"
                :placeholder="t(options.placeholder ?? 'GENERAL_PLACEHOLDER')"
                :readonly="options.readonly"
                type="tel"
                @blur="$emit('blur')"
            />
        </div>

        <Collapse :duration="500" :max-height="'50px'" :show="!!error">
            <div v-if="error" class="text-red-500 text-xs ml-1">{{ t(error) }}</div>
        </Collapse>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent } from 'vue';
    import Collapse from '@/components/transitions/Collapse.vue';
    import { useI18n } from 'vue-i18n';
    import type { PhoneSplitFieldOptions } from '@/modules/types/support/inputs/PhoneSplitFieldOptions.ts';

    type PhoneSplitModelValue = {
        country_code: string;
        national_number: string;
    };

    defineComponent({
        name: 'PhoneSplitFieldComponent',
    });

    const props = defineProps<{
        options: PhoneSplitFieldOptions;
        error?: string;
        modelValue: PhoneSplitModelValue | string | null;
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', v: PhoneSplitModelValue): void;
        (e: 'blur'): void;
    }>();

    const { t } = useI18n();

    function normalize(v: typeof props.modelValue): PhoneSplitModelValue {
        if (v && typeof v === 'object' && 'national_number' in v) {
            return {
                country_code: String((v as PhoneSplitModelValue).country_code ?? ''),
                national_number: String((v as PhoneSplitModelValue).national_number ?? ''),
            };
        }
        if (typeof v === 'string') {
            return { country_code: '', national_number: v };
        }
        return { country_code: '', national_number: '' };
    }

    const normalized = computed(() => normalize(props.modelValue));

    function patch(partial: Partial<PhoneSplitModelValue>) {
        emit('update:modelValue', { ...normalized.value, ...partial });
    }

    const countryCode = computed({
        get: () => normalized.value.country_code,
        set: (country_code: string) => patch({ country_code }),
    });

    const nationalNumber = computed({
        get: () => normalized.value.national_number,
        set: (national_number: string) => patch({ national_number }),
    });
</script>
