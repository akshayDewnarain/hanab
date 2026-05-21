import { createI18n } from 'vue-i18n';
import { readStoredLocale } from '@/composables/useLocalStorage';

import en from './locales/en.json';
import nl from './locales/nl.json';

const i18n = createI18n({
    locale: readStoredLocale(),
    fallbackLocale: 'nl',
    messages: {
        en,
        nl,
    },
});

export default i18n;
