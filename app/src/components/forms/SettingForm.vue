<template>
    <div class="grid grid-cols-12 gap-4 w-full items-start auto-rows-auto">
        <!-- PANELS -->
        <section
            v-for="(panel, panelIndex) in panels"
            :key="`panel-${panelIndex}`"
            :class="[colClass(panel.col)]"
            :style="{ zIndex: zForPanel(panelIndex) }"
        >
            <div class="rounded border shadow-md border-gray-200 isolate">
                <!-- TABS -->
                <div
                    class="rounded-t py-2 px-2 border-b border-gray-200 bg-[var(--color-background)] shadow-md z-10 relative"
                >
                    <nav class="flex gap-2">
                        <div v-for="(tab, tabIndex) in panel.tabs" :key="`tab-${panelIndex}-${tabIndex}`">
                            <div class="relative">
                                <button
                                    :class="[
                                        'px-3 py-1 text-sm font-bold rounded transition-colors duration-200 ease-in hover:bg-[var(--color-highlight-dark)] text-white cursor-pointer',
                                        isActive(panelIndex, tabIndex)
                                            ? 'bg-[var(--color-highlight-dark)] text-[var(--color-primary)]'
                                            : '',
                                    ]"
                                    type="button"
                                    @click="setActiveTab(panelIndex, tabIndex)"
                                >
                                    {{ t(tab.label) }}
                                </button>
                            </div>
                        </div>
                    </nav>
                </div>

                <!-- CURRENT TAB CONTENT -->
                <div v-if="panel.tabs?.length" class="relative z-0">
                    <div :class="[colClass(12)]" class="bg-white rounded-b px-2 py-4">
                        <div class="grid grid-cols-12 gap-4">
                            <SettingField
                                v-for="s in panel.tabs[activeTabs[panelIndex] ?? 0].settings"
                                :key="`setting-${panel.domain}-${s.id}-${s.key}`"
                                :setting="s as Setting"
                                :typedValue="typedValues[s.key]"
                                class="col-span-12 md:col-span-6"
                                @update:typedValue="onUpdateTypedValue(s as Setting, $event)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <slot name="actions" />
    </div>
</template>

<script lang="ts" setup>
    import { onMounted } from 'vue';
    import { useI18n } from 'vue-i18n';
    import SettingField from '@/components/forms/inputs/SettingField.vue';
    import { useSettingsForm } from '@/composables/forms/useSettingsForm';
    import Setting from '@/models/general/Setting';

    const { t } = useI18n();

    const props = withDefaults(
        defineProps<{
            domains: string[];
            titles?: Record<string, string>;
            panelCol?: number;
        }>(),
        {
            domains: () => ['general'],
            titles: () => ({}),
            panelCol: 12,
        },
    );

    const {
        panels,
        activeTabs,
        typedValues,
        saving,
        hasChanges,
        changedSettingsList,
        loadPanels,
        setActiveTab,
        isActive,
        colClass,
        zForPanel,
        onUpdateTypedValue,
        saveSettings,
        resetChanges,
    } = useSettingsForm(props.domains, props.panelCol, {
        // Puts System (e.g. public booking maintenance) last so it stays easy to find.
        sectionOrderByDomain: {
            general: ['CURRENCY', 'FORMAT', 'TAX', 'SYSTEM'],
        },
    });

    onMounted(() => {
        loadPanels();
    });

    defineExpose({
        reload: loadPanels,
        panels,
        activeTabs,
        typedValues,
        hasChanges,
        changedSettingsList,
        saving,
        saveSettings,
        resetChanges,
    });
</script>
