<!--
  Employee-facing alias for {@link SimpleGroupedRelationPicker}.
  Use via `CategoryGroupedRelationManager` + `useSimplePicker: true`, or import directly.
-->
<template>
    <SimpleGroupedRelationPicker v-bind="$props" />
</template>

<script lang="ts" setup>
    import SimpleGroupedRelationPicker from '@/components/forms/custom/SimpleGroupedRelationPicker.vue';
    import type { CategoryGroupedRelationManagerProps } from '@/modules/types/support/forms/CategoryGroupedRelationManagerProps.ts';

    defineProps<CategoryGroupedRelationManagerProps>();
</script>
