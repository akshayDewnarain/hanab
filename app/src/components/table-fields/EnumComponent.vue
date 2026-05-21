<template>
    <div class="flex items-center justify-center">
        <span>{{ $t('GENERAL_' + value.toUpperCase()) }}</span>
    </div>
</template>

<script lang="ts" setup>

    defineProps<{ value: string }>();
</script>
