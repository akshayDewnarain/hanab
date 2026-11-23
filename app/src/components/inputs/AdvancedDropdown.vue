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

        <Teleport to="body">
            <transition name="fade">
                <div
                    v-if="isOpen"
                    ref="dropdownRef"
                    :class="[matchWidth ? 'min-w-0' : 'w-56', customDropdownClass]"
                    :style="menuStyle"
                    aria-labelledby="options-menu"
                    aria-orientation="vertical"
                    class="relative z-[1000] rounded-md border border-gray-200 bg-white shadow-lg"
                    role="menu"
                    @click.stop
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
        </Teleport>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, type CSSProperties } from 'vue';
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
            /** Size the menu to the trigger. The menu is portaled so it is not clipped by the modal. */
            matchWidth?: boolean;
        }>(),
        {
            showCaret: true,
            maxHeight: undefined,
            fullWidth: false,
            matchWidth: false,
        },
    );

    defineEmits<{
        (e: 'update:modelValue', value: unknown): void;
    }>();

    const { t } = useI18n();
    const {
        isOpen,
        triggerRef,
        dropdownRef,
        toggleDropdown,
        closeDropdown,
        dropdownStyles,
        position: placement,
    } = useDropdown(undefined, {
        preferredPosition: props.position ?? 'bottom',
        align: props.align ?? 'left',
        matchWidth: props.matchWidth,
    });

    defineExpose({
        closeDropdown,
        isOpen,
    });

    function onToggleDropdown() {
        toggleDropdown();
    }

    const menuStyle = computed<CSSProperties>(() => ({
        ...dropdownStyles.value,
        ...(props.maxHeight ? { maxHeight: props.maxHeight, overflowY: 'auto' as const } : {}),
    }));

    const triangleClasses = computed(() => {
        const alignClasses = props.align === 'right' ? 'left-[85%]' : 'right-[85%]';

        return placement.value === 'top'
            ? `${alignClasses} bottom-0 -mb-2 border-t-8 border-t-gray-200`
            : `${alignClasses} top-0 -mt-2 border-b-8 border-b-gray-200`;
    });
</script>
