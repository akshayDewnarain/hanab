<template>
    <div class="space-y-3">
        <ArrangementPivotAccordion
            :add-label="addLabel"
            :entity-key="entityKey"
            :entity-model="entityModel"
            :entities="arrangements"
            :label="label"
            :pivot-items="items"
            :title-key="titleKey"
            @update:items="onItemsUpdate"
        />
    </div>
</template>

<script lang="ts" setup>
    import { onMounted, ref, watch } from 'vue';
    import ArrangementPivotAccordion, { type PivotItem } from '@/components/forms/custom/ArrangementPivotAccordion.vue';
    import Arrangement from '@/models/general/Arrangement';

    const props = withDefaults(
        defineProps<{
            modelValue: PivotItem[];
            label: string;
            addLabel: string;
            titleKey: string;
            entityKey: string;
            entityModel: new () => Arrangement;
        }>(),
        {},
    );

    const emit = defineEmits<{ (e: 'update:modelValue', v: PivotItem[]): void }>();

    const items = ref<PivotItem[]>([]);
    const arrangements = ref<Arrangement[]>([]);

    watch(
        () => props.modelValue,
        (v) => {
            items.value = (v ?? []).map((i) => ({
                id: i.id,
                name: i.name,
                quantity: Number(i.quantity) || 1,
                unit_price: i.unit_price ?? 0,
                meta: i.meta ?? null,
            }));
        },
        { immediate: true, deep: true },
    );

    function onItemsUpdate(newItems: PivotItem[]): void {
        items.value = newItems.map((i) => ({ ...i }));
        emit('update:modelValue', items.value);
    }

    onMounted(async () => {
        const res = await new Arrangement().index({ per_page: 500 });
        arrangements.value = res.data.data ?? [];
    });
</script>
