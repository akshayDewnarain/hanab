import { computed, reactive, ref } from 'vue';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { ValidatorFunction } from '@/modules/types/support/forms/ValidatorFunction.ts';
import type { ValidatorRule } from '@/modules/types/support/forms/ValidatorRule.ts';
import { maxLength, maxNumber, minLength, minNumber, pattern, required } from '@/utils/validators';
import { ValidatorType } from '@/modules/enums/support/forms/ValidatorType.ts';
import type { AnyRec } from '@/modules/types/support/form-builder/AnyRec.ts';
import { cloneValue, isEqual } from '@/composables/useGlobalHelpers';

export function useFormValidator(fields: BaseInputField[], formData: Record<string, unknown>) {
    const errors: Record<string, string | null> = reactive<Record<string, string | null>>({});
    const touched: Record<string, boolean> = reactive<Record<string, boolean>>({});

    // ---- Baseline snapshot for dirty checks ----
    // We snapshot only the fields you care about (based on `fields`)
    const fieldNames = fields.filter((f) => !f.isExcludedFromForm()).map((f) => f.options.name);
    const initial = ref<AnyRec>({});
    const makeBaseline = () => {
        const base: AnyRec = {};
        for (const name of fieldNames) base[name] = cloneValue(formData[name]);
        return base;
    };

    // On init: for updates snapshot current form; for create you can still snapshot to allow "revert"
    initial.value = makeBaseline();

    // Compute which fields changed
    const dirtyFields = computed<string[]>(() => {
        const changed: string[] = [];
        for (const name of fieldNames) {
            if (!isEqual(formData[name], initial.value[name])) changed.push(name);
        }
        return changed;
    });

    const isDirty = computed<boolean>(() => dirtyFields.value.length > 0);

    // Provide a convenient diff map: { fieldName: { from, to } }
    const diff = computed<Record<string, { from: unknown; to: unknown }>>(() => {
        const out: Record<string, { from: unknown; to: unknown }> = {};
        for (const name of dirtyFields.value) {
            out[name] = { from: initial.value[name], to: formData[name] };
        }
        return out;
    });

    // Revert a single field to its initial value
    function revertField(name: string): void {
        if (!fieldNames.includes(name)) return;
        formData[name] = cloneValue(initial.value[name]);
        // Clear error/touched for UX
        errors[name] = null;
        touched[name] = false;
    }

    // Revert all fields
    function revertAll(): void {
        for (const name of fieldNames) {
            formData[name] = cloneValue(initial.value[name]);
            errors[name] = null;
            touched[name] = false;
        }
    }

    // Accept current values as the new baseline (call after successful save)
    function applyChanges(): void {
        initial.value = makeBaseline();
        for (const name of fieldNames) {
            errors[name] = null;
            touched[name] = false;
        }
    }

    function validateField(name: string): string | null {
        const field = fields.find((f) => f.options.name === name);
        if (!field) return null;

        const value = formData[name];
        const validators = normalizeValidators(field.options.validators, name);
        for (const validate of validators) {
            const error = validate(value);
            if (error) {
                errors[name] = error;
                return error;
            }
        }
        errors[name] = null;
        return null;
    }

    function markTouched(name: string): void {
        touched[name] = true;
        validateField(name);
    }

    function validateAll(): boolean {
        let isValid: boolean = true;
        for (const field of fields) {
            if (field.isExcludedFromForm()) {
                continue;
            }
            const name = field.options.name;
            touched[name] = true;
            const err = validateField(name);
            if (err) isValid = false;
        }
        return isValid;
    }

    function getError(name: string): string | null {
        return touched[name] ? (errors[name] ?? null) : null;
    }

    function normalizeValidators(rule: ValidatorRule | undefined, name?: string): ValidatorFunction[] {
        if (!rule) return [];
        if (Array.isArray(rule)) return rule as ValidatorFunction[];
        if (typeof rule === 'function') return [rule];

        const validators: ValidatorFunction[] = [];
        for (const [key, val] of Object.entries(rule)) {
            switch (key) {
                case ValidatorType.REQUIRED:
                    if (val) {
                        validators.push((v: unknown) => {
                            if (name == null) {
                                return required(v);
                            }
                            const init = initial.value[name];
                            const hadInitialValue =
                                init !== undefined &&
                                init !== null &&
                                init !== '' &&
                                !(typeof init === 'number' && Number.isNaN(init));
                            // Edit: skip required when user did not touch a field that already had a value.
                            if (!dirtyFields.value.includes(name) && hadInitialValue) {
                                return null;
                            }
                            return required(v);
                        });
                    }
                    break;
                case ValidatorType.MIN_LENGTH:
                    if (typeof val === 'number') {
                        validators.push(minLength(val));
                    }
                    break;
                case ValidatorType.MAX_LENGTH:
                    if (typeof val === 'number') {
                        validators.push(maxLength(val));
                    }
                    break;
                case ValidatorType.MIN:
                    if (typeof val === 'number') {
                        validators.push(minNumber(val));
                    }
                    break;
                case ValidatorType.MAX:
                    if (typeof val === 'number') {
                        validators.push(maxNumber(val));
                    }
                    break;
                case ValidatorType.PATTERN:
                    if (val instanceof RegExp) validators.push(pattern(val));
                    break;
                case ValidatorType.CUSTOM:
                    if (typeof val === 'function') validators.push(val);
                    break;
                default:
                    console.warn(`[Validator] Unsupported rule key: ${key}`);
            }
        }
        return validators;
    }

    // Optional utility: reset baseline from arbitrary data (e.g., after refetch)
    function setBaselineFrom(data: AnyRec): void {
        const base: AnyRec = {};
        for (const name of fieldNames) base[name] = cloneValue(data[name]);
        initial.value = base;
    }

    return {
        errors,
        touched,
        validateField,
        markTouched,
        validateAll,
        getError,

        // dirty & snapshot
        isDirty,
        dirtyFields,
        diff,
        revertField,
        revertAll,
        applyChanges,
        setBaselineFrom,
    };
}
