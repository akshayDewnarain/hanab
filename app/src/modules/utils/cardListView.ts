import dayjs from 'dayjs';
import type { CardListConfig, CardListFieldConfig } from '@/modules/types/support/list-views/CardListConfig.ts';
import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';

const DEFAULT_EXCLUDE_COLUMNS = new Set(['image', 'created_at', 'updated_at', 'label_preview']);

export function safeGet(obj: unknown, path: string): unknown {
    return path.split('.').reduce((o: unknown, k: string) => {
        if (o && typeof o === 'object') {
            return (o as Record<string, unknown>)[k];
        }
        return undefined;
    }, obj);
}

export function resolveCardTitle(
    item: Record<string, unknown>,
    config: CardListConfig,
): string {
    if (config.titleField) {
        const v = safeGet(item, config.titleField);
        if (v != null && String(v).trim()) {
            return String(v).trim();
        }
    }

    if (config.titleFields?.length) {
        const sep = config.titleSeparator ?? ' ';
        const parts = config.titleFields
            .map((field) => safeGet(item, field))
            .filter((v) => v != null && String(v).trim() !== '')
            .map((v) => String(v).trim());
        if (parts.length) {
            return parts.join(sep);
        }
    }

    const fallback = item.name ?? item.title ?? item.email;
    return fallback != null ? String(fallback).trim() : '';
}

export function resolveCardSubtitle(item: Record<string, unknown>, config: CardListConfig): string {
    if (!config.subtitleField) {
        return '';
    }
    const v = safeGet(item, config.subtitleField);
    return v != null && String(v).trim() ? String(v).trim() : '';
}

export function resolveCardImageUrl(item: Record<string, unknown>, config: CardListConfig): string | null {
    if (!config.imageField) {
        return null;
    }
    const v = safeGet(item, config.imageField);
    if (!v || typeof v !== 'object') {
        return null;
    }
    const media = v as { thumb_url?: string; url?: string };
    return media.thumb_url || media.url || null;
}

export function resolveCardHeaderColor(item: Record<string, unknown>, config: CardListConfig): string {
    if (config.headerColorField) {
        const v = safeGet(item, config.headerColorField);
        if (typeof v === 'string' && v.trim()) {
            return v.trim();
        }
    }
    return config.headerColor ?? 'var(--color-primary)';
}

function inferFieldType(column: TableColumnInterface): CardListFieldConfig['type'] {
    const componentType = column.component?.type;
    if (componentType === TableColumnComponentEnum.DATE) {
        return 'date';
    }
    if (componentType === TableColumnComponentEnum.BOOLEAN) {
        return 'boolean';
    }
    if (componentType === TableColumnComponentEnum.BADGE) {
        return 'badges';
    }
    return 'text';
}

export function buildFieldsFromColumns(
    columns: TableColumnInterface[],
    config: CardListConfig,
): CardListFieldConfig[] {
    const exclude = new Set([...(config.excludeColumnNames ?? []), ...DEFAULT_EXCLUDE_COLUMNS]);

    return columns
        .filter((col) => col.visible !== false && !exclude.has(col.name))
        .map((col) => ({
            key: col.name,
            label: col.label,
            columnName: col.name,
            type: inferFieldType(col),
            hideEmpty: true,
        }));
}

export function resolveCardFields(
    config: CardListConfig,
    visibleColumns: TableColumnInterface[],
): CardListFieldConfig[] {
    if (config.fields?.length) {
        return config.fields;
    }
    if (config.deriveFromColumns === false) {
        return [];
    }
    return buildFieldsFromColumns(visibleColumns, config);
}

export type CardListBadge = {
    label: string;
    /** Inline chip colors; omit to use the default primary card badge styling. */
    chipStyle?: Record<string, string>;
};

const DEFAULT_LABEL_CHIP_BG = '#f1f5f9';
const DEFAULT_LABEL_CHIP_TEXT = '#334155';
const DEFAULT_LABEL_CHIP_BORDER = '#cbd5e1';

export function labelRecordChipStyle(record: Record<string, unknown>): Record<string, string> {
    return {
        backgroundColor:
            (typeof record.label_background_color === 'string' && record.label_background_color) ||
            DEFAULT_LABEL_CHIP_BG,
        color:
            (typeof record.label_text_color === 'string' && record.label_text_color) ||
            DEFAULT_LABEL_CHIP_TEXT,
        borderColor:
            (typeof record.label_border_color === 'string' && record.label_border_color) ||
            DEFAULT_LABEL_CHIP_BORDER,
    };
}

