<template>
    <div class="flex items-center justify-center">
        <span>{{ $t('GENERAL_' + value.toUpperCase()) }}</span>
    </div>
</template>

<script lang="ts" setup>
    import { defineProps } from 'vue';

    defineProps<{ value: string }>();
</script>
