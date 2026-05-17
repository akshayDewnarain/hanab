/**
 * Width token for date, time, and select dropdown panels.
 *
 * Consumed by date/time/select field options’ `dropdownWidth` property.
 *
 * Enum members:
 * - FULL — match full trigger or panel width.
 * - HALF — half-width panel.
 * - THIRD — narrow panel (one third).
 * - QUARTER — minimal panel width.
 * - THIRD_QUARTER — wide but not full panel.
 */
export enum DropdownWidth {
    FULL = 'full',
    HALF = 'half',
    THIRD = 'third',
    QUARTER = 'quarter',
    THIRD_QUARTER = 'third-quarter',
}
