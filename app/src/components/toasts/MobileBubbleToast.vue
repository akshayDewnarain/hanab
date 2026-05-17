<template>
    <div class="flex w-full items-center justify-center p-4">
        <div class="pointer-events-auto w-full max-w-sm shadow-md">
            <div
                :class="containerClasses"
                aria-live="polite"
                class="flex items-start gap-3 rounded border p-4 shadow-lg backdrop-blur"
                role="status"
            >
                <!-- Icon bubble -->
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full">
                    <Icon :class="iconClasses" :icon="iconName" class="w-6 h-6 max-h-[20px] max-w-[20px]" />
                </div>

                <!-- Message -->
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-slate-900">
                        {{ title }}
                    </p>
                    <!-- Optional subtitle: keep empty or add a prop later -->
                    <p class="mt-0.5 text-xs text-slate-500">{{ toastBody }}</p>
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
        name: 'MobileBubbleToast',
    });

    const { t, te } = useI18n();

    const props = withDefaults(
        defineProps<{
            message: string;
            variant?: 'info' | 'success' | 'warning' | 'error';
        }>(),
        {
            variant: 'info',
        },
    );

    /** Avoid double-translation: callers often pass `t('KEY')` already; raw i18n keys still work. */
    const toastBody = computed(() => (te(props.message) ? t(props.message) : props.message));

    const containerClasses = computed(() => {
        const variants: Record<string, string> = {
            info: 'border-sky-200 bg-gradient-to-r from-[#dff2fe]/90 from-[40%] to-[#ffffff]/90 shadow-sky-100/50',
            success:
                'border-emerald-200 bg-gradient-to-r from-[#d0fae5]/90 from-[40%] to-[#ffffff]/90 shadow-emerald-100/50',
            warning:
                'border-amber-200 bg-gradient-to-r from-[#fef3c6]/90 from-[40%] to-[#ffffff]/90 shadow-amber-100/50',
            error: 'border-red-200 bg-gradient-to-r from-[#ffe2e2]/90 from-[40%] to-[#ffffff]/90 shadow-red-100/50',
        };
        return variants[props.variant] || variants.info;
    });

    const iconClasses = computed(() => {
        const variants: Record<string, string> = {
            info: 'bg-sky-500 rounded-full',
            success: 'text-emerald-500',
            warning: 'bg-amber-500 rounded-full',
            error: 'bg-red-500 rounded-full',
        };
        return variants[props.variant] || variants.info;
    });

    const iconName = computed(() => {
        const variants: Record<string, string> = {
            info: 'material-symbols:info-i-rounded',
            success: 'material-symbols:check-circle',
            warning: 'material-symbols:exclamation-rounded',
            error: 'material-symbols:exclamation-rounded',
        };
        return variants[props.variant] || variants.info;
    });

    const title = computed(() => {
        const variants: Record<string, string> = {
            info: t('TOAST_INFO'),
            success: t('TOAST_SUCCESS'),
            warning: t('TOAST_WARNING'),
            error: t('TOAST_WARNING'), // ErrorToast uses TOAST_WARNING
        };
        return variants[props.variant] || variants.info;
    });
</script>
