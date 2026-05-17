<template>
    <div class="space-y-3">
        <TimeSlotAccordion
            :attached-ids="attachedIds"
            :defaultOpen="true"
            :time-slots="filteredTimeSlots"
            @update:selected-ids="onSelectionChange"
        />
    </div>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref, watch } from 'vue';
    import TimeSlotAccordion from '@/components/forms/custom/TimeSlotAccordion.vue';
    import TimeSlot from '@/models/general/TimeSlot';

    const props = defineProps<{ modelValue: number[] }>();
    const emit = defineEmits<{ (e: 'update:modelValue', v: number[]): void }>();

    const attachedIds = ref<number[]>([]);
    watch(
        () => props.modelValue,
        (v) => {
            if (v) attachedIds.value = [...v];
        },
        { immediate: true },
    );

    const timeSlots = ref<TimeSlot[]>([]);

    const filteredTimeSlots = computed<TimeSlot[]>(() => {
        const ids = new Set(attachedIds.value ?? []);
        return timeSlots.value
            .filter((timeSlot) => ids.has(timeSlot.id))
            .sort((a, b) => {
                const timeA = a.start_time || '00:00';
                const timeB = b.start_time || '00:00';
                if (timeA !== timeB) {
                    return timeA.localeCompare(timeB);
                }
                return String(a.label || '').localeCompare(String(b.label || ''));
            }) as TimeSlot[];
    });

    function onSelectionChange(ids: number[]): void {
        attachedIds.value = ids;
        emit('update:modelValue', ids);
    }

    onMounted(async () => {
        const tsRes = await new TimeSlot().index({ includes: ['pricingTable'] });
        timeSlots.value = tsRes.data.data;
    });
</script>
