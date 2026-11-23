import type { FeedMessage } from '@/composables/useMessages';

export function messageIcon(type: string): string {
    if (type === 'introduction') return 'material-symbols:person-add-outline';
    if (type === 'birthday') return 'material-symbols:cake-outline';
    if (type === 'anniversary') return 'material-symbols:celebration-outline';
    if (type === 'certificate') return 'material-symbols:workspace-premium-outline';
    return 'material-symbols:campaign-outline';
}

export function messageKickerKey(type: string): string {
    if (type === 'introduction') return 'MESSAGE_INTRODUCTION_TITLE';
    if (type === 'birthday') return 'MESSAGE_BIRTHDAY_TITLE';
    if (type === 'anniversary') return 'MESSAGE_ANNIVERSARY_TITLE';
    if (type === 'certificate') return 'MESSAGE_CERTIFICATE_TITLE';
    return 'MESSAGE_CARD_COMPANY';
}

export function messageSource(message: FeedMessage, translate: (key: string, values?: Record<string, unknown>) => string): string {
    if (message.type === 'introduction') return translate('MESSAGE_SOURCE_INTRODUCTION');
    if (message.type === 'contract') {
        return message.authorName
            ? translate('MESSAGE_SOURCE_AUTHOR', { name: message.authorName })
            : translate('MESSAGE_SOURCE_EDITOR');
    }
    return translate('MESSAGE_SOURCE_MILESTONE');
}

export function formatMessageDate(value: string | null, locale: string): string {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat(locale === 'nl' ? 'nl' : 'en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
}
