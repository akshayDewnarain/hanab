<template>
    <!-- Success Toast (new styling) -->
    <div class="p-4">
        <div class="pointer-events-auto w-full max-w-sm shadow-md">
            <div
                class="flex items-start gap-3 rounded border border-emerald-200 bg-gradient-to-r from-[#d0fae5]/90 to-[#ffffff]/90 p-4 shadow-lg shadow-emerald-100/50 backdrop-blur"
            >
                <!-- Icon bubble -->
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full">
                    <Icon class="h-6 w-6 text-emerald-500" icon="material-symbols:check-circle" />
                </div>

                <!-- Message -->
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ t('TOAST_SUCCESS') }}
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
        name: 'SuccessToast',
    });

    const { t, te } = useI18n();

    const props = defineProps<{
        message: string;
    }>();

    const toastBody = computed(() => (te(props.message) ? t(props.message) : props.message));
</script>
