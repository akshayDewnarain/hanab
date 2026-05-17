import Model from '@/modules/models/support/Model.ts';
import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
import { InputField } from '@/modules/models/support/forms/InputField.ts';
import { SelectField } from '@/modules/models/support/forms/SelectField.ts';
import { ToggleSwitchField } from '@/modules/models/support/forms/ToggleSwitchField.ts';
import { ColorField } from '@/modules/models/support/forms/ColorField.ts';
import { CustomField } from '@/modules/models/support/forms/CustomField.ts';
import TableColumn from '@/modules/models/support/list-views/TableColumn.ts';
import { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';
import { TableColumnComponentEnum } from '@/modules/enums/support/list-views/TableColumnComponentEnum.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import type { CertificateRecord } from '@/modules/types/support/models/CertificateRecord.ts';
import UserDetailFieldsSection from '@/components/forms/custom/UserDetailFieldsSection.vue';
import CertificateDetailLabelPreview from '@/components/forms/custom/CertificateDetailLabelPreview.vue';
import CertificateDetailHelpPanel from '@/components/forms/custom/CertificateDetailHelpPanel.vue';

const HEX_COLOR_PATTERN = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;

const CERTIFICATE_IDENTITY_FIELD_NAMES = [
    'is_active',
    'name',
    'code',
    'category',
    'requires_expiry_date',
] as const;
const CERTIFICATE_DESCRIPTION_FIELD_NAMES = ['description'] as const;
const CERTIFICATE_LABEL_COLOR_FIELD_NAMES = [
    'label_background_color',
    'label_text_color',
    'label_border_color',
] as const;

function certificateDetailEmbeddedFields(
    model: Certificate,
    names: readonly string[],
): BaseInputField[] {
    const map = new Map(model.inputFields().map((f) => [f.options.name, f]));
    return names.map((n) => map.get(n)).filter((f): f is BaseInputField => f != null);
}

function certificateDetailSectionField(options: {
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
            embeddedFields: certificateDetailEmbeddedFields(inst as Certificate, options.fieldNames),
        }),
    });
}

function certificateDetailLabelPreviewField(): CustomField {
    return new CustomField({
        name: 'certificate_detail_label_preview',
        label: 'CERTIFICATE_DETAIL_TAB_PREVIEW',
        required: false,
        validators: { required: false },
        component: CertificateDetailLabelPreview,
        excludeFromForm: true,
    });
}

function certificateDetailHelpField(): CustomField {
    return new CustomField({
        name: 'certificate_detail_help',
        label: 'CERTIFICATE_DETAIL_HELP_TITLE',
        required: false,
        validators: { required: false },
        component: CertificateDetailHelpPanel,
        excludeFromForm: true,
    });
}

/**
 * API model for the `certificates` resource (CRUD, list builder, forms).
 */
export default class Certificate extends Model<CertificateRecord> {
    constructor() {
        super('certificates', 'certificate');
    }

