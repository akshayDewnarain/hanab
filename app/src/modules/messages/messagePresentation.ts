import { parseMessageBody } from '@freelance/vue-ui';
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

export function messageSummary(
    message: { type: string; title?: string | null; body?: string | null; subject_name?: string | null; detail?: string | null; years?: number | null },
    translate: (key: string, values?: Record<string, unknown>) => string,
): { title: string; text: string } {
    const name = message.subject_name ?? '';
    if (message.type === 'birthday') {
        return { title: translate('MESSAGE_BIRTHDAY_TITLE'), text: translate('MESSAGE_BIRTHDAY_BODY', { name }) };
    }
    if (message.type === 'anniversary') {
        return { title: translate('MESSAGE_ANNIVERSARY_TITLE'), text: translate('MESSAGE_ANNIVERSARY_BODY', { name, years: message.years ?? 0 }) };
    }
    if (message.type === 'introduction') {
        return {
            title: translate('MESSAGE_INTRODUCTION_TITLE'),
            text: message.detail
                ? translate('MESSAGE_INTRODUCTION_BODY', { name, role: message.detail })
                : translate('MESSAGE_INTRODUCTION_BODY_PLAIN', { name }),
        };
    }
    if (message.type === 'certificate') {
        return { title: translate('MESSAGE_CERTIFICATE_TITLE'), text: translate('MESSAGE_CERTIFICATE_BODY', { name, certificate: message.detail ?? '' }) };
    }
    const text = parseMessageBody(message.body).text.replace(/\s+/g, ' ').trim();
    return { title: message.title?.trim() || translate('MESSAGE_CARD_COMPANY'), text };
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
