<template>
    <div class="flex items-center justify-start">
        <span
            v-if="displayText"
            class="inline-flex max-w-full items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
            :style="chipStyle"
        >
            <span class="truncate">{{ displayText }}</span>
        </span>
        <span v-else class="text-sm text-slate-400">—</span>
    </div>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    const props = defineProps<{
        item?: Record<string, unknown>;
    }>();

    const row = computed(() => props.item);

    const displayText = computed(() => {
        const item = row.value;
        if (!item) {
            return '';
        }
        const code = typeof item.code === 'string' ? item.code.trim() : '';
        const name = typeof item.name === 'string' ? item.name.trim() : '';
        return code || name || '';
    });

    const chipStyle = computed(() => ({
        backgroundColor:
            (typeof row.value?.label_background_color === 'string' && row.value.label_background_color) ||
            '#f1f5f9',
        color:
            (typeof row.value?.label_text_color === 'string' && row.value.label_text_color) || '#334155',
        borderColor:
            (typeof row.value?.label_border_color === 'string' && row.value.label_border_color) ||
            '#cbd5e1',
    }));
</script>
