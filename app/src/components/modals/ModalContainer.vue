<template>
    <Teleport to="body">
        <div ref="modal-container" :style="{ 'z-index': zIndex }" class="fixed">
            <OpacityFade>
                <div v-if="modals.length > 0" class="fixed inset-0 transform bg-black/20 backdrop-blur-sm"></div>
            </OpacityFade>

            <component
                :is="getMap(modal.type)"
                v-for="(modal, index) in modals"
                :key="index"
                :modal="modal"
                :z-index="getZIndex()"
            />
        </div>
    </Teleport>
</template>

<script lang="ts" setup>
    import { useModalService } from '@/composables/useModalService';
    import type { IModalService } from '@/modules/types/support/modals/IModalService.ts';
    import type { ModalProps } from '@/modules/types/support/modals/ModalProps.ts';
    import type { Component, DefineComponent } from 'vue';
    import { computed, defineAsyncComponent, defineComponent } from 'vue';
    import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
    import OpacityFade from '@/components/transitions/OpacityFade.vue';

    defineComponent({
        name: 'ModalContainer',
    });

    const modalService: IModalService = useModalService();
    const zIndex = 50;

    const modals = computed<ModalProps[]>(() => modalService.modals);

    const modalMap: Record<ModalType, ReturnType<typeof defineAsyncComponent>> = {
        [ModalType.SLIDE_IN_RIGHT]: defineAsyncComponent(
            () => import('@/components/modals/wrapper-modals/SlideInModal.vue'),
        ),
        [ModalType.SLIDE_IN_TOP_CENTER]: defineAsyncComponent(
            () => import('@/components/modals/wrapper-modals/SlideInCenterModal.vue'),
        ),
    };

    function getMap(type: ModalType | Component): ReturnType<typeof defineAsyncComponent> {
        if (typeof type === 'object' || typeof type === 'function') {
            return type as DefineComponent;
        }

        if (!(type in modalMap)) {
            console.warn(`Modal type "${type}" not found. Falling back to SLIDE_IN_RIGHT.`);
            return modalMap[ModalType.SLIDE_IN_RIGHT];
        }

        return modalMap[type as ModalType];
    }

    function getZIndex(): number {
        return zIndex + modals.value.length * 10;
    }
</script>
