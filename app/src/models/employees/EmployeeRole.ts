import Model from '@/modules/models/support/Model.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import { InputField } from '@/modules/models/support/forms/InputField.ts';
import { NumberField } from '@/modules/models/support/forms/NumberField.ts';
import { SelectField } from '@/modules/models/support/forms/SelectField.ts';
import { ToggleSwitchField } from '@/modules/models/support/forms/ToggleSwitchField.ts';
import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import OrganizationLevel from '@/models/employees/OrganizationLevel.ts';
import type { EmployeeRoleRecord } from '@/modules/types/support/models/EmployeeRoleRecord.ts';

export default class EmployeeRole extends Model<EmployeeRoleRecord> {
    constructor() {
        super('employee-roles', 'employee_role');
    }

    override transferKey(): string {
        return 'employee-roles';
    }

    override columns(): TableColumn[] {
        return [
            new TableColumn({
                name: 'name',
                label: 'EMPLOYEE_ROLE',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
            }),
            new TableColumn({
                name: 'description',
                label: 'GENERAL_DESCRIPTION',
                sortable: false,
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
                label: 'EMPLOYEE_ROLE',
                required: true,
                placeholder: 'EMPLOYEE_ROLE_PLACEHOLDER',
                validators: { required: true, maxLength: 255 },
            }),
            new InputField({
                name: 'description',
                label: 'GENERAL_DESCRIPTION',
                required: false,
                placeholder: 'GENERAL_DESCRIPTION_PLACEHOLDER',
            }),
            new SelectField({
                name: 'parent_id',
                label: 'EMPLOYEE_ROLE_PARENT',
                required: false,
                placeholder: 'EMPLOYEE_ROLE_PARENT_PLACEHOLDER',
                position: 'bottom',
                align: 'right',
                remoteFetch: {
                    instance: EmployeeRole,
                    map: { label: 'name', value: 'id' },
                    params: { per_page: 100 },
                    immediate: true,
                    translate: false,
                    filter: (row) => (row as EmployeeRoleRecord).is_active !== false,
                },
            }),
            new NumberField({
                name: 'sort_order',
                label: 'EMPLOYEE_ROLE_SORT',
                required: false,
                min: 0,
                max: 9999,
                step: 1,
                defaultValue: 0,
                width: FieldWidth.HALF,
            }),
            new SelectField({
                name: 'organization_level_id',
                label: 'EMPLOYEE_ROLE_LEVEL',
                required: false,
                placeholder: 'EMPLOYEE_ORGANIZATION_LEVEL_PLACEHOLDER',
                position: 'bottom',
                align: 'right',
                remoteFetch: {
                    instance: OrganizationLevel,
                    map: { label: 'name', value: 'id' },
                    params: { per_page: 100 },
                    immediate: true,
                    translate: false,
                    filter: (row) => (row as { is_active?: boolean }).is_active !== false,
                },
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
                    name: 'Role',
                    col: 12,
                    tabs: [
                        {
                            label: 'EMPLOYEE_ROLE',
                            rows: [
                                {
                                    type: 'fields',
                                    fields: fields('name', 'description', 'parent_id', 'organization_level_id', 'sort_order', 'is_active'),
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
