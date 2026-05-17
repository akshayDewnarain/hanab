import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { PhoneSplitFieldOptions } from '@/modules/types/support/inputs/PhoneSplitFieldOptions.ts';
import type { Component } from 'vue';
import PhoneSplitFieldComponent from '@/components/forms/inputs/PhoneSplitFieldComponent.vue';

export class PhoneSplitField extends BaseInputField {
    declare options: PhoneSplitFieldOptions;

    constructor(options: PhoneSplitFieldOptions) {
        super(options);
    }

    getComponent(): Component | null {
        return PhoneSplitFieldComponent;
    }
}
