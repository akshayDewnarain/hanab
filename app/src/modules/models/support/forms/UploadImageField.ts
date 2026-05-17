import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { UploadImageFieldOptions } from '@/modules/types/support/inputs/UploadImageFieldOptions.ts';
import type { Component } from 'vue';
import UploadImageFieldComponent from '@/components/forms/inputs/UploadImageFieldComponent.vue';

export class UploadImageField extends BaseInputField {
    declare options: UploadImageFieldOptions;

    constructor(options: UploadImageFieldOptions) {
        super(options);
    }

    getComponent(): Component | null {
        return UploadImageFieldComponent;
    }
}
