import type { DropdownWidth } from '@/modules/enums/support/inputs/DropDownWidth.ts';
import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

/**
 * Options for time picker fields.
 *
 * Inherits all properties from {@link BaseInputFieldOptions}.
 *
 * @property align - Alignment of the dropdown panel (interpreted by the time UI).
 * @property position - Placement of the picker relative to the trigger (interpreted by the time UI).
 * @property dropdownWidth - Width token for the dropdown panel.
 * @property stepMinutes - Increment between selectable times, in minutes.
 * @property showSeconds - When true, seconds are selectable or displayed.
 * @property twentyFourHour - When true, use 24-hour clock; when false, use 12-hour clock.
 * @property min - Earliest selectable time (string format agreed with the time UI, e.g. `HH:mm`).
 * @property max - Latest selectable time (string format agreed with the time UI).
 */
export type TimeFieldOptions = BaseInputFieldOptions & {
    align: string;
    position: string;
    dropdownWidth?: DropdownWidth;
    stepMinutes?: number;
    showSeconds?: boolean;
    twentyFourHour?: boolean;
    min?: string;
    max?: string;
};
