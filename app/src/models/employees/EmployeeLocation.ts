import Model from '@/modules/models/support/Model.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import { InputField } from '@/modules/models/support/forms/InputField.ts';
import { ToggleSwitchField } from '@/modules/models/support/forms/ToggleSwitchField.ts';
import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import type { EmployeeLocationRecord } from '@/modules/types/support/models/EmployeeLocationRecord.ts';

export default class EmployeeLocation extends Model<EmployeeLocationRecord> {
    constructor() {
        super('employee-locations', 'employee_location');
    }

    override columns(): TableColumn[] {
        return [
            new TableColumn({
                name: 'name',
                label: 'EMPLOYEE_LOCATION',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
            }),
            new TableColumn({
                name: 'cluster_name',
                label: 'EMPLOYEE_CLUSTER_NAME',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
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
                label: 'EMPLOYEE_LOCATION',
                required: true,
                placeholder: 'EMPLOYEE_LOCATION_PLACEHOLDER',
                validators: { required: true, maxLength: 255 },
            }),
            new InputField({
                name: 'cluster_name',
                label: 'EMPLOYEE_CLUSTER_NAME',
                required: true,
                placeholder: 'EMPLOYEE_CLUSTER_NAME_PLACEHOLDER',
                validators: { required: true, maxLength: 255 },
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
            const field = all.find((f) => f.options.name === name);
            if (!field) {
                throw new Error(`Field "${name}" not found in inputFields()`);
            }
            return field;
        };
        const fields = (...names: string[]) => names.map(by);

        return {
            name: 'DETAIL',
            panels: [
                {
                    name: 'Location',
                    col: 12,
                    tabs: [
                        {
                            label: 'EMPLOYEE_LOCATION',
                            rows: [
                                {
                                    type: 'fields',
                                    fields: fields('name', 'cluster_name', 'is_active'),
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
