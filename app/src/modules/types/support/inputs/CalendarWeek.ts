import type { CalendarDay } from '@/modules/types/support/inputs/CalendarDay.ts';

/**
 * One ISO-style week row in a month grid.
 *
 * @property weekNumber - Week index shown in the calendar chrome.
 * @property days - Seven (or partial) {@link CalendarDay} entries for this row.
 */
export type CalendarWeek = {
    weekNumber: number;
    days: CalendarDay[];
};
