import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { SelectFieldOptions } from '@/modules/types/support/inputs/SelectFieldOptions.ts';
import type { Component } from 'vue';
import MultiSelectFieldComponent from '@/components/forms/inputs/MultiSelectFieldComponent.vue';

export class MultiSelectField extends BaseInputField {
    declare options: SelectFieldOptions;

    constructor(options: SelectFieldOptions, enumType?: Record<string, string | number>) {
        super(options);

        if (!this.options.options && enumType) {
            this.options.options = this.enumValues(enumType);
        }
    }

    getComponent(): Component | null {
        return MultiSelectFieldComponent;
    }

    private enumValues(enumType: Record<string, string | number>) {
        const entries = Object.entries(enumType).filter(([k]) => Number.isNaN(Number(k)));
        return entries.map(([key, value]) => ({
            label: 'GENERAL_' + key,
            value,
        }));
    }
}
