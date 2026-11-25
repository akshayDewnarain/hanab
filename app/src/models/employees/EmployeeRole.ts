import Model from '@/modules/models/support/Model.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import { InputField } from '@/modules/models/support/forms/InputField.ts';
import { NumberField } from '@/modules/models/support/forms/NumberField.ts';
import { SelectField } from '@/modules/models/support/forms/SelectField.ts';
import { ToggleSwitchField } from '@/modules/models/support/forms/ToggleSwitchField.ts';
import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import { CustomField } from '@/modules/models/support/forms/CustomField.ts';
import { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import OrganizationLevel from '@/models/employees/OrganizationLevel.ts';
import type { EmployeeRoleRecord } from '@/modules/types/support/models/EmployeeRoleRecord.ts';
import UserDetailFieldsSection from '@/components/forms/custom/UserDetailFieldsSection.vue';
import RoleDetailHelpPanel from '@/components/forms/custom/RoleDetailHelpPanel.vue';
import RoleDetailPreview from '@/components/forms/custom/RoleDetailPreview.vue';

const ROLE_IDENTITY_FIELD_NAMES = ['name', 'is_active'] as const;
const ROLE_DESCRIPTION_FIELD_NAMES = ['description'] as const;
const ROLE_PLACEMENT_FIELD_NAMES = ['parent_id', 'organization_level_id', 'sort_order'] as const;

function roleDetailEmbeddedFields(model: EmployeeRole, names: readonly string[]): BaseInputField[] {
    const map = new Map(model.inputFields().map((field) => [field.options.name, field]));
    return names.map((name) => map.get(name)).filter((field): field is BaseInputField => field != null);
}

function roleDetailSectionField(options: {
    name: string;
    label: string;
    descriptionKey: string;
    headerIcon: string;
    fieldNames: readonly string[];
}): CustomField {
    return new CustomField({
        name: options.name,
        label: options.label,
        required: false,
        validators: { required: false },
        component: UserDetailFieldsSection,
        excludeFromForm: true,
        includeInputFieldNamesForPayload: [...options.fieldNames],
        props: {
            descriptionKey: options.descriptionKey,
            headerIcon: options.headerIcon,
            elevateInputSurfaces: true,
        },
        resolveExtraProps: (instance) => ({
            embeddedFields: roleDetailEmbeddedFields(instance as EmployeeRole, options.fieldNames),
        }),
    });
}

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
                hintKey: 'ROLE_ACTIVE_HINT',
                required: true,
                defaultValue: true,
                width: FieldWidth.HALF,
                toggleTone: 'status',
                validators: { required: true },
            }),
        ];
    }

    override detailSchematic(): FormBuilder {
        return {
            name: 'DETAIL',
            panels: [
                {
                    name: 'ROLE_DETAIL_PANEL_HELP',
                    col: 12,
                    tabs: [
                        {
                            label: 'EMPLOYEE_ROLE',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        new CustomField({
                                            name: 'role_detail_help',
                                            label: 'ROLE_DETAIL_HELP_TITLE',
                                            required: false,
                                            validators: { required: false },
                                            component: RoleDetailHelpPanel,
                                            excludeFromForm: true,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'ROLE_DETAIL_PANEL_IDENTITY',
                    col: 6,
                    tabs: [
                        {
                            label: 'ROLE_DETAIL_TAB_IDENTITY',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        roleDetailSectionField({
                                            name: 'role_identity_section',
                                            label: 'ROLE_DETAIL_TAB_IDENTITY',
                                            descriptionKey: 'ROLE_DETAIL_SECTION_IDENTITY_DESCRIPTION',
                                            headerIcon: 'material-symbols:badge-outline',
                                            fieldNames: ROLE_IDENTITY_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'ROLE_DETAIL_PANEL_DESCRIPTION',
                    col: 6,
                    tabs: [
                        {
                            label: 'ROLE_DETAIL_TAB_DESCRIPTION',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        roleDetailSectionField({
                                            name: 'role_description_section',
                                            label: 'ROLE_DETAIL_TAB_DESCRIPTION',
                                            descriptionKey: 'ROLE_DETAIL_SECTION_DESCRIPTION_DESCRIPTION',
                                            headerIcon: 'material-symbols:description-outline',
                                            fieldNames: ROLE_DESCRIPTION_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'ROLE_DETAIL_PANEL_PLACEMENT',
                    col: 6,
                    tabs: [
                        {
                            label: 'ROLE_DETAIL_TAB_PLACEMENT',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        roleDetailSectionField({
                                            name: 'role_placement_section',
                                            label: 'ROLE_DETAIL_TAB_PLACEMENT',
                                            descriptionKey: 'ROLE_DETAIL_SECTION_PLACEMENT_DESCRIPTION',
                                            headerIcon: 'material-symbols:account-tree-outline',
                                            fieldNames: ROLE_PLACEMENT_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'ROLE_DETAIL_PANEL_PREVIEW',
                    col: 6,
                    tabs: [
                        {
                            label: 'ROLE_DETAIL_PREVIEW_TITLE',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        new CustomField({
                                            name: 'role_detail_preview',
                                            label: 'ROLE_DETAIL_PREVIEW_TITLE',
                                            required: false,
                                            validators: { required: false },
                                            component: RoleDetailPreview,
                                            excludeFromForm: true,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
            ],
        };
    }
}
