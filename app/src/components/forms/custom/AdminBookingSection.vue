<template>
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center gap-2">
            <Icon class="h-4 w-4 text-[var(--color-primary)]" icon="material-symbols:event-available" />
            <span class="text-sm font-semibold text-gray-800">{{ t(options.label) }}</span>
        </div>

        <div
            v-if="!scopedLocationId"
            class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700"
        >
            {{ t('BOOKING_CALENDAR_SELECT_LOCATION') }}
        </div>
        <div v-else-if="!activityId" class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
            {{ t('BOOKING_STEP_DATE_REQUIRE_PREVIOUS') }}
        </div>
        <div v-else class="flex flex-col items-center space-y-3">
            <div class="w-full max-w-[600px] space-y-3">
                <div class="flex items-center justify-between rounded bg-[var(--color-background)] p-2">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded text-white hover:bg-[var(--color-highlight-dark)]"
                            @click="prevMonth"
                        >
                            <Icon icon="material-symbols:arrow-back-rounded" />
                        </button>
                        <span class="text-sm font-semibold text-white">{{ headerLabel }}</span>
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded text-white hover:bg-[var(--color-highlight-dark)]"
                            @click="nextMonth"
                        >
                            <Icon icon="material-symbols:arrow-forward-rounded" />
                        </button>
                    </div>
                </div>

                <div v-if="isLoading" class="text-sm text-gray-500">{{ t('FORM_LOADING') }}...</div>
                <div v-else-if="errorMessage" class="text-sm text-red-500">{{ errorMessage }}</div>
                <template v-else>
                    <div class="grid grid-cols-7 text-center text-xs font-medium text-gray-500">
                        <div v-for="dayLabel in weekdays" :key="dayLabel" class="py-1">{{ t(dayLabel) }}</div>
                    </div>
                    <div class="grid grid-cols-7 gap-px rounded border border-gray-200 bg-gray-100">
                        <button
                            v-for="day in calendarCells"
                            :key="day.dateKey"
                            type="button"
                            :disabled="!day.bookable || day.fullyBookedBoats"
                            class="aspect-square p-1 text-sm transition-colors"
                            :class="dayCellClass(day)"
                            @click="pickDate(day)"
                        >
                            {{ day.dayNumber }}
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                        <div class="flex items-center gap-1">
                            <span class="inline-block h-3 w-3 rounded bg-[var(--color-primary)]"></span>
                            {{ t('BOOKING_CALENDAR_BOOKABLE') }}
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="inline-block h-3 w-3 rounded bg-[var(--color-bg-blocked)]"></span>
                            {{ t('BOOKING_CALENDAR_NOT_BOOKABLE') }}
                        </div>
                        <div v-if="calendarShowsBoatCapacityLegend" class="flex items-center gap-1">
                            <span class="inline-block h-3 w-3 shrink-0 rounded border border-red-100 bg-red-50"></span>
                            {{ t('BOOKING_CALENDAR_FULLY_BOOKED_BOATS_DAY') }}
                        </div>
                    </div>
                </template>
            </div>

            <div v-if="selectedDate" class="w-full max-w-[600px] rounded border border-gray-200 bg-gray-50 p-3">
                <div class="mb-2 text-sm font-semibold text-gray-800">
                    {{ dayjs(selectedDate).format('dddd, DD MMMM YYYY') }}
                </div>
                <div v-if="timeslotsLoading" class="text-sm text-gray-500">{{ t('FORM_LOADING') }}...</div>
                <div v-else-if="timeslotsError" class="text-sm text-red-500">{{ timeslotsError }}</div>
                <div v-else-if="timeslots.length === 0" class="text-sm text-gray-500">
                    {{ t('BOOKING_CALENDAR_NO_TIMESLOTS') }}
                </div>
                <div v-else class="space-y-2">
                    <div
                        v-if="resolvingBoatAssignment"
                        class="rounded border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600"
                    >
                        {{ t('BOOKING_ADMIN_RESOLVING_BOAT') }}
                    </div>
                    <div
                        v-for="slot in timeslots"
                        :key="slot.id"
                        class="flex items-center justify-between gap-3 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 transition-colors duration-200 hover:bg-gray-100"
                    >
                        <div class="flex flex-1 items-start gap-2">
                            <Icon
                                class="mt-1 h-5 w-5 shrink-0 text-gray-500"
                                icon="material-symbols:schedule-outline"
                            />
                            <span class="text-lg font-semibold leading-snug text-[var(--color-primary)]">
                                {{
                                    formatTimeslotHeading({
                                        label: slot.label,
                                        start_time: slot.start_time,
                                        end_time: slot.end_time,
                                        emptyFallback: t('BOOKING_CALENDAR_TIMESLOT'),
                                    })
                                }}
                            </span>
                        </div>
                        <div
                            class="flex max-w-[300px] shrink-0 items-center gap-1"
                            :class="{ 'min-w-0 flex-1 justify-end': hidePickUi(slot) }"
                        >
                            <p
                                v-if="slotHintUi(slot).message"
                                :class="slotHintUi(slot).tone === 'error' ? 'text-red-600' : 'text-gray-500'"
                                class="text-right text-xs leading-snug"
                            >
                                {{ slotHintUi(slot).message }}
                            </p>
                            <template v-if="!hidePickUi(slot)">
                                <div
                                    v-if="guests != null && getSlotPrice(slot) !== null"
                                    class="flex items-center whitespace-nowrap rounded-sm border border-green-300 bg-green-200 px-1 text-base font-semibold text-green-700 shadow-sm"
                                >
                                    €{{ formatSlotPrice(getSlotPrice(slot) || 0) }}
                                </div>
                                <button
                                    type="button"
                                    :class="[
                                        'whitespace-nowrap rounded-md px-4 py-2 text-sm font-medium text-white transition-colors duration-200',
                                        selectedTimeSlotId === slot.id
                                            ? 'bg-[var(--color-highlight-dark)] ring-2 ring-[var(--color-primary)]'
                                            : slotHintUi(slot).disabled
                                              ? 'cursor-not-allowed bg-gray-400'
                                              : 'cursor-pointer bg-[var(--color-primary)] hover:bg-[var(--color-highlight-dark)]',
                                    ]"
                                    :disabled="slotHintUi(slot).disabled || resolvingBoatAssignment"
                                    @click="pickTimeSlot(slot)"
                                >
                                    {{ t('BOOKING_ADMIN_SELECT_TIMESLOT') }}
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, ref, watch } from 'vue';
    import dayjs from 'dayjs';
    import publicHttp from '@/utils/public-http';
    import http from '@/utils/http';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { useTimeslots } from '@/composables/useTimeslots';
    import { useGlobalScopeService } from '@/composables/useGlobalScopeService';
    import { useBookingSettings } from '@/composables/useBookingSettings';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
    import type { BookingSettings } from '@/modules/types/BookingTypes';
    import {
        calendarGetTimeslotPrice,
        formatTimeslotHeading,
        hideTimeslotPriceAndPick,
        slotPickHint,
        type CalendarTimeslotLike,
    } from '@/utils/calendarTimeslotPresentation';
    import { useToastService } from '@/composables/useToastService';

    type ApiCalendarDay = { date: string; bookable: boolean; fully_booked_boats?: boolean };
    type CalendarCell = {
        dateKey: string;
        dayNumber: number;
        bookable: boolean;
        fullyBookedBoats: boolean;
        isCurrentMonth: boolean;
    };

    type SlotRow = CalendarTimeslotLike & {
        label?: string | null;
        start_time: string;
        end_time: string;
        pricing_table_id: number;
    };

    const props = withDefaults(
        defineProps<{
            modelValue?: unknown;
            options: BaseInputFieldOptions;
            formData?: Record<string, unknown>;
            setFieldValue?: (name: string, value: unknown) => void;
            getFieldValue?: (name: string) => unknown;
            /** When editing a reservation, exclude it from availability checks (same as boat hold semantics). */
            reservationId?: number | null;
        }>(),
        { reservationId: null },
    );

    const { t } = useI18n();
    const toastService = useToastService();
    const { fetchForDate } = useTimeslots();
    const { fetchForLocation } = useBookingSettings();
    const globalScope = useGlobalScopeService();

    const currentMonth = ref(dayjs().startOf('month'));
    const isLoading = ref(false);
    const errorMessage = ref<string | null>(null);
    const daysMap = ref<Map<string, ApiCalendarDay>>(new Map());
    const selectedDate = ref<string | null>(null);
    const timeslotsLoading = ref(false);
    const timeslotsError = ref<string | null>(null);
    const timeslots = ref<SlotRow[]>([]);
    const bookingSettings = ref<BookingSettings | null>(null);
    const resolvingBoatAssignment = ref(false);
    let boatResolveSeq = 0;

    const scopedLocationId = computed((): number | null => {
        const id = globalScope.locationRef.value?.id;
        const n = Number(id);
        return Number.isFinite(n) && n > 0 ? n : null;
    });

    const activityId = computed<number | null>(() => {
        const v = props.getFieldValue?.('activity_id') ?? props.formData?.activity_id;
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    });

    const guests = computed<number | null>(() => {
        const v = props.getFieldValue?.('guests') ?? props.formData?.guests;
        const n = Number(v);
        return Number.isFinite(n) && n >= 1 ? n : null;
    });

    const childrenCount = computed(() => {
        const v = props.getFieldValue?.('children') ?? props.formData?.children;
        const n = Number(v);
        return Number.isFinite(n) && n >= 0 ? Math.floor(n) : 0;
    });

    const selectedTimeSlotId = computed<number | null>(() => {
        const v = props.getFieldValue?.('time_slot_id') ?? props.formData?.time_slot_id;
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
    });

    const calendarShowsBoatCapacityLegend = computed(() =>
        Array.from(daysMap.value.values()).some((d) => d.fully_booked_boats === true),
    );

    const weekdays = [
        'MONDAY_ABBREVIATION',
        'TUESDAY_ABBREVIATION',
        'WEDNESDAY_ABBREVIATION',
        'THURSDAY_ABBREVIATION',
        'FRIDAY_ABBREVIATION',
        'SATURDAY_ABBREVIATION',
        'SUNDAY_ABBREVIATION',
    ];

    const headerLabel = computed(() => currentMonth.value.format('MMMM YYYY'));

    const calendarCells = computed<CalendarCell[]>(() => {
        const start = currentMonth.value.startOf('month');
        const offset = (start.day() + 6) % 7;
        let cursor = start.subtract(offset, 'day');
        const out: CalendarCell[] = [];
        for (let i = 0; i < 42; i++) {
            const dateKey = cursor.format('YYYY-MM-DD');
            const isCurrentMonth = cursor.month() === currentMonth.value.month();
            const row = daysMap.value.get(dateKey);
            out.push({
                dateKey,
                dayNumber: cursor.date(),
                isCurrentMonth,
                bookable: isCurrentMonth ? (row?.bookable ?? false) : false,
                fullyBookedBoats: isCurrentMonth ? row?.fully_booked_boats === true : false,
            });
            cursor = cursor.add(1, 'day');
        }
        return out;
    });

    function dayCellClass(day: CalendarCell): string {
        if (!day.isCurrentMonth) return 'bg-gray-50 text-gray-300 cursor-not-allowed';
        if (day.fullyBookedBoats && day.bookable) {
            return 'bg-red-50/90 border border-red-100 text-red-900 cursor-not-allowed';
        }
        if (!day.bookable) return 'bg-[var(--color-bg-blocked)] text-[var(--color-text-blocked)] cursor-not-allowed';
        if (selectedDate.value === day.dateKey) return 'bg-[var(--color-primary)] text-white';
        return 'bg-white text-gray-800 hover:bg-[var(--color-primary)] hover:text-white cursor-pointer';
    }

    async function loadBookingSettings(): Promise<void> {
        const lid = scopedLocationId.value;
        if (!lid) {
            bookingSettings.value = null;
            return;
        }
        bookingSettings.value = await fetchForLocation(lid);
    }

    async function fetchCalendar(): Promise<void> {
        if (!scopedLocationId.value) {
            daysMap.value = new Map();
            return;
        }
        isLoading.value = true;
        errorMessage.value = null;
        try {
            const params: Record<string, number> = {
                year: currentMonth.value.year(),
                month: currentMonth.value.month() + 1,
            };
            if (activityId.value) {
                params.activity_id = activityId.value;
            }
            if (guests.value != null) {
                params.guests = guests.value;
            }
            const response = await publicHttp.get(`locations/${scopedLocationId.value}/calendar`, { params });
            const rows = (response.data?.data?.days ?? response.data?.days ?? []) as ApiCalendarDay[];
            const map = new Map<string, ApiCalendarDay>();
            for (const row of rows) map.set(row.date, row);
            daysMap.value = map;
        } catch {
            errorMessage.value = t('BOOKING_CALENDAR_FETCH_ERROR');
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchTimeslots(dateKey: string): Promise<void> {
        timeslotsLoading.value = true;
        timeslotsError.value = null;
        try {
            const rows = await fetchForDate(dateKey, scopedLocationId.value, activityId.value, guests.value);
            timeslots.value = (rows as unknown as SlotRow[]) ?? [];
        } catch {
            timeslots.value = [];
            timeslotsError.value = t('BOOKING_CALENDAR_TIMESLOTS_FETCH_ERROR');
        } finally {
            timeslotsLoading.value = false;
        }
    }

    function hidePickUi(slot: SlotRow): boolean {
        return hideTimeslotPriceAndPick(slot, guests.value);
    }

    function slotHintUi(slot: SlotRow) {
        return slotPickHint(slot, guests.value, false, t);
    }

    function getSlotPrice(slot: SlotRow): number | null {
        return calendarGetTimeslotPrice(slot, guests.value, childrenCount.value, bookingSettings.value);
    }

    function formatSlotPrice(price: number | string): string {
        const numPrice = typeof price === 'number' ? price : parseFloat(String(price));
        return new Intl.NumberFormat('nl-NL', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number.isFinite(numPrice) ? numPrice : 0);
    }

    function pickDate(day: CalendarCell): void {
        if (!day.bookable || !day.isCurrentMonth || day.fullyBookedBoats) return;
        selectedDate.value = day.dateKey;
        props.setFieldValue?.('service_date', day.dateKey);
        props.setFieldValue?.('time_slot_id', null);
        props.setFieldValue?.('boat_id', null);
        props.setFieldValue?.('boat_category_id', null);
        fetchTimeslots(day.dateKey);
    }

    async function resolveBoatAssignmentFromForm(): Promise<boolean> {
        const lid = scopedLocationId.value;
        const aid = activityId.value;
        const dateRaw = props.getFieldValue?.('service_date') ?? props.formData?.service_date;
        const date = typeof dateRaw === 'string' && dateRaw.length > 0 ? dateRaw : selectedDate.value;
        const tidRaw = props.getFieldValue?.('time_slot_id') ?? props.formData?.time_slot_id;
        const tid = Number(tidRaw);
        const g = guests.value;

        if (!lid || !aid || !date || !Number.isFinite(tid) || tid <= 0 || g == null) {
            return false;
        }

        const mySeq = ++boatResolveSeq;
        resolvingBoatAssignment.value = true;
        try {
            const payload: Record<string, unknown> = {
                activity_id: aid,
                service_date: typeof date === 'string' ? date.split('T')[0] : date,
                time_slot_id: tid,
                guests: g,
            };
            const rid = props.reservationId;
            if (rid != null && rid > 0) {
                payload.exclude_reservation_id = rid;
            }
            const catRaw = props.getFieldValue?.('boat_category_id') ?? props.formData?.boat_category_id;
            const cat = Number(catRaw);
            if (Number.isFinite(cat) && cat > 0) {
                payload.boat_category_id = cat;
            }

            const response = await http.post('reservations/resolve-boat-assignment', payload);
            if (mySeq !== boatResolveSeq) {
                return false;
            }
            const data = response.data?.data as { boat_id?: number; boat_category_id?: number } | undefined;
            if (data?.boat_id != null) {
                props.setFieldValue?.('boat_id', data.boat_id);
            }
            if (data?.boat_category_id != null) {
                props.setFieldValue?.('boat_category_id', data.boat_category_id);
            }
            return true;
        } catch (error: unknown) {
            if (mySeq === boatResolveSeq) {
                const msg =
                    (error as { response?: { data?: { message?: string } } })?.response?.data?.message ??
                    t('BOOKING_ADMIN_RESOLVE_BOAT_FAILED');
                toastService.error(msg);
                props.setFieldValue?.('boat_id', null);
                props.setFieldValue?.('boat_category_id', null);
                props.setFieldValue?.('time_slot_id', null);
            }
            return false;
        } finally {
            if (mySeq === boatResolveSeq) {
                resolvingBoatAssignment.value = false;
            }
        }
    }

    async function pickTimeSlot(slot: SlotRow): Promise<void> {
        if (slotHintUi(slot).disabled || hidePickUi(slot)) return;
        props.setFieldValue?.('time_slot_id', slot.id);
        await resolveBoatAssignmentFromForm();
    }

    function prevMonth(): void {
        currentMonth.value = currentMonth.value.subtract(1, 'month');
    }

    function nextMonth(): void {
        currentMonth.value = currentMonth.value.add(1, 'month');
    }

    watch(
        () => scopedLocationId.value,
        () => {
            void loadBookingSettings();
        },
        { immediate: true },
    );

    watch(
        () => [scopedLocationId.value, activityId.value, guests.value, currentMonth.value.year(), currentMonth.value.month()] as const,
        () => {
            fetchCalendar();
        },
        { immediate: true },
    );

    watch(
        () => props.getFieldValue?.('service_date') ?? props.formData?.service_date,
        (v) => {
            if (typeof v === 'string' && v.length > 0) {
                selectedDate.value = dayjs(v).format('YYYY-MM-DD');
                fetchTimeslots(selectedDate.value);
            } else {
                selectedDate.value = null;
                timeslots.value = [];
            }
        },
        { immediate: true },
    );

    watch(
        () => [guests.value, childrenCount.value] as const,
        () => {
            if (selectedDate.value && scopedLocationId.value && activityId.value) {
                fetchTimeslots(selectedDate.value);
            }
        },
    );
</script>
