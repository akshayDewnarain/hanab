<template>
    <div class="space-y-3">
        <DishCategoryPivotAccordion
            v-for="cat in sortedCategories"
            :key="cat.id"
            :category="cat"
            :dish-model="dishModel"
            :dishes-in-category="dishesByCategory[cat.id] ?? []"
            :pivot-items="pivotItemsByCategory[cat.id] ?? []"
            :default-open="true"
            @update:items="(items) => onCategoryItemsUpdate(cat.id, items)"
        />
    </div>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref, watch } from 'vue';
    import DishCategoryPivotAccordion, {
        type PivotItem,
    } from '@/components/forms/custom/DishCategoryPivotAccordion.vue';
    import Dish from '@/models/general/Dish';
    import DishCategory from '@/models/general/DishCategory';

    const props = defineProps<{
        modelValue: PivotItem[];
        dishModel: new () => Dish;
    }>();

    const emit = defineEmits<{ (e: 'update:modelValue', v: PivotItem[]): void }>();

    const items = ref<PivotItem[]>([]);

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

    const dishes = ref<Dish[]>([]);
    const categories = ref<DishCategory[]>([]);

    const sortedCategories = computed(() => [...categories.value].sort((a, b) => a.name.localeCompare(b.name)));

    const dishesByCategory = computed<Record<number, Dish[]>>(() => {
        const map: Record<number, Dish[]> = {};
        const rows = dishes.value as unknown as Dish[];
        for (const dish of rows) {
            const cid = dish.category?.id ?? dish.category_id;
            if (cid == null) continue;
            (map[cid] ||= []).push(dish);
        }
        for (const cid of Object.keys(map)) {
            map[+cid].sort((a, b) => String(a.name).localeCompare(String(b.name)));
        }
        return map;
    });

    const categoryIdsByDishId = computed(() => {
        const map = new Map<number, number>();
        const rows = dishes.value as unknown as Dish[];
        for (const dish of rows) {
            const cid = dish.category?.id ?? dish.category_id;
            if (cid != null) map.set(dish.id, cid);
        }
        return map;
    });

    const pivotItemsByCategory = computed<Record<number, PivotItem[]>>(() => {
        const byCat: Record<number, PivotItem[]> = {};
        const catIds = categoryIdsByDishId.value;
        for (const item of items.value) {
            const cid = catIds.get(item.id);
            if (cid == null) continue;
            (byCat[cid] ||= []).push(item);
        }
        for (const cid of Object.keys(byCat)) {
            byCat[+cid].sort((a, b) => {
                const dishA = (dishes.value as unknown as Dish[]).find((d) => d.id === a.id);
                const dishB = (dishes.value as unknown as Dish[]).find((d) => d.id === b.id);
                return String(dishA?.name ?? '').localeCompare(String(dishB?.name ?? ''));
            });
        }
        return byCat;
    });

    function onCategoryItemsUpdate(categoryId: number, newItems: PivotItem[]): void {
        const catIds = categoryIdsByDishId.value;
        const otherItems = items.value.filter((item) => catIds.get(item.id) !== categoryId);
        items.value = [...otherItems, ...newItems];
        emit(
            'update:modelValue',
            items.value.map((i) => ({ ...i })),
        );
    }

    onMounted(async () => {
        const [dRes, cRes] = await Promise.all([
            new Dish().index({ include: ['category'] }),
            new DishCategory().index(),
        ]);
        dishes.value = (dRes.data.data ?? []) as unknown as Dish[];
        categories.value = (cRes.data.data ?? []) as unknown as DishCategory[];
    });
</script>
