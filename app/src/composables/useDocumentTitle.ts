import { watch } from 'vue';
import { useI18n } from 'vue-i18n';
import type { RouteLocationNormalized, Router } from 'vue-router';

/** Application name shown as the first segment of `document.title`. */
export const APP_DOCUMENT_TITLE = 'hanab';

const TITLE_SEPARATOR = ' | ';

/**
 * Build the browser tab title: `hanab | Page name`.
 */
export function formatDocumentTitle(pageTitle: string): string {
    const trimmed = pageTitle.trim();
    if (!trimmed) {
        return APP_DOCUMENT_TITLE;
    }
    return `${APP_DOCUMENT_TITLE}${TITLE_SEPARATOR}${trimmed}`;
}

/**
 * Resolve and apply browser tab titles from route meta and i18n.
 *
 * Prefer `meta.titleKey` on routes; use {@link setPageTitle} for dynamic detail pages.
 */
export function useDocumentTitle() {
    const { t, te } = useI18n();

    function resolvePageTitle(meta: RouteLocationNormalized['meta']): string {
        if (typeof meta.titleKey === 'string' && te(meta.titleKey)) {
            return t(meta.titleKey);
        }
        if (typeof meta.title === 'string' && meta.title.trim()) {
            return meta.title.trim();
        }
        return '';
    }

    function applyFromRoute(to: RouteLocationNormalized): void {
        const recordWithTitle = [...to.matched].reverse().find((record) => record.meta.titleKey || record.meta.title);
        const pageTitle = recordWithTitle ? resolvePageTitle(recordWithTitle.meta) : '';
        document.title = formatDocumentTitle(pageTitle);
    }

    function setPageTitle(pageTitle: string): void {
        document.title = formatDocumentTitle(pageTitle);
    }

    return {
        applyFromRoute,
        setPageTitle,
        formatDocumentTitle,
    };
}

/**
 * Wire document titles to router navigation and locale changes.
 * Call once from the root component `setup` (e.g. `App.vue`) after plugins are installed.
 */
export function setupDocumentTitle(router: Router): void {
    const { applyFromRoute } = useDocumentTitle();

    router.afterEach((to) => {
        applyFromRoute(to);
    });

    void router.isReady().then(() => {
        applyFromRoute(router.currentRoute.value);
    });

    const { locale } = useI18n();
    watch(locale, () => {
        applyFromRoute(router.currentRoute.value);
    });
}
