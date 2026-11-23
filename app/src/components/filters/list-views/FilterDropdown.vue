<template>
    <div ref="triggerRef" class="relative inline-block text-left group">
        <div>
            <button
                id="options-menu"
                :class="[customClass]"
                :aria-expanded="isOpen"
                aria-haspopup="true"
                class="inline-flex h-8 w-auto cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2 text-[13px] font-medium text-slate-700 shadow-none ring-0 outline-none transition-colors hover:border-slate-300 hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-primary/10"
                type="button"
                @click="onToggleDropdown"
            >
                <span class="max-w-44 truncate">{{ t(placeholder) }}</span>
                <Icon
                    :class="isOpen ? 'rotate-180' : ''"
                    class="size-3.5 text-slate-400 transition-transform"
                    icon="material-symbols:expand-more-rounded"
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
                    class="absolute z-50 min-w-56 rounded-lg border border-slate-200 bg-white shadow-[0_4px_16px_rgba(15,23,42,0.06)]"
                    role="menu"
                >
                    <div role="none">
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
                    top: `${rect.bottom + window.scrollY + 4}px`,
                    left: `${rect.left + window.scrollX}px`,
                    minWidth: `${rect.width}px`,
                };
            }
        });
    }

    const dropdownClasses = computed(() => {
        return {
            'right-0': props.align === 'right',
            'left-0': props.align !== 'right',
        };
    });
</script>
