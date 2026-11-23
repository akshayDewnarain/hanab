<template>
    <div class="flex items-center justify-start">
        <ColorLabel
            v-if="displayText"
            :label="displayText"
            :description="fullName"
            :background-color="backgroundColor"
            :text-color="textColor"
            :border-color="borderColor"
        />
        <span v-else class="text-sm text-slate-400">—</span>
    </div>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { ColorLabel } from '@freelance/vue-ui';

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

    const fullName = computed(() => {
        const item = row.value;
        const name = typeof item?.name === 'string' ? item.name.trim() : '';
        if (!name || name === displayText.value) return '';
        return name;
    });

    const backgroundColor = computed(() =>
        typeof row.value?.label_background_color === 'string' ? row.value.label_background_color : null,
    );
    const textColor = computed(() =>
        typeof row.value?.label_text_color === 'string' ? row.value.label_text_color : null,
    );
    const borderColor = computed(() =>
        typeof row.value?.label_border_color === 'string' ? row.value.label_border_color : null,
    );
</script>