    override columns(): TableColumn[] {
        return [
            new TableColumn({
                name: 'name',
                label: 'GENERAL_CERTIFICATE',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
            }),
            new TableColumn({
                name: 'code',
                label: 'CERTIFICATE_CODE',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
                width: 'fit',
            }),
            new TableColumn({
                name: 'label_preview',
                label: 'CERTIFICATE_LABEL_PREVIEW',
                sortable: false,
                filterable: false,
                visible: true,
                quickFilter: false,
                component: { type: TableColumnComponentEnum.SKILL_LABEL },
            }),
            new TableColumn({
                name: 'category',
                label: 'GENERAL_CATEGORY',
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
                visible: false,
                quickFilter: true,
            }),
            new TableColumn({
                name: 'requires_expiry_date',
                label: 'CERTIFICATE_REQUIRES_EXPIRY_DATE',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: false,
                component: { type: TableColumnComponentEnum.BOOLEAN },
                width: 'fit',
            }),
            new TableColumn({
                name: 'label_background_color',
                label: 'CERTIFICATE_LABEL_BACKGROUND_COLOR',
                sortable: false,
                filterable: false,
                visible: false,
                quickFilter: false,
                width: 'fit',
            }),
            new TableColumn({
                name: 'label_text_color',
                label: 'CERTIFICATE_LABEL_TEXT_COLOR',
                sortable: false,
                filterable: false,
                visible: false,
                quickFilter: false,
                width: 'fit',
            }),
            new TableColumn({
                name: 'label_border_color',
                label: 'CERTIFICATE_LABEL_BORDER_COLOR',
                sortable: false,
                filterable: false,
                visible: false,
                quickFilter: false,
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
            ...super.columns(),
        ];
    }

    override inputFields(): BaseInputField[] {
        return [
            new InputField({
                name: 'name',
                label: 'GENERAL_CERTIFICATE',
                required: true,
                placeholder: 'GENERAL_NAME_PLACEHOLDER',
                validators: { required: true, maxLength: 255 },
            }),
            new InputField({
                name: 'code',
                label: 'CERTIFICATE_CODE',
                required: false,
                placeholder: 'CERTIFICATE_CODE_PLACEHOLDER',
                validators: { maxLength: 32 },
                width: FieldWidth.HALF,
            }),
            new SelectField({
                name: 'category',
                label: 'GENERAL_CATEGORY',
                required: true,
                validators: { required: true },
                placeholder: 'GENERAL_CATEGORY_PLACEHOLDER',
                position: 'bottom',
                align: 'right',
                translateOptionLabels: true,
                options: [
                    { label: 'CERTIFICATE_CATEGORY_SAFETY', value: 'safety' },
                    { label: 'CERTIFICATE_CATEGORY_TECHNICAL', value: 'technical' },
                    { label: 'CERTIFICATE_CATEGORY_COMPLIANCE', value: 'compliance' },
                ],
                width: FieldWidth.HALF,
            }),
            new InputField({
                name: 'description',
                label: 'GENERAL_DESCRIPTION',
                required: false,
                placeholder: 'GENERAL_DESCRIPTION_PLACEHOLDER',
            }),
            new ColorField({
                name: 'label_background_color',
                label: 'CERTIFICATE_LABEL_BACKGROUND_COLOR',
                required: false,
                placeholder: 'CERTIFICATE_COLOR_PLACEHOLDER',
                validators: { pattern: HEX_COLOR_PATTERN },
                width: FieldWidth.THIRD,
            }),
            new ColorField({
                name: 'label_text_color',
                label: 'CERTIFICATE_LABEL_TEXT_COLOR',
                required: false,
                placeholder: 'CERTIFICATE_COLOR_PLACEHOLDER',
                validators: { pattern: HEX_COLOR_PATTERN },
                width: FieldWidth.THIRD,
            }),
            new ColorField({
                name: 'label_border_color',
                label: 'CERTIFICATE_LABEL_BORDER_COLOR',
                required: false,
                placeholder: 'CERTIFICATE_COLOR_PLACEHOLDER',
                validators: { pattern: HEX_COLOR_PATTERN },
                width: FieldWidth.THIRD,
            }),
            new ToggleSwitchField({
                name: 'requires_expiry_date',
                label: 'CERTIFICATE_REQUIRES_EXPIRY_DATE',
                required: true,
                defaultValue: false,
                width: FieldWidth.HALF,
                validators: { required: true },
            }),
            new ToggleSwitchField({
                name: 'is_active',
                label: 'GENERAL_ACTIVE',
                hintKey: 'CERTIFICATE_ACTIVE_HINT',
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
                    name: 'CERTIFICATE_DETAIL_PANEL_HELP',
                    col: 12,
                    tabs: [
                        {
                            label: 'GENERAL_CERTIFICATE',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [certificateDetailHelpField()],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'CERTIFICATE_DETAIL_PANEL_IDENTITY',
                    col: 6,
                    tabs: [
                        {
                            label: 'CERTIFICATE_DETAIL_TAB_IDENTITY',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        certificateDetailSectionField({
                                            name: 'certificate_identity_section',
                                            label: 'CERTIFICATE_DETAIL_TAB_IDENTITY',
                                            descriptionKey: 'CERTIFICATE_DETAIL_SECTION_IDENTITY_DESCRIPTION',
                                            headerIcon: 'material-symbols:verified-outline-rounded',
                                            fieldNames: CERTIFICATE_IDENTITY_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'CERTIFICATE_DETAIL_PANEL_DESCRIPTION',
                    col: 6,
                    tabs: [
                        {
                            label: 'CERTIFICATE_DETAIL_TAB_DESCRIPTION',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        certificateDetailSectionField({
                                            name: 'certificate_description_section',
                                            label: 'CERTIFICATE_DETAIL_TAB_DESCRIPTION',
                                            descriptionKey: 'CERTIFICATE_DETAIL_SECTION_DESCRIPTION_DESCRIPTION',
                                            headerIcon: 'material-symbols:description-outline',
                                            fieldNames: CERTIFICATE_DESCRIPTION_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'CERTIFICATE_DETAIL_PANEL_PREVIEW',
                    col: 6,
                    tabs: [
                        {
                            label: 'CERTIFICATE_DETAIL_TAB_PREVIEW',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [certificateDetailLabelPreviewField()],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'CERTIFICATE_DETAIL_PANEL_LABEL_COLORS',
                    col: 6,
                    tabs: [
                        {
                            label: 'CERTIFICATE_DETAIL_TAB_LABEL_COLORS',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        certificateDetailSectionField({
                                            name: 'certificate_label_colors_section',
                                            label: 'CERTIFICATE_DETAIL_TAB_LABEL_COLORS',
                                            descriptionKey: 'CERTIFICATE_DETAIL_SECTION_LABEL_COLORS_DESCRIPTION',
                                            headerIcon: 'material-symbols:palette-outline',
                                            fieldNames: CERTIFICATE_LABEL_COLOR_FIELD_NAMES,
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
