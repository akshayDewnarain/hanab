import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { TimeFieldOptions } from '@/modules/types/support/inputs/TimeFieldOptions.ts';
import type { Component } from 'vue';
import TimeFieldComponent from '@/components/forms/inputs/TimeFieldComponent.vue';

export class TimeField extends BaseInputField {
    declare options: TimeFieldOptions;

    constructor(options: TimeFieldOptions) {
        super(options);
    }

    getComponent(): Component | null {
        return TimeFieldComponent;
    }
}
