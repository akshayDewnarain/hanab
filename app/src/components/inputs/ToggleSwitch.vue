<template>
    <button
        type="button"
        role="switch"
        :aria-checked="modelValue"
        :class="[
            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 border shadow-sm',
            trackClass,
            disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : 'hover:cursor-pointer',
        ]"
        @click="!disabled && (modelValue = !modelValue)"
    >
        <span
            :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                modelValue ? 'translate-x-6' : 'translate-x-1',
            ]"
        />
    </button>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';

    const modelValue = defineModel<boolean>({ default: false });

    const props = withDefaults(
        defineProps<{
            disabled?: boolean;
            /** `status`: green track when on, red when off; `neutral`: theme defaults. */
            tone?: 'neutral' | 'status';
        }>(),
        { tone: 'neutral' },
    );

    const trackClass = computed(() => {
        if (props.tone === 'status') {
            return modelValue.value
                ? 'border-emerald-700 bg-emerald-600'
                : 'border-rose-600 bg-rose-500';
        }
        return [
            'border-gray-300',
            modelValue.value ? 'bg-[var(--color-background)]' : 'bg-gray-300',
        ];
    });
</script>
