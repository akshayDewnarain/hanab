<template>
    <div class="flex items-center gap-1">
        <!-- Compact dot representation -->
        <div v-if="format === 'dots'" class="flex gap-0.5">
            <div
                v-for="day in 7"
                :key="day - 1"
                :class="['w-2 h-2 rounded-full', sortedWeekdays.includes(day - 1) ? 'bg-blue-500' : 'bg-gray-200']"
                :title="getWeekdayName(day - 1)"
            />
        </div>

        <!-- Letter representation -->
        <div v-else-if="format === 'letters'" class="flex gap-0.5">
            <span
                v-for="day in 7"
                :key="day - 1"
                :class="[
                    'w-4 h-4 text-xs flex items-center justify-center rounded',
                    sortedWeekdays.includes(day - 1) ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500',
                ]"
                :title="getWeekdayName(day - 1)"
            >
                {{ getWeekdayLetter(day - 1) }}
            </span>
        </div>

        <!-- Badge representation -->
        <div v-else class="flex flex-wrap gap-1">
            <!-- Show "All Days" when null and showAllDaysAsText is true -->
            <span v-if="props.value === null && props.showAllDaysAsText" class="badge-primary text-xs">
                {{ t('ALL_DAYS', 'All Days') }}
            </span>

            <!-- Show individual days -->
            <span
                v-for="weekday in sortedWeekdays"
                v-else-if="sortedWeekdays.length > 0"
                :key="weekday"
                class="badge-secondary text-xs"
            >
                {{ getWeekdayName(weekday) }}
            </span>

            <!-- Empty state -->
            <span v-else class="text-sm text-gray-400"> — </span>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';

    interface Props {
        value: number[] | null | undefined;
        format?: 'dots' | 'letters' | 'badges';
        sort?: boolean;
        showAllDaysAsText?: boolean; // If true, shows "All Days" instead of individual days when null
    }

    const props = withDefaults(defineProps<Props>(), {
        format: 'badges',
        sort: true,
        showAllDaysAsText: false,
    });

    const { t } = useI18n();

    const sortedWeekdays = computed(() => {
        // If null, return all days (0-6) to represent "all days"
        if (props.value === null) {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        if (!props.value || !Array.isArray(props.value)) {
            return [];
        }

        // Remove duplicates and filter valid weekdays (0-6)
        const validWeekdays = [...new Set(props.value)].filter(
            (day) => typeof day === 'number' && day >= 0 && day <= 6,
        );

        if (props.sort) {
            return validWeekdays.sort((a, b) => a - b);
        }

        return validWeekdays;
    });

    const getWeekdayName = (weekday: number): string => {
        const weekdayNames: Record<number, string> = {
            0: t('WEEKDAY_MONDAY', 'Monday'),
            1: t('WEEKDAY_TUESDAY', 'Tuesday'),
            2: t('WEEKDAY_WEDNESDAY', 'Wednesday'),
            3: t('WEEKDAY_THURSDAY', 'Thursday'),
            4: t('WEEKDAY_FRIDAY', 'Friday'),
            5: t('WEEKDAY_SATURDAY', 'Saturday'),
            6: t('WEEKDAY_SUNDAY', 'Sunday'),
        };

        return weekdayNames[weekday] || `Day ${weekday}`;
    };

    const getWeekdayLetter = (weekday: number): string => {
        const letters = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
        return letters[weekday] || '?';
    };
</script>
