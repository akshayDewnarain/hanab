/** localStorage key for demo theme overrides (development only). */
export const THEME_STORAGE_KEY = 'demo-theme-builder';

export {
    EDITABLE_THEME_TOKENS,
    THEME_TOKEN_DEFAULTS,
    THEME_TOKEN_GROUPS,
    THEME_TOKEN_META,
    isThemeToken,
    isValidThemeColor,
    mergeThemeConfig,
    tokensForGroup,
} from '@freelance/vue-ui';

export type { ThemeConfig, ThemeConfigState, ThemeToken, ThemeTokenGroupId, ThemeTokenMeta } from '@freelance/vue-ui';
