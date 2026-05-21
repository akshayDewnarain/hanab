import Model from '@/modules/models/support/Model.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import { DateField } from '@/modules/models/support/forms/DateField.ts';
import { InputField } from '@/modules/models/support/forms/InputField.ts';
import { SelectField } from '@/modules/models/support/forms/SelectField.ts';
import { ToggleSwitchField } from '@/modules/models/support/forms/ToggleSwitchField.ts';
import { UploadImageField } from '@/modules/models/support/forms/UploadImageField.ts';
import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import { DropdownWidth } from '@/modules/enums/support/inputs/DropDownWidth.ts';
import { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import type { EmployeeLocationRecord } from '@/modules/types/support/models/EmployeeLocationRecord.ts';
import type { EmployeeRecord } from '@/modules/types/support/models/EmployeeRecord.ts';
import type { EmployeeRoleRecord } from '@/modules/types/support/models/EmployeeRoleRecord.ts';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import EmployeeLocation from '@/models/employees/EmployeeLocation.ts';
import EmployeeRole from '@/models/employees/EmployeeRole.ts';
import type MediaURL from '@/modules/types/support/media/MediaURL.ts';
import http from '@/utils/http.ts';
import type { AxiosRequestConfig, AxiosResponse } from 'axios';
import { CustomField } from '@/modules/models/support/forms/CustomField.ts';
import UserDetailFieldsSection from '@/components/forms/custom/UserDetailFieldsSection.vue';
import CategoryGroupedRelationManager from '@/components/forms/custom/CategoryGroupedRelationManager.vue';
import Skill from '@/models/employees/Skill.ts';
import Certificate from '@/models/employees/Certificate.ts';
import type { SkillRecord } from '@/modules/types/support/models/SkillRecord.ts';
import type { CertificateRecord } from '@/modules/types/support/models/CertificateRecord.ts';
import type { CategoryGroupedRelationCategory } from '@/modules/types/support/forms/CategoryGroupedRelationCategory.ts';
import type { CategoryGroupedRelationFieldProps } from '@/modules/types/support/inputs/CategoryGroupedRelationFieldProps.ts';

const EMPLOYEE_INFO_FIELD_NAMES = ['employee_number', 'first_name', 'last_name', 'email', 'phone'] as const;
const EMPLOYEE_DETAILS_FIELD_NAMES = [
    'employee_role_id',
    'employee_location_id',
    'employment_type',
    'start_date',
] as const;
const EMPLOYEE_STATUS_FIELD_NAMES = ['is_active'] as const;

const SKILL_CATEGORY_OPTIONS: CategoryGroupedRelationCategory[] = [
    { value: 'technical', labelKey: 'SKILL_CATEGORY_TECHNICAL' },
    { value: 'soft_skill', labelKey: 'SKILL_CATEGORY_SOFT_SKILL' },
    { value: 'domain', labelKey: 'SKILL_CATEGORY_DOMAIN' },
    { value: 'safety', labelKey: 'SKILL_CATEGORY_SAFETY' },
];

const CERTIFICATE_CATEGORY_OPTIONS: CategoryGroupedRelationCategory[] = [
    { value: 'safety', labelKey: 'CERTIFICATE_CATEGORY_SAFETY' },
    { value: 'technical', labelKey: 'CERTIFICATE_CATEGORY_TECHNICAL' },
    { value: 'compliance', labelKey: 'CERTIFICATE_CATEGORY_COMPLIANCE' },
];

const EMPLOYEE_SKILL_RELATION_PROPS: CategoryGroupedRelationFieldProps = {
    entity: 'skills',
    model: Skill,
    categories: SKILL_CATEGORY_OPTIONS,
    columnNames: ['name', 'category', 'is_active'],
    pickerTitleKey: 'EMPLOYEE_SKILL_PICKER',
    addButtonKey: 'EMPLOYEE_ADD_SKILL',
    countLabelKey: 'GENERAL_SKILLS',
    useSimplePicker: true,
    searchPlaceholderKey: 'EMPLOYEE_SKILL_SEARCH',
    saveButtonKey: 'EMPLOYEE_SKILL_PICKER_SAVE',
    addInCategoryKey: 'EMPLOYEE_ADD_CATEGORY_SKILL',
    summaryTitleKey: 'EMPLOYEE_SKILLS_SUMMARY_TITLE',
    summaryDescriptionKey: 'EMPLOYEE_SKILLS_SUMMARY_DESCRIPTION',
    summaryIcon: 'material-symbols:workspace-premium-outline',
    linkedCountKey: 'EMPLOYEE_SKILLS_LINKED_COUNT',
    howToTitleKey: 'EMPLOYEE_SKILLS_HOW_TO_TITLE',
    howToTips: [
        {
            icon: 'material-symbols:visibility-outline-rounded',
            textKey: 'EMPLOYEE_SKILLS_HOW_TO_TIP_1',
        },
        {
            icon: 'material-symbols:add-circle-outline-rounded',
            textKey: 'EMPLOYEE_SKILLS_HOW_TO_TIP_2',
        },
        {
            icon: 'material-symbols:save-outline-rounded',
            textKey: 'EMPLOYEE_SKILLS_HOW_TO_TIP_3',
        },
    ],
    sectionHeadingKey: 'EMPLOYEE_SKILLS_SECTION_HEADING',
    footerNoteKey: 'EMPLOYEE_SKILLS_FOOTER_NOTE',
    panelExpandLabelKey: 'EMPLOYEE_SKILLS_PANEL_EXPAND',
    panelCollapseLabelKey: 'EMPLOYEE_SKILLS_PANEL_COLLAPSE',
};

const EMPLOYEE_CERTIFICATE_RELATION_PROPS: CategoryGroupedRelationFieldProps = {
    entity: 'certificates',
    model: Certificate,
    categories: CERTIFICATE_CATEGORY_OPTIONS,
    columnNames: ['name', 'code', 'category', 'requires_expiry_date', 'is_active'],
    pickerTitleKey: 'EMPLOYEE_CERTIFICATE_PICKER',
    addButtonKey: 'EMPLOYEE_ADD_CERTIFICATE',
    countLabelKey: 'GENERAL_CERTIFICATES',
    useSimplePicker: true,
    searchPlaceholderKey: 'EMPLOYEE_CERTIFICATE_SEARCH',
    saveButtonKey: 'EMPLOYEE_CERTIFICATE_PICKER_SAVE',
    addInCategoryKey: 'EMPLOYEE_ADD_CATEGORY_CERTIFICATE',
    summaryTitleKey: 'EMPLOYEE_CERTIFICATES_SUMMARY_TITLE',
    summaryDescriptionKey: 'EMPLOYEE_CERTIFICATES_SUMMARY_DESCRIPTION',
    summaryIcon: 'material-symbols:verified-outline-rounded',
    linkedCountKey: 'EMPLOYEE_CERTIFICATES_LINKED_COUNT',
    howToTitleKey: 'EMPLOYEE_CERTIFICATES_HOW_TO_TITLE',
    howToTips: [
        {
            icon: 'material-symbols:visibility-outline-rounded',
            textKey: 'EMPLOYEE_CERTIFICATES_HOW_TO_TIP_1',
        },
        {
            icon: 'material-symbols:add-circle-outline-rounded',
            textKey: 'EMPLOYEE_CERTIFICATES_HOW_TO_TIP_2',
        },
        {
            icon: 'material-symbols:save-outline-rounded',
            textKey: 'EMPLOYEE_CERTIFICATES_HOW_TO_TIP_3',
        },
    ],
    sectionHeadingKey: 'EMPLOYEE_CERTIFICATES_SECTION_HEADING',
    footerNoteKey: 'EMPLOYEE_CERTIFICATES_FOOTER_NOTE',
    panelExpandLabelKey: 'EMPLOYEE_CERTIFICATES_PANEL_EXPAND',
    panelCollapseLabelKey: 'EMPLOYEE_CERTIFICATES_PANEL_COLLAPSE',
};

function employeeDetailEmbeddedFields(
    model: Employee,
    names: readonly string[],
): BaseInputField[] {
    const map = new Map(model.inputFields().map((f) => [f.options.name, f]));
    return names.map((n) => map.get(n)).filter((f): f is BaseInputField => f != null);
}

function employeeDetailSectionField(options: {
    name: string;
    label: string;
    descriptionKey: string;
    headerIcon: string;
    fieldNames: readonly string[];
    headerTrailingFieldNames?: string[];
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
            headerTrailingFieldNames: options.headerTrailingFieldNames,
        },
        resolveExtraProps: (inst) => ({
            embeddedFields: employeeDetailEmbeddedFields(inst as Employee, options.fieldNames),
        }),
    });
}

