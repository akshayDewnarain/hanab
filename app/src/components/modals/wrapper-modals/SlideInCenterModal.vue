<template>
    <div class="fixed inset-0 flex justify-center items-center transform bg-black/20 backdrop-blur-sm">
        <SlideInTopToCenter
            :enter-active="'transform 0.5s ease, opacity 0.5s ease'"
            :enter-from="'translateY(-50%)'"
            :enter-to="'translateY(0)'"
            :leave-active="'transform 0.5s ease, opacity 0.5s ease'"
            :leave-from="'translateY(0)'"
            :leave-to="'translateY(-50%)'"
        >
            <div v-show="visible" ref="modalRef" :class="panelClasses">
                <!-- Header -->
                <div
                    class="flex font-bold text-white justify-between p-4 border-b bg-(--color-background) items-center shadow-md"
                >
                    <span class="text-xl"> {{ t(_modal.title) }}</span>
                    <div
                        class="flex items-center p-2 gap-1 text-white rounded-md hover:bg-highlight-dark transition-colors duration-200 ease-in relative cursor-pointer"
                        @click="handleAction({ name: 'GENERAL_DISMISS', type: ModalResultType.DISMISS })"
                    >
                        <Icon class="text-white text-xl" icon="material-symbols:close-rounded"></Icon>
                    </div>
                </div>

                <!-- Scrollable content -->
                <div
                    class="flex min-h-0 flex-1 flex-col overflow-hidden"
                    :class="{ 'p-4': !modal.disableDefaultPadding }"
                >
                    <component
                        :is="modal.component"
                        v-if="modal.component"
                        ref="modalComponentRef"
                        :props="modal.props"
                    />
                </div>

                <!-- Footer -->
                <div class="p-4 border-t border-t-gray-300 flex justify-between bg-gray-200">
                    <!-- Left Side: Dismiss Actions -->
                    <div class="flex space-x-2">
                        <button
                            v-for="action in dismissActions"
                            :key="action.name"
                            :class="[`button-${action.variant}`, 'button']"
                            class="px-4 py-2 rounded-md shadow-md"
                            @click="handleAction(action)"
                        >
                            {{ t(action.name) }}
                        </button>
                    </div>
                    <!-- Right Side: Accept Actions -->
                    <div class="flex space-x-2">
                        <button
                            v-for="action in acceptActions"
                            :key="action.name"
                            :class="[`button-${action.variant}`, 'button']"
                            class="px-4 py-2 rounded-md shadow-md"
                            @click="handleAction(action)"
                        >
                            {{ t(action.name) }}
                        </button>
                    </div>
                </div>
            </div>
        </SlideInTopToCenter>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useWrapperModalComponent } from '@/composables/modals/useWrapperModalComponent';
    import type { ModalAction } from '@/modules/types/support/modals/ModalAction.ts';
    import type { ModalProps } from '@/modules/types/support/modals/ModalProps.ts';
    import type { ModalSize } from '@/modules/types/support/modals/ModalSize.ts';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
    import { Icon } from '@iconify/vue';
    import SlideInTopToCenter from '@/components/transitions/SlideInTopToCenter.vue';

    defineComponent({
        name: 'SlideInCenterModal',
    });

    const props = defineProps<{
        modal: ModalProps;
    }>();

    const widthBySize: Record<ModalSize, string> = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
        full: 'w-[92vw] max-w-[1200px]',
    };

    const defaultHeightClass = 'max-h-[90vh]';

    const panelClasses = computed(() => {
        const s = props.modal.size ?? 'xl';

        const hasCustomWidth =
            !!props.modal.widthClass || !!props.modal.panelClass?.match(/(?:^|\s)(!:)?(w-|max-w-|w\[|max-w\[)/);

        const hasCustomHeight =
            !!props.modal.heightClass ||
            !!props.modal.panelClass?.match(/(?:^|\s)(!:)?(min-h-|max-h-|h-|h\[|min-h\[|max-h\[)/);

        return [
            'relative flex flex-col overflow-hidden rounded-md border border-gray-500 bg-white shadow-xl',
            hasCustomWidth ? 'mx-auto w-auto max-w-full' : `w-full ${widthBySize[s]}`,
            hasCustomHeight ? null : defaultHeightClass,
            props.modal.widthClass,
            props.modal.heightClass,
            props.modal.panelClass,
        ]
            .filter(Boolean)
            .join(' ');
    });

    const loaded = ref(false);
    const modalComponentRef = ref<InstanceType<any> | null>(null);

    const { t } = useI18n();
    const _modal = props.modal;
    const { handleClick, visible, modalRef } = useWrapperModalComponent(_modal, modalComponentRef);

    const dismissActions = computed(() => {
        return props.modal.actions?.filter((action: ModalAction) => action.type === ModalResultType.DISMISS);
    });

    const acceptActions = computed(() => {
        return props.modal.actions?.filter((action: ModalAction) => action.type === ModalResultType.ACCEPT);
    });

    const handleAction = async (action: ModalAction) => {
        loaded.value = false;
        await handleClick(action, 500);
    };

    onMounted(() => {
        visible.value = true;
    });
</script>
