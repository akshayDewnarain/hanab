import type { Component } from 'vue';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { InputFieldOptions } from '@/modules/types/support/inputs/InputFieldOptions.ts';
import ColorFieldComponent from '@/components/forms/inputs/ColorFieldComponent.vue';

export class ColorField extends BaseInputField {
    declare options: InputFieldOptions;

    constructor(options: InputFieldOptions) {
        super(options);
    }

    getComponent(): Component | null {
        return ColorFieldComponent;
    }
}
