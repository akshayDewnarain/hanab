import { defineStore } from 'pinia';
import { useThemeTransition } from '@/composables/useThemeTransition';
import {
    parseThemeImportJson,
    sanitizeThemeOverrides,
    serializeThemeExport,
    type ThemeImportResult,
} from '@/modules/support/theme/themeConfigIo';
import {
    applyAppearance,
    persistAppearance,
    readStoredAppearance,
    type ThemeAppearance,
} from '@/modules/support/theme/themeAppearance';
import {
    EDITABLE_THEME_TOKENS,
    isValidThemeColor,
    THEME_STORAGE_KEY,
    THEME_TOKEN_DEFAULTS,
    type ThemeConfig,
    type ThemeConfigState,
    type ThemeToken,
} from '@/modules/support/theme/themeTokens';

function normalizeComputedColor(value: string): string {
    const match = value.match(/^rgb\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)\s*\)$/i);
    if (!match) return value;
    const hex = [match[1], match[2], match[3]].map((part) => Number(part).toString(16).padStart(2, '0')).join('');
    return `#${hex}`;
}

/** Stylesheet colors captured once, so the builder starts from the live theme rather than the library catalog. */
let stylesheetBaseline: ThemeConfigState | null = null;

function readStylesheetBaseline(): ThemeConfigState {
    if (stylesheetBaseline) return stylesheetBaseline;

    const root = document.documentElement;
    const previous: [string, string][] = [];
    for (const token of EDITABLE_THEME_TOKENS) {
        const inline = root.style.getPropertyValue(token);
        if (!inline) continue;
        previous.push([token, inline]);
        root.style.removeProperty(token);
    }

    const styles = getComputedStyle(root);
    const values = { ...THEME_TOKEN_DEFAULTS };
    for (const token of EDITABLE_THEME_TOKENS) {
        const value = normalizeComputedColor(styles.getPropertyValue(token).trim());
        if (value) values[token] = value;
    }

    for (const [token, value] of previous) root.style.setProperty(token, value);
    stylesheetBaseline = values;
    return values;
}

function mergeWithBaseline(overrides: ThemeConfig): ThemeConfigState {
    return { ...readStylesheetBaseline(), ...overrides };
}

function readStoredOverrides(): ThemeConfig {
    try {
        const raw = localStorage.getItem(THEME_STORAGE_KEY);
        if (!raw) {
            return {};
        }
        return sanitizeThemeOverrides(JSON.parse(raw));
    } catch {
        return {};
    }
}

function applyValuesToDocument(values: ThemeConfigState): void {
    const root = document.documentElement;
    for (const token of EDITABLE_THEME_TOKENS) {
        root.style.setProperty(token, values[token]);
    }
}

function clearCustomPropertiesFromDocument(): void {
    const root = document.documentElement;
    for (const token of EDITABLE_THEME_TOKENS) {
        root.style.removeProperty(token);
    }
}

/** Latest selection, including one still waiting for the current wave to finish. */
let queuedAppearance: ThemeAppearance | null = null;
/** Lets the selector highlight finish sliding before the page wave starts. */
const SLIDER_LEAD_MS = 400;
let waveTimer = 0;

export const useThemeStore = defineStore('theme', {
    state: () => ({
        appearance: 'project' as ThemeAppearance,
        overrides: {} as ThemeConfig,
        values: { ...THEME_TOKEN_DEFAULTS } as ThemeConfigState,
    }),

    getters: {
        hasOverrides(state): boolean {
            return Object.keys(state.overrides).length > 0;
        },

        isTokenOverridden(state) {
            return (token: ThemeToken): boolean => Object.prototype.hasOwnProperty.call(state.overrides, token);
        },
    },

    actions: {
        bootFromStorage(): void {
            this.appearance = readStoredAppearance();
            applyAppearance(this.appearance);
            this.overrides = readStoredOverrides();
            this.values = mergeWithBaseline(this.overrides);
            this.syncOverrideStyles();
        },

        setAppearance(appearance: ThemeAppearance): void {
            if (appearance === this.appearance) return;
            this.appearance = appearance;
            queuedAppearance = appearance;
            persistAppearance(appearance);
            window.clearTimeout(waveTimer);
            waveTimer = window.setTimeout(() => {
                const selected = this.appearance;
                const { runThemeTransition } = useThemeTransition();
                runThemeTransition(() => {
                    applyAppearance(selected);
                    this.syncOverrideStyles();
                    if (queuedAppearance === selected) queuedAppearance = null;
                });
            }, SLIDER_LEAD_MS);
        },

        setTokenValue(token: ThemeToken, value: string): boolean {
            const trimmed = value.trim();
            if (!isValidThemeColor(trimmed)) {
                return false;
            }

            this.overrides[token] = trimmed;
            this.values = mergeWithBaseline(this.overrides);
            document.documentElement.style.setProperty(token, trimmed);
            this.persistChanges();
            return true;
        },

        applySavedValues(): void {
            applyValuesToDocument(this.values);
        },

        resetTokenValue(token: ThemeToken): void {
            if (!(token in this.overrides)) {
                return;
            }

            delete this.overrides[token];
            this.values = mergeWithBaseline(this.overrides);
            document.documentElement.style.removeProperty(token);
            this.persistChanges();
        },

        resetTheme(): void {
            this.overrides = {};
            this.values = { ...readStylesheetBaseline() };
            clearCustomPropertiesFromDocument();
            localStorage.removeItem(THEME_STORAGE_KEY);
        },

        exportConfigJson(): string {
            return serializeThemeExport(this.overrides, this.values);
        },

        applyImportedTheme(result: Extract<ThemeImportResult, { ok: true }>): void {
            this.overrides = { ...result.overrides };
            this.values = result.values ?? mergeWithBaseline(this.overrides);
            applyValuesToDocument(this.values);
            this.persistChanges();
            this.appearance = 'project';
            applyAppearance('project');
            persistAppearance('project');
        },

        importConfigJson(text: string): ThemeImportResult {
            const result = parseThemeImportJson(text);
            if (!result.ok) {
                return result;
            }

            if (result.values) {
                this.values = result.values;
                this.overrides = { ...result.overrides };
            } else {
                this.overrides = { ...result.overrides };
                this.values = mergeWithBaseline(this.overrides);
            }

            applyValuesToDocument(this.values);
            this.persistChanges();
            this.appearance = 'project';
            applyAppearance('project');
            persistAppearance('project');
            return result;
        },

        /** Color-builder overrides sit on the project theme. Named themes use the CSS configs. */
        syncOverrideStyles(): void {
            if (this.appearance === 'project' && Object.keys(this.overrides).length > 0) {
                applyValuesToDocument(this.values);
                return;
            }
            clearCustomPropertiesFromDocument();
        },

        persistChanges(): void {
            if (Object.keys(this.overrides).length === 0) {
                localStorage.removeItem(THEME_STORAGE_KEY);
                return;
            }
            localStorage.setItem(THEME_STORAGE_KEY, JSON.stringify(this.overrides));
        },
    },
});
