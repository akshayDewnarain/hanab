<template>
    <div class="mb-4">
        <label :for="id" class="block text-gray-700 text-sm font-bold mb-2">{{ label }}</label>
        <input
            :id="id"
            v-model="inputValue"
            :class="[
                'shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline',
                { 'border-red-500': error },
            ]"
            :placeholder="placeholder"
            :type="type"
        />
        <p v-if="error" class="text-red-500 text-xs italic">{{ error }}</p>
    </div>
</template>

<script lang="ts" setup>
    import { ref, watch } from 'vue';

    const props = defineProps({
        id: {
            type: String,
            required: true,
        },
        label: {
            type: String,
            required: true,
        },
        type: {
            type: String,
            default: 'text',
        },
        modelValue: {
            type: [String, Number],
            default: '',
        },
        placeholder: {
            type: String,
            default: '',
        },
        error: {
            type: String,
            default: '',
        },
    });

    const emit = defineEmits(['update:modelValue']);
    const inputValue = ref(props.modelValue);

    watch(inputValue, (newValue) => {
        emit('update:modelValue', newValue);
    });

    watch(
        () => props.modelValue,
        (newValue) => {
            inputValue.value = newValue;
        },
    );
</script>

<style scoped></style>
