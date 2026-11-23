<template>
    <div class="flex flex-col gap-1.5">
        <label class="text-sm font-semibold text-list-text" :for="id">{{ label }}</label>
        <input
            :id="id"
            class="h-11 w-full rounded-lg border border-list-border bg-list-surface px-3 text-sm text-list-text outline-none transition-[border-color,box-shadow] duration-200 ease-out placeholder:text-list-text-muted focus:border-primary focus:ring-2 focus:ring-primary/25 motion-reduce:transition-none disabled:cursor-not-allowed disabled:bg-list-row-hover disabled:text-list-text"
            :autocomplete="autocomplete"
            :disabled="disabled"
            :name="name"
            :placeholder="placeholder"
            :required="required"
            :type="type"
            :value="modelValue"
            @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
    </div>
</template>

<script lang="ts" setup>
    withDefaults(
        defineProps<{
            id: string;
            label: string;
            modelValue: string;
            autocomplete?: string;
            disabled?: boolean;
            name?: string;
            placeholder?: string;
            required?: boolean;
            type?: string;
        }>(),
        { autocomplete: 'off', disabled: false, name: undefined, placeholder: '', required: false, type: 'text' },
    );

    const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

    defineOptions({ name: 'AuthTextField' });
</script>
