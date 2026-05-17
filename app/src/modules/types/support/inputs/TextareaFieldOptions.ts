import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

/**
 * Options for a multi-line textarea input.
 *
 * Inherits all properties from {@link BaseInputFieldOptions}.
 *
 * @property rows - Initial visible row count when fixed height is used.
 * @property minRows - Minimum rows when auto-growing.
 * @property maxRows - Maximum rows when auto-growing.
 * @property autoResize - When true, height follows content within min/max rows.
 * @property resize - CSS resize behavior for the textarea control.
 * @property minHeight - Minimum height (CSS length or number interpreted by the UI layer).
 * @property maxHeight - Maximum height (CSS length or number interpreted by the UI layer).
 * @property monospace - When true, prefer monospace typography (e.g. code or IDs).
 * @property maxLength - Hard limit on character count when enforced by the UI.
 */
export type TextareaFieldOptions = BaseInputFieldOptions & {
    rows?: number;
    minRows?: number;
    maxRows?: number;
    autoResize?: boolean;
    resize?: 'none' | 'vertical' | 'horizontal' | 'both';
    minHeight?: string | number;
    maxHeight?: string | number;
    monospace?: boolean;
    maxLength?: number;
};
