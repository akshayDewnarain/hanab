<template>
    <SimpleGroupedRelationPicker
        v-if="useSimplePicker"
        v-bind="pickerProps"
        :model-value="attachedIds"
        @update:model-value="onSimplePickerUpdate"
    />

    <div v-else class="space-y-4">
        <div
            v-if="introKey || tipsKey"
            class="rounded-sm border border-slate-200/90 bg-gradient-to-br from-slate-50 to-white px-4 py-3 text-sm text-slate-600 shadow-sm"
        >
            <p v-if="introKey" class="leading-relaxed">{{ t(introKey) }}</p>
            <p v-if="tipsKey" class="mt-2 text-xs text-slate-500">{{ t(tipsKey) }}</p>
        </div>

        <CategoryGroupedRelationAccordion
            v-for="cat in categories"
            :key="cat.value"
            :add-button-key="addButtonKey"
            :attached-ids="attachedIds"
            :category="cat"
            :count-label-key="countLabelKey"
            :default-open="defaultOpenAccordions"
            :entity="entity"
            :includes="includes"
            :items="itemsByCategory[cat.value] ?? []"
            :list-columns="listColumns"
            :model="model"
            :picker-title-key="pickerTitleKey"
            @picker-accept="onPickerAccept"
            @remove-in-category="(remaining) => onRemoveInCategory(cat.value, remaining)"
        />
    </div>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import SimpleGroupedRelationPicker from '@/components/forms/custom/SimpleGroupedRelationPicker.vue';
    import CategoryGroupedRelationAccordion from '@/components/forms/custom/CategoryGroupedRelationAccordion.vue';
    import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
    import type { CategoryGroupedRelationManagerProps } from '@/modules/types/support/forms/CategoryGroupedRelationManagerProps.ts';
    import type { CategoryGroupedRelationRow } from '@/modules/types/support/forms/CategoryGroupedRelationRow.ts';

    const props = withDefaults(defineProps<CategoryGroupedRelationManagerProps>(), {
        modelValue: () => [],
        defaultOpenAccordions: true,
        useSimplePicker: false,
    });

    const emit = defineEmits<{ (e: 'update:modelValue', v: number[]): void }>();

    const { t } = useI18n();

    const pickerProps = computed(() => ({ ...props }));

    function onSimplePickerUpdate(ids: number[]): void {
        attachedIds.value = [...ids];
        emit('update:modelValue', attachedIds.value);
    }
    const attachedIds = ref<number[]>([]);
    const library = ref<CategoryGroupedRelationRow[]>([]);

    watch(
        () => props.modelValue,
        (v) => {
            attachedIds.value = v ? [...v] : [];
        },
        { immediate: true },
    );

    const listColumns = computed(() => {
        const cols = new props.model().columns();
        return cols.filter((col: TableColumn) => props.columnNames.includes(col.name));
    });

    const itemsByCategory = computed<Record<string, CategoryGroupedRelationRow[]>>(() => {
        const ids = new Set(attachedIds.value);
        const map: Record<string, CategoryGroupedRelationRow[]> = {};

        for (const row of library.value) {
            if (!ids.has(row.id)) continue;
            const key = row.category ?? '';
            (map[key] ||= []).push(row);
        }

        for (const key of Object.keys(map)) {
            map[key]?.sort((a, b) => String(a.name ?? '').localeCompare(String(b.name ?? '')));
        }

        return map;
    });

    function categoryOfId(id: number): string | undefined {
        return library.value.find((r) => r.id === id)?.category ?? undefined;
    }

    function onPickerAccept(ids: number[]): void {
        if (props.readonly || props.disabled) return;
        attachedIds.value = [...ids];
        emit('update:modelValue', attachedIds.value);
    }

    function onRemoveInCategory(categoryValue: string, remainingInCategory: number[]): void {
        if (props.readonly || props.disabled) return;

        const others = attachedIds.value.filter((id) => categoryOfId(id) !== categoryValue);
        attachedIds.value = [...others, ...remainingInCategory];
        emit('update:modelValue', attachedIds.value);
    }

    onMounted(async () => {
        if (props.useSimplePicker) {
            return;
        }
        const res = await new props.model().index({ per_page: 500 });
        library.value = (res.data.data ?? []) as CategoryGroupedRelationRow[];
    });
</script>
