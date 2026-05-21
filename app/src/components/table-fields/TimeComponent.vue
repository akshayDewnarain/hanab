<template>
    <div class="flex items-center justify-center">
        <span v-if="formattedTime" class="badge-secondary"> {{ formattedTime }} </span>
        <span v-else class="text-sm text-gray-400"> — </span>
    </div>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';

    interface Props {
        value: string | Date | null | undefined;
        format?: 'time' | 'datetime' | 'date';
        showSeconds?: boolean;
    }

    const props = withDefaults(defineProps<Props>(), {
        format: 'time',
        showSeconds: false,
    });

    function pad2(n: number): string {
        return String(n).padStart(2, '0');
    }

    /** 24-hour clock as `H:i`, or `H:i:s` when `showSeconds` is true. */
    function formatTimeDisplay(date: Date): string {
        const h = pad2(date.getHours());
        const m = pad2(date.getMinutes());
        if (props.showSeconds) {
            return `${h}:${m}:${pad2(date.getSeconds())}`;
        }
        return `${h}:${m}`;
    }

    /** Calendar day as `dd-mm-YYYY` (local timezone). */
    function formatDateDisplay(date: Date): string {
        const d = pad2(date.getDate());
        const m = pad2(date.getMonth() + 1);
        const y = date.getFullYear();
        return `${d}-${m}-${y}`;
    }

    const formattedTime = computed(() => {
        if (!props.value) {
            return null;
        }

        try {
            let date: Date;

            if (typeof props.value === 'string' && /^\d{1,2}:\d{2}(:\d{2})?$/.test(props.value)) {
                const today = new Date();
                const [hours = '0', minutes = '0', seconds = '00'] = props.value.split(':');
                date = new Date(
                    today.getFullYear(),
                    today.getMonth(),
                    today.getDate(),
                    parseInt(hours, 10),
                    parseInt(minutes, 10),
                    parseInt(seconds, 10),
                );
            } else {
                date = new Date(props.value);
            }

            if (isNaN(date.getTime())) {
                return null;
            }

            switch (props.format) {
                case 'time':
                    return formatTimeDisplay(date);
                case 'date':
                    return formatDateDisplay(date);
                case 'datetime':
                    return `${formatDateDisplay(date)} ${formatTimeDisplay(date)}`;
                default:
                    return formatTimeDisplay(date);
            }
        } catch (error) {
            console.error('TimeComponent formatting error:', error);
            return null;
        }
    });
</script>
