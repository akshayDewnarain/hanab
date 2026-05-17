import { isRef, type Ref } from 'vue';
import type { AnyRec } from '@/modules/types/support/form-builder/AnyRec.ts';
import type { PayLoadOptions } from '@/modules/types/support/form-builder/PayLoadOptions.ts';

/**
 * Convert a value to a string.
 * @param value
 */
export const convertToString = (value: unknown): string => {
    if (value === null || value === undefined) {
        return '';
    }
    if (typeof value === 'string') {
        return value;
    }
    if (typeof value === 'number') {
        return value.toString();
    }
    if (Array.isArray(value)) {
        return value.join(', ');
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return String(value);
};

/**
 * Convert a array to a string value with a delimiter
 * @param array
 * @param delimiter
 */
export const flattenArray = (array: unknown[], delimiter: string): string => {
    if (!Array.isArray(array)) return '';

    const flat = array.flat(Infinity);
    return flat.join(delimiter);
};

export function unwrap<T>(val: T | Ref<T>): T {
    return isRef(val) ? val.value : val;
}

function hasFileDeep(v: unknown): boolean {
    if (!v) return false;
    if (v instanceof File || v instanceof Blob) return true;
    if (Array.isArray(v)) return v.some(hasFileDeep);
    if (typeof v === 'object') return Object.values(v).some(hasFileDeep);
    return false;
}

function appendFormData(fd: FormData, value: unknown, key: string) {
    if (value === undefined) return;

    if (value === null) {
        fd.append(key, '');
        return;
    }

    if (value instanceof File || value instanceof Blob) {
        fd.append(key, value);
        return;
    }

    if (typeof value === 'boolean') {
        fd.append(key, value ? '1' : '0');
        return;
    }

    if (Array.isArray(value)) {
        value.forEach((v, i) => appendFormData(fd, v, `${key}[${i}]`));
        return;
    }

    if (typeof value === 'object') {
        // ignore empty object placeholders like {}
        const entries = Object.entries(value);
        if (!entries.length) return;
        entries.forEach(([k, v]) => appendFormData(fd, v, `${key}[${k}]`));
        return;
    }

    fd.append(key, String(value));
}

export function toPayload(data: Record<string, unknown>, options: PayLoadOptions = {}) {
    const prunedData = pruneForJson(data, options);

    if (!hasFileDeep(prunedData)) {
        return { body: prunedData, isForm: false };
    }
    const fd = new FormData();
    Object.entries(prunedData as Record<string, unknown>).forEach(([k, v]) => appendFormData(fd, v, k));
    return { body: fd, isForm: true };
}

export function cloneValue<T>(v: T): T {
    if (v === null || typeof v !== 'object') return v;
    if (v instanceof File || v instanceof Blob) return v;
    if (v instanceof Date) return new Date(v.getTime()) as T;
    if (Array.isArray(v)) return v.map(cloneValue) as T;
    const out: AnyRec = {};
    for (const [k, val] of Object.entries(v as AnyRec)) out[k] = cloneValue(val);
    return out as T;
}

export function isEqual(a: unknown, b: unknown): boolean {
    if (a === b) return true;
    if (a instanceof File && b instanceof File) {
        return a.name === b.name && a.size === b.size && a.lastModified === b.lastModified && a.type === b.type;
    }
    if (a instanceof Date && b instanceof Date) return a.getTime() === b.getTime();
    if (typeof a !== 'object' || typeof b !== 'object' || a === null || b === null) return false;
    if (Array.isArray(a) !== Array.isArray(b)) return false;

    if (Array.isArray(a)) {
        if (a.length !== (b as unknown[]).length) return false;
        for (let i = 0; i < a.length; i++) if (!isEqual(a[i], (b as unknown[])[i])) return false;
        return true;
    }

    const aKeys = Object.keys(a),
        bKeys = Object.keys(b as AnyRec);
    if (aKeys.length !== bKeys.length) return false;
    for (const k of aKeys) if (!isEqual((a as AnyRec)[k], (b as AnyRec)[k])) return false;
    return true;
}

/** Deep clone while pruning preview objects and enforcing file-only keys */
function pruneForJson(input: unknown, opts: PayLoadOptions): unknown {
    const { fileOnlyKeys = [], skipMediaPreview = true } = opts;

    // ⬇️ add explicit return type
    const recur = (val: unknown, keyPath: string[] = []): unknown => {
        const key = keyPath[keyPath.length - 1] ?? '';
        const isFileOnly = fileOnlyKeys.includes(key);

        if (val == null) return val;
        if (val instanceof File || val instanceof Blob) return val;
        if (typeof val !== 'object') return val;

        if (isFileOnly && !(val instanceof File) && !(val instanceof Blob)) {
            return undefined;
        }

        if (skipMediaPreview && isMediaPreviewObject(val)) {
            return undefined;
        }

        if (Array.isArray(val)) {
            // ⬇️ annotate the array type
            const arr: unknown[] = val.map((v) => recur(v, keyPath)).filter((v) => v !== undefined);
            return arr;
        }

        const out: Record<string, unknown> = {};
        for (const [k, v] of Object.entries(val)) {
            const pruned = recur(v, [...keyPath, k]);
            if (pruned !== undefined) out[k] = pruned;
        }
        return out;
    };

    return recur(input, []);
}

/** Identify a preview object like { url, thumb_url } with no File/Blob inside */
function isMediaPreviewObject(v: unknown): boolean {
    if (!v || typeof v !== 'object' || v instanceof File || v instanceof Blob) return false;
    const keys = Object.keys(v);
    if (!keys.length) return false;
    const hasURL = 'url' in v || 'thumb_url' in v || 'web_url' in v;
    return hasURL && !hasFileDeep(v);
}
