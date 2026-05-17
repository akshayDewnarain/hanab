import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
import type { Component } from 'vue';

/**
 * Abstract input field wrapper pairing declarative {@link BaseInputFieldOptions} with a Vue component.
 *
 * Used by model-driven forms to:
 * - resolve which component renders the control
 * - pass optional extra props derived from the editing instance
 * - exclude synthetic fields from submission when needed
 */
export default abstract class BaseInputField {
    options!: BaseInputFieldOptions;

    protected constructor(options: BaseInputFieldOptions) {
        this.options = options;
    }

    abstract getComponent(): Component<unknown> | null;

    getExtraProps(_instance?: unknown): Record<string, unknown> {
        return {};
    }

    isExcludedFromForm(): boolean {
        return false;
    }
}
