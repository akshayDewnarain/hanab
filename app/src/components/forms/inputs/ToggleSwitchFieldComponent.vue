<template>
    <div class="flex flex-col gap-1">
        <label :for="options.name" class="flex items-center gap-1.5 text-sm font-medium">
            <span>{{ t(options.label) }}</span>
            <span v-if="options.required" class="font-bold text-red-500">*</span>
            <FieldHintTooltip v-if="options.hintKey" :text="t(options.hintKey)" />
        </label>

        <div class="h-full flex items-center">
            <ToggleSwitch v-model="value" :disabled="options.disabled" :tone="options.toggleTone ?? 'neutral'" />
        </div>

        <Collapse :duration="500" :max-height="'50px'" :show="!!error">
            <div v-if="error" class="text-red-500 text-xs ml-1">
                {{ t(error) }}
            </div>
        </Collapse>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent } from 'vue';
    import type { ToggleFieldOptions } from '@/modules/types/support/inputs/ToggleFieldOptions.ts';
    import Collapse from '@/components/transitions/Collapse.vue';
    import ToggleSwitch from '@/components/inputs/ToggleSwitch.vue';
    import FieldHintTooltip from '@/components/forms/inputs/FieldHintTooltip.vue';
    import { useI18n } from 'vue-i18n';

    defineComponent({
        name: 'ToggleSwitchFieldComponent',
    });

    const props = defineProps<{
        options: ToggleFieldOptions;
        error?: string;
        modelValue: boolean;
    }>();

    const { t } = useI18n();
    const emit = defineEmits<{
        (e: 'update:modelValue', v: boolean): void;
        (e: 'blur'): void;
    }>();

    const value = computed({
        get: () => props.modelValue,
        set: (v: boolean) => emit('update:modelValue', v),
    });
</script>
