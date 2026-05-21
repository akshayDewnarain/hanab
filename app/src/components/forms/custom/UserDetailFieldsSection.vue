<template>
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div class="flex min-w-0 flex-1 items-start gap-3">
                <Icon
                    class="mt-0.5 h-5 w-5 shrink-0 text-[var(--color-primary)]"
                    :icon="headerIcon || 'material-symbols:person-rounded'"
                />
                <div class="min-w-0 flex-1">
                    <span class="text-sm font-semibold text-gray-800">{{ t(options.label) }}</span>
                    <p v-if="descriptionKey" class="mt-1 text-xs leading-relaxed text-gray-500">
                        {{ t(descriptionKey) }}
                    </p>
                </div>
            </div>

            <div
                v-if="headerTrailingFields.length"
                class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center sm:justify-end"
            >
                <div v-for="field in headerTrailingFields" :key="field.options.name" class="min-w-0">
                    <component
                        :is="field.getComponent()"
                        :activity-id="activityId"
                        :error="resolveFieldError(field.options.name)"
                        :form-data="formData"
                        :get-field-value="getFieldValue"
                        :model-value="coerceModel(field.options.name, formData?.[field.options.name])"
                        :options="mergedFieldOptions(field)"
                        :set-field-value="setFieldValue"
                        @blur="() => resolveMarkTouched(field.options.name)"
                        @update:modelValue="onFieldUpdate(field.options.name, $event)"
                    />
                </div>
            </div>
        </div>

        <div class="mt-3 rounded border border-gray-200 bg-gray-50 p-3">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    v-for="field in gridFields"
                    :key="field.options.name"
                    :class="gridFieldWrapperClasses(field.options.name)"
                >
                    <component
                        :is="field.getComponent()"
                        :activity-id="activityId"
                        :error="resolveFieldError(field.options.name)"
                        :form-data="formData"
                        :get-field-value="getFieldValue"
                        :model-value="coerceModel(field.options.name, formData?.[field.options.name])"
                        :options="mergedFieldOptions(field)"
                        :set-field-value="setFieldValue"
                        @blur="() => resolveMarkTouched(field.options.name)"
                        @update:modelValue="onFieldUpdate(field.options.name, $event)"
                    />
                </div>
            </div>
        </div>

        <p v-if="error" class="mt-2 text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, inject } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import type BaseInputField from '@/modules/models/support/inputs/BaseInputField';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
    import {
        formBuilderGetFieldErrorKey,
        formBuilderMarkFieldTouchedKey,
    } from '@/composables/forms/formBuilderFieldContext';

    defineComponent({ name: 'UserDetailFieldsSection' });

    const props = defineProps<{
        options: BaseInputFieldOptions;
        descriptionKey?: string;
        headerIcon?: string;
        embeddedFields?: BaseInputField[];
        error?: string;
        modelValue?: unknown;
        formData?: Record<string, unknown>;
        setFieldValue?: (name: string, value: unknown) => void;
        getFieldValue?: (name: string) => unknown;
        activityId?: number;
        /** When true, each control sits on a white surface so it reads clearly on gray-50 panels. */
        elevateInputSurfaces?: boolean;
        /** Field names rendered in the section header row (e.g. active toggle beside the title). */
        headerTrailingFieldNames?: string[];
    }>();

    const { t } = useI18n();

    const injectedGetFieldError = inject(formBuilderGetFieldErrorKey);
    const injectedMarkTouched = inject(formBuilderMarkFieldTouchedKey);

    function resolveFieldError(name: string): string | undefined {
        return injectedGetFieldError?.(name);
    }

    function resolveMarkTouched(name: string): void {
        injectedMarkTouched?.(name);
    }

    const fieldsResolved = computed(() => props.embeddedFields ?? []);

    const headerTrailingNameSet = computed(() => new Set(props.headerTrailingFieldNames ?? []));

    const headerTrailingFields = computed((): BaseInputField[] => {
        const order = props.headerTrailingFieldNames ?? [];
        const map = new Map(fieldsResolved.value.map((f) => [f.options.name, f]));
        return order.map((n) => map.get(n)).filter((f): f is BaseInputField => f != null);
    });

    const gridFields = computed(() =>
        fieldsResolved.value.filter((f) => !headerTrailingNameSet.value.has(f.options.name)),
    );

    function fieldCellClass(name: string): string {
        if (name === 'address' || name === 'description' || name === 'is_active') {
            return 'md:col-span-2';
        }
        return '';
    }

    /** White elevated shell only for fields in the gray panel — not for header controls (e.g. Active). */
    function gridFieldWrapperClasses(name: string): string {
        const parts = [fieldCellClass(name)];
        if (props.elevateInputSurfaces) {
            parts.push('rounded-md border border-gray-200/80 bg-white px-3 py-2 shadow-sm');
        }
        return parts.filter(Boolean).join(' ');
    }

    function mergedFieldOptions(field: BaseInputField): BaseInputFieldOptions {
        return {
            ...field.options,
            readonly: Boolean(props.options.readonly) || Boolean(field.options.readonly),
        } as BaseInputFieldOptions;
    }

    function coerceModel(name: string, raw: unknown): unknown {
        if (name === 'active' || name === 'is_active') {
            if (raw === true || raw === 1 || raw === '1') {
                return true;
            }
            if (raw === false || raw === 0 || raw === '0') {
                return false;
            }
            if (raw === undefined || raw === null) {
                return false;
            }
            return Boolean(raw);
        }
        if (raw !== undefined && raw !== null) {
            return raw;
        }
        if (name === 'role_id') {
            return null;
        }
        return '';
    }

    function onFieldUpdate(name: string, value: unknown): void {
        props.setFieldValue?.(name, value);
    }
</script>
