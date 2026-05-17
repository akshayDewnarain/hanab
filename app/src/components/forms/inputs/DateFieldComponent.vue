<template>
    <div v-if="options.label" class="flex flex-col gap-1">
        <label :for="options.name" class="text-sm font-medium">
            {{ t(options.label) }}
            <span v-if="options.required" class="text-red-500 font-bold">*</span>
        </label>

        <!-- Dropdown trigger button -->
        <div ref="triggerRef" class="relative inline-block text-left group">
            <div>
                <div
                    id="options-menu"
                    :class="[
                        'inline-flex items-center gap-x-2 bg-white text-sm text-gray-700 border border-gray-200 w-full rounded px-4 py-2 shadow cursor-pointer',
                        'transition-all duration-200 ease-in-out',
                        isOpen ? 'ring-2 ring-[var(--color-primary)] ring-offset-0' : 'ring-0',
                        'hover:ring-2 hover:ring-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)] group-hover:ring-2 group-hover:ring-[var(--color-primary)]',
                    ]"
                    aria-expanded="true"
                    aria-haspopup="true"
                    type="button"
                >
                    <span
                        :class="{
                            'text-[var(--color-placeholder)]': isPlaceholder,
                            'text-black': !isPlaceholder,
                        }"
                        class="w-full text-left flex items-center"
                        @click="toggleDropdown"
                    >
                        <Icon
                            class="text-[var(--color-placeholder)] w-5 h-5 mr-1"
                            icon="material-symbols:date-range-outline-rounded"
                            @click="clear()"
                        />
                        {{ selectedLabel }}
                    </span>
                    <div class="flex absolute right-0 h-full items-center gap-x-1 pr-2">
                        <Icon
                            :class="{ 'rotate-270': isOpen }"
                            class="w-5 h-5 rotate-90 text-gray-500 transition-transform duration-200 ease-in-out"
                            icon="material-symbols:chevron-right-rounded"
                            @click="toggleDropdown"
                        />
                        <button
                            class="h-7 w-7 shrink-0 flex items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded cursor-pointer hover:bg-[var(--color-highlight-dark)]"
                        >
                            <Icon
                                class="text-white w-4 h-4"
                                icon="material-symbols:delete-outline-rounded"
                                @click="clear()"
                            />
                        </button>
                    </div>
                </div>
            </div>

            <transition name="fade-top">
                <div
                    v-if="isOpen"
                    ref="dropdownRef"
                    :class="[
                        dropdownClasses,
                        getDropdownWidth,
                        'max-w-[300px]',
                        isOpen ? 'outline-2 outline-[var(--color-primary)]' : '',
                    ]"
                    aria-labelledby="options-menu"
                    aria-orientation="vertical"
                    class="absolute rounded-md shadow-lg bg-white border border-gray-200 z-10"
                    role="menu"
                >
                    <div id="datepicker">
                        <div class="relative overflow-hidden">
                            <!-- Header -->
                            <div class="w-full mb-2">
                                <div class="flex justify-between bg-[var(--color-background)] rounded p-1 items-center">
                                    <button
                                        class="h-full flex items-center justify-center bg-[var(--color-background)] rounded p-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                                        @click="prevMonth"
                                    >
                                        <Icon class="text-white w-4 h-4" icon="material-symbols:arrow-back-rounded" />
                                    </button>
                                    <span class="text-white text-md">
                                        {{ `${t('MONTH_' + getMonth)} ${getYear}` }}
                                    </span>
                                    <button
                                        class="h-full flex items-center justify-center bg-[var(--color-background)] rounded p-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                                        @click="nextMonth"
                                    >
                                        <Icon
                                            class="text-white w-4 h-4"
                                            icon="material-symbols:arrow-forward-rounded"
                                        />
                                    </button>
                                </div>
                            </div>

                            <!-- Weekday Labels -->
                            <div class="grid grid-cols-7 text-center font-medium text-md mb-2">
                                <div
                                    v-for="dayLabel in weekdays"
                                    :key="dayLabel"
                                    class="flex justify-center items-center py-2"
                                >
                                    {{ t(dayLabel) }}
                                </div>
                            </div>
                            <div
                                ref="gridRef"
                                :style="gridHeight ? { height: `${gridHeight}px` } : {}"
                                class="relative"
                            >
                                <!-- Animated Day Grid -->
                                <transition :name="transitionDirection === 'left' ? 'slide-left' : 'slide-right'">
                                    <div :key="currentDate.format('YYYY-MM')" class="grid grid-cols-7 gap-px">
                                        <div v-for="week in datePicker" :key="week.weekNumber" class="contents">
                                            <div
                                                v-for="day in week.days"
                                                :key="day.dayNumber"
                                                :class="[
                                                    !day.selectable
                                                        ? 'bg-[var(--color-bg-blocked)] text-[var(--color-text-blocked)]'
                                                        : 'group/cell',
                                                ]"
                                                class="p-1 relative"
                                            >
                                                <div
                                                    :class="[
                                                        !day.selectable
                                                            ? 'cursor-not-allowed'
                                                            : 'cursor-pointer rounded hover:bg-[var(--color-primary)] hover:text-white transition-colors duration-200 ease-in',
                                                    ]"
                                                    class="h-full w-full flex justify-center items-center aspect-square"
                                                    @click="handleClick(day)"
                                                >
                                                    <div :class="{ disabled: !day.selectable }">
                                                        <span>{{ day.dayNumber }}</span>
                                                    </div>

                                                    <!-- Blue dot indicator -->
                                                    <div
                                                        v-if="isToday(day)"
                                                        class="absolute top-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-blue-500 group-hover/cell:bg-white transition-colors duration-200"
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

        <!-- Error message (if present) -->
        <Collapse :duration="500" :max-height="'50px'" :show="!!error">
            <div v-if="error" class="text-red-500 text-xs ml-1">
                {{ t(error) }}
            </div>
        </Collapse>
    </div>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import Collapse from '@/components/transitions/Collapse.vue';
    import { computed, defineComponent, nextTick, onMounted, ref, watch } from 'vue';
    import type { CalendarDay } from '@/modules/types/support/inputs/CalendarDay.ts';
    import type { CalendarWeek } from '@/modules/types/support/inputs/CalendarWeek.ts';
    import type { DateFieldOptions } from '@/modules/types/support/inputs/DateFieldOptions.ts';
    import { useDropdown } from '@/composables/fields/useDropdown';
    import { useI18n } from 'vue-i18n';
    import dayjs, { Dayjs } from 'dayjs';
    import { useComponentState } from '@/composables/components/useComponentState';
    import { DropdownWidth } from '@/modules/enums/support/inputs/DropDownWidth.ts';

    defineComponent({
        name: 'DateFieldComponent',
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

    const props = defineProps<{
        options: DateFieldOptions;
        error?: string;
        modelValue: string | number;
    }>();

    const currentDate = ref<Dayjs>(dayjs());
    const selectedDate = ref<Dayjs | null>(null);
    const datePicker = ref<CalendarWeek[]>();
    const gridHeight = ref<number | null>(null);
    const gridRef = ref<HTMLElement | null>(null);

    const emit = defineEmits<{
        (e: 'update:modelValue', value: string | null): void;
        (e: 'blur'): void;
    }>();

    const handleBlur = () => {
        emit('blur');
    };

    const { isOpen, triggerRef, toggleDropdown, position, dropdownRef } = useDropdown(handleBlur, {
        preferredPosition: props.options.position === 'top' ? 'top' : 'bottom',
        viewportPadding: 8,
    });
    const { t } = useI18n();

    const selectedLabel = computed(() => {
        return selectedDate.value
            ? selectedDate.value.format('DD-MM-YYYY')
            : t(props.options.placeholder ?? 'FORM_SELECT_PLACEHOLDER');
    });

    const disablePast = props.options.disablePast;

    const dropdownClasses = computed(() => {
        return {
            'bottom-full mb-2': position.value === 'top',
            'top-full mt-2': position.value !== 'top',
            'right-0': props.options.align === 'right',
            'left-0': props.options.align !== 'right',
        };
    });

    const transitionDirection = ref<'left' | 'right'>('left');

    const getMonth = computed(() => {
        return currentDate.value.format('MMMM').toUpperCase();
    });
    const getYear = computed(() => {
        return currentDate.value.format('YYYY');
    });

    const clear = () => {
        selectedDate.value = null;
        emit('update:modelValue', '');
        emit('blur');
    };

    const isPlaceholder = computed(
        () => selectedLabel.value === t(props.options.placeholder ?? 'FORM_SELECT_PLACEHOLDER'),
    );

    const getDropdownWidth = computed(() => {
        console.log(props.options);
        switch (props.options.dropdownWidth) {
            case DropdownWidth.FULL:
                return 'w-full';
            case DropdownWidth.HALF:
                return 'w-1/2';
            case DropdownWidth.THIRD:
                return 'w-1/3';
            case DropdownWidth.QUARTER:
                return 'w-1/4';
            default:
                return 'w-full';
        }
    });

    function nextMonth(): void {
        transitionDirection.value = 'left';
        currentDate.value = currentDate.value.add(1, 'month');
        generateCalendar();
    }

    function prevMonth(): void {
        transitionDirection.value = 'right';
        currentDate.value = currentDate.value.subtract(1, 'month');
        generateCalendar();
    }

    function isToday(day: CalendarDay): boolean {
        return day.date.isSame(dayjs(), 'day');
    }

    function handleClick(day: CalendarDay) {
        if (!day.selectable) {
            return;
        }

        selectedDate.value = day.date;
        emit('update:modelValue', day.date.format('YYYY-MM-DD'));
        emit('blur');
        isOpen.value = false;
    }

    function generateCalendar(): void {
        let firstDayIndex = currentDate.value.date(1).day() - 1;
        if (firstDayIndex === -1) {
            firstDayIndex = 6;
        }

        // let dayIdentifier = 0;

        let dayIndex: Dayjs = currentDate.value
            .subtract(1, 'month')
            .date(currentDate.value.subtract(1, 'month').daysInMonth() - firstDayIndex + 1);

        const calendar: CalendarWeek[] = [];

        let weekIndex = 0;
        let week: CalendarWeek = { weekNumber: weekIndex, days: [] };

        for (let i = 0; i < 43; i++) {
            let isSelectable = true;
            if (dayIndex.isBefore(dayjs())) {
                isSelectable = !disablePast;
            }
            if (i < firstDayIndex || i > currentDate.value.daysInMonth() + firstDayIndex - 1) {
                isSelectable = !disablePast;
            }

            const today = dayjs();
            if (dayIndex.isSame(today, 'day')) {
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
            // dayIdentifier += 1;
        }

        datePicker.value = calendar;
        setContent();
    }

    watch(isOpen, (val: boolean) => {
        if (val) {
            nextTick(() => {
                if (gridRef.value) {
                    gridHeight.value = gridRef.value.offsetHeight;
                }
            });
        } else {
            gridHeight.value = null;
        }
    });

    // Watch for changes in modelValue to update selectedDate
    watch(
        () => props.modelValue,
        (newValue) => {
            if (newValue) {
                const parsedDate = dayjs(newValue);
                if (parsedDate.isValid()) {
                    selectedDate.value = parsedDate;
                    currentDate.value = parsedDate;
                }
            } else {
                selectedDate.value = null;
            }
        },
        { immediate: true },
    );

    onMounted(() => {
        generateCalendar();
    });
</script>
