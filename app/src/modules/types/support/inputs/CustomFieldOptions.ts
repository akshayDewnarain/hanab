import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
import type { Component } from 'vue';

/**
 * Options for embedding an arbitrary Vue component as a form field.
 *
 * Used when built-in field types are insufficient; the host form merges props, wires v-model, and may exclude from payload.
 *
 * Inherits all properties from {@link BaseInputFieldOptions}.
 *
 * @property component - Vue component rendered for this field.
 * @property props - Static props passed to the component before `resolveExtraProps`.
 * @property resolveExtraProps - Lazy props derived from the model instance (e.g. catalogs).
 * @property valueProp - Name of the prop used as the model value (v-model target).
 * @property updateEvent - Event name emitted when the value changes (v-model listener).
 * @property events - Additional component listeners mapped by event name.
 * @property excludeFromForm - When true, omit from formData, validation, and submit payloads.
 * @property includeInputFieldNamesForPayload - Other `inputFields()` names to include in payload/schema when this block is present.
 */
export type CustomFieldOptions = BaseInputFieldOptions & {
    component: Component;
    props?: Record<string, unknown>;
    resolveExtraProps?: (instance: unknown) => Record<string, unknown>;
    valueProp?: string;
    updateEvent?: string;
    events?: Record<string, (...args: unknown[]) => unknown>;
    excludeFromForm?: boolean;
    includeInputFieldNamesForPayload?: string[];
};
