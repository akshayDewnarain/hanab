<template>
    <div v-if="options.label" class="flex flex-col gap-1">
        <label :for="options.name" class="text-sm font-medium">
            {{ t(options.label) }}
            <span v-if="options.required" class="text-red-500 font-bold">*</span>
        </label>

        <!-- Multi-select trigger button -->
        <div ref="triggerRef" class="relative inline-block text-left group">
            <div>
                <div
                    id="options-menu"
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
                    <span
                        :class="{
                            'text-[var(--color-placeholder)]': isPlaceholder,
                            'text-black': !isPlaceholder,
                        }"
                        class="w-full text-left"
                    >
                        {{ selectedLabel }}
                    </span>
                    <div class="flex absolute right-0 h-full items-center gap-x-1 pr-2">
                        <Icon
                            :class="{ 'rotate-270': isOpen }"
                            class="w-5 h-5 rotate-90 text-gray-500 transition-transform duration-200 ease-in-out pointer-events-none"
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
            </div>

            <Teleport to="body">
                <Transition name="fade">
                    <div
                        v-if="isOpen"
                        ref="dropdownRef"
                        :style="dropdownStyles"
                        class="bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
                    >
                        <!-- Loading state -->
                        <div v-if="loading" class="px-4 py-2 text-sm text-gray-500">{{ t('GENERAL_LOADING') }}...</div>

                        <!-- Error state -->
                        <div v-else-if="loadError" class="px-4 py-2 text-sm text-red-500">
                            {{ loadError }}
                        </div>

                        <!-- Options -->
                        <div v-else>
                            <button
                                v-for="option in items"
                                :key="getOptionKey(option)"
                                :class="[
                                    'px-4 py-2 text-sm w-full text-left cursor-pointer transition-colors flex items-center gap-2 hover:bg-gray-100',
                                ]"
                                role="menuitem"
                                type="button"
                                @click="handleOptionSelect(option)"
                            >
                                <!-- Selection indicator dot -->
                                <div
                                    v-if="isSelected(option)"
                                    class="w-2 h-2 rounded-full bg-[var(--color-primary)] flex-shrink-0"
                                ></div>
                                <div v-else class="w-2 h-2 rounded-full flex-shrink-0"></div>
                                <span
                                    :class="{
                                        'font-semibold': isSelected(option),
                                    }"
                                >
                                    {{ getDisplayLabel(getOptionLabel(option)) }}
                                </span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </div>

        <!-- Error message -->
        <div v-if="error" class="text-sm text-red-500">
            {{ error }}
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useSelectField } from '@/composables/forms/useSelectField';
    import type { SelectFieldOptions } from '@/modules/types/support/inputs/SelectFieldOptions.ts';
    import { useI18n } from 'vue-i18n';

    defineComponent({ name: 'MultiSelectFieldComponent' });

    const props = defineProps<{
        options: SelectFieldOptions;
        error?: string;
        modelValue: string | number | (string | number)[] | null;
    }>();

    const { t } = useI18n();

    const emit = defineEmits<{
        (e: 'update:modelValue', value: string | number | (string | number)[] | null): void;
        (e: 'blur'): void;
    }>();

    // Normalize modelValue to always be an array for multi-select
    const normalizedModelValue = computed(() => {
        if (Array.isArray(props.modelValue)) {
            return props.modelValue;
        } else if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
            return [];
        } else {
            return [props.modelValue];
        }
    });

    const {
        items,
        loading,
        loadError,
        isOpen,
        triggerRef,
        dropdownRef,
        dropdownStyles,
        toggleDropdown,
        clear,
        getOptionKey,
        getOptionLabel,
        getDisplayLabel,
    } = useSelectField({ ...props, modelValue: normalizedModelValue.value }, emit);

    // Custom selectedLabel for multi-select
    const selectedLabel = computed(() => {
        if (normalizedModelValue.value.length === 0) {
            return t(props.options.placeholder ?? 'FORM_SELECT_PLACEHOLDER');
        }

        const selectedOptions = items.value.filter((o) => normalizedModelValue.value.includes(o.value));
        return selectedOptions.map((o) => getDisplayLabel(getOptionLabel(o))).join(', ');
    });

    // Custom isPlaceholder for multi-select
    const isPlaceholder = computed(() => {
        return normalizedModelValue.value.length === 0;
    });

    // Override handleOptionSelect to prevent dropdown from closing
    const handleOptionSelect = (option: any) => {
        if (Array.isArray(normalizedModelValue.value)) {
            const currentValues = normalizedModelValue.value;
            const newValues = currentValues.includes(option.value)
                ? currentValues.filter((v) => v !== option.value)
                : [...currentValues, option.value];
            emit('update:modelValue', newValues);
        }
        // Don't call emit('blur') to keep dropdown open
    };

    // Helper function to check if option is selected
    const isSelected = (option: any) => {
        return normalizedModelValue.value.includes(option.value);
    };
</script>
