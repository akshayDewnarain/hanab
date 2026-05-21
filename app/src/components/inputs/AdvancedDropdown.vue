<template>
    <div
        ref="triggerRef"
        :class="fullWidth ? 'relative block w-full min-w-0 text-left group' : 'relative inline-block text-left group'"
    >
        <div>
            <slot :onToggleDropdown="onToggleDropdown" name="trigger">
                <button
                    id="options-menu"
                    :class="[
                        customClass,
                        'inline-flex items-center gap-x-2 bg-white text-sm text-gray-700 border border-gray-200 w-full rounded px-4 py-2 shadow cursor-pointer',
                        'transition-all duration-200 ease-in-out',
                        isOpen ? 'ring-2 ring-[var(--color-primary)] ring-offset-0' : 'ring-0',
                        'hover:ring-2 hover:ring-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)] group-hover:ring-2 group-hover:ring-[var(--color-primary)]',
                    ]"
                    aria-expanded="true"
                    aria-haspopup="true"
                    type="button"
                    @click="onToggleDropdown"
                >
                    <span>{{ t('GENERAL_ACCEPT') }}</span>
                    <Icon
                        :class="{ 'rotate-270': isOpen }"
                        class="w-5 h-5 rotate-90 text-gray-500 transition-transform duration-200 ease-in-out"
                        icon="material-symbols:chevron-right-rounded"
                    />
                </button>
            </slot>
        </div>

        <transition name="fade">
            <div
                v-if="isOpen"
                ref="dropdownRef"
                :class="[dropdownClasses, customDropdownClass]"
                :style="maxHeight ? { maxHeight: maxHeight, overflowY: 'auto' } : {}"
                aria-labelledby="options-menu"
                aria-orientation="vertical"
                class="absolute w-56 rounded-md border border-gray-200 bg-white shadow-lg z-[100]"
                role="menu"
            >
                <div
                    v-if="showCaret"
                    :class="triangleClasses"
                    class="absolute w-0 h-0 border-x-8 border-x-transparent"
                ></div>

                <div class="py-1" role="none">
                    <slot name="dropdown-content"></slot>
                </div>
            </div>
        </transition>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, nextTick, ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { useDropdown } from '@/composables/fields/useDropdown';

    defineComponent({
        name: 'AdvancedDropdown',
    });

    const props = withDefaults(
        defineProps<{
            position?: 'top' | 'bottom';
            align?: 'left' | 'right';
            customClass?: string | string[] | Record<string, boolean>;
            customDropdownClass?: string | string[] | Record<string, boolean>;
            showCaret?: boolean;
            maxHeight?: string;
            /** Use in full-width form rows so the trigger can span 100% width. */
            fullWidth?: boolean;
        }>(),
        {
            showCaret: true,
            maxHeight: undefined,
            fullWidth: false,
        },
    );

    defineEmits<{
        (e: 'update:modelValue', value: unknown): void;
    }>();

    const { t } = useI18n();
    const { isOpen, triggerRef, dropdownRef, toggleDropdown, closeDropdown } = useDropdown();

    defineExpose({
        closeDropdown,
    });

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
