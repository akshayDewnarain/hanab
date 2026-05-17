import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { DateFieldOptions } from '@/modules/types/support/inputs/DateFieldOptions.ts';
import type { Component } from 'vue';
import DateFieldComponent from '@/components/forms/inputs/DateFieldComponent.vue';

export class DateField extends BaseInputField {
    declare options: DateFieldOptions;

    constructor(options: DateFieldOptions) {
        super(options);
    }

    getComponent(): Component | null {
        return DateFieldComponent;
    }
}
