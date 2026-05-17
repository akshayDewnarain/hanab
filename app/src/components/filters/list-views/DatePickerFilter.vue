<template>
    <div class="flex flex-col gap-3 p-2 min-w-80">
        <!-- From Date Picker -->
        <div class="flex flex-col gap-1">
            <label class="text-sm font-bold text-gray-700"> {{ t('FILTER_FROM') }}: </label>
            <div ref="fromTriggerRef" class="relative inline-block text-left group">
                <div>
                    <div
                        :class="[
                            'inline-flex items-center gap-x-2 bg-white text-sm text-gray-700 border border-gray-200 w-full rounded px-3 py-2 shadow cursor-pointer',
                            'transition-all duration-200 ease-in-out',
                            fromIsOpen ? 'ring-2 ring-[var(--color-primary)] ring-offset-0' : 'ring-0',
                            'hover:ring-2 hover:ring-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]',
                        ]"
                        aria-expanded="true"
                        aria-haspopup="true"
                        type="button"
                        @click="toggleFromDropdown"
                    >
                        <span
                            :class="{
                                'text-[var(--color-placeholder)]': !fromDate,
                                'text-black': fromDate,
                            }"
                            class="w-full text-left flex items-center"
                        >
                            <Icon
                                class="text-[var(--color-placeholder)] w-4 h-4 mr-1"
                                icon="material-symbols:date-range-outline-rounded"
                            />
                            {{ fromDate ? fromDate.format('DD-MM-YYYY') : t('FILTER_SELECT_DATE') }}
                        </span>
                        <Icon
                            :class="{ 'rotate-270': fromIsOpen }"
                            class="w-4 h-4 rotate-90 text-gray-500 transition-transform duration-200 ease-in-out"
                            icon="material-symbols:chevron-right-rounded"
                        />
                    </div>
                </div>

                <transition name="fade-top">
                    <div
                        v-if="fromIsOpen"
                        ref="fromDropdownRef"
                        :class="fromDropdownClasses"
                        class="absolute rounded-md shadow-lg bg-white border border-gray-200 z-10 w-full"
                        role="menu"
                    >
                        <div id="datepicker-from">
                            <div class="relative overflow-hidden">
                                <!-- Header -->
                                <div class="w-full">
                                    <div
                                        class="flex justify-between bg-[var(--color-background)] rounded-t p-1 items-center"
                                    >
                                        <button
                                            class="h-full flex items-center justify-center bg-[var(--color-background)] rounded p-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                                            @click="prevFromMonth"
                                        >
                                            <Icon
                                                class="text-white w-4 h-4"
                                                icon="material-symbols:arrow-back-rounded"
                                            />
                                        </button>
                                        <span class="text-white text-sm">
                                            {{ `${t('MONTH_' + fromGetMonth)} ${fromGetYear}` }}
                                        </span>
                                        <button
                                            class="h-full flex items-center justify-center bg-[var(--color-background)] rounded p-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                                            @click="nextFromMonth"
                                        >
                                            <Icon
                                                class="text-white w-4 h-4"
                                                icon="material-symbols:arrow-forward-rounded"
                                            />
                                        </button>
                                    </div>
                                </div>

                                <!-- Weekday Labels -->
                                <div class="grid grid-cols-7 text-center font-medium text-xs">
                                    <div
                                        v-for="dayLabel in weekdays"
                                        :key="dayLabel"
                                        class="flex justify-center items-center py-3 text-gray-600"
                                    >
                                        {{ t(dayLabel) }}
                                    </div>
                                </div>
                                <div
                                    ref="fromGridRef"
                                    :style="fromGridHeight ? { height: `${fromGridHeight}px` } : {}"
                                    class="relative"
                                >
                                    <!-- Animated Day Grid -->
                                    <transition
                                        :name="fromTransitionDirection === 'left' ? 'slide-left' : 'slide-right'"
                                    >
                                        <div :key="fromCurrentDate.format('YYYY-MM')" class="grid grid-cols-7 gap-px">
                                            <div v-for="week in fromDatePicker" :key="week.weekNumber" class="contents">
                                                <div
                                                    v-for="day in week.days"
                                                    :key="day.dayNumber"
                                                    :class="[
                                                        !day.selectable
                                                            ? 'bg-[var(--color-bg-blocked)] text-[var(--color-text-blocked)]'
                                                            : 'group/cell',
                                                    ]"
                                                    class="p-1 relative bg-gray-50 text-gray-600"
                                                >
                                                    <div
                                                        :class="[
                                                            !day.selectable
                                                                ? 'cursor-not-allowed'
                                                                : 'cursor-pointer rounded hover:bg-[var(--color-primary-light)] hover:text-white transition-colors duration-200 ease-in',
                                                            isFromSelected(day)
                                                                ? 'bg-[var(--color-primary)] text-white'
                                                                : '',
                                                        ]"
                                                        class="h-full w-full flex justify-center items-center aspect-square text-xs"
                                                        @click="handleFromClick(day)"
                                                    >
                                                        <div :class="{ disabled: !day.selectable }">
                                                            <span>{{ day.dayNumber }}</span>
                                                        </div>

                                                        <!-- Blue dot indicator -->
                                                        <div
                                                            v-if="isToday(day)"
                                                            class="absolute top-2 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-blue-500 group-hover/cell:bg-white transition-colors duration-200"
                                                        ></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- To Date Picker -->
        <div class="flex flex-col gap-1">
            <label class="text-sm font-bold text-gray-700"> {{ t('FILTER_TO') }}: </label>
            <div ref="toTriggerRef" class="relative inline-block text-left group">
                <div>
                    <div
                        :class="[
                            'inline-flex items-center gap-x-2 bg-white text-sm text-gray-700 border border-gray-200 w-full rounded px-3 py-2 shadow cursor-pointer',
                            'transition-all duration-200 ease-in-out',
                            toIsOpen ? 'ring-2 ring-[var(--color-primary)] ring-offset-0' : 'ring-0',
                            'hover:ring-2 hover:ring-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]',
                        ]"
                        aria-expanded="true"
                        aria-haspopup="true"
                        type="button"
                        @click="toggleToDropdown"
                    >
                        <span
                            :class="{
                                'text-[var(--color-placeholder)]': !toDate,
                                'text-black': toDate,
                            }"
                            class="w-full text-left flex items-center"
                        >
                            <Icon
                                class="text-[var(--color-placeholder)] w-4 h-4 mr-1"
                                icon="material-symbols:date-range-outline-rounded"
                            />
                            {{ toDate ? toDate.format('DD-MM-YYYY') : t('FILTER_SELECT_DATE') }}
                        </span>
                        <Icon
                            :class="{ 'rotate-270': toIsOpen }"
                            class="w-4 h-4 rotate-90 text-gray-500 transition-transform duration-200 ease-in-out"
                            icon="material-symbols:chevron-right-rounded"
                        />
                    </div>
                </div>

                <transition name="fade-top">
                    <div
                        v-if="toIsOpen"
                        ref="toDropdownRef"
                        :class="toDropdownClasses"
                        class="absolute rounded-md shadow-lg bg-white border border-gray-200 z-10 w-full"
                        role="menu"
                    >
                        <div id="datepicker-to">
                            <div class="relative overflow-hidden">
                                <!-- Header -->
                                <div class="w-full">
                                    <div
                                        class="flex justify-between bg-[var(--color-background)] rounded-t p-1 items-center"
                                    >
                                        <button
                                            class="h-full flex items-center justify-center bg-[var(--color-background)] rounded p-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                                            @click="prevToMonth"
                                        >
                                            <Icon
                                                class="text-white w-4 h-4"
                                                icon="material-symbols:arrow-back-rounded"
                                            />
                                        </button>
                                        <span class="text-white text-sm">
                                            {{ `${t('MONTH_' + toGetMonth)} ${toGetYear}` }}
                                        </span>
                                        <button
                                            class="h-full flex items-center justify-center bg-[var(--color-background)] rounded p-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                                            @click="nextToMonth"
                                        >
                                            <Icon
                                                class="text-white w-4 h-4"
                                                icon="material-symbols:arrow-forward-rounded"
                                            />
                                        </button>
                                    </div>
                                </div>

                                <!-- Weekday Labels -->
                                <div class="grid grid-cols-7 text-center font-medium text-xs">
                                    <div
                                        v-for="dayLabel in weekdays"
                                        :key="dayLabel"
                                        class="flex justify-center items-center py-3 text-gray-600"
                                    >
                                        {{ t(dayLabel) }}
                                    </div>
                                </div>
                                <div
                                    ref="toGridRef"
                                    :style="toGridHeight ? { height: `${toGridHeight}px` } : {}"
                                    class="relative"
                                >
                                    <!-- Animated Day Grid -->
                                    <transition :name="toTransitionDirection === 'left' ? 'slide-left' : 'slide-right'">
                                        <div :key="toCurrentDate.format('YYYY-MM')" class="grid grid-cols-7 gap-px">
                                            <div v-for="week in toDatePicker" :key="week.weekNumber" class="contents">
                                                <div
                                                    v-for="day in week.days"
                                                    :key="day.dayNumber"
                                                    :class="[
                                                        !day.selectable
                                                            ? 'bg-[var(--color-bg-blocked)] text-[var(--color-text-blocked)]'
                                                            : 'group/cell',
                                                    ]"
                                                    class="p-1 relative bg-gray-50 text-gray-600"
                                                >
                                                    <div
                                                        :class="[
                                                            !day.selectable
                                                                ? 'cursor-not-allowed'
                                                                : 'cursor-pointer rounded hover:bg-[var(--color-primary-light)] hover:text-white transition-colors duration-200 ease-in',
                                                            isToSelected(day)
                                                                ? 'bg-[var(--color-primary)] text-white'
                                                                : '',
                                                        ]"
                                                        class="h-full w-full flex justify-center items-center aspect-square text-xs"
                                                        @click="handleToClick(day)"
                                                    >
                                                        <div :class="{ disabled: !day.selectable }">
                                                            <span>{{ day.dayNumber }}</span>
                                                        </div>

                                                        <!-- Blue dot indicator -->
                                                        <div
                                                            v-if="isToday(day)"
                                                            class="absolute top-2 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-blue-500 group-hover/cell:bg-white transition-colors duration-200"
                                                        ></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Reset Button -->
        <button
            v-if="hasValue"
            class="w-full px-3 py-2 text-sm font-medium text-white bg-red-500 rounded hover:bg-red-600 transition-colors duration-200 ease-in-out flex items-center justify-center gap-2"
            @click="reset"
        >
            <Icon class="w-4 h-4" icon="material-symbols:delete-outline-rounded" />
            {{ t('FILTER_RESET') }}
        </button>
    </div>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { computed, defineComponent, nextTick, onMounted, ref, watch } from 'vue';
    import type { CalendarDay } from '@/modules/types/support/inputs/CalendarDay.ts';
    import type { CalendarWeek } from '@/modules/types/support/inputs/CalendarWeek.ts';
    import { useDropdown } from '@/composables/fields/useDropdown';
    import { useI18n } from 'vue-i18n';
    import dayjs, { Dayjs } from 'dayjs';
    import { useComponentState } from '@/composables/components/useComponentState';

    defineComponent({
        name: 'DatePickerFilter',
    });

    const { setContent } = useComponentState();
    const weekdays = [
        'MONDAY_ABBREVIATION',
        'TUESDAY_ABBREVIATION',
        'WEDNESDAY_ABBREVIATION',
        'THURSDAY_ABBREVIATION',
        'FRIDAY_ABBREVIATION',
        'SATURDAY_ABBREVIATION',
        'SUNDAY_ABBREVIATION',
    ];

    export interface DateRange {
        from: string | null;
        to: string | null;
    }

    const props = defineProps<{
        modelValue: DateRange | null | undefined;
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', value: DateRange | null): void;
    }>();

    // From Date State
    const fromCurrentDate = ref<Dayjs>(dayjs());
    const fromDate = ref<Dayjs | null>(null);
    const fromDatePicker = ref<CalendarWeek[]>();
    const fromGridHeight = ref<number | null>(null);
    const fromGridRef = ref<HTMLElement | null>(null);
    const fromTransitionDirection = ref<'left' | 'right'>('left');

    const handleFromBlur = () => {
        // Handle blur if needed
    };

    const {
        isOpen: fromIsOpen,
        triggerRef: fromTriggerRef,
        toggleDropdown: toggleFromDropdown,
        position: fromPosition,
        dropdownRef: fromDropdownRef,
    } = useDropdown(handleFromBlur, {
        preferredPosition: 'bottom',
        viewportPadding: 8,
    });

    const fromDropdownClasses = computed(() => {
        return {
            'bottom-full mb-2': fromPosition.value === 'top',
            'top-full mt-2': fromPosition.value !== 'top',
            'right-0': false,
            'left-0': true,
        };
    });

    const fromGetMonth = computed(() => {
        return fromCurrentDate.value.format('MMMM').toUpperCase();
    });
    const fromGetYear = computed(() => {
        return fromCurrentDate.value.format('YYYY');
    });

    // To Date State
    const toCurrentDate = ref<Dayjs>(dayjs());
    const toDate = ref<Dayjs | null>(null);
    const toDatePicker = ref<CalendarWeek[]>();
    const toGridHeight = ref<number | null>(null);
    const toGridRef = ref<HTMLElement | null>(null);
    const toTransitionDirection = ref<'left' | 'right'>('left');

    const handleToBlur = () => {
        // Handle blur if needed
    };

    const {
        isOpen: toIsOpen,
        triggerRef: toTriggerRef,
        toggleDropdown: toggleToDropdown,
        position: toPosition,
        dropdownRef: toDropdownRef,
    } = useDropdown(handleToBlur, {
        preferredPosition: 'bottom',
        viewportPadding: 8,
    });

    const toDropdownClasses = computed(() => {
        return {
            'bottom-full mb-2': toPosition.value === 'top',
            'top-full mt-2': toPosition.value !== 'top',
            'right-0': false,
            'left-0': true,
        };
    });

    const toGetMonth = computed(() => {
        return toCurrentDate.value.format('MMMM').toUpperCase();
    });
    const toGetYear = computed(() => {
        return toCurrentDate.value.format('YYYY');
    });

    const { t } = useI18n();

    const hasValue = computed(() => {
        return fromDate.value !== null || toDate.value !== null;
    });

    function isToday(day: CalendarDay): boolean {
        return day.date.isSame(dayjs(), 'day');
    }

    function isFromSelected(day: CalendarDay): boolean {
        return fromDate.value !== null && day.date.isSame(fromDate.value, 'day');
    }

    function isToSelected(day: CalendarDay): boolean {
        return toDate.value !== null && day.date.isSame(toDate.value, 'day');
    }

    function generateCalendar(currentDate: Dayjs): CalendarWeek[] {
        let firstDayIndex = currentDate.date(1).day() - 1;
        if (firstDayIndex === -1) {
            firstDayIndex = 6;
        }

        let dayIndex: Dayjs = currentDate
            .subtract(1, 'month')
            .date(currentDate.subtract(1, 'month').daysInMonth() - firstDayIndex + 1);

        const calendar: CalendarWeek[] = [];

        let weekIndex = 0;
        let week: CalendarWeek = { weekNumber: weekIndex, days: [] };

        for (let i = 0; i < 43; i++) {
            let isSelectable = true;
            // Allow all dates for filter (no disablePast restriction)
            if (i < firstDayIndex || i > currentDate.daysInMonth() + firstDayIndex - 1) {
                isSelectable = true;
            }

            if (week.days.length === 7) {
                calendar.push(week);
                weekIndex++;
                week = { weekNumber: weekIndex, days: [] };
            }

            if (week.days.length === 7) {
                calendar.push(week);
                weekIndex++;
                week = { weekNumber: weekIndex, days: [] };
            }

            week.days.push({
                dayNumber: dayIndex.date(),
                date: dayIndex,
                selectable: isSelectable,
            });
            dayIndex = dayIndex.add(1, 'day');
        }

        return calendar;
    }

    function generateFromCalendar(): void {
        fromDatePicker.value = generateCalendar(fromCurrentDate.value);
        setContent();
    }

    function generateToCalendar(): void {
        toDatePicker.value = generateCalendar(toCurrentDate.value);
        setContent();
    }

    function nextFromMonth(): void {
        fromTransitionDirection.value = 'left';
        fromCurrentDate.value = fromCurrentDate.value.add(1, 'month');
        generateFromCalendar();
    }

    function prevFromMonth(): void {
        fromTransitionDirection.value = 'right';
        fromCurrentDate.value = fromCurrentDate.value.subtract(1, 'month');
        generateFromCalendar();
    }

    function nextToMonth(): void {
        toTransitionDirection.value = 'left';
        toCurrentDate.value = toCurrentDate.value.add(1, 'month');
        generateToCalendar();
    }

    function prevToMonth(): void {
        toTransitionDirection.value = 'right';
        toCurrentDate.value = toCurrentDate.value.subtract(1, 'month');
        generateToCalendar();
    }

    function handleFromClick(day: CalendarDay) {
        if (!day.selectable) {
            return;
        }

        fromDate.value = day.date;
        fromCurrentDate.value = day.date;
        emitValue();
        fromIsOpen.value = false;
    }

    function handleToClick(day: CalendarDay) {
        if (!day.selectable) {
            return;
        }

        toDate.value = day.date;
        toCurrentDate.value = day.date;
        emitValue();
        toIsOpen.value = false;
    }

    function emitValue() {
        // Preserve existing values from props if the current selection doesn't have them
        const existingRange = props.modelValue || { from: null, to: null };

        const range: DateRange = {
            from: fromDate.value ? fromDate.value.format('YYYY-MM-DD') : existingRange.from || null,
            to: toDate.value ? toDate.value.format('YYYY-MM-DD') : existingRange.to || null,
        };

        // Only emit if at least one value is set
        if (range.from || range.to) {
            emit('update:modelValue', range);
        } else {
            emit('update:modelValue', null);
        }
    }

    function reset() {
        fromDate.value = null;
        toDate.value = null;
        fromCurrentDate.value = dayjs();
        toCurrentDate.value = dayjs();
        generateFromCalendar();
        generateToCalendar();
        emit('update:modelValue', null);
    }

    watch(fromIsOpen, (val: boolean) => {
        if (val) {
            nextTick(() => {
                if (fromGridRef.value) {
                    fromGridHeight.value = fromGridRef.value.offsetHeight;
                }
            });
        } else {
            fromGridHeight.value = null;
        }
    });

    watch(toIsOpen, (val: boolean) => {
        if (val) {
            nextTick(() => {
                if (toGridRef.value) {
                    toGridHeight.value = toGridRef.value.offsetHeight;
                }
            });
        } else {
            toGridHeight.value = null;
        }
    });

    // Watch for changes in modelValue to update selected dates
    watch(
        () => props.modelValue,
        (newValue) => {
            if (newValue) {
                if (newValue.from) {
                    const parsedFrom = dayjs(newValue.from);
                    if (parsedFrom.isValid()) {
                        fromDate.value = parsedFrom;
                        fromCurrentDate.value = parsedFrom;
                    }
                } else {
                    fromDate.value = null;
                }

                if (newValue.to) {
                    const parsedTo = dayjs(newValue.to);
                    if (parsedTo.isValid()) {
                        toDate.value = parsedTo;
                        toCurrentDate.value = parsedTo;
                    }
                } else {
                    toDate.value = null;
                }
            } else {
                fromDate.value = null;
                toDate.value = null;
            }
        },
        { immediate: true },
    );

    onMounted(() => {
        generateFromCalendar();
        generateToCalendar();
    });
</script>
