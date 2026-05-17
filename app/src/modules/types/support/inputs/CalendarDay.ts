import type { Dayjs } from 'dayjs';

/**
 * One cell in a calendar grid used by date-picker UIs.
 *
 * @property dayNumber - Day of month (1–31) for display.
 * @property date - Full calendar date for selection logic.
 * @property selectable - When false, the day is disabled (e.g. outside min/max range).
 */
export type CalendarDay = {
    dayNumber: number;
    date: Dayjs;
    selectable: boolean;
};
