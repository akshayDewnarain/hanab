<template>
    <div class="flex gap-1">
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
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';

    interface Props {
        value: number[] | null | undefined;
        format?: 'short' | 'long' | 'abbreviated';
        sort?: boolean;
        showAllDaysAsText?: boolean; // If true, shows "All Days" instead of individual days when null
    }

    const props = withDefaults(defineProps<Props>(), {
        format: 'short',
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
        const validWeekdays = [...new Set(props.value)].filter((day) => day >= 0 && day <= 6);

        if (props.sort) {
            return validWeekdays.sort((a, b) => a - b);
        }

        return validWeekdays;
    });

    const getWeekdayName = (weekday: number): string => {
        const weekdayNames: Record<string, Record<number, string>> = {
            short: {
                0: t('WEEKDAY_MON_SHORT', 'Mon'),
                1: t('WEEKDAY_TUE_SHORT', 'Tue'),
                2: t('WEEKDAY_WED_SHORT', 'Wed'),
                3: t('WEEKDAY_THU_SHORT', 'Thu'),
                4: t('WEEKDAY_FRI_SHORT', 'Fri'),
                5: t('WEEKDAY_SAT_SHORT', 'Sat'),
                6: t('WEEKDAY_SUN_SHORT', 'Sun'),
            },
            long: {
                0: t('WEEKDAY_MONDAY', 'Monday'),
                1: t('WEEKDAY_TUESDAY', 'Tuesday'),
                2: t('WEEKDAY_WEDNESDAY', 'Wednesday'),
                3: t('WEEKDAY_THURSDAY', 'Thursday'),
                4: t('WEEKDAY_FRIDAY', 'Friday'),
                5: t('WEEKDAY_SATURDAY', 'Saturday'),
                6: t('WEEKDAY_SUNDAY', 'Sunday'),
            },
            abbreviated: {
                0: t('WEEKDAY_MON_ABBR', 'M'),
                1: t('WEEKDAY_TUE_ABBR', 'T'),
                2: t('WEEKDAY_WED_ABBR', 'W'),
                3: t('WEEKDAY_THU_ABBR', 'T'),
                4: t('WEEKDAY_FRI_ABBR', 'F'),
                5: t('WEEKDAY_SAT_ABBR', 'S'),
                6: t('WEEKDAY_SUN_ABBR', 'S'),
            },
        };

        return weekdayNames[props.format]?.[weekday] || `Day ${weekday}`;
    };
</script>
