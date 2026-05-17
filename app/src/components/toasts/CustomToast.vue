<template>
    <div class="bg-white shadow-xl border border-gray-300 rounded-xl p-4 flex items-start space-x-4">
        <div class="text-blue-600 mt-1">
            <slot name="icon">🔔</slot>
        </div>
        <div class="flex-1">
            <h3 class="text-sm font-semibold">{{ toastProps.content }}</h3>
        </div>
        <button class="text-gray-400 hover:text-gray-600" @click="toastProps.closeToast">✖</button>
    </div>
</template>

<script lang="ts" setup>
    defineProps<{
        toastProps: {
            content: string;
            closeToast: () => void;
        };
    }>();
</script>
