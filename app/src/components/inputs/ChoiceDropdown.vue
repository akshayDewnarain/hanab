<template>
    <AdvancedDropdown
        ref="menu"
        :align="align"
        :full-width="true"
        :match-width="true"
        :max-height="maxHeight"
        :position="position"
        :show-caret="false"
    >
        <template #trigger="{ onToggleDropdown }">
            <button
                type="button"
                class="inline-flex min-h-9 w-full cursor-pointer items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2 text-left text-sm font-medium text-list-text"
                @click="onToggleDropdown"
            >
                <span class="min-w-0 flex-1 truncate">{{ current?.label || placeholder }}</span>
                <Icon
                    :class="menu?.isOpen ? 'rotate-180' : ''"
                    class="size-4 shrink-0 text-slate-400 transition-transform duration-200"
                    icon="material-symbols:keyboard-arrow-down-rounded"
                />
            </button>
        </template>
        <template #dropdown-content>
            <ul class="m-0 list-none p-1">
                <li v-for="option in options" :key="option.value">
                    <button
                        type="button"
                        class="flex w-full cursor-pointer flex-col rounded-md px-2 py-1.5 text-left hover:bg-slate-50"
                        :class="option.value === modelValue ? 'bg-primary/10 text-primary' : 'text-list-text'"
                        @click="choose(option.value)"
                    >
                        <span class="text-sm font-medium">{{ option.label }}</span>
                        <span v-if="option.help" class="text-xs font-normal text-list-text-muted">{{ option.help }}</span>
                    </button>
                </li>
            </ul>
        </template>
    </AdvancedDropdown>
</template>

<script lang="ts" setup generic="T extends string">
    import { computed, ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import AdvancedDropdown from '@/components/inputs/AdvancedDropdown.vue';

    export type ChoiceOption<Value extends string = string> = {
        value: Value;
        label: string;
        help?: string;
    };

    const props = withDefaults(
        defineProps<{
            modelValue: T;
            options: ChoiceOption<T>[];
            placeholder?: string;
            position?: 'top' | 'bottom';
            align?: 'left' | 'right';
            maxHeight?: string;
        }>(),
        {
            placeholder: '',
            position: 'bottom',
            align: 'left',
            maxHeight: '16rem',
        },
    );

    const emit = defineEmits<{
        (e: 'update:modelValue', value: T): void;
    }>();

    const menu = ref<{ closeDropdown: () => void; isOpen?: boolean } | null>(null);
    const current = computed(() => props.options.find((option) => option.value === props.modelValue));

    function choose(value: T): void {
        emit('update:modelValue', value);
        menu.value?.closeDropdown();
    }
</script>
