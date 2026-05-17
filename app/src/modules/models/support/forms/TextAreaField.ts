import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { TextareaFieldOptions } from '@/modules/types/support/inputs/TextareaFieldOptions.ts';
import type { Component } from 'vue';
import TextAreaFieldComponent from '@/components/forms/inputs/TextAreaFieldComponent.vue';

export class TextAreaField extends BaseInputField {
    declare options: TextareaFieldOptions;

    constructor(options: TextareaFieldOptions) {
        super(options);
    }

    getComponent(): Component | null {
        return TextAreaFieldComponent;
    }
}
