import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { InputFieldOptions } from '@/modules/types/support/inputs/InputFieldOptions.ts';
import type { Component } from 'vue';
import InputFieldComponent from '@/components/forms/inputs/InputFieldComponent.vue';

export class InputField extends BaseInputField {
    declare options: InputFieldOptions;

    constructor(options: InputFieldOptions) {
        super(options);
    }

    getComponent(): Component | null {
        return InputFieldComponent;
    }
}
