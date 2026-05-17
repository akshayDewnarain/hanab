import type { DropdownWidth } from '@/modules/enums/support/inputs/DropDownWidth.ts';
import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

/**
 * Options for date picker fields (calendar popover / dropdown).
 *
 * Inherits all properties from {@link BaseInputFieldOptions}.
 *
 * @property position - Placement of the calendar relative to the trigger (interpreted by the date UI).
 * @property align - Alignment of the dropdown panel (interpreted by the date UI).
 * @property dropdownWidth - Width token for the dropdown panel.
 * @property disablePast - When true, dates before today cannot be selected.
 */
export type DateFieldOptions = BaseInputFieldOptions & {
    position: string;
    align: string;
    dropdownWidth?: DropdownWidth;
    disablePast?: boolean;
};
