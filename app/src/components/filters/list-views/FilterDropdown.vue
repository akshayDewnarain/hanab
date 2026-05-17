<template>
    <div ref="triggerRef" class="relative inline-block text-left group">
        <div>
            <button
                id="options-menu"
                :class="[customClass]"
                aria-expanded="true"
                aria-haspopup="true"
                class="inline-flex items-center gap-x-2 bg-white text-sm text-gray-700 border border-gray-200 w-full rounded px-4 py-2 shadow hover:outline-2 hover:outline-[var(--color-primary)] focus:outline-2 focus:outline-[var(--color-primary)] group-hover:outline-2 group-hover:outline-[var(--color-primary)] transition-colors duration-200 ease-in-out cursor-pointer"
                type="button"
                @click="onToggleDropdown"
            >
                <span>{{ t(placeholder) }}</span>
                <Icon
                    :class="{ 'rotate-270': isOpen }"
                    class="w-5 h-5 rotate-90 text-gray-500 transition-transform duration-200 ease-in-out"
                    icon="material-symbols:chevron-right-rounded"
                />
            </button>
        </div>

        <teleport to="body">
            <transition name="fade">
                <div
                    v-if="isOpen"
                    ref="dropdownRef"
                    :class="[dropdownClasses, customDropdownClass]"
                    :style="dropdownStyles"
                    class="absolute w-56 rounded-md shadow-lg bg-white border border-gray-200 z-50"
                    role="menu"
                >
                    <div :class="[triangleClasses]" class="absolute w-0 h-0 border-x-8 border-x-transparent"></div>
                    <div class="py-1" role="none">
                        <slot name="dropdown-content"></slot>
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

<script lang="ts" setup>
    import { computed, nextTick, ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { useDropdown } from '@/composables/fields/useDropdown';

    const props = defineProps<{
        placeholder: string;
        modelValue: unknown;
        position?: 'top' | 'bottom';
        align?: 'left' | 'right';
        customClass?: string | string[] | Record<string, boolean>;
        customDropdownClass?: string | string[] | Record<string, boolean>;
    }>();

    defineEmits<{
        (e: 'update:modelValue', value: unknown): void;
    }>();

    const { t } = useI18n();
    const { isOpen, triggerRef, dropdownRef, toggleDropdown } = useDropdown();
    const dropdownStyles = ref<Record<string, string>>({});

    function onToggleDropdown() {
        toggleDropdown();
        if (!isOpen.value) return;

        nextTick(() => {
            const trigger = triggerRef.value;
            const menu = dropdownRef.value;

            if (trigger && menu) {
                const rect = trigger.getBoundingClientRect();
                dropdownStyles.value = {
                    position: 'absolute',
                    top: `${rect.bottom + window.scrollY}px`,
                    left: `${rect.left + window.scrollX}px`,
                    minWidth: `${rect.width}px`,
                };
            }
        });
    }

    const dropdownClasses = computed(() => {
        return {
            'bottom-full mb-2': props.position === 'top',
            'top-full mt-2': props.position !== 'top',
            'right-0': props.align === 'right',
            'left-0': props.align !== 'right',
        };
    });

    const triangleClasses = computed(() => {
        const alignClasses = props.align === 'right' ? 'left-[85%]' : 'right-[85%]';

        return props.position === 'top'
            ? `${alignClasses} bottom-0 -mb-2 border-t-8 border-b-gray-400`
            : `${alignClasses} top-0 -mt-2 border-b-8 border-b-gray-400`;
    });
</script>
