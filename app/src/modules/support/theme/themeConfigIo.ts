import {
    EDITABLE_THEME_TOKENS,
    isThemeToken,
    isValidThemeColor,
    THEME_TOKEN_DEFAULTS,
    type ThemeConfig,
    type ThemeConfigState,
    type ThemeToken,
} from '@/modules/support/theme/themeTokens';

export const THEME_EXPORT_VERSION = 1;

export type ThemeExportFile = {
    version: number;
    exportedAt: string;
    overrides: ThemeConfig;
    values: ThemeConfigState;
};

export type ThemeImportResult =
    | { ok: true; overrides: ThemeConfig; importedCount: number; values?: ThemeConfigState }
    | { ok: false; error: string };

export function sanitizeThemeOverrides(input: unknown): ThemeConfig {
    if (!input || typeof input !== 'object') {
        return {};
    }

    const overrides: ThemeConfig = {};
    for (const [key, value] of Object.entries(input as Record<string, unknown>)) {
        if (!isThemeToken(key) || typeof value !== 'string') {
            continue;
        }
        const trimmed = value.trim();
        if (!isValidThemeColor(trimmed)) {
            continue;
        }
        overrides[key] = trimmed;
    }
    return overrides;
}

export function overridesFromValues(values: ThemeConfigState): ThemeConfig {
    const overrides: ThemeConfig = {};
    for (const token of EDITABLE_THEME_TOKENS) {
        if (values[token] !== THEME_TOKEN_DEFAULTS[token]) {
            overrides[token] = values[token];
        }
    }
    return overrides;
}

export function sanitizeThemeValues(input: unknown): ThemeConfigState | null {
    if (!input || typeof input !== 'object') {
        return null;
    }

    const values = { ...THEME_TOKEN_DEFAULTS };
    let validCount = 0;

    for (const token of EDITABLE_THEME_TOKENS) {
        const raw = (input as Record<string, unknown>)[token];
        if (typeof raw !== 'string') {
            continue;
        }
        const trimmed = raw.trim();
        if (!isValidThemeColor(trimmed)) {
            continue;
        }
        values[token] = trimmed;
        validCount += 1;
    }

    return validCount > 0 ? values : null;
}

export function parseThemeImportPayload(raw: unknown): ThemeImportResult {
    if (!raw || typeof raw !== 'object') {
        return { ok: false, error: 'Invalid JSON: expected an object.' };
    }

    const root = raw as Record<string, unknown>;

    if (root.overrides !== undefined) {
        const overrides = sanitizeThemeOverrides(root.overrides);
        if (Object.keys(overrides).length === 0) {
            return { ok: false, error: 'No valid theme tokens found under "overrides".' };
        }
        return { ok: true, overrides, importedCount: Object.keys(overrides).length };
    }

    if (root.values !== undefined) {
        const values = sanitizeThemeValues(root.values);
        if (!values) {
            return { ok: false, error: 'No valid theme tokens found under "values".' };
        }
        const overrides = overridesFromValues(values);
        return {
            ok: true,
            overrides,
            values,
            importedCount: Object.keys(overrides).length || EDITABLE_THEME_TOKENS.length,
        };
    }

    const directOverrides = sanitizeThemeOverrides(root);
    if (Object.keys(directOverrides).length > 0) {
        return { ok: true, overrides: directOverrides, importedCount: Object.keys(directOverrides).length };
    }

    return {
        ok: false,
        error: 'Unrecognized format. Use exported file or an object with "overrides" / "values".',
    };
}

export function createThemeExportFile(overrides: ThemeConfig, values: ThemeConfigState): ThemeExportFile {
    return {
        version: THEME_EXPORT_VERSION,
        exportedAt: new Date().toISOString(),
        overrides: { ...overrides },
        values: { ...values },
    };
}

export function serializeThemeExport(overrides: ThemeConfig, values: ThemeConfigState): string {
    return JSON.stringify(createThemeExportFile(overrides, values), null, 2);
}

export function parseThemeImportJson(text: string): ThemeImportResult {
    let parsed: unknown;
    try {
        parsed = JSON.parse(text);
    } catch {
        return { ok: false, error: 'Invalid JSON file or pasted content.' };
    }
    return parseThemeImportPayload(parsed);
}
