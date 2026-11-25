import Model from '@/modules/models/support/Model.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import { InputField } from '@/modules/models/support/forms/InputField.ts';
import { ColorField } from '@/modules/models/support/forms/ColorField.ts';
import { NumberField } from '@/modules/models/support/forms/NumberField.ts';
import { ToggleSwitchField } from '@/modules/models/support/forms/ToggleSwitchField.ts';
import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import { CustomField } from '@/modules/models/support/forms/CustomField.ts';
import { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import UserDetailFieldsSection from '@/components/forms/custom/UserDetailFieldsSection.vue';
import OrganizationLevelDetailHelpPanel from '@/components/forms/custom/OrganizationLevelDetailHelpPanel.vue';
import OrganizationLevelDetailPreview from '@/components/forms/custom/OrganizationLevelDetailPreview.vue';

const LEVEL_IDENTITY_FIELD_NAMES = ['name', 'is_active'] as const;
const LEVEL_APPEARANCE_FIELD_NAMES = ['sort_order', 'color'] as const;

function levelDetailEmbeddedFields(model: OrganizationLevel, names: readonly string[]): BaseInputField[] {
    const map = new Map(model.inputFields().map((field) => [field.options.name, field]));
    return names.map((name) => map.get(name)).filter((field): field is BaseInputField => field != null);
}

function levelDetailSectionField(options: {
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
            embeddedFields: levelDetailEmbeddedFields(instance as OrganizationLevel, options.fieldNames),
        }),
    });
}

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
                hintKey: 'LEVEL_ACTIVE_HINT',
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
                    name: 'LEVEL_DETAIL_PANEL_HELP',
                    col: 12,
                    tabs: [
                        {
                            label: 'ORGANIZATION_LEVEL',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        new CustomField({
                                            name: 'level_detail_help',
                                            label: 'LEVEL_DETAIL_HELP_TITLE',
                                            required: false,
                                            validators: { required: false },
                                            component: OrganizationLevelDetailHelpPanel,
                                            excludeFromForm: true,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'LEVEL_DETAIL_PANEL_IDENTITY',
                    col: 6,
                    tabs: [
                        {
                            label: 'LEVEL_DETAIL_TAB_IDENTITY',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        levelDetailSectionField({
                                            name: 'level_identity_section',
                                            label: 'LEVEL_DETAIL_TAB_IDENTITY',
                                            descriptionKey: 'LEVEL_DETAIL_SECTION_IDENTITY_DESCRIPTION',
                                            headerIcon: 'material-symbols:layers-outline',
                                            fieldNames: LEVEL_IDENTITY_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'LEVEL_DETAIL_PANEL_APPEARANCE',
                    col: 6,
                    tabs: [
                        {
                            label: 'LEVEL_DETAIL_TAB_APPEARANCE',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        levelDetailSectionField({
                                            name: 'level_appearance_section',
                                            label: 'LEVEL_DETAIL_TAB_APPEARANCE',
                                            descriptionKey: 'LEVEL_DETAIL_SECTION_APPEARANCE_DESCRIPTION',
                                            headerIcon: 'material-symbols:palette-outline',
                                            fieldNames: LEVEL_APPEARANCE_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'LEVEL_DETAIL_PANEL_PREVIEW',
                    col: 12,
                    tabs: [
                        {
                            label: 'LEVEL_DETAIL_PREVIEW_TITLE',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        new CustomField({
                                            name: 'level_detail_preview',
                                            label: 'LEVEL_DETAIL_PREVIEW_TITLE',
                                            required: false,
                                            validators: { required: false },
                                            component: OrganizationLevelDetailPreview,
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
