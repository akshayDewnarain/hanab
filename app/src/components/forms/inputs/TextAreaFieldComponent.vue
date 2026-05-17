<template>
    <div class="flex flex-col gap-1">
        <label :for="options.name" class="text-sm font-medium">
            {{ t(options.label) }}
            <span v-if="options.required" class="text-red-500 font-bold">*</span>
        </label>

        <textarea
            :id="options.name"
            ref="el"
            v-model="value"
            :autocomplete="options.autocomplete"
            :class="['input-base rounded px-3 py-2 text-sm', { 'font-mono': !!options.monospace }]"
            :disabled="options.disabled"
            :maxlength="options.maxLength"
            :name="options.name"
            :placeholder="t(options.placeholder ?? 'GENERAL_PLACEHOLDER')"
            :readonly="options.readonly"
            :rows="options.rows ?? 4"
            :style="textareaStyle"
            @blur="$emit('blur')"
            @input="onInput"
        />
        <Collapse :duration="500" :max-height="'50px'" :show="!!error">
            <div v-if="error" class="text-red-500 text-xs ml-1">
                {{ t(error) }}
            </div>
        </Collapse>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, onMounted, ref, watch } from 'vue';
    import type { TextareaFieldOptions } from '@/modules/types/support/inputs/TextareaFieldOptions.ts';
    import Collapse from '@/components/transitions/Collapse.vue';
    import { useI18n } from 'vue-i18n';

    defineComponent({ name: 'TextAreaFieldComponent' });

    const props = defineProps<{
        options: TextareaFieldOptions;
        error?: string;
        modelValue: string | null;
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', v: string | null): void;
        (e: 'blur'): void;
    }>();

    const { t } = useI18n();

    const value = computed({
        get: () => props.modelValue,
        set: (v: string | null) => emit('update:modelValue', v),
    });

    const el = ref<HTMLTextAreaElement | null>(null);

    /**
     * Inline style derived from options. Explicit min/maxHeight take precedence.
     * 'resize' maps directly to the CSS property.
     */
    const textareaStyle = computed(() => {
        const s: Record<string, string> = {
            resize: props.options.resize ?? 'vertical',
        };

        const { minHeight, maxHeight } = props.options;

        if (minHeight !== undefined) {
            s.minHeight = typeof minHeight === 'number' ? `${minHeight}px` : minHeight;
        }
        if (maxHeight !== undefined) {
            s.maxHeight = typeof maxHeight === 'number' ? `${maxHeight}px` : maxHeight;
        }
        return s;
    });

    /**
     * If min/maxHeight not explicitly provided, derive them from minRows/maxRows.
     */
    function applyRowsAsHeightsFallback() {
        const ta = el.value;
        if (!ta) return;

        const { minHeight, maxHeight, minRows, maxRows } = props.options;
        if (minHeight !== undefined && maxHeight !== undefined) return;

        const cs = window.getComputedStyle(ta);
        const line = parseFloat(cs.lineHeight || '0') || 20;
        const vertical =
            parseFloat(cs.paddingTop || '0') +
            parseFloat(cs.paddingBottom || '0') +
            parseFloat(cs.borderTopWidth || '0') +
            parseFloat(cs.borderBottomWidth || '0');

        if (minHeight === undefined && minRows) {
            ta.style.minHeight = `${minRows * line + vertical}px`;
        }
        if (maxHeight === undefined && maxRows) {
            ta.style.maxHeight = `${maxRows * line + vertical}px`;
        }
    }

    /**
     * Auto-resize to fit content.
     */
    function autoResizeNow() {
        const ta = el.value;
        if (!ta || !props.options.autoResize) return;
        ta.style.height = 'auto';
        ta.style.height = `${ta.scrollHeight}px`;
    }

    function onInput() {
        if (props.options.autoResize) autoResizeNow();
    }

    onMounted(() => {
        applyRowsAsHeightsFallback();
        autoResizeNow();
    });

    watch(
        () => props.modelValue,
        () => autoResizeNow(),
    );

    watch(
        () => [
            props.options.autoResize,
            props.options.minRows,
            props.options.maxRows,
            props.options.minHeight,
            props.options.maxHeight,
        ],
        () => {
            applyRowsAsHeightsFallback();
            autoResizeNow();
        },
    );
</script>
