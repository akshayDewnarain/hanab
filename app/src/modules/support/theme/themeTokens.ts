/** localStorage key for demo theme overrides (development only). */
export const THEME_STORAGE_KEY = 'demo-theme-builder';

/** CSS custom properties allowed to be edited at runtime. */
export const EDITABLE_THEME_TOKENS = [
    '--color-primary',
    '--color-primary-light',
    '--color-secondary',
    '--color-ternary',
    '--color-ternary-highlight-dark',
    '--color-accent',
    '--color-background',
    '--color-text',
    '--color-text-blocked',
    '--color-bg-blocked',
    '--color-placeholder',
    '--color-error',
    '--color-warning',
    '--color-success',
    '--color-warning-light',
    '--color-success-light',
    '--color-highlight-dark',
    '--color-highlight-light',
    '--color-sidebar-background',
    '--color-sidebar-icon',
    '--color-sidebar-item-text',
    '--color-sidebar-item-highlight-dark',
] as const;

export type ThemeToken = (typeof EDITABLE_THEME_TOKENS)[number];

/** Partial map of token → CSS value (only overridden keys are stored). */
export type ThemeConfig = Partial<Record<ThemeToken, string>>;

/** Full token map with a value for every editable token. */
export type ThemeConfigState = Record<ThemeToken, string>;

/** Defaults from `src/css/global.css` @theme block. */
export const THEME_TOKEN_DEFAULTS: ThemeConfigState = {
    '--color-primary': '#00416e',
    '--color-primary-light': 'rgba(0, 65, 110, 0.12)',
    '--color-secondary': '#e63223',
    '--color-ternary': 'green',
    '--color-ternary-highlight-dark': '#015b01',
    '--color-accent': '#e63223',
    '--color-background': '#00416e',
    '--color-text': '#1f2937',
    '--color-text-blocked': '#888888',
    '--color-bg-blocked': '#ececec',
    '--color-placeholder': '#7d7d7d',
    '--color-error': '#c90400',
    '--color-warning': '#da8f00',
    '--color-success': '#0b7500',
    '--color-warning-light': 'rgba(218, 143, 0, 0.2)',
    '--color-success-light': 'rgba(11, 117, 0, 0.15)',
    '--color-highlight-dark': '#002d4d',
    '--color-highlight-light': '#eaf3f8',
    '--color-sidebar-background': '#b8241b',
    '--color-sidebar-icon': '#ffffff',
    '--color-sidebar-item-text': '#ffffff',
    '--color-sidebar-item-highlight-dark': '#62120e',
};

export function isThemeToken(key: string): key is ThemeToken {
    return (EDITABLE_THEME_TOKENS as readonly string[]).includes(key);
}

export function isValidThemeColor(value: string): boolean {
    const trimmed = value.trim();
    if (!trimmed) {
        return false;
    }
    return CSS.supports('color', trimmed);
}

export function mergeThemeConfig(overrides: ThemeConfig): ThemeConfigState {
    return { ...THEME_TOKEN_DEFAULTS, ...overrides };
}

export type ThemeTokenGroupId = 'brand' | 'sidebar' | 'surface' | 'feedback' | 'highlights';

export type ThemeTokenMeta = {
    label: string;
    hint: string;
    icon: string;
    group: ThemeTokenGroupId;
};

export const THEME_TOKEN_GROUPS: { id: ThemeTokenGroupId; title: string; description: string; icon: string }[] = [
    {
        id: 'brand',
        title: 'Brand & actions',
        description: 'Core identity colors used on buttons, links, and key actions.',
        icon: 'material-symbols:palette-outline-rounded',
    },
    {
        id: 'sidebar',
        title: 'Sidebar',
        description: 'Navigation rail — background, icons, and menu labels.',
        icon: 'material-symbols:side-navigation-rounded',
    },
    {
        id: 'surface',
        title: 'Text & surfaces',
        description: 'Typography, placeholders, and muted UI states.',
        icon: 'material-symbols:text-fields-rounded',
    },
    {
        id: 'feedback',
        title: 'Status & alerts',
        description: 'Success, warning, and error tones across the app.',
        icon: 'material-symbols:feedback-outline-rounded',
    },
    {
        id: 'highlights',
        title: 'Highlights & depth',
        description: 'Hover states, soft backgrounds, and accent washes.',
        icon: 'material-symbols:contrast-rounded',
    },
];

