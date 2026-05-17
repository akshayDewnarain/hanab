<template>
    <div class="p-4">
        <div class="pointer-events-auto w-full max-w-sm shadow-md">
            <div
                class="flex items-start gap-3 rounded border border-amber-200 bg-gradient-to-r from-[#fef3c6]/90 to-[#ffffff]/90 p-4 shadow-lg shadow-amber-100/50 backdrop-blur"
            >
                <!-- Icon bubble -->
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full">
                    <Icon
                        class="w-6 h-6 max-h-[20px] max-w-[20px] bg-amber-500 rounded-full"
                        icon="material-symbols:exclamation-rounded"
                    />
                </div>

                <!-- Message -->
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ t('TOAST_WARNING') }}
                    </p>
                    <!-- Optional subtitle: keep empty or add a prop later -->
                    <p class="mt-0.5 text-sm text-slate-500">{{ toastBody }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';

    defineComponent({
        name: 'WarningToast',
    });

    const { t, te } = useI18n();

    const props = defineProps<{
        message: string;
    }>();

    const toastBody = computed(() => (te(props.message) ? t(props.message) : props.message));
</script>
