<template>
    <div class="space-y-3">
        <DrinkAccordion
            :attached-ids="attachedIds"
            :default-open="true"
            :drinks="filteredDrinks"
            @update:selected-ids="onSelectionChange"
        />
    </div>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref, watch } from 'vue';
    import DrinkAccordion from '@/components/forms/custom/DrinkAccordion.vue';
    import Drink from '@/models/general/Drink';

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

    const drinks = ref<Drink[]>([]);

    const filteredDrinks = computed((): Drink[] => {
        const ids = new Set(attachedIds.value ?? []);
        const rows = drinks.value as unknown as Drink[];
        return [...rows.filter((drink) => ids.has(drink.id))].sort((a, b) =>
            String(a.name || '').localeCompare(String(b.name || '')),
        );
    });

    function onSelectionChange(ids: number[]): void {
        attachedIds.value = ids;
        emit('update:modelValue', ids);
    }

    onMounted(async () => {
        const res = await new Drink().index({ per_page: 500 });
        drinks.value = (res.data.data ?? []) as unknown as Drink[];
    });
</script>
