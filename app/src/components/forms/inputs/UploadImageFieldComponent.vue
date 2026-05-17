<template>
    <div class="flex flex-col gap-2">
        <div class="flex justify-center">
            <div
                v-if="previewUrl"
                class="rounded-full border border-gray-300 bg-gray-100 shadow-md relative max-h-40 min-h-40 min-w-40"
            >
                <img :src="previewUrl" alt="image" class="rounded-full aspect-square max-h-40" />
                <button
                    v-if="modelValue || (!clearedExisting && existingUrl)"
                    class="absolute rounded-full top-2 right-2 bg-[var(--color-primary)] border border-gray-300 min-h-8 max-h-8 aspect-square flex justify-center items-center text-white px-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                    type="button"
                    @click.stop="clear"
                >
                    <icon class="inline-block" height="16" icon="material-symbols:close" />
                </button>
            </div>
            <div
                v-else
                class="rounded-full p-3 border border-gray-300 bg-gray-100 shadow-md max-h-40 min-h-40 min-w-40 flex items-center justify-center"
            >
                <icon class="" height="48" icon="material-symbols:image-not-supported-outline-rounded" />
            </div>
        </div>

        <div class="col-span-full flex justify-center">
            <div
                class="w-full relative border-gray-300 border-2 border-dashed rounded-lg p-4 text-center cursor-pointer hover:border-[var(--color-primary)] transition bg-gray-100 max-w-lg"
                @click="pick"
                @dragover.prevent
                @drop.prevent="onDrop"
            >
                <input ref="file" :accept="options.accept" class="hidden" type="file" @change="onPick" />

                <div class="text-gray-500 text-sm">
                    <p>{{ options.placeholder || 'Drop an image here or click to upload' }}</p>
                    <p class="mt-1 opacity-75">Max. {{ options.maxFileSizeMB }}MB</p>
                </div>
            </div>
        </div>

        <Collapse :duration="500" :max-height="'50px'" :show="!!error">
            <div v-if="error" class="text-red-500 text-xs ml-1">
                {{ t(error) }}
            </div>
        </Collapse>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, onBeforeUnmount, ref, watch } from 'vue';
    import type { UploadImageFieldOptions } from '@/modules/types/support/inputs/UploadImageFieldOptions.ts';
    import Collapse from '@/components/transitions/Collapse.vue';
    import { useI18n } from 'vue-i18n';
    import { Icon } from '@iconify/vue';
    import { useToastService } from '@/composables/useToastService';
    import ConfirmationModal from '@/components/modals/content-modals/ConfirmationModal.vue';
    import { useModalService } from '@/composables/useModalService';
    import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
    import { ModalVariant } from '@/modules/enums/support/modals/ModalVariant.ts';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
    import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType.ts';
    import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget.ts';

    defineComponent({
        name: 'FieldUploadImage',
    });

    const props = defineProps<{
        options: UploadImageFieldOptions;
        error?: string;
        modelValue: string | number | object;
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', v: File | null): void;
        (e: 'removed'): void;
    }>();
    const { t } = useI18n();

    const file = ref<HTMLInputElement | null>(null);
    const objectUrl = ref<string | null>(null);
    const clearedExisting = ref(false);
    const toastService = useToastService();
    const modalService = useModalService();

    const existingUrl = computed(() => props.options.media?.thumb_url ?? props.options.media?.url ?? '');

    // final preview priority: new file → existing (if not cleared) → none
    const previewUrl = computed<string | null>(() => {
        if (objectUrl.value) return objectUrl.value;
        if (!clearedExisting.value && existingUrl.value) return existingUrl.value;
        return null;
    });

    onBeforeUnmount(() => {
        if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
    });

    function pick() {
        file.value?.click();
    }

    function onPick(e: Event) {
        const f = (e.target as HTMLInputElement).files?.[0] ?? null;
        if (f) clearedExisting.value = false;
        emit('update:modelValue', f);
    }

    function onDrop(e: DragEvent) {
        const f = e.dataTransfer?.files?.[0] ?? null;
        if (f) clearedExisting.value = false;
        emit('update:modelValue', f);
    }

    async function clear() {
        modalService.open({
            title: 'GENERAL_WARNING_REMOVE_IMAGE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: ConfirmationModal,
            props: {
                message: 'GENERAL_WARNING_REMOVE_IMAGE_MESSAGE',
                variant: ModalVariant.WARNING,
            },
            actions: [
                {
                    type: ModalResultType.DISMISS,
                    name: 'GENERAL_CANCEL',
                    variant: ModalActionType.DISMISS,
                    target: ModalActionTarget.CANCEL,
                },
                {
                    type: ModalResultType.ACCEPT,
                    name: 'GENERAL_ACCEPT',
                    variant: ModalActionType.PRIMARY,
                    target: ModalActionTarget.CONFIRM,
                },
            ],
            onAccept: async () => {
                if (!props.options.callback) {
                    return;
                }

                const res = await props.options.callback();
                if (res) {
                    emit('update:modelValue', null);
                    if (file.value) file.value.value = '';
                    if (existingUrl.value) {
                        clearedExisting.value = true;
                        emit('removed');
                    }
                } else {
                    toastService.error('IMAGE_CLEAR_ERROR');
                }
            },
        });
    }

    watch(
        () => props.modelValue,
        (val: unknown, oldVal: unknown) => {
            if (objectUrl.value && oldVal instanceof File) {
                URL.revokeObjectURL(objectUrl.value);
                objectUrl.value = null;
            }
            if (val instanceof File) {
                objectUrl.value = URL.createObjectURL(val);
            }
        },
        { immediate: true },
    );
</script>
