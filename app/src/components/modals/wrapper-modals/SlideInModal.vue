<template>
    <div class="fixed inset-0 flex justify-end transform bg-black/20 backdrop-blur-sm">
        <SlideInRightToLeft
            :enter-active="'transform 0.5s ease-in-out, opacity 0.5s ease-in-out'"
            :enter-from="'translateX(80%)'"
            :leave-active="'transform 0.5s ease-in-out, opacity 0.5s ease-in-out'"
        >
            <div v-show="visible" ref="modalRef" class="relative bg-white min-w-[600px] w-2/8 h-full flex flex-col">
                <!-- Header -->
                <div
                    class="flex font-bold text-white justify-between p-4 border-b bg-[var(--color-background)] items-center shadow-md"
                >
                    <span class="text-xl"> {{ t(_modal.title) }}</span>
                    <div
                        class="flex items-center p-2 gap-1 text-white rounded-md hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in relative cursor-pointer"
                        @click="handleAction({ name: 'GENERAL_DISMISS', type: ModalResultType.DISMISS })"
                    >
                        <Icon class="text-white text-xl" icon="material-symbols:close-rounded"></Icon>
                    </div>
                </div>

                <!-- Scrollable content -->
                <div class="flex-1 overflow-y-auto">
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
                            class="px-4 py-2 rounded-md"
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
                            class="px-4 py-2 rounded-md"
                            @click="handleAction(action)"
                        >
                            {{ t(action.name) }}
                        </button>
                    </div>
                </div>
            </div>
        </SlideInRightToLeft>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, onMounted, ref } from 'vue';
    import SlideInRightToLeft from '@/components/transitions/SlideInRightToLeft.vue';
    import { useI18n } from 'vue-i18n';
    import type { ModalAction } from '@/modules/types/support/modals/ModalAction.ts';
    import type { ModalProps } from '@/modules/types/support/modals/ModalProps.ts';
    import { Icon } from '@iconify/vue';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
    import { useWrapperModalComponent } from '@/composables/modals/useWrapperModalComponent';

    defineComponent({
        name: 'SlideInModal',
    });

    const props = defineProps<{
        modal: ModalProps;
    }>();

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
