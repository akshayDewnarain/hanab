<template>
    <div
        class="max-w-[18.75rem] overflow-hidden text-ellipsis whitespace-nowrap cursor-pointer hover:text-[var(--color-primary)] transition-colors"
        @click.stop="openPreview"
    >
        {{ value }}
    </div>
</template>

<script lang="ts" setup>
    import { useModalService } from '@/composables/useModalService';
    import ComponentPreviewModal from '@/components/modals/content-modals/ComponentPreviewModal.vue';
    import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';

    const props = defineProps<{
        value: string;
        item?: Record<string, unknown>;
        previewComponent?: unknown;
    }>();

    const modalService = useModalService();

    function openPreview() {
        modalService.open({
            title: 'Component Preview',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: ComponentPreviewModal,
            props: {
                model: props.item || null,
                previewComponent: props.previewComponent
            },
        });
    }
</script>