/**
 * API model for the `employees` resource (CRUD, list builder, forms).
 *
 * Mirrors route binding: plural segment `employees`, singular parameter `employee` (see API `v1` group).
 */
export default class Employee extends Model<EmployeeRecord> {
    image?: MediaURL;
    skills?: SkillRecord[];
    certificates?: CertificateRecord[];

    constructor() {
        super('employees', 'employee');
    }

    removeImage(id: number | string, config: AxiosRequestConfig = {}): Promise<AxiosResponse<void>> {
        return http.delete(`${this.getEntity()}/${id}/image`, config);
    }

    override columns(): TableColumn[] {
        return [
            new TableColumn({
                name: 'image',
                label: 'GENERAL_IMAGE',
                sortable: false,
                filterable: false,
                visible: true,
                quickFilter: false,
                component: {
                    type: TableColumnComponentEnum.IMAGE,
                },
            }),
            new TableColumn({
                name: 'employee_number',
                label: 'EMPLOYEE_NUMBER',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
                width: 'fit',
            }),
            new TableColumn({
                name: 'first_name',
                label: 'EMPLOYEE_FIRST_NAME',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
            }),
            new TableColumn({
                name: 'last_name',
                label: 'EMPLOYEE_LAST_NAME',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
            }),
            new TableColumn({
                name: 'email',
                label: 'GENERAL_EMAIL',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
            }),
            new TableColumn({
                name: 'phone',
                label: 'GENERAL_PHONE',
                sortable: false,
                filterable: true,
                visible: true,
                quickFilter: false,
                width: 'fit',
            }),
            new TableColumn({
                name: 'employee_role',
                label: 'EMPLOYEE_ROLE',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
                relation: {
                    type: 'belongsTo',
                    path: 'employee_role',
                    displayKey: 'name',
                    idKey: 'id',
                    sortKey: 'employee_roles.name',
                    filterKey: 'employee_role_name',
                    emptyLabel: '—',
                },
            }),
            new TableColumn({
                name: 'employee_location',
                label: 'EMPLOYEE_LOCATION',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
                relation: {
                    type: 'belongsTo',
                    path: 'employee_location',
                    displayKey: 'name',
                    idKey: 'id',
                    sortKey: 'employee_locations.name',
                    filterKey: 'employee_location_name',
                    emptyLabel: '—',
                },
            }),
            new TableColumn({
                name: 'employment_type',
                label: 'EMPLOYEE_EMPLOYMENT_TYPE',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
            }),
            new TableColumn({
                name: 'start_date',
                label: 'EMPLOYEE_START_DATE',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: false,
                filterKey: 'start_date_between',
                component: {
                    type: TableColumnComponentEnum.DATE,
                },
                width: 'fit',
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
            new TableColumn({
                name: 'skills',
                label: 'EMPLOYEE_SKILLS',
                sortable: false,
                filterable: true,
                visible: false,
                quickFilter: true,
                relation: {
                    type: 'manyToMany',
                    path: 'skills',
                    displayKey: 'name',
                    idKey: 'id',
                    filterKey: 'skill_ids',
                    multipleSeparator: ', ',
                    emptyLabel: '—',
                },
                component: {
                    type: TableColumnComponentEnum.MULTI_SELECT,
                    multiSelect: {
                        model: Skill,
                        labelKey: 'name',
                        valueKey: 'id',
                        staticFilter: { is_active: true },
                    },
                },
            }),
            new TableColumn({
                name: 'certificates',
                label: 'EMPLOYEE_CERTIFICATES',
                sortable: false,
                filterable: true,
                visible: false,
                quickFilter: true,
                relation: {
                    type: 'manyToMany',
                    path: 'certificates',
                    displayKey: 'name',
                    idKey: 'id',
                    filterKey: 'certificate_ids',
                    multipleSeparator: ', ',
                    emptyLabel: '—',
                },
                component: {
                    type: TableColumnComponentEnum.MULTI_SELECT,
                    multiSelect: {
                        model: Certificate,
                        labelKey: 'name',
                        valueKey: 'id',
                        staticFilter: { is_active: true },
                    },
                },
            }),
            ...super.columns(),
        ];
    }

    override inputFields(): BaseInputField[] {
        return [
            new UploadImageField({
                name: 'image',
                label: 'GENERAL_IMAGE',
                maxFiles: 1,
                multiple: false,
                accept: 'image/*',
                media: this.image ?? null,
                callback: async () => {
                    if (!this.id) {
                        return false;
                    }

                    await this.removeImage(this.id);
                    this.image = undefined;

                    return true;
                },
            }),
            new InputField({
                name: 'employee_number',
                label: 'EMPLOYEE_NUMBER',
                required: true,
                placeholder: 'EMPLOYEE_NUMBER_PLACEHOLDER',
                validators: {
                    required: true,
                    maxLength: 255,
                },
            }),

            new InputField({
                name: 'first_name',
                label: 'EMPLOYEE_FIRST_NAME',
                required: true,
                placeholder: 'EMPLOYEE_FIRST_NAME_PLACEHOLDER',
                width: FieldWidth.HALF,
                validators: {
                    required: true,
                    maxLength: 255,
                },
            }),

            new InputField({
                name: 'last_name',
                label: 'EMPLOYEE_LAST_NAME',
                required: true,
                placeholder: 'EMPLOYEE_LAST_NAME_PLACEHOLDER',
                width: FieldWidth.HALF,
                validators: {
                    required: true,
                    maxLength: 255,
                },
            }),

            new InputField({
                name: 'email',
                label: 'GENERAL_EMAIL',
                required: true,
                placeholder: 'GENERAL_EMAIL_PLACEHOLDER',
                autocomplete: 'email',
                validators: {
                    required: true,
                    maxLength: 255,
                    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                },
            }),

            new InputField({
                name: 'phone',
                label: 'GENERAL_PHONE',
                required: false,
                placeholder: 'GENERAL_PHONE_PLACEHOLDER',
                autocomplete: 'tel',
                validators: {
                    maxLength: 255,
                },
            }),

            new SelectField({
                name: 'employee_role_id',
                label: 'EMPLOYEE_ROLE',
                required: true,
                validators: { required: true },
                placeholder: 'EMPLOYEE_ROLE_PLACEHOLDER',
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

            new SelectField({
                name: 'employee_location_id',
                label: 'EMPLOYEE_LOCATION',
                required: true,
                validators: { required: true },
                placeholder: 'EMPLOYEE_LOCATION_PLACEHOLDER',
                position: 'bottom',
                align: 'right',
                remoteFetch: {
                    instance: EmployeeLocation,
                    map: { label: 'name', value: 'id' },
                    params: { per_page: 100 },
                    immediate: true,
                    translate: false,
                    filter: (row) => (row as EmployeeLocationRecord).is_active,
                },
            }),

            new SelectField({
                name: 'employment_type',
                label: 'EMPLOYEE_EMPLOYMENT_TYPE',
                required: true,
                validators: { required: true },
                placeholder: 'EMPLOYEE_EMPLOYMENT_TYPE_PLACEHOLDER',
                position: 'bottom',
                align: 'right',
                translateOptionLabels: true,
                options: [
                    { label: 'EMPLOYEE_EMPLOYMENT_INTERNAL', value: 'internal' },
                    { label: 'EMPLOYEE_EMPLOYMENT_EXTERNAL', value: 'external' },
                ],
            }),

            new DateField({
                name: 'start_date',
                label: 'EMPLOYEE_START_DATE',
                required: false,
                position: 'bottom',
                align: 'right',
                dropdownWidth: DropdownWidth.FULL,
            }),

            new ToggleSwitchField({
                name: 'is_active',
                label: 'GENERAL_ACTIVE',
                required: true,
                defaultValue: true,
                width: FieldWidth.HALF,
                validators: { required: true },
            }),

            new CustomField({
                name: 'skill_ids',
                label: 'EMPLOYEE_SKILLS',
                required: false,
                validators: { required: false },
                hideInCreate: true,
                component: CategoryGroupedRelationManager,
                relation: {
                    type: 'manyToMany',
                    path: 'skills',
                    idKey: 'id',
                    formDataKey: 'skill_ids',
                },
                props: EMPLOYEE_SKILL_RELATION_PROPS,
            }),

            new CustomField({
                name: 'certificate_ids',
                label: 'EMPLOYEE_CERTIFICATES',
                required: false,
                validators: { required: false },
                hideInCreate: true,
                component: CategoryGroupedRelationManager,
                relation: {
                    type: 'manyToMany',
                    path: 'certificates',
                    idKey: 'id',
                    formDataKey: 'certificate_ids',
                },
                props: EMPLOYEE_CERTIFICATE_RELATION_PROPS,
            }),
        ];
    }

    override detailSchematic(): FormBuilder {
        const fields = this.inputFields();
        const requireField = (name: string) => {
            const field = fields.find((f) => f.options.name === name);
            if (!field) {
                throw new Error(`Field "${name}" not found in inputFields()`);
            }
            return field;
        };

        const imageField = requireField('image');
        const skillIdsField = requireField('skill_ids');
        const certificateIdsField = requireField('certificate_ids');

        return {
            name: 'DETAIL',
            panels: [
                {
                    name: 'EMPLOYEE_DETAIL_STACK_IMAGE',
                    col: 6,
                    tabs: [
                        {
                            label: 'GENERAL_IMAGE',
                            rows: [
                                {
                                    type: 'fields',
                                    fields: [imageField],
                                    col: 12,
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'EMPLOYEE_DETAIL_STACK_DETAILS',
                    col: 6,
                    tabs: [
                        {
                            label: 'EMPLOYEE_DETAIL_TAB_DETAILS',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        employeeDetailSectionField({
                                            name: 'employee_details_section',
                                            label: 'EMPLOYEE_DETAIL_TAB_DETAILS',
                                            descriptionKey: 'EMPLOYEE_DETAIL_SECTION_DETAILS_DESCRIPTION',
                                            headerIcon: 'material-symbols:work-outline',
                                            fieldNames: EMPLOYEE_DETAILS_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'EMPLOYEE_DETAIL_STACK_INFO',
                    col: 6,
                    tabs: [
                        {
                            label: 'EMPLOYEE_DETAIL_TAB_INFO',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        employeeDetailSectionField({
                                            name: 'employee_info_section',
                                            label: 'EMPLOYEE_DETAIL_TAB_INFO',
                                            descriptionKey: 'EMPLOYEE_DETAIL_SECTION_INFO_DESCRIPTION',
                                            headerIcon: 'material-symbols:badge-outline',
                                            fieldNames: EMPLOYEE_INFO_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'EMPLOYEE_DETAIL_STACK_STATUS',
                    col: 6,
                    tabs: [
                        {
                            label: 'EMPLOYEE_DETAIL_TAB_STATUS',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        employeeDetailSectionField({
                                            name: 'employee_status_section',
                                            label: 'EMPLOYEE_DETAIL_TAB_STATUS',
                                            descriptionKey: 'EMPLOYEE_DETAIL_SECTION_STATUS_DESCRIPTION',
                                            headerIcon: 'material-symbols:toggle-on-rounded',
                                            fieldNames: EMPLOYEE_STATUS_FIELD_NAMES,
                                            headerTrailingFieldNames: ['is_active'],
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'EMPLOYEE_DETAIL_PANEL_SKILLS',
                    col: 12,
                    tabs: [
                        {
                            label: 'EMPLOYEE_SKILLS',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [skillIdsField],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'EMPLOYEE_DETAIL_PANEL_CERTIFICATES',
                    col: 12,
                    tabs: [
                        {
                            label: 'EMPLOYEE_CERTIFICATES',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [certificateIdsField],
                                },
                            ],
                        },
                    ],
                },
            ],
        };
    }
}
