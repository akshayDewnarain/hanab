import Model from '@/modules/models/support/Model.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import { DateField } from '@/modules/models/support/forms/DateField.ts';
import { InputField } from '@/modules/models/support/forms/InputField.ts';
import { SelectField } from '@/modules/models/support/forms/SelectField.ts';
import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import { DropdownWidth } from '@/modules/enums/support/inputs/DropDownWidth.ts';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import type { EmployeeRecord } from '@/modules/types/support/models/EmployeeRecord.ts';
import type { SkillRecord } from '@/modules/types/support/models/SkillRecord.ts';
import Employee from '@/models/employees/Employee.ts';
import Skill from '@/models/employees/Skill.ts';
import type { EmployeeSkillRecord } from '@/modules/types/support/models/EmployeeSkillRecord.ts';

export default class EmployeeSkill extends Model<EmployeeSkillRecord> {
    constructor() {
        super('employee-skills', 'employee_skill');
    }

    override columns(): TableColumn[] {
        return [
            new TableColumn({
                name: 'employee',
                label: 'GENERAL_EMPLOYEE',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
                relation: {
                    type: 'belongsTo',
                    path: 'employee',
                    displayKey: 'first_name',
                    idKey: 'id',
                    sortKey: 'employees.first_name',
                    filterKey: 'employee_id',
                    emptyLabel: '—',
                },
            }),
            new TableColumn({
                name: 'skill',
                label: 'GENERAL_SKILL',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
                relation: {
                    type: 'belongsTo',
                    path: 'skill',
                    displayKey: 'name',
                    idKey: 'id',
                    sortKey: 'skills.name',
                    filterKey: 'skill_id',
                    emptyLabel: '—',
                },
            }),
            new TableColumn({
                name: 'level',
                label: 'GENERAL_LEVEL',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: false,
                width: 'fit',
            }),
            new TableColumn({
                name: 'assessed_at',
                label: 'EMPLOYEE_ASSESSED_AT',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: false,
                filterKey: 'assessed_at_between',
                component: {
                    type: TableColumnComponentEnum.DATE,
                },
                width: 'fit',
            }),
            ...super.columns(),
        ];
    }

    override inputFields(): BaseInputField[] {
        return [
            new SelectField({
                name: 'employee_id',
                label: 'GENERAL_EMPLOYEE',
                required: true,
                validators: { required: true },
                placeholder: 'GENERAL_EMPLOYEE',
                position: 'bottom',
                align: 'right',
                remoteFetch: {
                    instance: Employee,
                    map: { label: 'email', value: 'id' },
                    params: { per_page: 100 },
                    immediate: true,
                    translate: false,
                    filter: (row) => (row as EmployeeRecord).is_active !== false,
                },
            }),
            new SelectField({
                name: 'skill_id',
                label: 'GENERAL_SKILL',
                required: true,
                validators: { required: true },
                placeholder: 'GENERAL_SKILL',
                position: 'bottom',
                align: 'right',
                remoteFetch: {
                    instance: Skill,
                    map: { label: 'name', value: 'id' },
                    params: { per_page: 100 },
                    immediate: true,
                    translate: false,
                    filter: (row) => (row as SkillRecord).is_active !== false,
                },
            }),
            new SelectField({
                name: 'level',
                label: 'GENERAL_LEVEL',
                required: true,
                validators: { required: true },
                placeholder: 'GENERAL_LEVEL',
                position: 'bottom',
                align: 'right',
                options: [
                    { label: '1', value: 1 },
                    { label: '2', value: 2 },
                    { label: '3', value: 3 },
                    { label: '4', value: 4 },
                    { label: '5', value: 5 },
                ],
            }),
            new InputField({
                name: 'notes',
                label: 'GENERAL_NOTES',
                required: false,
                placeholder: 'GENERAL_NOTES_PLACEHOLDER',
            }),
            new DateField({
                name: 'assessed_at',
                label: 'EMPLOYEE_ASSESSED_AT',
                required: false,
                position: 'bottom',
                align: 'right',
                dropdownWidth: DropdownWidth.FULL,
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
                    name: 'Employee skill',
                    col: 12,
                    tabs: [
                        {
                            label: 'EMPLOYEE_SKILLS',
                            rows: [
                                {
                                    type: 'fields',
                                    fields: fields('employee_id', 'skill_id', 'level', 'notes', 'assessed_at'),
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
