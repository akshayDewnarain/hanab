<template>
    <div ref="triggerRef" class="group relative inline-block w-full text-left">
        <div class="w-full">
            <button
                id="options-menu"
                aria-expanded="true"
                aria-haspopup="true"
                :class="[
                    'inline-flex w-full cursor-pointer items-center justify-between rounded border border-gray-200 bg-white text-gray-700 shadow transition-colors duration-200 ease-in-out hover:outline-2 hover:outline-[var(--color-primary)] focus:outline-2 focus:outline-[var(--color-primary)] group-hover:outline-2 group-hover:outline-[var(--color-primary)]',
                    compact ? 'gap-x-1.5 px-2.5 py-1.5 text-sm' : 'gap-x-2 px-4 py-2',
                ]"
                type="button"
                @click="toggleDropdown"
            >
                <span class="truncate">{{ selectedLabel }}</span>
                <Icon
                    :class="[
                        { 'rotate-270': isOpen },
                        compact ? 'size-4' : 'size-5',
                        'shrink-0 rotate-90 text-gray-500 transition-transform duration-200 ease-in-out',
                    ]"
                    icon="material-symbols:chevron-right-rounded"
                />
            </button>
        </div>

        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="isOpen"
                    ref="dropdownRef"
                    :class="menuPanelClasses"
                    :style="dropdownStyles"
                    aria-labelledby="options-menu"
                    aria-orientation="vertical"
                    class="max-h-60 overflow-auto rounded-md border border-gray-200 bg-white shadow-lg"
                    role="menu"
                >
                    <div class="py-1" role="none">
                        <button
                            v-for="option in options"
                            :key="getOptionKey(option)"
                            :class="[
                                'block w-full cursor-pointer text-left text-gray-700 hover:bg-gray-100',
                                compact ? 'px-3 py-1.5 text-sm' : 'px-4 py-2',
                            ]"
                            role="menuitem"
                            @click="selectOption(option, emit)"
                        >
                            {{ getOptionLabel(option) }}
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, withDefaults } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { useDropdown } from '@/composables/fields/useDropdown';

    defineComponent({
        name: 'CustomDropdown',
    });

    const props = withDefaults(
        defineProps<{
            placeholder: string;
            options: unknown[];
            modelValue: unknown;
            hideTriangle: boolean;
            position?: 'top' | 'bottom';
            align?: 'left' | 'right';
            compact?: boolean;
        }>(),
        {
            compact: false,
        },
    );

    const emit = defineEmits<{
        (e: 'update:modelValue', value: unknown): void;
    }>();

    const { isOpen, triggerRef, dropdownRef, toggleDropdown, selectOption, dropdownStyles } = useDropdown(
        undefined,
        {
            preferredPosition: props.position ?? 'bottom',
            align: props.align ?? 'left',
            matchWidth: true,
        },
    );
    const { t } = useI18n();

    const selectedLabel = computed(() => {
        const selectedOption = findOption(props.modelValue);
        return selectedOption ? getOptionLabel(selectedOption) : t(props.placeholder);
    });

    const menuPanelClasses = computed(() => (props.compact ? 'min-w-[4.5rem]' : 'min-w-[14rem]'));

    const findOption = (value: unknown) =>
        props.options.find((option) => option === value || (option as Record<string, unknown>).value === value);
    const getOptionKey = (option: unknown): string | number => {
        const optionObj = option as Record<string, unknown>;
        return (optionObj.value as string | number) ?? String(option);
    };
    const getOptionLabel = (option: unknown) => (option as Record<string, unknown>).label ?? option;
</script>
