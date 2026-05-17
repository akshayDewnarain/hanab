import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useDropdown } from '@/composables/fields/useDropdown';
import type { OptionMap } from '@/modules/types/support/inputs/OptionMap.ts';
import type { Primitive } from '@/modules/types/support/inputs/Primitive.ts';
import type { SelectFieldOptions } from '@/modules/types/support/inputs/SelectFieldOptions.ts';
import type { StaticOptions } from '@/modules/types/support/inputs/StaticOptions.ts';
import type { IndexResponseLike } from '@/modules/types/support/responses/IndexResponseLike.ts';
import type { AxiosResponse } from 'axios';

export function useSelectField(
    props: {
        options: SelectFieldOptions;
        modelValue: string | number | (string | number)[] | null;
    },
    emit: {
        (e: 'update:modelValue', value: string | number | Array<string | number> | null): void;
        (e: 'blur'): void;
    },
) {
    const { t } = useI18n();

    function normalizeOptions(input?: StaticOptions): OptionMap[] {
        if (input == null) return [];

        // Record<string, Primitive>  -> [{label, value}]
        if (!Array.isArray(input)) {
            return Object.entries(input).map(([label, value]) => ({ label, value }));
        }

        if (input.length === 0) return [];

        const first = input[0];

        if (typeof first === 'string' || typeof first === 'number') {
            return (input as Primitive[]).map((v) => ({ label: String(v), value: v }));
        }

        return (input as Array<{ label: string; value: unknown }>)
            .filter(
                (o): o is { label: string; value: Primitive } =>
                    typeof o?.label === 'string' && (typeof o?.value === 'string' || typeof o?.value === 'number'),
            )
            .map((o) => ({ label: o.label, value: o.value }));
    }

    const items = ref<OptionMap[]>(normalizeOptions(props.options.options));
    const loading = ref(false);
    const loadError = ref<string | null>(null);
    const loadedOnce = ref(items.value.length > 0); // true if static options provided

    async function loadFromModel() {
        if (!props.options.remoteFetch) return;

        loading.value = true;
        loadError.value = null;

        try {
            const { instance, map, params, filter } = props.options.remoteFetch;

            const model = new instance();
            const res: AxiosResponse<IndexResponseLike<unknown>> = await model.index(params);

            const raw = res.data;
            const rows = Array.isArray(raw)
                ? raw
                : raw && typeof raw === 'object' && 'data' in raw
                  ? (raw as any).data
                  : null;

            if (!Array.isArray(rows)) {
                console.error('Expected array data, got:', raw);
                loadError.value = 'Invalid response format';
                return;
            }

            if (!map) return;

            const sourceRows = typeof filter === 'function' ? rows.filter((row: any) => filter(row)) : rows;

            // ✅ Narrow the union properly
            if (typeof map === 'function') {
                items.value = sourceRows.map((row: any) => map(row));
                return;
            }

            const { label, value } = map;

            items.value = sourceRows.map((row: any) => ({
                label: row?.[label],
                value: row?.[value],
            }));
            loadedOnce.value = true;
        } catch (error) {
            loadError.value = 'Failed to load options';
            console.error('Remote fetch error:', error);
        } finally {
            loading.value = false;
        }
    }

    const handleBlur = () => emit('blur');

    const { isOpen, triggerRef, toggleDropdown: toggleDropdownRaw, closeDropdown, dropdownRef, dropdownStyles, updatePlacement } =
        useDropdown(handleBlur, {
            preferredPosition: props.options.position as 'top' | 'bottom',
            align: props.options.align as 'left' | 'right',
            matchWidth: true,
            offset: 8,
            viewportPadding: 8,
        });

    function toggleDropdown(): void {
        if (props.options.disabled) {
            return;
        }
        toggleDropdownRaw();
    }

    watch(
        () => props.options.disabled,
        (disabled) => {
            if (disabled) {
                closeDropdown();
            }
        },
    );

    const isMultiple = computed(() => props.options.multiple === true);

    type SelectValue = string | number;
    type ModelValue = SelectValue | SelectValue[] | null | undefined;

    const selectedOptions = computed(() => {
        const mv = props.modelValue as ModelValue;

        if (isMultiple.value) {
            if (!Array.isArray(mv)) return [];

            const selectedKeys = new Set(mv.map((v) => String(v)));

            return items.value.filter((o) => selectedKeys.has(String(o.value)));
        }

        if (mv === null || mv === undefined || Array.isArray(mv)) return [];

        const key = String(mv);
        return items.value.filter((o) => String(o.value) === key);
    });

    const selectedLabel = computed(() => {
        if (isMultiple.value && Array.isArray(props.modelValue)) {
            if (props.modelValue.length === 0) {
                return t(props.options.placeholder ?? 'FORM_SELECT_PLACEHOLDER');
            }
            return selectedOptions.value.map((o) => getDisplayLabel(getOptionLabel(o))).join(', ');
        } else if (!isMultiple.value) {
            const selectedOption = selectedOptions.value[0];
            return selectedOption
                ? getDisplayLabel(getOptionLabel(selectedOption))
                : t(props.options.placeholder ?? 'FORM_SELECT_PLACEHOLDER');
        }
        return t(props.options.placeholder ?? 'FORM_SELECT_PLACEHOLDER');
    });

    const isPlaceholder = computed(() => {
        if (isMultiple.value && Array.isArray(props.modelValue)) {
            return props.modelValue.length === 0;
        } else if (!isMultiple.value) {
            return !selectedOptions.value[0];
        }
        return true;
    });

    const handleOptionSelect = (option: OptionMap) => {
        if (props.options.disabled) {
            return;
        }
        if (isMultiple.value) {
            const currentValues = Array.isArray(props.modelValue) ? props.modelValue : [];
            if (typeof option.value !== 'number') {
                return;
            }

            const newValues = currentValues.includes(option.value)
                ? currentValues.filter((v) => v !== option.value)
                : [...currentValues, option.value];
            emit('update:modelValue', newValues);
        } else {
            emit('update:modelValue', option.value);
            toggleDropdown();
        }
    };

    const clear = () => {
        if (props.options.disabled) {
            return;
        }
        if (props.options.hideClearButton === true) {
            return;
        }
        if (isMultiple.value) {
            emit('update:modelValue', []);
        } else {
            emit('update:modelValue', '');
        }
        emit('blur');
    };

    const getOptionKey = (o: OptionMap) => o.value;
    const getOptionLabel = (o: OptionMap) => o.label;

    /** Display label for an option: translate when configured for remote rows or static keyed labels. */
    const getDisplayLabel = (label: string): string => {
        if (props.options.translateOptionLabels === true || props.options.remoteFetch?.translate === true) {
            return t(label);
        }
        return label;
    };

    watch(isOpen, (open) => {
        if (open && !loadedOnce.value && !props.options.remoteFetch?.immediate) {
            loadFromModel();
        }
    });

    if (props.options.remoteFetch?.immediate && !loadedOnce.value) {
        loadFromModel();
    }

    return {
        // State
        items,
        loading,
        loadError,
        isOpen,
        triggerRef,
        dropdownRef,
        dropdownStyles,

        // Computed
        isMultiple,
        selectedOptions,
        selectedLabel,
        isPlaceholder,

        // Methods
        toggleDropdown,
        handleOptionSelect,
        clear,
        getOptionKey,
        getOptionLabel,
        getDisplayLabel,
        updatePlacement,
    };
}
