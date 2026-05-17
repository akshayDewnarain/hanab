import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { NumberFieldOptions } from '@/modules/types/support/inputs/NumberFieldOptions.ts';
import type { Component } from 'vue';
import NumberFieldComponent from '@/components/forms/inputs/NumberFieldComponent.vue';

export class NumberField extends BaseInputField {
    declare options: NumberFieldOptions;

    constructor(options: NumberFieldOptions) {
        super(options);
    }

    getComponent(): Component | null {
        return NumberFieldComponent;
    }
}
