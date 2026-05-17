<template>
    <form class="grid grid-cols-12 gap-4 w-full auto-rows-auto" @submit.prevent="onSubmit">
        <!-- PANELS -->
        <section
            v-for="(panel, panelIndex) in schema.panels ?? []"
            :key="`panel-${panelIndex}`"
            :class="[
                colClass(panel.col),
                panelFillsRow(panel) ? 'flex min-h-0 flex-col self-stretch' : 'self-start',
            ]"
            :style="{ zIndex: zForPanel(panelIndex) }"
        >
            <div :class="panelStackWrapperClass(panel)">
                <div
                    v-for="(slot, slotIndex) in panelSlots(panel, panelIndex)"
                    :key="`panel-${panelIndex}-${slot.keySuffix}`"
                    :class="panelCardClass(panel, slotIndex, panelSlots(panel, panelIndex).length)"
                >
                    <!-- Tab strip: any panel with ≥1 tab (labels always on tab buttons) -->
                    <div
                        v-if="tabCount(slot.leaf) > 0"
                        class="rounded-t py-2 px-2 border-b border-gray-200 bg-[var(--color-background)] shadow-md z-10 relative"
                    >
                        <nav class="flex gap-2">
                            <div
                                v-for="(tab, tabIndex) in slot.leaf.tabs"
                                :key="`tab-${panelIndex}-${slot.keySuffix}-${tabIndex}`"
                            >
                                <div class="relative">
                                    <button
                                        :class="[
                                            'px-3 py-1 text-sm font-bold rounded transition-colors duration-200 ease-in hover:bg-[var(--color-highlight-dark)] text-white cursor-pointer',
                                            isActive(slot.tabKey, tabIndex)
                                                ? 'bg-[var(--color-highlight-dark)] text-[var(--color-primary)]'
                                                : '',
                                        ]"
                                        type="button"
                                        @click="setActiveTab(slot.tabKey, tabIndex)"
                                    >
                                        {{ t(tab.label) }}
                                    </button>
                                </div>
                            </div>
                        </nav>
                    </div>

                    <!-- CURRENT TAB CONTENT (always from active tab; single-tab uses first tab) -->
                    <div
                        v-if="activeTabRows(slot.leaf, slot.tabKey).length > 0"
                        class="relative z-0"
                        :class="[
                            tabCount(slot.leaf) > 0 ? 'border-t border-gray-200' : 'rounded bg-white',
                            panelTabBodyClass(
                                panel,
                                slotIndex,
                                panelSlots(panel, panelIndex).length,
                            ),
                        ]"
                    >
                        <div
                            v-for="(row, rowIndex) in activeTabRows(slot.leaf, slot.tabKey)"
                            :key="`row-${panelIndex}-${slot.keySuffix}-${rowIndex}-${row.type}`"
                            :class="[
                                colClass(row.col),
                                rowContentRowClass(slot.leaf, slot.tabKey, rowIndex),
                            ]"
                        >
                            <!-- ROW: fields -->
                            <template v-if="row.type === 'fields'">
                                <div class="grid grid-cols-12 gap-4">
                                    <component
                                        :is="field.getComponent()"
                                        v-for="(field, fIdx) in row.fields ?? []"
                                        :key="`field-${panelIndex}-${slot.keySuffix}-${rowIndex}-${fIdx}-${field.options.name}`"
                                        :activityId="instance.id"
                                        :class="[fieldWidthClass(field.options.width), 'col-span-12']"
                                        :error="validator?.getError(field.options.name)"
                                        :modelValue="formData[field.options.name]"
                                        :options="{
                                            ...field.options,
                                            readonly: readonly || field.options.readonly,
                                            disabled: readonly || field.options.disabled,
                                        }"
                                        v-bind="getExtraPropsForField(field)"
                                        :form-data="formData"
                                        :set-field-value="setFieldValue"
                                        :get-field-value="getFieldValue"
                                        @blur="() => validator?.markTouched(field.options.name)"
                                        @update:modelValue="handleFieldModelUpdate(field.options.name, $event)"
                                    />
                                </div>
                            </template>

                            <!-- ROW: text -->
                            <template v-else-if="row.type === 'text'">
                                <p class="text-sm leading-6 whitespace-pre-line">{{ row.text }}</p>
                            </template>

                            <!-- ROW: icon -->
                            <template v-else-if="row.type === 'icon'">
                                <div class="flex items-center gap-2">
                                    <component :is="row.icon" />
                                    <span class="text-sm">{{ row.text }}</span>
                                </div>
                            </template>

                            <!-- ROW: custom -->
                            <template v-else-if="row.type === 'custom'">
                                <slot :formData="formData" :row="row" name="custom" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <slot
            :applyChanges="validator?.applyChanges"
            :diff="validator?.diff"
            :dirtyFields="validator?.dirtyFields"
            :isDirty="validator?.isDirty"
            :revertAll="validator?.revertAll"
            :submit="onSubmit"
        />
        <slot
            :applyChanges="validator?.applyChanges"
            :isDirty="validator?.isDirty"
            :revertAll="validator?.revertAll"
            :submit="onSubmit"
            name="actions"
        />
    </form>
</template>

