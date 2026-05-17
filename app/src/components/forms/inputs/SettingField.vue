<template>
    <div class="flex flex-col gap-1">
        <!-- Description -->
        <p v-if="setting.description" class="text-xs text-gray-500 -mt-1 mb-1">
            {{ t(setting.description) }}
        </p>

        <!-- TYPE: boolean -->
        <ToggleSwitchFieldComponent
            v-if="setting.type === 'boolean'"
            :model-value="booleanValue"
            :options="toggleOptions"
            @update:modelValue="(value: boolean) => (booleanValue = value)"
        />

        <!-- TYPE: integer -->
        <NumberFieldComponent
            v-else-if="setting.type === 'integer'"
            :model-value="numberValue"
            :options="numberOptions"
            @update:modelValue="(value: number | null) => (numberValue = value)"
        />

        <!-- TYPE: decimal -->
        <NumberFieldComponent
            v-else-if="setting.type === 'decimal'"
            :model-value="numberValue"
            :options="numberOptions"
            @update:modelValue="(value: number | null) => (numberValue = value)"
        />

        <!-- TYPE: dropdown -->
        <SelectFieldComponent
            v-else-if="setting.type === 'dropdown'"
            :model-value="selectValue"
            :options="selectOptions"
            @update:modelValue="(value: string | number | (string | number)[] | null) => (selectValue = value)"
        />

        <!-- TYPE: array / json -->
        <template v-else-if="setting.type === 'array' || setting.type === 'json'">
            <textarea
                :value="stringifyJson(typedValue)"
                class="w-full rounded border border-gray-300 px-3 py-2 text-sm font-mono min-h-[96px] focus:outline-[var(--color-primary)]"
                spellcheck="false"
                @input="emitJson(($event.target as HTMLTextAreaElement).value)"
            />
            <p v-if="jsonError" class="text-xs text-red-500">{{ jsonError }}</p>
        </template>

        <!-- TYPE: encrypted -->
        <InputFieldComponent
            v-else-if="setting.type === 'encrypted'"
            :model-value="stringValue"
            :options="inputOptions"
            @update:modelValue="(value: string | null) => (stringValue = value)"
        />

        <!-- TYPE: string (default) -->
        <InputFieldComponent
            v-else
            :model-value="stringValue"
            :options="inputOptions"
            @update:modelValue="(value: string | null) => (stringValue = value)"
        />
    </div>
</template>

<script lang="ts" setup>
    import { computed, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import SelectFieldComponent from '@/components/forms/inputs/SelectFieldComponent.vue';
    import InputFieldComponent from '@/components/forms/inputs/InputFieldComponent.vue';
    import NumberFieldComponent from '@/components/forms/inputs/NumberFieldComponent.vue';
    import ToggleSwitchFieldComponent from '@/components/forms/inputs/ToggleSwitchFieldComponent.vue';
    import type { InputFieldOptions } from '@/modules/types/support/inputs/InputFieldOptions.ts';
    import type { NumberFieldOptions } from '@/modules/types/support/inputs/NumberFieldOptions.ts';
    import type { SelectFieldOptions } from '@/modules/types/support/inputs/SelectFieldOptions.ts';
    import type { ToggleFieldOptions } from '@/modules/types/support/inputs/ToggleFieldOptions.ts';
    import Setting from '@/models/general/Setting';

    const { t } = useI18n();

    const props = defineProps<{
        setting: Setting;
        typedValue: unknown;
    }>();

    const emit = defineEmits<{
        (e: 'update:typedValue', value: unknown): void;
    }>();

    type DropdownValue = string | number | (string | number)[] | null;
    type SelectEmitValue = string | number | (string | number)[] | null;

    function toDropdownValue(v: unknown): DropdownValue {
        return typeof v === 'string' || typeof v === 'number' || v === null ? v : null;
    }

    const booleanValue = computed({
        get: () => !!props.typedValue,
        set: (value: boolean) => emit('update:typedValue', value),
    });

    const numberValue = computed({
        get: () => toNumber(props.typedValue),
        set: (value: number | null) => emit('update:typedValue', value),
    });

    const stringValue = computed({
        get: () => (props.typedValue ? String(props.typedValue) : null),
        set: (value: string | null) => emit('update:typedValue', value),
    });

    const selectValue = computed<DropdownValue>({
        get: () => toDropdownValue(props.typedValue),
        set: (value: SelectEmitValue) => {
            const normalized = Array.isArray(value) ? (value[0] ?? null) : value;

            emit('update:typedValue', normalized);
        },
    });

    const jsonError = ref<string | null>(null);

    const label = computed(() => {
        return props.setting.key.toUpperCase();
    });

    // Input field options
    const inputOptions = computed(
        (): InputFieldOptions => ({
            name: props.setting.key,
            label: label.value,
            placeholder: props.setting.description || '',
            required: false,
        }),
    );

    // Toggle field options
    const toggleOptions = computed(
        (): ToggleFieldOptions => ({
            name: props.setting.key,
            label: label.value,
            required: false,
        }),
    );

    // Number field options
    const numberOptions = computed(
        (): NumberFieldOptions => ({
            name: props.setting.key,
            label: label.value,
            placeholder: props.setting.description || '',
            required: false,
            min: 0,
            max: props.setting.type === 'integer' ? 2147483647 : 1000000000,
        }),
    );

    // Select field options
    const selectOptions = computed((): SelectFieldOptions => {
        const filteredOptions = (props.setting.setting_options ?? [])
            .filter((o) => o.active)
            .map((opt) => ({
                label: t(opt.label ?? opt.value),
                value: opt.value,
            }));

        return {
            name: props.setting.key,
            label: label.value,
            placeholder: props.setting.description || '',
            required: false,
            position: 'bottom',
            align: 'left',
            options: filteredOptions,
        };
    });

    function toNumber(v: unknown): number | null {
        if (v === '' || v === null || v === undefined) return null;
        const n = Number(v);
        return Number.isNaN(n) ? null : n;
    }

    function stringifyJson(v: unknown): string {
        try {
            if (typeof v === 'string') {
                const parsed = JSON.parse(v);
                return JSON.stringify(parsed, null, 2);
            }
            return JSON.stringify(v ?? {}, null, 2);
        } catch {
            return String(v ?? '');
        }
    }

    function emitJson(text: string) {
        try {
            if (text.trim() === '') {
                emit('update:typedValue', {});
                jsonError.value = null;
                return;
            }
            const parsed = JSON.parse(text);
            jsonError.value = null;
            emit('update:typedValue', parsed);
        } catch {
            jsonError.value = 'Invalid JSON';
            emit('update:typedValue', text);
        }
    }
</script>
