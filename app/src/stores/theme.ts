import { defineStore } from 'pinia';
import {
    parseThemeImportJson,
    sanitizeThemeOverrides,
    serializeThemeExport,
    type ThemeImportResult,
} from '@/modules/support/theme/themeConfigIo';
import {
    EDITABLE_THEME_TOKENS,
    isValidThemeColor,
    mergeThemeConfig,
    THEME_STORAGE_KEY,
    THEME_TOKEN_DEFAULTS,
    type ThemeConfig,
    type ThemeConfigState,
    type ThemeToken,
} from '@/modules/support/theme/themeTokens';

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

export const useThemeStore = defineStore('theme', {
    state: () => ({
        overrides: {} as ThemeConfig,
        values: { ...THEME_TOKEN_DEFAULTS } as ThemeConfigState,
    }),

    getters: {
        hasOverrides(state): boolean {
            return Object.keys(state.overrides).length > 0;
        },

        isTokenOverridden(state) {
            return (token: ThemeToken): boolean =>
                Object.prototype.hasOwnProperty.call(state.overrides, token);
        },
    },

    actions: {
        bootFromStorage(): void {
            this.overrides = readStoredOverrides();
            this.values = mergeThemeConfig(this.overrides);
            if (Object.keys(this.overrides).length > 0) {
                applyValuesToDocument(this.values);
            }
        },

        setTokenValue(token: ThemeToken, value: string): boolean {
            const trimmed = value.trim();
            if (!isValidThemeColor(trimmed)) {
                return false;
            }

            this.overrides[token] = trimmed;
            this.values = mergeThemeConfig(this.overrides);
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
            this.values = mergeThemeConfig(this.overrides);
            document.documentElement.style.removeProperty(token);
            this.persistChanges();
        },

        resetTheme(): void {
            this.overrides = {};
            this.values = { ...THEME_TOKEN_DEFAULTS };
            clearCustomPropertiesFromDocument();
            localStorage.removeItem(THEME_STORAGE_KEY);
        },

        exportConfigJson(): string {
            return serializeThemeExport(this.overrides, this.values);
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
                this.values = mergeThemeConfig(this.overrides);
            }

            applyValuesToDocument(this.values);
            this.persistChanges();
            return result;
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