export const THEME_TOKEN_META: Record<ThemeToken, ThemeTokenMeta> = {
    '--color-primary': {
        label: 'Primary',
        hint: 'Main brand color — buttons, links, and focus rings.',
        icon: 'material-symbols:circle-rounded',
        group: 'brand',
    },
    '--color-primary-light': {
        label: 'Primary light',
        hint: 'Soft tint behind primary badges and outline hovers.',
        icon: 'material-symbols:circle-outline',
        group: 'brand',
    },
    '--color-secondary': {
        label: 'Secondary',
        hint: 'Supporting accent for variety in marketing blocks.',
        icon: 'material-symbols:looks-two-rounded',
        group: 'brand',
    },
    '--color-ternary': {
        label: 'Ternary',
        hint: 'Third accent — use sparingly for charts or tags.',
        icon: 'material-symbols:looks-3-rounded',
        group: 'brand',
    },
    '--color-ternary-highlight-dark': {
        label: 'Ternary dark',
        hint: 'Darker variant for ternary hover or borders.',
        icon: 'material-symbols:dark-mode-outline-rounded',
        group: 'brand',
    },
    '--color-accent': {
        label: 'Accent',
        hint: 'Eye-catching highlight separate from primary.',
        icon: 'material-symbols:auto-awesome-outline-rounded',
        group: 'brand',
    },
    '--color-background': {
        label: 'Background',
        hint: 'Top bar and primary chrome outside the sidebar.',
        icon: 'material-symbols:wallpaper-rounded',
        group: 'brand',
    },
    '--color-sidebar-background': {
        label: 'Sidebar background',
        hint: 'Main fill behind the navigation rail.',
        icon: 'material-symbols:view-sidebar-rounded',
        group: 'sidebar',
    },
    '--color-sidebar-icon': {
        label: 'Sidebar icon',
        hint: 'Icon color for nav items, settings, and collapse control.',
        icon: 'material-symbols:apps-rounded',
        group: 'sidebar',
    },
    '--color-sidebar-item-text': {
        label: 'Sidebar menu text',
        hint: 'Labels beside icons when the sidebar is expanded.',
        icon: 'material-symbols:menu-rounded',
        group: 'sidebar',
    },
    '--color-sidebar-item-highlight-dark': {
        label: 'Sidebar item highlight',
        hint: 'Hover and active background on nav items, settings, and logo row.',
        icon: 'material-symbols:highlight-rounded',
        group: 'sidebar',
    },
    '--color-text': {
        label: 'Body text',
        hint: 'Default paragraph and label color.',
        icon: 'material-symbols:title-rounded',
        group: 'surface',
    },
    '--color-text-blocked': {
        label: 'Muted text',
        hint: 'Disabled or de-emphasized copy.',
        icon: 'material-symbols:block-rounded',
        group: 'surface',
    },
    '--color-bg-blocked': {
        label: 'Blocked background',
        hint: 'Fill for disabled inputs or rows.',
        icon: 'material-symbols:select-all-rounded',
        group: 'surface',
    },
    '--color-placeholder': {
        label: 'Placeholder',
        hint: 'Hint text inside empty fields.',
        icon: 'material-symbols:text-format-rounded',
        group: 'surface',
    },
    '--color-error': {
        label: 'Error',
        hint: 'Validation errors and destructive emphasis.',
        icon: 'material-symbols:error-outline-rounded',
        group: 'feedback',
    },
    '--color-warning': {
        label: 'Warning',
        hint: 'Caution states and pending actions.',
        icon: 'material-symbols:warning-outline-rounded',
        group: 'feedback',
    },
    '--color-success': {
        label: 'Success',
        hint: 'Confirmations and positive outcomes.',
        icon: 'material-symbols:check-circle-outline-rounded',
        group: 'feedback',
    },
    '--color-warning-light': {
        label: 'Warning light',
        hint: 'Soft warning backgrounds and banners.',
        icon: 'material-symbols:wb-sunny-outline-rounded',
        group: 'feedback',
    },
    '--color-success-light': {
        label: 'Success light',
        hint: 'Soft success backgrounds and banners.',
        icon: 'material-symbols:eco-outline-rounded',
        group: 'feedback',
    },
    '--color-highlight-dark': {
        label: 'Highlight dark',
        hint: 'Hover on primary surfaces, buttons, and chrome outside the sidebar.',
        icon: 'material-symbols:highlight-mouse-cursor-rounded',
        group: 'highlights',
    },
    '--color-highlight-light': {
        label: 'Highlight light',
        hint: 'Warm wash for cards and info panels.',
        icon: 'material-symbols:light-mode-outline-rounded',
        group: 'highlights',
    },
};

export function tokensForGroup(groupId: ThemeTokenGroupId): ThemeToken[] {
    return EDITABLE_THEME_TOKENS.filter((token) => THEME_TOKEN_META[token].group === groupId);
}
