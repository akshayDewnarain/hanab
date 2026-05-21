import type { Component } from 'vue';
import type BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';

/**
 * Row composed of model-driven fields.
 *
 * @property type - Row discriminator.
 * @property fields - Field definitions to render in this row.
 * @property col - Optional grid width (1-12) for this row.
 */
export type FormBuilderFieldsRow = {
    type: 'fields';
    fields: BaseInputField[];
    col?: number;
};

export type FormBuilderTextRow = {
    type: 'text';
    text: string;
    col?: number;
};

export type FormBuilderIconRow = {
    type: 'icon';
    icon: Component;
    text: string;
    col?: number;
};

export type FormBuilderCustomRow = {
    type: 'custom';
    col?: number;
};

export type FormBuilderRow =
    | FormBuilderFieldsRow
    | FormBuilderTextRow
    | FormBuilderIconRow
    | FormBuilderCustomRow;

/**
 * Tab section inside a panel.
 *
 * @property label - Display label (usually i18n key).
 * @property rows - Row definitions rendered in order.
 */
export type FormBuilderTab = {
    label: string;
    rows: FormBuilderRow[];
};

/**
 * Top-level panel in a detail schematic.
 *
 * @property name - Panel title (usually i18n key or short label).
 * @property col - Optional grid width (1-12) for this panel.
 * @property tabs - Tabs rendered inside the panel.
 * @property fillRowHeight - When true, this panel stretches to match the tallest sibling in the same grid row (opt-in; default is content height). Prefer placing side-by-side panels in separate schematic entries (same row) instead of stacking when you need aligned pairs.
 * @property takeFullHeight - When true on a single (non-stacked) panel, the card body expands to fill the row. Ignored for stacked panels (use the last stack slot to absorb slack).
 * @property alwaysShowTabs - When true, show the tab strip even for a single tab.
 */
export type FormBuilderPanel = {
    name: string;
    col?: number;
    fillRowHeight?: boolean;
    takeFullHeight?: boolean;
    alwaysShowTabs?: boolean;
    tabs: FormBuilderTab[];
    stackedPanels?: FormBuilderPanel[];
};

/**
 * Schematic describing how to build a detail or edit form for an entity.
 *
 * @property name - Identifier for this schematic (e.g. tab key or layout slot).
 * @property panels - Optional panel layout definition.
 */
export type FormBuilder = {
    name: string;
    panels?: FormBuilderPanel[];
};
