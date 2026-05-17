<template>
    <div class="overflow-y-auto h-full">
        <form class="grid grid-cols-12 gap-4 p-4" @submit.prevent>
            <component
                :is="field.getComponent()"
                v-for="(field, index) in fields"
                :key="index"
                :class="getFieldClass(field)"
                :error="validator?.getError(field.options.name)"
                :modelValue="formData[field.options.name]"
                :options="field.options"
                v-bind="field.getExtraProps(modelInstance)"
                @blur="() => validator?.markTouched(field.options.name)"
                @update:modelValue="(val: unknown) => handleInput(field, val)"
            />
        </form>
    </div>
</template>

<script lang="ts" setup>
    import { defineComponent, defineProps, onMounted, reactive, ref } from 'vue';
    import type { EntityModalProps } from '@/modules/types/support/modals/EntityModalProps.ts';
    import type Model from '@/modules/models/support/Model.ts';
    import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';
    import BaseInputField from '@/modules/models/support/inputs/BaseInputField.ts';
    import { useFormValidator } from '@/composables/forms/useFormValidator';
    import { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';
    import { useModalComponent } from '@/composables/modals/useModalComponent';
    import { useToastService } from '@/composables/useToastService';

    defineComponent({
        name: 'EntityModal',
    });

    const props = defineProps<{
        props: EntityModalProps;
    }>();

    const modelInstance = new props.props.model();
    const fields = ref<BaseInputField[]>([]);
    const formData = reactive<Record<string, unknown>>({});
    const validator = ref<ReturnType<typeof useFormValidator>>();
    const toastService = useToastService();

    const { notifyAccepted, notifyDismissed } = useModalComponent({
        name: `MODAL_ENTITY_${props.props.entity}`,
    });

    /**
     * @override
     * @see useModalComponent
     */
    function getData(): Record<string, unknown> {
        return {
            model: props.props.model,
            record: {
                ...formData,
            },
        };
    }

    /**
     * @override
     * @see useModalComponent
     */
    function isValidForAccept(): boolean {
        if (!validator.value) {
            return false;
        }

        if (!validator.value?.validateAll()) {
            toastService.warning('ERROR_FORM_VALIDATION_NOT_PASSED');
        }

        return validator.value?.validateAll();
    }

    function handleInput(field: BaseInputField, value: unknown) {
        const name = field.options.name;
        formData[name] = value;
    }

    function getFieldClass(field: BaseInputField): string {
        const width = field.options.width ?? 'w-full';

        switch (width) {
            case FieldWidth.HALF:
                return 'col-span-6';
            case FieldWidth.THIRD:
                return 'col-span-4';
            case FieldWidth.QUARTER:
                return 'col-span-3';
            case FieldWidth.THIRD_QUARTER:
                return 'col-span-9';
            default:
                return 'col-span-12';
        }
    }

    defineExpose({
        isValidForAccept,
        notifyAccepted,
        notifyDismissed,
        getData,
    });

    onMounted(async () => {
        fields.value = modelInstance.inputFields().filter((field: BaseInputField) => {
            return !field.options.hideInCreate;
        });

        const modelCtor = props.props.model as ModelConstructor<unknown> & {
            hydrateForCreate?: (instance: Model<unknown>) => Promise<void>;
        };
        if (typeof modelCtor.hydrateForCreate === 'function') {
            await modelCtor.hydrateForCreate(modelInstance);
        }

        for (const field of fields.value) {
            const name = field.options.name;
            formData[name] = props.props.record?.[name] ?? field.options.defaultValue ?? '';
        }

        validator.value = useFormValidator(fields.value, formData);
    });
</script>
