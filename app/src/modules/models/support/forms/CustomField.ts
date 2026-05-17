import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import type { CustomFieldOptions } from '@/modules/types/support/inputs/CustomFieldOptions.ts';
import type { Component } from 'vue';

export class CustomField extends BaseInputField {
    declare options: CustomFieldOptions;

    constructor(options: CustomFieldOptions) {
        super(options);

        this.options.valueProp ??= 'modelValue';
        this.options.updateEvent ??= 'update:modelValue';
    }

    getComponent(): Component<unknown> | null {
        return this.options.component;
    }

    getExtraProps(instance?: unknown): Record<string, unknown> {
        const staticProps = this.options.props ?? {};
        const resolver = this.options.resolveExtraProps;
        if (resolver && instance !== undefined) {
            return { ...staticProps, ...resolver(instance) };
        }
        return staticProps;
    }

    getValueBindingMeta() {
        return {
            valueProp: this.options.valueProp!,
            updateEvent: this.options.updateEvent!,
        };
    }

    getExtraEvents() {
        return this.options.events ?? {};
    }

    isExcludedFromForm(): boolean {
        return this.options.excludeFromForm === true;
    }
}
