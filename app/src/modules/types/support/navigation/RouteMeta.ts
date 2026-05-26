import 'vue-router';

/**
 * Vue Router meta fields used across Hanab admin and auth routes.
 *
 * @property requiresAuth - When true, navigation guard loads the user and redirects to login if missing.
 * @property requiresScope - Reserved for scope checks on nested admin routes.
 * @property titleKey - i18n key for the browser tab page segment (see {@link useDocumentTitle}).
 * @property title - Optional raw page title fallback when `titleKey` is not set.
 */
declare module 'vue-router' {
    interface RouteMeta {
        requiresAuth?: boolean;
        requiresScope?: boolean;
        titleKey?: string;
        title?: string;
    }
}

export {};
