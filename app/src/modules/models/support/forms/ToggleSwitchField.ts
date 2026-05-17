import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { ToggleFieldOptions } from '@/modules/types/support/inputs/ToggleFieldOptions.ts';
import type { Component } from 'vue';
import ToggleSwitchFieldComponent from '@/components/forms/inputs/ToggleSwitchFieldComponent.vue';

export class ToggleSwitchField extends BaseInputField {
    declare options: ToggleFieldOptions;

    constructor(options: ToggleFieldOptions) {
        super(options);
    }

    getComponent(): Component | null {
        return ToggleSwitchFieldComponent;
    }
}
