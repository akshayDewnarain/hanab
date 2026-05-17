import Model from '@/modules/models/support/Model.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import { DateField } from '@/modules/models/support/forms/DateField.ts';
import { InputField } from '@/modules/models/support/forms/InputField.ts';
import { SelectField } from '@/modules/models/support/forms/SelectField.ts';
import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import { DropdownWidth } from '@/modules/enums/support/inputs/DropDownWidth.ts';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import type { CertificateRecord } from '@/modules/types/support/models/CertificateRecord.ts';
import type { EmployeeRecord } from '@/modules/types/support/models/EmployeeRecord.ts';
import Certificate from '@/models/employees/Certificate.ts';
import Employee from '@/models/employees/Employee.ts';
import type { EmployeeCertificateRecord } from '@/modules/types/support/models/EmployeeCertificateRecord.ts';

export default class EmployeeCertificate extends Model<EmployeeCertificateRecord> {
    constructor() {
        super('employee-certificates', 'employee_certificate');
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
                name: 'certificate',
                label: 'GENERAL_CERTIFICATE',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
                relation: {
                    type: 'belongsTo',
                    path: 'certificate',
                    displayKey: 'name',
                    idKey: 'id',
                    sortKey: 'certificates.name',
                    filterKey: 'certificate_id',
                    emptyLabel: '—',
                },
            }),
            new TableColumn({
                name: 'certificate_number',
                label: 'CERTIFICATE_NUMBER',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
            }),
            new TableColumn({
                name: 'issued_at',
                label: 'CERTIFICATE_ISSUED_AT',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: false,
                filterKey: 'issued_at_between',
                component: {
                    type: TableColumnComponentEnum.DATE,
                },
                width: 'fit',
            }),
            new TableColumn({
                name: 'expires_at',
                label: 'CERTIFICATE_EXPIRES_AT',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: false,
                filterKey: 'expires_at_between',
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
                name: 'certificate_id',
                label: 'GENERAL_CERTIFICATE',
                required: true,
                validators: { required: true },
                placeholder: 'GENERAL_CERTIFICATE',
                position: 'bottom',
                align: 'right',
                remoteFetch: {
                    instance: Certificate,
                    map: { label: 'name', value: 'id' },
                    params: { per_page: 100 },
                    immediate: true,
                    translate: false,
                    filter: (row) => (row as CertificateRecord).is_active !== false,
                },
            }),
            new InputField({
                name: 'certificate_number',
                label: 'CERTIFICATE_NUMBER',
                required: false,
                placeholder: 'CERTIFICATE_NUMBER_PLACEHOLDER',
                validators: { maxLength: 255 },
            }),
            new DateField({
                name: 'issued_at',
                label: 'CERTIFICATE_ISSUED_AT',
                required: false,
                position: 'bottom',
                align: 'right',
                dropdownWidth: DropdownWidth.FULL,
            }),
            new DateField({
                name: 'expires_at',
                label: 'CERTIFICATE_EXPIRES_AT',
                required: false,
                position: 'bottom',
                align: 'right',
                dropdownWidth: DropdownWidth.FULL,
            }),
            new InputField({
                name: 'notes',
                label: 'GENERAL_NOTES',
                required: false,
                placeholder: 'GENERAL_NOTES_PLACEHOLDER',
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
                    name: 'Employee certificate',
                    col: 12,
                    tabs: [
                        {
                            label: 'EMPLOYEE_CERTIFICATES',
                            rows: [
                                {
                                    type: 'fields',
                                    fields: fields(
                                        'employee_id',
                                        'certificate_id',
                                        'certificate_number',
                                        'issued_at',
                                        'expires_at',
                                        'notes',
                                    ),
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
