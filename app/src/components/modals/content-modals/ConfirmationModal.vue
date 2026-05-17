<template>
    <div class="flex flex-col overflow-y-auto h-full">
        <component :is="getBadge().component" v-if="getBadge()" v-bind="getBadge().props" />

        <div class="p-2">
            <span class="text-sm text-gray-500">
                {{ t(props.props.message) }}
            </span>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { defineComponent } from 'vue';
    import type { ConfirmationModalProps } from '@/modules/types/support/modals/ConfirmationModalProps.ts';
    import { ModalVariant } from '@/modules/enums/support/modals/ModalVariant.ts';
    import WarningBadge from '@/components/badges/WarningBadge.vue';
    import { useI18n } from 'vue-i18n';

    defineComponent({
        name: 'ConfirmationModal',
    });

    const props = defineProps<{
        props: ConfirmationModalProps;
    }>();

    const { t } = useI18n();

    const getBadge = () => {
        switch (props.props.variant) {
            case ModalVariant.WARNING:
                return {
                    component: WarningBadge,
                    props: { message: 'GENERAL_WARNING_MESSAGE' },
                };
            case ModalVariant.ERROR:
                return {
                    component: WarningBadge,
                    props: { message: 'GENERAL_ERROR_MESSAGE' },
                };
            case ModalVariant.SUCCESS:
                return {
                    component: WarningBadge,
                    props: { message: 'GENERAL_SUCCESS_MESSAGE' },
                };
            case ModalVariant.INFO:
                return {
                    component: WarningBadge,
                    props: { message: 'GENERAL_INFO_MESSAGE' },
                };
            default:
                return {
                    component: WarningBadge,
                    props: { message: 'GENERAL_WARNING_MESSAGE' },
                };
        }
    };
</script>
