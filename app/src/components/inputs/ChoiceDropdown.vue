<template>
    <UiSelect
        :model-value="modelValue"
        :multiple="multiple"
        :options="options"
        :placeholder="placeholder"
        :aria-label="ariaLabel"
        :position="position"
        :align="align"
        :full-width="fullWidth"
        :size="size"
        :searchable="searchable"
        :search-placeholder="searchPlaceholder"
        :empty-label="emptyLabel"
        @update:model-value="emit('update:modelValue', $event)"
    />
</template>

<script lang="ts" setup generic="T extends string | number | null">
    import { UiSelect } from '@freelance/vue-ui';

    export type ChoiceOption<Value extends string | number | null = string> = {
        value: Value;
        label: string;
        help?: string;
    };

    withDefaults(
        defineProps<{
            modelValue: T | readonly T[];
            options: ChoiceOption<T>[];
            multiple?: boolean;
            placeholder?: string;
            ariaLabel?: string;
            position?: 'top' | 'bottom';
            align?: 'left' | 'right';
            fullWidth?: boolean;
            size?: 'sm' | 'md';
            searchable?: boolean;
            searchPlaceholder?: string;
            emptyLabel?: string;
        }>(),
        {
            multiple: false,
            placeholder: '',
            ariaLabel: undefined,
            position: 'bottom',
            align: 'left',
            fullWidth: true,
            size: 'md',
            searchable: false,
            searchPlaceholder: '',
            emptyLabel: '',
        },
    );

    const emit = defineEmits<{
        'update:modelValue': [value: T | T[]];
    }>();
</script>
