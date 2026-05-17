import type { ValidatorFunction } from '@/modules/types/support/forms/ValidatorFunction.ts';

export const required: ValidatorFunction = (value: unknown) =>
    value === undefined || value === null || value === '' ? 'FORM_FIELD_REQUIRED' : null;

export const minLength =
    (min: number): ValidatorFunction =>
    (value: unknown) =>
        typeof value === 'string' && value.length < min ? `FORM_FIELD_MIN_LENGTH ${min}` : null;

export const maxLength =
    (max: number): ValidatorFunction =>
    (value: unknown) =>
        typeof value === 'string' && value.length > max ? `FORM_FIELD_MAX_LENGTH ${max}` : null;

/** Use with numeric fields; empty values are left to {@link required}. */
export const minNumber =
    (min: number): ValidatorFunction =>
    (value: unknown) => {
        if (value === undefined || value === null || value === '') {
            return null;
        }
        const n = typeof value === 'number' ? value : Number(value);
        if (Number.isNaN(n)) {
            return 'FORM_FIELD_INVALID_NUMBER';
        }
        return n < min ? 'FORM_FIELD_MIN_NUMBER' : null;
    };

export const maxNumber =
    (max: number): ValidatorFunction =>
    (value: unknown) => {
        if (value === undefined || value === null || value === '') {
            return null;
        }
        const n = typeof value === 'number' ? value : Number(value);
        if (Number.isNaN(n)) {
            return 'FORM_FIELD_INVALID_NUMBER';
        }
        return n > max ? 'FORM_FIELD_MAX_NUMBER' : null;
    };

export const pattern =
    (regex: RegExp): ValidatorFunction =>
    (value: unknown) => {
        return value && typeof value === 'string' && !regex.test(value) ? 'FORM_FIELD_INVALID_FORMAT' : null;
    };

export const custom =
    (fn: ValidatorFunction): ValidatorFunction =>
    (value: unknown) =>
        fn(value);
