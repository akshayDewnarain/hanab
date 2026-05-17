/**
 * Relative width of a field within a responsive form grid.
 *
 * Consumed by {@link import('@/modules/interfaces/support/inputs/BaseInputFieldOptions.ts').BaseInputFieldOptions BaseInputFieldOptions}.width.
 *
 * Enum members:
 * - FULL — entire row width.
 * - HALF — half row.
 * - THIRD — one third.
 * - QUARTER — one quarter.
 * - THIRD_QUARTER — three quarters of the row.
 */
export enum FieldWidth {
    FULL = 'full',
    HALF = 'half',
    THIRD = 'third',
    QUARTER = 'quarter',
    THIRD_QUARTER = 'third-quarter',
}
