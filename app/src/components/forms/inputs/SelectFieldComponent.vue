<template>
    <div v-if="options.label" class="flex flex-col gap-1">
        <label :for="options.name" class="text-sm font-medium">
            {{ t(options.label) }}
            <span v-if="options.required" class="text-red-500 font-bold">*</span>
        </label>

        <!-- Dropdown trigger button -->
        <div ref="triggerRef" class="relative block w-full min-w-0 text-left group">
            <div>
                <div
                    id="options-menu"
                    :class="[
                        'inline-flex items-center gap-x-2 text-sm text-gray-700 border border-gray-200 w-full rounded px-4 py-2 shadow',
                        options.disabled
                            ? 'cursor-not-allowed bg-gray-50 opacity-95'
                            : 'cursor-pointer bg-white transition-all duration-200 ease-in-out hover:ring-2 hover:ring-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)] group-hover:ring-2 group-hover:ring-[var(--color-primary)]',
                        !options.disabled && isOpen ? 'ring-2 ring-[var(--color-primary)] ring-offset-0' : 'ring-0',
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
                            v-if="!options.hideClearButton && !options.disabled"
                            class="h-7 w-7 shrink-0 flex items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded transition-colors duration-200 ease-in hover:bg-[var(--color-highlight-dark)]"
                            type="button"
                            @click.stop="clear()"
                        >
                            <Icon class="text-white w-4 h-4" icon="material-symbols:delete-outline-rounded" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Teleport to body: fixed positioning matches viewport (avoids transformed ancestors e.g. detail page stagger). -->
            <Teleport to="body">
                <Transition name="fade">
                    <div
                        v-if="isOpen && !options.disabled"
                        ref="dropdownRef"
                        :style="dropdownStyles"
                        aria-labelledby="options-menu"
                        aria-orientation="vertical"
                        class="bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-2 ring-[var(--color-primary)] ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
                        role="menu"
                    >
                        <!-- Loading state -->
                        <div v-if="loading" class="px-4 py-2 text-sm text-gray-500">{{ t('FORM_LOADING') }}...</div>

                        <!-- Error state -->
                        <div v-else-if="loadError" class="px-4 py-2 text-sm text-red-500">
                            {{ loadError }}
                        </div>

                        <!-- Options -->
                        <template v-else>
                            <div
                                v-for="option in items"
                                :key="getOptionKey(option)"
                                :class="[
                                    'px-4 py-2 text-sm cursor-pointer select-none relative hover:bg-gray-100 flex items-center gap-2',
                                ]"
                                role="menuitem"
                                @click="handleOptionSelect(option)"
                            >
                                <!-- Selection indicator dot -->
                                <div
                                    v-if="selectedOptions.some((o) => o.value === option.value)"
                                    class="w-2 h-2 rounded-full bg-[var(--color-primary)] flex-shrink-0"
                                ></div>
                                <div v-else class="w-2 h-2 rounded-full flex-shrink-0"></div>
                                <span
                                    :class="{
                                        'font-semibold': selectedOptions.some((o) => o.value === option.value),
                                    }"
                                >
                                    {{ getDisplayLabel(getOptionLabel(option)) }}
                                </span>
                            </div>

                            <!-- No options -->
                            <div v-if="items.length === 0" class="px-4 py-2 text-sm text-gray-500">
                                {{ t('FORM_NO_OPTIONS') }}
                            </div>
                        </template>
                    </div>
                </Transition>
            </Teleport>
        </div>

        <!-- Error message -->
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<script lang="ts" setup>
    import { defineComponent } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useSelectField } from '@/composables/forms/useSelectField';
    import { useI18n } from 'vue-i18n';
    import type { SelectFieldOptions } from '@/modules/types/support/inputs/SelectFieldOptions.ts';

    defineComponent({ name: 'SelectFieldComponent' });

    const props = defineProps<{
        options: SelectFieldOptions;
        error?: string;
        modelValue: string | number | (string | number)[] | null;
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', value: string | number | Array<string | number> | null): void;
        (e: 'blur'): void;
    }>();

    const { t } = useI18n();

    const {
        isOpen,
        triggerRef,
        toggleDropdown,
        handleOptionSelect,
        clear,
        selectedLabel,
        isPlaceholder,
        selectedOptions,
        items,
        loading,
        loadError,
        dropdownRef,
        dropdownStyles,
        // updatePlacement,
        getOptionKey,
        getOptionLabel,
        getDisplayLabel,
    } = useSelectField(props, emit);
</script>
