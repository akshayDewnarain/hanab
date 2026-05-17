<template>
    <div class="space-y-3">
        <ArrangementAccordion
            :attached-ids="attachedIds"
            :default-open="true"
            :arrangements="filteredArrangements"
            @update:selected-ids="onSelectionChange"
        />
    </div>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref, watch } from 'vue';
    import ArrangementAccordion from '@/components/forms/custom/ArrangementAccordion.vue';
    import Arrangement from '@/models/general/Arrangement';

    const props = defineProps<{ modelValue: number[] }>();
    const emit = defineEmits<{ (e: 'update:modelValue', v: number[]): void }>();

    const attachedIds = ref<number[]>([]);
    watch(
        () => props.modelValue,
        (v) => {
            if (v) attachedIds.value = [...v];
        },
        { immediate: true },
    );

    const arrangements = ref<Arrangement[]>([]);

    const filteredArrangements = computed((): Arrangement[] => {
        const ids = new Set(attachedIds.value ?? []);
        const rows = arrangements.value as unknown as Arrangement[];
        return [...rows.filter((arr) => ids.has(arr.id))].sort((a, b) =>
            String(a.name || '').localeCompare(String(b.name || '')),
        );
    });

    function onSelectionChange(ids: number[]): void {
        attachedIds.value = ids;
        emit('update:modelValue', ids);
    }

    onMounted(async () => {
        const res = await new Arrangement().index({ per_page: 500 });
        arrangements.value = (res.data.data ?? []) as unknown as Arrangement[];
    });
</script>
