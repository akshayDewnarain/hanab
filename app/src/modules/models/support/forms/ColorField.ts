import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { ColorFieldOptions } from '@/modules/types/support/inputs/ColorFieldOptions.ts';
import type { Component } from 'vue';
import ColorFieldComponent from '@/components/forms/inputs/ColorFieldComponent.vue';

export class ColorField extends BaseInputField {
    declare options: ColorFieldOptions;

    constructor(options: ColorFieldOptions) {
        super(options);
    }

    getComponent(): Component | null {
        return ColorFieldComponent;
    }
}
