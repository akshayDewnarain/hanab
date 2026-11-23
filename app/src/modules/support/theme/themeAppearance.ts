/** localStorage key for the active appearance. Stored as a raw string, not JSON. */
export const THEME_APPEARANCE_STORAGE_KEY = 'app-theme';

export const THEME_APPEARANCES = ['project', 'light', 'dark'] as const;

export type ThemeAppearance = (typeof THEME_APPEARANCES)[number];

export function isThemeAppearance(value: unknown): value is ThemeAppearance {
    return value === 'project' || value === 'light' || value === 'dark';
}

export function readStoredAppearance(): ThemeAppearance {
    try {
        const raw = localStorage.getItem(THEME_APPEARANCE_STORAGE_KEY);
        return isThemeAppearance(raw) ? raw : 'project';
    } catch {
        return 'project';
    }
}

/** Point the document at a theme config. CSS variables do the rest. */
export function applyAppearance(appearance: ThemeAppearance): void {
    const root = document.documentElement;
    root.dataset.theme = appearance;
    root.classList.toggle('dark', appearance === 'dark');
}

export function persistAppearance(appearance: ThemeAppearance): void {
    localStorage.setItem(THEME_APPEARANCE_STORAGE_KEY, appearance);
}
