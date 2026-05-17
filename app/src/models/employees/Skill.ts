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
import type { SkillRecord } from '@/modules/types/support/models/SkillRecord.ts';
import UserDetailFieldsSection from '@/components/forms/custom/UserDetailFieldsSection.vue';
import SkillDetailLabelPreview from '@/components/forms/custom/SkillDetailLabelPreview.vue';
import SkillDetailHelpPanel from '@/components/forms/custom/SkillDetailHelpPanel.vue';

const HEX_COLOR_PATTERN = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;

const SKILL_IDENTITY_FIELD_NAMES = ['is_active', 'name', 'code', 'category'] as const;
const SKILL_DESCRIPTION_FIELD_NAMES = ['description'] as const;
const SKILL_LABEL_COLOR_FIELD_NAMES = [
    'label_background_color',
    'label_text_color',
    'label_border_color',
] as const;

function skillDetailEmbeddedFields(model: Skill, names: readonly string[]): BaseInputField[] {
    const map = new Map(model.inputFields().map((f) => [f.options.name, f]));
    return names.map((n) => map.get(n)).filter((f): f is BaseInputField => f != null);
}

function skillDetailSectionField(options: {
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
            embeddedFields: skillDetailEmbeddedFields(inst as Skill, options.fieldNames),
        }),
    });
}

function skillDetailLabelPreviewField(): CustomField {
    return new CustomField({
        name: 'skill_detail_label_preview',
        label: 'SKILL_DETAIL_TAB_PREVIEW',
        required: false,
        validators: { required: false },
        component: SkillDetailLabelPreview,
        excludeFromForm: true,
    });
}

function skillDetailHelpField(): CustomField {
    return new CustomField({
        name: 'skill_detail_help',
        label: 'SKILL_DETAIL_HELP_TITLE',
        required: false,
        validators: { required: false },
        component: SkillDetailHelpPanel,
        excludeFromForm: true,
    });
}

/**
 * API model for the `skills` resource (CRUD, list builder, forms).
 */
export default class Skill extends Model<SkillRecord> {
    constructor() {
        super('skills', 'skill');
    }

    override columns(): TableColumn[] {
        return [
            new TableColumn({
                name: 'name',
                label: 'GENERAL_SKILL',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
            }),
            new TableColumn({
                name: 'code',
                label: 'SKILL_CODE',
                sortable: true,
                filterable: true,
                visible: true,
                quickFilter: true,
                width: 'fit',
            }),
            new TableColumn({
                name: 'label_preview',
                label: 'SKILL_LABEL_PREVIEW',
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
                name: 'label_background_color',
                label: 'SKILL_LABEL_BACKGROUND_COLOR',
                sortable: false,
                filterable: false,
                visible: false,
                quickFilter: false,
                width: 'fit',
            }),
            new TableColumn({
                name: 'label_text_color',
                label: 'SKILL_LABEL_TEXT_COLOR',
                sortable: false,
                filterable: false,
                visible: false,
                quickFilter: false,
                width: 'fit',
            }),
            new TableColumn({
                name: 'label_border_color',
                label: 'SKILL_LABEL_BORDER_COLOR',
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
                label: 'GENERAL_SKILL',
                required: true,
                placeholder: 'GENERAL_NAME_PLACEHOLDER',
                validators: { required: true, maxLength: 255 },
            }),
            new InputField({
                name: 'code',
                label: 'SKILL_CODE',
                required: false,
                placeholder: 'SKILL_CODE_PLACEHOLDER',
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
                    { label: 'SKILL_CATEGORY_TECHNICAL', value: 'technical' },
                    { label: 'SKILL_CATEGORY_SOFT_SKILL', value: 'soft_skill' },
                    { label: 'SKILL_CATEGORY_DOMAIN', value: 'domain' },
                    { label: 'SKILL_CATEGORY_SAFETY', value: 'safety' },
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
                label: 'SKILL_LABEL_BACKGROUND_COLOR',
                required: false,
                placeholder: 'SKILL_COLOR_PLACEHOLDER',
                validators: { pattern: HEX_COLOR_PATTERN },
                width: FieldWidth.THIRD,
            }),
            new ColorField({
                name: 'label_text_color',
                label: 'SKILL_LABEL_TEXT_COLOR',
                required: false,
                placeholder: 'SKILL_COLOR_PLACEHOLDER',
                validators: { pattern: HEX_COLOR_PATTERN },
                width: FieldWidth.THIRD,
            }),
            new ColorField({
                name: 'label_border_color',
                label: 'SKILL_LABEL_BORDER_COLOR',
                required: false,
                placeholder: 'SKILL_COLOR_PLACEHOLDER',
                validators: { pattern: HEX_COLOR_PATTERN },
                width: FieldWidth.THIRD,
            }),
            new ToggleSwitchField({
                name: 'is_active',
                label: 'GENERAL_ACTIVE',
                hintKey: 'SKILL_ACTIVE_HINT',
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
                    name: 'SKILL_DETAIL_PANEL_HELP',
                    col: 12,
                    tabs: [
                        {
                            label: 'GENERAL_SKILL',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [skillDetailHelpField()],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'SKILL_DETAIL_PANEL_IDENTITY',
                    col: 6,
                    tabs: [
                        {
                            label: 'SKILL_DETAIL_TAB_IDENTITY',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        skillDetailSectionField({
                                            name: 'skill_identity_section',
                                            label: 'SKILL_DETAIL_TAB_IDENTITY',
                                            descriptionKey: 'SKILL_DETAIL_SECTION_IDENTITY_DESCRIPTION',
                                            headerIcon: 'material-symbols:psychology-alt-outline',
                                            fieldNames: SKILL_IDENTITY_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'SKILL_DETAIL_PANEL_DESCRIPTION',
                    col: 6,
                    tabs: [
                        {
                            label: 'SKILL_DETAIL_TAB_DESCRIPTION',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        skillDetailSectionField({
                                            name: 'skill_description_section',
                                            label: 'SKILL_DETAIL_TAB_DESCRIPTION',
                                            descriptionKey: 'SKILL_DETAIL_SECTION_DESCRIPTION_DESCRIPTION',
                                            headerIcon: 'material-symbols:description-outline',
                                            fieldNames: SKILL_DESCRIPTION_FIELD_NAMES,
                                        }),
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'SKILL_DETAIL_PANEL_PREVIEW',
                    col: 6,
                    tabs: [
                        {
                            label: 'SKILL_DETAIL_TAB_PREVIEW',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [skillDetailLabelPreviewField()],
                                },
                            ],
                        },
                    ],
                },
                {
                    name: 'SKILL_DETAIL_PANEL_LABEL_COLORS',
                    col: 6,
                    tabs: [
                        {
                            label: 'SKILL_DETAIL_TAB_LABEL_COLORS',
                            rows: [
                                {
                                    type: 'fields',
                                    col: 12,
                                    fields: [
                                        skillDetailSectionField({
                                            name: 'skill_label_colors_section',
                                            label: 'SKILL_DETAIL_TAB_LABEL_COLORS',
                                            descriptionKey: 'SKILL_DETAIL_SECTION_LABEL_COLORS_DESCRIPTION',
                                            headerIcon: 'material-symbols:palette-outline',
                                            fieldNames: SKILL_LABEL_COLOR_FIELD_NAMES,
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
