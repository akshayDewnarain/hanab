<template>
    <div class="space-y-3">
        <DishCategoryAccordion
            v-for="cat in sortedCategories"
            :key="cat.id"
            :attached-ids="attachedIds"
            :category="cat"
            :defaultOpen="true"
            :dishes="dishesByCategory[cat.id] ?? []"
            @update:selected-ids="onSelectionChange"
        />
    </div>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref, watch } from 'vue';
    import DishCategoryAccordion from '@/components/forms/custom/DishCategoryAccordion.vue';
    import Dish from '@/models/general/Dish';
    import DishCategory from '@/models/general/DishCategory';

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

    const dishes = ref<Dish[]>([]);
    const categories = ref<DishCategory[]>([]);

    const dishesByCategory = computed<Record<number, Dish[]>>(() => {
        const ids = new Set(attachedIds.value ?? []);

        const map: Record<number, Dish[]> = {};

        for (const dish of dishes.value) {
            if (!ids.has(dish.id)) continue;
            const cid = dish.category?.id ?? dish.category_id;
            if (cid == null) continue;
            (map[cid] ||= []).push(dish as Dish);
        }
        for (const cid of Object.keys(map)) {
            map[+cid].sort((a, b) => String(a.name).localeCompare(String(b.name)));
        }
        return map;
    });

    const sortedCategories = computed(() => [...categories.value].sort((a, b) => a.name.localeCompare(b.name)));

    function onSelectionChange(ids: number[]): void {
        attachedIds.value = ids;
        emit('update:modelValue', ids);
    }

    onMounted(async () => {
        const [dRes, cRes] = await Promise.all([
            new Dish().index({ ...{ include: ['category'] } }),
            new DishCategory().index(),
        ]);
        dishes.value = dRes.data.data;
        categories.value = cRes.data.data;
    });
</script>
