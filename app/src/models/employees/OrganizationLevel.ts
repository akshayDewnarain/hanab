import Model from '@/modules/models/support/Model.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import { InputField } from '@/modules/models/support/forms/InputField.ts';
import { ColorField } from '@/modules/models/support/forms/ColorField.ts';
import { NumberField } from '@/modules/models/support/forms/NumberField.ts';
import { ToggleSwitchField } from '@/modules/models/support/forms/ToggleSwitchField.ts';
import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';

export type OrganizationLevelRecord = {
    id: number;
    created_at: string;
    updated_at: string;
    name: string;
    sort_order: number;
    color: string;
    is_active: boolean;
};

export default class OrganizationLevel extends Model<OrganizationLevelRecord> {
    constructor() {
        super('organization-levels', 'organization_level');
    }

    override columns(): TableColumn[] {
        return [
            new TableColumn({
                name: 'name',
                label: 'ORGANIZATION_LEVEL',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
            }),
            new TableColumn({
                name: 'sort_order',
                label: 'ORGANIZATION_LEVEL_SORT',
                sortable: true,
                filterable: false,
                visible: true,
                quickFilter: false,
            }),
            new TableColumn({
                name: 'color',
                label: 'ORGANIZATION_LEVEL_COLOR',
                sortable: false,
                filterable: false,
                visible: true,
                quickFilter: false,
            }),
            new TableColumn({
                name: 'is_active',
                label: 'GENERAL_ACTIVE',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: false,
                component: { type: TableColumnComponentEnum.BOOLEAN },
                width: 'fit',
            }),
            ...super.columns(),
        ];
    }

    override inputFields(): BaseInputField[] {
        return [
            new InputField({
                name: 'name',
                label: 'ORGANIZATION_LEVEL',
                required: true,
                placeholder: 'ORGANIZATION_LEVEL_PLACEHOLDER',
                validators: { required: true, maxLength: 255 },
            }),
            new NumberField({
                name: 'sort_order',
                label: 'ORGANIZATION_LEVEL_SORT',
                required: false,
                min: 0,
                max: 9999,
                step: 1,
                defaultValue: 0,
                width: FieldWidth.HALF,
            }),
            new ColorField({
                name: 'color',
                label: 'ORGANIZATION_LEVEL_COLOR',
                required: true,
                placeholder: 'ORGANIZATION_LEVEL_COLOR_PLACEHOLDER',
                validators: { required: true, maxLength: 7 },
                width: FieldWidth.HALF,
            }),
            new ToggleSwitchField({
                name: 'is_active',
                label: 'GENERAL_ACTIVE',
                required: true,
                defaultValue: true,
                width: FieldWidth.HALF,
                validators: { required: true },
            }),
        ];
    }

    override detailSchematic(): FormBuilder {
        const all = this.inputFields();
        const by = (name: string) => {
            const field = all.find((item) => item.options.name === name);
            if (!field) {
                throw new Error(`Field "${name}" not found in inputFields()`);
            }
            return field;
        };

        return {
            name: 'DETAIL',
            panels: [
                {
                    name: 'Level',
                    col: 12,
                    tabs: [
                        {
                            label: 'ORGANIZATION_LEVEL',
                            rows: [
                                {
                                    type: 'fields',
                                    fields: ['name', 'sort_order', 'color', 'is_active'].map(by),
                                    col: 12,
                                },
                            ],
                        },
                    ],
                },
            ],
        };
    }
}
