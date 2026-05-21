<template>
    <div v-if="options.label" class="flex flex-col gap-1">
        <label :for="options.name" class="text-sm font-medium">
            {{ t(options.label) }}
            <span v-if="options.required" class="text-red-500 font-bold">*</span>
        </label>

        <!-- Trigger -->
        <div ref="triggerRef" class="relative inline-block text-left group">
            <div
                id="time-menu"
                :class="[
                    'inline-flex items-center gap-x-2 bg-white text-sm text-gray-700 border border-gray-200 w-full rounded px-4 py-2 shadow cursor-pointer',
                    'transition-all duration-200 ease-in-out',
                    isOpen ? 'ring-2 ring-[var(--color-primary)] ring-offset-0' : 'ring-0',
                    'hover:ring-2 hover:ring-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)] group-hover:ring-2 group-hover:ring-[var(--color-primary)]',
                ]"
                aria-expanded="true"
                aria-haspopup="true"
                type="button"
                @click="toggleDropdown"
            >
                <Icon class="text-[var(--color-placeholder)] w-5 h-5 mr-1" icon="material-symbols:schedule-outline" />
                <span
                    :class="isPlaceholder ? 'text-[var(--color-placeholder)]' : 'text-black'"
                    class="w-full text-left"
                >
                    {{ selectedLabel }}
                </span>
                <div class="flex absolute right-0 top-0 bottom-0 items-center gap-x-1 pr-2">
                    <Icon
                        :class="{ 'rotate-270': isOpen }"
                        class="w-5 h-5 rotate-90 text-gray-500 transition-transform"
                        icon="material-symbols:chevron-right-rounded"
                    />
                    <button
                        class="h-7 w-7 shrink-0 flex items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded cursor-pointer hover:bg-[var(--color-highlight-dark)]"
                        @click.stop="clear"
                    >
                        <Icon class="text-white w-4 h-4" icon="material-symbols:delete-outline-rounded" />
                    </button>
                </div>
            </div>

            <!-- Dropdown -->
            <teleport to="body">
                <transition name="fade-top">
                    <div
                        v-if="isOpen"
                        ref="dropdownRef"
                        :class="[
                            'rounded-lg shadow-xl bg-white border border-gray-200 z-[1000] transition-all duration-200 ease-in-out',
                            isOpen ? 'ring-2 ring-[var(--color-primary)] ring-offset-0' : 'ring-0',
                        ]"
                        :style="dropdownStyles"
                        aria-labelledby="time-menu"
                        class="absolute rounded-lg shadow-xl bg-white border border-gray-200 z-10"
                        role="menu"
                    >
                        <!-- Main Time Display -->
                        <div class="flex flex-col items-center justify-center py-6 px-4">
                            <!-- Large Time Display -->
                            <div class="flex items-center justify-center mb-4">
                                <!-- Hours -->
                                <div class="flex flex-col items-center">
                                    <button
                                        class="p-2 text-gray-500 hover:text-[var(--color-primary)] transition-colors cursor-pointer"
                                        @click="incrementHour"
                                    >
                                        <Icon class="w-6 h-6" icon="material-symbols:keyboard-arrow-up" />
                                    </button>
                                    <div class="text-4xl font-light text-gray-800 mx-2 min-w-[3rem] text-center">
                                        {{ selectedHour !== null ? pad2(selectedHour) : '00' }}
                                    </div>
                                    <button
                                        class="p-2 text-gray-500 hover:text-[var(--color-primary)] transition-colors cursor-pointer"
                                        @click="decrementHour"
                                    >
                                        <Icon class="w-6 h-6" icon="material-symbols:keyboard-arrow-down" />
                                    </button>
                                </div>

                                <!-- Colon -->
                                <div class="text-4xl font-light text-gray-800 mx-2">:</div>

                                <!-- Minutes -->
                                <div class="flex flex-col items-center">
                                    <button
                                        class="p-2 text-gray-500 hover:text-[var(--color-primary)] transition-colors cursor-pointer"
                                        @click="incrementMinute"
                                    >
                                        <Icon class="w-6 h-6" icon="material-symbols:keyboard-arrow-up" />
                                    </button>
                                    <div class="text-4xl font-light text-gray-800 mx-2 min-w-[3rem] text-center">
                                        {{ selectedMinute !== null ? pad2(selectedMinute) : '00' }}
                                    </div>
                                    <button
                                        class="p-2 text-gray-500 hover:text-[var(--color-primary)] transition-colors cursor-pointer"
                                        @click="decrementMinute"
                                    >
                                        <Icon class="w-6 h-6" icon="material-symbols:keyboard-arrow-down" />
                                    </button>
                                </div>
                            </div>

                            <!-- Current Time Preview -->
                            <div class="text-sm text-gray-600 mb-4">
                                {{
                                    selectedHour !== null && selectedMinute !== null
                                        ? `${pad2(selectedHour)}:${pad2(selectedMinute)}`
                                        : '--:--'
                                }}
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex justify-between items-center px-4 py-3 border-t border-gray-200">
                            <button
                                class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md border border-gray-300 transition-colors"
                                @click="clear"
                            >
                                {{ t('CANCEL') }}
                            </button>
                            <button
                                :disabled="!canConfirm"
                                class="px-4 py-2 text-sm text-white bg-[var(--color-primary)] hover:bg-[var(--color-highlight-dark)] disabled:opacity-50 disabled:cursor-not-allowed rounded-md transition-colors"
                                @click="confirm"
                            >
                                {{ t('SELECT') }}
                            </button>
                        </div>
                    </div>
                </transition>
            </teleport>
        </div>

        <!-- Error -->
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
    import { computed, defineComponent, nextTick, ref, watch } from 'vue';
    import { useDropdown } from '@/composables/fields/useDropdown';
    import { useI18n } from 'vue-i18n';
    import { useComponentState } from '@/composables/components/useComponentState';
    import type { TimeFieldOptions } from '@/modules/types/support/inputs/TimeFieldOptions.ts';

    defineComponent({ name: 'TimeFieldComponent' });

    const { setContent } = useComponentState();
    const { t } = useI18n();

    const props = defineProps<{
        options: TimeFieldOptions;
        error?: string;
        modelValue: string | number;
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', value: string | null): void;
        (e: 'blur'): void;
    }>();

    const handleBlur = () => emit('blur');

    const { isOpen, triggerRef, toggleDropdown, dropdownRef, dropdownStyles, updatePlacement } = useDropdown(
        handleBlur,
        {
            preferredPosition: props.options.position === 'top' ? 'top' : 'bottom',
            align: props.options.align === 'right' ? 'right' : 'left',
            matchWidth: true,
            offset: 8,
            viewportPadding: 8,
        },
    );

    const STEP = computed(() => Math.max(1, props.options.stepMinutes ?? 15));
    const IS_24H = computed(() => props.options.twentyFourHour ?? true);

    const isPlaceholder = computed(() => !props.modelValue || String(props.modelValue).length === 0);

    const selectedLabel = computed(() => {
        if (isPlaceholder.value) return t(props.options.placeholder ?? 'FORM_SELECT_PLACEHOLDER');
        return displayLabel(String(props.modelValue));
    });

    // const dropdownClasses = computed(() => ({
    //     'bottom-full mb-2': position.value === 'top',
    //     'top-full mt-2': position.value !== 'top',
    //     'right-0': props.options.align === 'right',
    //     'left-0': props.options.align !== 'right',
    // }));

    // --- Utils ---
    function pad2(n: number) {
        return String(n).padStart(2, '0');
    }
    function displayLabel(value: string): string {
        const [hhRaw, mmRaw] = value.split(':').map(Number);
        const hh = hhRaw ?? 0;
        const mm = mmRaw ?? 0;
        if (!IS_24H.value) {
            const ampm = hh >= 12 ? 'PM' : 'AM';
            const h12 = hh % 12 === 0 ? 12 : hh % 12;
            return `${h12}:${pad2(mm)} ${ampm}`;
        }
        return `${pad2(hh)}:${pad2(mm)}`;
    }
    // Note: MIN and MAX validation removed to allow free time selection

    // --- Local selection state ---
    const selectedHour = ref<number | null>(null);
    const selectedMinute = ref<number | null>(null);

    function splitValue(val: string | number | undefined) {
        if (!val && val !== 0) return { h: null as number | null, m: null as number | null };
        const s = String(val);
        const [hStr, mStr] = s.split(':');
        const h = Number(hStr);
        const m = Number(mStr);
        return Number.isFinite(h) && Number.isFinite(m) ? { h, m } : { h: null, m: null };
    }

    // Seed from modelValue when opening, or use current time if no value
    watch(isOpen, async (open) => {
        if (open) {
            const { h, m } = splitValue(props.modelValue as string);
            if (h !== null && m !== null) {
                selectedHour.value = h;
                selectedMinute.value = m;
            } else {
                // Start with current time if no value is set
                const now = new Date();
                selectedHour.value = now.getHours();
                selectedMinute.value = Math.floor(now.getMinutes() / STEP.value) * STEP.value;
            }
            await nextTick(() => setContent());
            requestAnimationFrame(() => updatePlacement());
        }
    });

    watch([selectedHour, STEP], async () => {
        if (!isOpen.value) return;
        await nextTick();
        requestAnimationFrame(() => updatePlacement());
    });

    // Note: The old column-based picker methods (pickHour, pickMinute, hours, minutesForSelectedHour)
    // are no longer used with the new arrow-based interface, but kept for potential future use

    const canConfirm = computed(() => selectedHour.value !== null && selectedMinute.value !== null);

    function confirm() {
        if (!canConfirm.value) return;
        const value = `${pad2(selectedHour.value!)}:${pad2(selectedMinute.value!)}`;
        emit('update:modelValue', value);
        emit('blur');
        isOpen.value = false;
    }

    function clear() {
        selectedHour.value = null;
        selectedMinute.value = null;
        emit('update:modelValue', '');
        emit('blur');
    }

    // Arrow button functionality
    function incrementHour() {
        if (selectedHour.value === null) {
            selectedHour.value = 0;
        } else {
            selectedHour.value = (selectedHour.value + 1) % 24;
        }
    }

    function decrementHour() {
        if (selectedHour.value === null) {
            selectedHour.value = 23;
        } else {
            selectedHour.value = selectedHour.value === 0 ? 23 : selectedHour.value - 1;
        }
    }

    function incrementMinute() {
        if (selectedMinute.value === null) {
            selectedMinute.value = 0;
        } else {
            const newMinute = selectedMinute.value + STEP.value;
            selectedMinute.value = newMinute >= 60 ? newMinute - 60 : newMinute;
        }
    }

    function decrementMinute() {
        if (selectedMinute.value === null) {
            selectedMinute.value = 0;
        } else {
            const newMinute = selectedMinute.value - STEP.value;
            selectedMinute.value = newMinute < 0 ? newMinute + 60 : newMinute;
        }
    }
</script>
