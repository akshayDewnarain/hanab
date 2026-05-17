import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useSettingHelper } from '@/composables/useSettingHelper';
import { useToastService } from '@/composables/useToastService';
import http from '@/utils/http';
import Setting from '@/models/general/Setting';

export interface Tab {
    name: string | null;
    label: string;
    settings: Setting[];
}

export interface Panel {
    domain: string;
    col: number;
    tabs: Tab[];
}

export interface SettingsFormOptions {
    /** Preferred section tab order per domain (API section names, usually uppercase). Others sort alphabetically after. */
    sectionOrderByDomain?: Record<string, string[]>;
}

export function useSettingsForm(domains: string[], panelCol: number = 12, options: SettingsFormOptions = {}) {
    const { t } = useI18n();
    const { fetch, getTypedValue, setTypedValue, settings } = useSettingHelper();
    const toastService = useToastService();

    // Loading state
    const saving = ref(false);

    /** Active tab index per panel */
    const activeTabs = reactive<Record<number, number>>({});

    /** Local typed values mapped by setting key */
    const typedValues = reactive<Record<string, unknown>>({});

    /** Original values to track changes */
    const originalValues = reactive<Record<string, unknown>>({});

    /** Track which settings have been changed */
    const changedSettings = reactive<Set<string>>(new Set());

    /** Panels (domains) with tabs (sections) */
    const panels = ref<Panel[]>([]);

    /* --------------------- UI helpers (reused from useFormData) --------------------- */

    function setActiveTab(panelIndex: number, tabIndex: number) {
        activeTabs[panelIndex] = tabIndex;
    }

    function isActive(panelIndex: number, tabIndex: number) {
        return (activeTabs[panelIndex] ?? 0) === tabIndex;
    }

    const GRID_COLS = 12;

    function normalizeCol(col?: number) {
        const allowed = [1, 2, 3, 4, 6, 8, 9, 10, 12];
        if (!col || !allowed.includes(col)) return 12;
        return col;
    }

    function colClass(col?: number) {
        return `col-span-${normalizeCol(col)}`;
    }

    const panelRows = computed<number[]>(() => {
        const p = panels.value ?? [];
        const rows: number[] = [];
        let row = 1;
        let width = 0;

        for (let i = 0; i < p.length; i++) {
            const col = Math.min(GRID_COLS, p[i]?.col ?? GRID_COLS);
            if (width > 0 && width + col > GRID_COLS) {
                row += 1;
                width = 0;
            }
            rows[i] = row;
            width += col;
            if (width === GRID_COLS) {
                width = 0;
                row += 1;
            }
        }
        return rows;
    });

    const totalPanelRows = computed(() => {
        const r = panelRows.value;
        return r.length ? Math.max(...r) : 0;
    });

    function zForPanel(i: number) {
        const row = panelRows.value[i] ?? 1;
        return totalPanelRows.value - row + 1;
    }

    function initActiveTabs() {
        panels.value.forEach((_p, i) => {
            if (activeTabs[i] == null) activeTabs[i] = 0;
        });
    }

    /* --------------------- Settings-specific methods --------------------- */

    function sectionLabelKey(domain: string, section: string | null): string {
        if (!section) {
            return `${domain.toUpperCase()}_SECTION_DEFAULT`;
        }
        return `${domain.toUpperCase()}_SECTION_${section.toUpperCase()}`;
    }

    async function loadPanels() {
        panels.value = [];

        for (const domain of domains) {
            const list = await fetch(domain);

            list.forEach((s: Setting) => {
                const typedValue = getTypedValue(s);
                typedValues[s.key] = typedValue;
                originalValues[s.key] = typedValue;
            });

            // Group settings by section
            const groups = new Map<string | null, Setting[]>();
            list.forEach((s) => {
                const k = s.section ?? null;
                if (!groups.has(k)) groups.set(k, []);
                groups.get(k)!.push(s);
            });

            // Build tabs from sections (null/default first; then explicit order per domain if provided)
            const rawNames = Array.from(groups.keys());
            const preferred = options.sectionOrderByDomain?.[domain];
            let names: (string | null)[];
            if (preferred?.length) {
                const nullSections = rawNames.filter((n): n is null => n === null);
                const nonNull = rawNames.filter((n): n is string => n !== null);
                const rank = new Map(preferred.map((s, i) => [s, i]));
                nonNull.sort((a, b) => {
                    const ra = rank.has(a) ? rank.get(a)! : 1000;
                    const rb = rank.has(b) ? rank.get(b)! : 1000;
                    if (ra !== rb) {
                        return ra - rb;
                    }
                    return a.localeCompare(b);
                });
                names = [...nullSections, ...nonNull];
            } else {
                names = rawNames.sort((a, b) => {
                    if (a === null && b !== null) return -1;
                    if (a !== null && b === null) return 1;
                    return String(a).localeCompare(String(b));
                });
            }

            const tabs: Tab[] = names.map((name) => ({
                name,
                label: sectionLabelKey(domain, name),
                settings: groups.get(name) ?? [],
            }));

            panels.value.push({
                domain,
                col: panelCol,
                tabs,
            });
        }

        initActiveTabs();
    }

    function onUpdateTypedValue(setting: Setting, newVal: unknown) {
        typedValues[setting.key] = newVal;
        setTypedValue(setting, newVal);

        // Track changes
        const originalValue = originalValues[setting.key];
        const hasChanged = JSON.stringify(originalValue) !== JSON.stringify(newVal);

        if (hasChanged) {
            changedSettings.add(setting.key);
        } else {
            changedSettings.delete(setting.key);
        }
    }

    /* --------------------- Change tracking --------------------- */

    const hasChanges = computed(() => changedSettings.size > 0);
    const changedSettingsList = computed(() => Array.from(changedSettings));

    const getChangedSettingsData = computed(() => {
        // Group changed settings by domain and section
        const updatesMap = new Map<string, Map<string | null, Record<string, unknown>>>();

        changedSettingsList.value.forEach((key) => {
            // Find which panel (domain) and tab (section) this setting belongs to
            for (const panel of panels.value) {
                for (const tab of panel.tabs) {
                    const setting = tab.settings.find((s) => s.key === key);
                    if (setting) {
                        const domainKey = panel.domain;
                        const sectionKey = tab.name;

                        if (!updatesMap.has(domainKey)) {
                            updatesMap.set(domainKey, new Map());
                        }

                        const domainMap = updatesMap.get(domainKey)!;
                        if (!domainMap.has(sectionKey)) {
                            domainMap.set(sectionKey, {});
                        }

                        const changes = domainMap.get(sectionKey)!;
                        changes[key] = typedValues[key];
                        break;
                    }
                }
            }
        });

        // Convert to array format
        const updates: Array<{ domain: string; section: string | null; changes: Record<string, unknown> }> = [];

        updatesMap.forEach((domainMap, domain) => {
            domainMap.forEach((changes, section) => {
                if (Object.keys(changes).length > 0) {
                    updates.push({
                        domain,
                        section,
                        changes,
                    });
                }
            });
        });

        return updates;
    });

    /* --------------------- Save functionality --------------------- */

    async function saveSettings() {
        if (!hasChanges.value) {
            console.log('No changes to save');
            return;
        }

        saving.value = true;

        try {
            // Get location ID from the first setting
            const firstSetting = settings.value[0];
            if (!firstSetting?.location_id) {
                throw new Error('No location found for settings');
            }

            await http.put(`/locations/${firstSetting.location_id}/settings`, {
                updates: getChangedSettingsData.value,
            });

            changedSettingsList.value.forEach((key) => {
                originalValues[key] = typedValues[key];
            });

            // Clear changed settings
            changedSettings.clear();

            // Show success toast
            toastService.success(t('GENERAL_SETTINGS_SAVED_SUCCESS'));
        } catch (error: unknown) {
            console.error('Error saving settings:', error);
            const errorMessage = (error as Error).message || 'Failed to save settings';
            toastService.error(errorMessage);
        } finally {
            saving.value = false;
        }
    }

    function resetChanges() {
        // Reset all changed values to original
        changedSettingsList.value.forEach((key) => {
            typedValues[key] = originalValues[key];
        });
        changedSettings.clear();
    }

    return {
        // State
        panels,
        activeTabs,
        typedValues,
        saving,
        hasChanges,
        changedSettingsList,
        panelRows,
        totalPanelRows,

        // Methods
        loadPanels,
        setActiveTab,
        isActive,
        colClass,
        zForPanel,
        onUpdateTypedValue,
        saveSettings,
        resetChanges,
    };
}
