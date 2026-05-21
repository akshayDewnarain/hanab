export const APP_LOCALE_STORAGE_KEY = 'app_locale';

export type AppLocale = 'nl' | 'en';

const DEFAULT_LOCALE: AppLocale = 'nl';

export function readStoredLocale(): AppLocale {
    try {
        const item = localStorage.getItem(APP_LOCALE_STORAGE_KEY);
        if (!item) {
            return DEFAULT_LOCALE;
        }
        const parsed: unknown = JSON.parse(item);
        return parsed === 'en' ? 'en' : 'nl';
    } catch {
        return DEFAULT_LOCALE;
    }
}

export function useLocalStorage() {
    function setLocalStorageItem(key: string, value: unknown) {
        localStorage.setItem(key, JSON.stringify(value));
    }

    function getLocalStorageItem(key: string) {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : null;
    }

    return {
        setLocalStorageItem,
        getLocalStorageItem,
    };
}