function badgeLabelFromRecord(record: Record<string, unknown>, displayKey: string): string {
    const keyValue = record[displayKey];
    if (keyValue != null && String(keyValue).trim() !== '') {
        return String(keyValue).trim();
    }
    if ('code' in record && record.code != null && String(record.code).trim() !== '') {
        return String(record.code).trim();
    }
    if ('name' in record && record.name != null && String(record.name).trim() !== '') {
        return String(record.name).trim();
    }
    return '';
}

export function normalizeToColoredBadges(value: unknown, displayKey = 'name'): CardListBadge[] {
    const toBadge = (record: Record<string, unknown>): CardListBadge | null => {
        const label = badgeLabelFromRecord(record, displayKey);
        if (!label) {
            return null;
        }
        const hasCustomColors =
            (typeof record.label_background_color === 'string' && record.label_background_color) ||
            (typeof record.label_text_color === 'string' && record.label_text_color) ||
            (typeof record.label_border_color === 'string' && record.label_border_color);

        return {
            label,
            ...(hasCustomColors ? { chipStyle: labelRecordChipStyle(record) } : {}),
        };
    };

    if (value == null || value === '') {
        return [];
    }
    if (Array.isArray(value)) {
        return value
            .map((item) => {
                if (item && typeof item === 'object') {
                    return toBadge(item as Record<string, unknown>);
                }
                const label = String(item).trim();
                return label ? { label } : null;
            })
            .filter((badge): badge is CardListBadge => badge != null);
    }
    if (typeof value === 'string' && value.includes(',')) {
        return value
            .split(',')
            .map((part) => part.trim())
            .filter(Boolean)
            .map((label) => ({ label }));
    }
    if (typeof value === 'object') {
        const badge = toBadge(value as Record<string, unknown>);
        return badge ? [badge] : [];
    }
    const label = String(value).trim();
    return label ? [{ label }] : [];
}

export function normalizeToBadgeValues(value: unknown, displayKey = 'name'): string[] {
    if (value == null || value === '') {
        return [];
    }
    if (Array.isArray(value)) {
        return value
            .map((v) => {
                if (v && typeof v === 'object') {
                    const record = v as Record<string, unknown>;
                    const keyValue = record[displayKey];
                    if (keyValue != null && String(keyValue).trim() !== '') {
                        return String(keyValue).trim();
                    }
                    if ('name' in record) {
                        return String(record.name ?? '');
                    }
                }
                return String(v);
            })
            .filter(Boolean);
    }
    if (typeof value === 'string' && value.includes(',')) {
        return value
            .split(',')
            .map((s) => s.trim())
            .filter(Boolean);
    }
    return [String(value)];
}

export function formatCardFieldValue(
    value: unknown,
    field: CardListFieldConfig,
    formatters: {
        t: (key: string) => string;
        convertToString: (value: unknown) => string;
    },
): string | null {
    if (field.format) {
        return field.format(value, {}) ?? null;
    }

    if (value == null || value === '') {
        return null;
    }

    switch (field.type) {
        case 'date':
            if (typeof value === 'string' && value) {
                return dayjs(value).format('DD MMM YYYY');
            }
            return null;
        case 'boolean':
            return value ? formatters.t('GENERAL_ACTIVE') : '—';
        case 'badges':
            return normalizeToBadgeValues(value).join(', ') || null;
        default:
            if (typeof value === 'object') {
                if (value && 'name' in value) {
                    return String((value as { name: unknown }).name ?? '');
                }
                return formatters.convertToString(value);
            }
            if (field.key === 'employment_type' && typeof value === 'string') {
                const key = `EMPLOYEE_EMPLOYMENT_${value.toUpperCase()}`;
                const translated = formatters.t(key);
                return translated !== key ? translated : value;
            }
            return formatters.convertToString(value);
    }
}

export function isEmptyFieldValue(value: unknown): boolean {
    if (value == null || value === '') {
        return true;
    }
    if (Array.isArray(value)) {
        return value.length === 0;
    }
    return false;
}
