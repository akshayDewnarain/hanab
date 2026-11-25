import Model from '@/modules/models/support/Model.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import { InputField } from '@/modules/models/support/forms/InputField.ts';
import { ToggleSwitchField } from '@/modules/models/support/forms/ToggleSwitchField.ts';
import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import { CustomField } from '@/modules/models/support/forms/CustomField.ts';
import { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import type { EmployeeLocationRecord } from '@/modules/types/support/models/EmployeeLocationRecord.ts';
import UserDetailFieldsSection from '@/components/forms/custom/UserDetailFieldsSection.vue';
import LocationDetailHelpPanel from '@/components/forms/custom/LocationDetailHelpPanel.vue';
import LocationDetailMap from '@/components/forms/custom/LocationDetailMap.vue';

const LOCATION_IDENTITY_FIELD_NAMES = ['name', 'cluster_name', 'is_active'] as const;
const LOCATION_COORDINATE_FIELD_NAMES = ['latitude', 'longitude'] as const;

function locationDetailEmbeddedFields(model: EmployeeLocation, names: readonly string[]): BaseInputField[] {
    const map = new Map(model.inputFields().map((field) => [field.options.name, field]));
    return names.map((name) => map.get(name)).filter((field): field is BaseInputField => field != null);
}

function locationDetailSectionField(options: {
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
            embeddedFields: locationDetailEmbeddedFields(instance as EmployeeLocation, options.fieldNames),
        }),
    });
}

export default class EmployeeLocation extends Model<EmployeeLocationRecord> {
    constructor() {
        super('employee-locations', 'employee_location');
    }

    override transferKey(): string {
        return 'employee-locations';
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
            new InputField({
                name: 'latitude',
                label: 'EMPLOYEE_LOCATION_LATITUDE',
                required: false,
                placeholder: 'EMPLOYEE_LOCATION_LATITUDE_PLACEHOLDER',
                hintKey: 'EMPLOYEE_LOCATION_COORDINATE_HINT',
                width: FieldWidth.HALF,
            }),
            new InputField({
                name: 'longitude',
                label: 'EMPLOYEE_LOCATION_LONGITUDE',
                required: false,
                placeholder: 'EMPLOYEE_LOCATION_LONGITUDE_PLACEHOLDER',
                hintKey: 'EMPLOYEE_LOCATION_COORDINATE_HINT',
                width: FieldWidth.HALF,
            }),
            new ToggleSwitchField({
                name: 'is_active',
                label: 'GENERAL_ACTIVE',
                hintKey: 'LOCATION_ACTIVE_HINT',
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
                    name: 'LOCATION_DETAIL_PANEL_HELP',
                    col: 12,
                    tabs: [
                        {
                            label: 'EMPLOYEE_LOCATION',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        new CustomField({
                                            name: 'location_detail_help',
                                            label: 'LOCATION_DETAIL_HELP_TITLE',
                                            required: false,
                                            validators: { required: false },
                                            component: LocationDetailHelpPanel,
                                            excludeFromForm: true,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'LOCATION_DETAIL_PANEL_IDENTITY',
                    col: 6,
                    tabs: [
                        {
                            label: 'LOCATION_DETAIL_TAB_IDENTITY',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        locationDetailSectionField({
                                            name: 'location_identity_section',
                                            label: 'LOCATION_DETAIL_TAB_IDENTITY',
                                            descriptionKey: 'LOCATION_DETAIL_SECTION_IDENTITY_DESCRIPTION',
                                            headerIcon: 'material-symbols:location-on-outline',
                                            fieldNames: LOCATION_IDENTITY_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'LOCATION_DETAIL_PANEL_COORDINATES',
                    col: 6,
                    tabs: [
                        {
                            label: 'LOCATION_DETAIL_TAB_COORDINATES',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        locationDetailSectionField({
                                            name: 'location_coordinate_section',
                                            label: 'LOCATION_DETAIL_TAB_COORDINATES',
                                            descriptionKey: 'LOCATION_DETAIL_SECTION_COORDINATES_DESCRIPTION',
                                            headerIcon: 'material-symbols:my-location-outline',
                                            fieldNames: LOCATION_COORDINATE_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'LOCATION_DETAIL_PANEL_MAP',
                    col: 12,
                    tabs: [
                        {
                            label: 'LOCATION_DETAIL_MAP_TITLE',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        new CustomField({
                                            name: 'location_detail_map',
                                            label: 'LOCATION_DETAIL_MAP_TITLE',
                                            required: false,
                                            validators: { required: false },
                                            component: LocationDetailMap,
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
