<template>
    <div class="flex h-full min-h-0 flex-col gap-5 p-4 sm:p-5">
        <InformationPanel :title="t('TABLE_CONFIGURATION_INTRO_TITLE')" class="shrink-0">
            <p>{{ t('TABLE_CONFIGURATION_INTRO_BODY') }}</p>
        </InformationPanel>

        <div class="flex min-h-0 flex-1 flex-col gap-2">
            <div class="flex items-center justify-between gap-3 px-0.5">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    {{ t('GENERAL_COLUMN') }}
                </span>
                <span class="tabular-nums text-xs text-slate-500">
                    {{ t('TABLE_CONFIGURATION_VISIBLE_SUMMARY', { visible: visibleCount, total: columns.length }) }}
                </span>
            </div>

            <div
                class="min-h-0 flex-1 overflow-y-auto rounded-sm border border-slate-200/90 bg-white p-2 shadow-inner"
            >
                <div ref="sortableRootRef" class="flex flex-col gap-2" role="list">
                    <div
                        v-for="element in columns"
                        :key="element.name"
                        role="listitem"
                        :class="[
                            'flex items-stretch gap-2 rounded-sm border px-3 py-2 transition-[box-shadow,border-color,opacity,background]',
                            element.visible
                                ? 'border-slate-200/90 bg-white shadow-sm hover:border-slate-300/90'
                                : 'border-dashed border-slate-200/80 bg-slate-50/80 opacity-[0.85]',
                        ]"
                    >
                        <button
                            type="button"
                            tabindex="-1"
                            :disabled="!element.visible"
                            :class="[
                                'flex w-9 shrink-0 items-center justify-center rounded-sm transition-colors touch-none select-none',
                                element.visible
                                    ? 'drag-handle cursor-grab text-slate-400 hover:bg-slate-100 hover:text-slate-600 active:cursor-grabbing'
                                    : 'cursor-not-allowed text-slate-300',
                            ]"
                            :aria-hidden="true"
                        >
                            <Icon icon="material-symbols:menu" class="size-5" />
                        </button>

                        <div class="min-w-0 flex-1 self-center py-0.5">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-sm font-medium text-slate-800">
                                    {{ t(element.label) }}
                                </span>
                                <span
                                    v-if="!element.visible"
                                    class="inline-flex shrink-0 items-center rounded-sm bg-slate-200/80 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 border-slate-400 border shadow-sm"
                                >
                                    {{ t('TABLE_CONFIGURATION_HIDDEN_LABEL') }}
                                </span>
                            </span>
                        </div>

                        <div class="pointer-events-none flex shrink-0 items-center gap-1 sm:gap-3">
                            <div
                                class="pointer-events-auto flex flex-col items-center gap-0.5 border-l border-slate-100 pl-3"
                            >
                                <span class="hidden text-[10px] font-medium uppercase tracking-wide text-slate-400 sm:block">
                                    {{ t('GENERAL_COLUMN_VISIBLE') }}
                                </span>
                                <ToggleSwitch v-model="element.visible" @update:model-value="handleOnChange(element)" />
                            </div>
                            <div
                                class="pointer-events-auto flex flex-col items-center gap-0.5 border-l border-slate-100 pl-3 sm:pl-4"
                            >
                                <span class="hidden text-[10px] font-medium uppercase tracking-wide text-slate-400 sm:block">
                                    {{ t('GENERAL_FILTER_VISIBLE') }}
                                </span>
                                <ToggleSwitch v-model="element.quickFilter" :disabled="!element.visible" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
    import Sortable from 'sortablejs';
    import { Icon } from '@iconify/vue';
    import InformationPanel from '@/components/feedback/InformationPanel.vue';
    import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';
    import { useI18n } from 'vue-i18n';
    import ToggleSwitch from '@/components/inputs/ToggleSwitch.vue';
    import { useModalComponent } from '@/composables/modals/useModalComponent';
    import { useToastService } from '@/composables/useToastService';
    import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';

    const props = defineProps<{
        props: Record<string, unknown>;
    }>();

    const STORAGE_KEY = `${props.props.entity}_table_configuration`;
    const columns = ref<TableColumnInterface[]>([]);
    const sortableRootRef = ref<HTMLElement | null>(null);
    let sortableInstance: Sortable | null = null;
    const { t } = useI18n();
    const toastService = useToastService();

    const visibleCount = computed(() => columns.value.filter((c) => c.visible).length);

    const { isValidForAccept, notifyAccepted, notifyDismissed } = useModalComponent({
        name: `${props.props.entity}_table_configuration`,
        columns: columns,
    });

    /**
     * @override
     * @see useModalComponent
     */
    function getData(): Record<string, unknown> {
        return {
            columns: columns.value,
        };
    }

    function initSortable(): void {
        sortableInstance?.destroy();
        sortableInstance = null;
        const el = sortableRootRef.value;
        if (!el) return;

        sortableInstance = Sortable.create(el, {
            animation: 210,
            handle: '.drag-handle',
            draggable: '[role="listitem"]',
            onEnd(evt) {
                const oldIndex = evt.oldIndex;
                const newIndex = evt.newIndex;
                if (oldIndex == null || newIndex == null || oldIndex === newIndex) return;
                const next = [...columns.value];
                const [moved] = next.splice(oldIndex, 1);
                next.splice(newIndex, 0, moved);
                columns.value = next;
                nextTick(() => initSortable());
            },
        });
    }

    function loadColumns() {
        const model = props.props.model;
        const instance = new (model as ModelConstructor<unknown>)();
        const saved = localStorage.getItem(STORAGE_KEY);
        const defaultCols = instance.columns();

        if (saved) {
            try {
                const parsed = JSON.parse(saved);

                if (Array.isArray(parsed)) {
                    columns.value = parsed.map((savedCol: unknown) => {
                        const defaultCol = defaultCols.find(
                            (col: TableColumnInterface) => col.name === (savedCol as TableColumnInterface).name,
                        );
                        return defaultCol ? Object.assign({ ...defaultCol }, savedCol) : savedCol;
                    }) as TableColumnInterface[];

                    const missingCols = defaultCols.filter(
                        (dc: TableColumnInterface) =>
                            !parsed.some((sc: unknown) => (sc as Record<string, unknown>).name === dc.name),
                    );
                    columns.value.push(...missingCols);
                } else {
                    columns.value = defaultCols;
                }
            } catch {
                toastService.error('TABLE_CONFIGURATION_LOAD_LOCAL_STORAGE_ERROR');
                columns.value = defaultCols;
            }
        } else {
            columns.value = defaultCols;
        }
    }

    function sortColumns(): void {
        const visible = columns.value.filter((col: { visible: boolean }) => col.visible);
        const hidden = columns.value.filter((col: { visible: boolean }) => !col.visible);
        columns.value = [...visible, ...hidden];
    }

    function handleOnChange(element: TableColumnInterface): void {
        if (!element.visible) {
            element.quickFilter = false;
        }

        setTimeout(() => {
            sortColumns();
            nextTick(() => initSortable());
        }, 200);
    }

    defineExpose({
        getData,
        isValidForAccept,
        notifyAccepted,
        notifyDismissed,
    });

    onMounted(() => {
        loadColumns();
        sortColumns();
        nextTick(() => initSortable());
    });

    onBeforeUnmount(() => {
        sortableInstance?.destroy();
        sortableInstance = null;
    });
</script>