<script lang="ts" setup>
    import { defineComponent, provide } from 'vue';
    import { useI18n } from 'vue-i18n';
    import type Model from '@/modules/models/Model';
    import type BaseInputField from '@/modules/models/forms/BaseInputField';
    import type { FormBuilderPanel, FormBuilderRow, FormBuilderTab } from '@/modules/types/FormBuilderTypes';
    import { useFormData } from '@/composables/forms/useFormData';
    import {
        formBuilderGetFieldErrorKey,
        formBuilderMarkFieldTouchedKey,
    } from '@/composables/forms/formBuilderFieldContext';

    defineComponent({ name: 'FormBuilder' });
    const { t } = useI18n();

    type PanelSlot = { leaf: FormBuilderPanel; tabKey: string; keySuffix: string };

    const props = withDefaults(
        defineProps<{
            instance: Model<unknown>;
            readonly?: boolean;
            syncToInstanceOnChange?: boolean;
        }>(),
        {
            readonly: false,
            syncToInstanceOnChange: true,
        },
    );

    const emit = defineEmits<{
        (e: 'submit', value: Record<string, unknown>): void;
        (e: 'invalid', errors: Record<string, string | null>): void;
    }>();

    const {
        schema,
        formData,
        activeTabs,
        validator,
        setActiveTab,
        colClass,
        fieldWidthClass,
        onFieldUpdate,
        validate,
        onSubmit: onSubmitData,
        isActive,
        zForPanel,
        tabStorageKey,
    } = useFormData(props.instance, {
        readonly: props.readonly,
        syncToInstanceOnChange: props.syncToInstanceOnChange,
    });

    function formFieldError(name: string): string | undefined {
        return validator.value?.getError(name) ?? undefined;
    }

    function formMarkFieldTouched(name: string): void {
        validator.value?.markTouched(name);
    }

    provide(formBuilderGetFieldErrorKey, formFieldError);
    provide(formBuilderMarkFieldTouchedKey, formMarkFieldTouched);

    async function onSubmit(): Promise<Record<string, unknown>> {
        const payload = await onSubmitData();
        emit('submit', payload);
        return payload;
    }

    function getExtraPropsForField(field: BaseInputField): Record<string, unknown> {
        return field.getExtraProps(props.instance);
    }

    function setFieldValue(name: string, value: unknown): void {
        onFieldUpdate(name, value);
    }

    function handleFieldModelUpdate(name: string, value: unknown): void {
        onFieldUpdate(name, value);
    }

    function getFieldValue(name: string): unknown {
        return formData[name];
    }

    function panelRenderSlots(panel: FormBuilderPanel, panelIndex: number): PanelSlot[] {
        if (panel.stackedPanels?.length) {
            return panel.stackedPanels.map((inner, j) => ({
                leaf: inner,
                tabKey: tabStorageKey(panelIndex, j),
                keySuffix: `s${j}`,
            }));
        }
        return [{ leaf: panel, tabKey: tabStorageKey(panelIndex), keySuffix: '0' }];
    }

    function panelSlots(panel: FormBuilderPanel, panelIndex: number): PanelSlot[] {
        return panelRenderSlots(panel, panelIndex);
    }

    function panelCardExpands(panel: FormBuilderPanel, stackIndex: number, stackCount: number): boolean {
        if (!panelFillsRow(panel)) {
            return false;
        }
        if (panel.stackedPanels?.length) {
            return stackIndex === stackCount - 1;
        }
        return panel.takeFullHeight === true;
    }

    function tabCount(panel: FormBuilderPanel): number {
        return panel.tabs?.length ?? 0;
    }

    /** Active tab: selected index for multi-tab; first tab when only one (unless alwaysShowTabs). */
    function activeTabForPanel(panel: FormBuilderPanel, tabKey: string): FormBuilderTab | null {
        const tabs = panel.tabs ?? [];
        if (tabs.length === 0) {
            return null;
        }
        const useStoredIndex = tabs.length > 1 || panel.alwaysShowTabs === true;
        const raw = useStoredIndex ? (activeTabs[tabKey] ?? 0) : 0;
        const idx = Math.min(Math.max(0, raw), tabs.length - 1);
        return tabs[idx] ?? null;
    }

    function activeTabRows(panel: FormBuilderPanel, tabKey: string): FormBuilderRow[] {
        return activeTabForPanel(panel, tabKey)?.rows ?? [];
    }

    /** Opt-in: `fillRowHeight: true` stretches this panel to the grid row height. */
    function panelFillsRow(panel: FormBuilderPanel): boolean {
        return panel.fillRowHeight === true || panel.takeFullHeight === true;
    }

    function panelStackWrapperClass(panel: FormBuilderPanel): string {
        if (!panel.stackedPanels?.length) {
            return '';
        }
        return panelFillsRow(panel) ? 'flex h-full flex-col gap-4 w-full' : 'flex flex-col gap-4 w-full';
    }

    function panelCardClass(panel: FormBuilderPanel, stackIndex: number, stackCount: number): string {
        const base = 'w-full rounded border border-gray-200 shadow-md isolate';
        if (panelFillsRow(panel) && panel.takeFullHeight === true && !panel.stackedPanels?.length) {
            return `${base} flex h-full min-h-0 flex-col`;
        }
        if (panelFillsRow(panel) && panel.stackedPanels?.length && stackIndex === stackCount - 1) {
            return `${base} flex min-h-0 flex-1 flex-col`;
        }
        return base;
    }

    function panelTabBodyClass(panel: FormBuilderPanel, stackIndex: number, stackCount: number): string {
        if (!panelCardExpands(panel, stackIndex, stackCount)) {
            return '';
        }
        return 'flex min-h-0 flex-1 flex-col';
    }

    function rowContentRowClass(panel: FormBuilderPanel, tabKey: string, rowIndex: number): string {
        const hasStrip = tabCount(panel) > 0;
        const rows = activeTabRows(panel, tabKey);
        const last = rows.length - 1;
        if (hasStrip) {
            return rowIndex === last ? 'bg-white rounded-b px-2 py-4' : 'bg-white px-2 py-4';
        }
        const sep = rowIndex > 0 ? ' border-t border-gray-100' : '';
        return `px-2 py-4${sep}`;
    }

    defineExpose({ validate, onSubmit });
</script>
