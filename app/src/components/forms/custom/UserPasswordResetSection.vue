<template>
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <Icon class="h-5 w-5 text-[var(--color-primary)]" icon="material-symbols:lock-reset-rounded" />
                    <span class="text-sm font-semibold text-gray-800">{{ t(options.label) }}</span>
                </div>
                <p class="mt-1 max-w-xl text-xs leading-relaxed text-gray-500">
                    {{ t('USER_PASSWORD_RESET_SECTION_DESCRIPTION') }}
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2 sm:pt-0.5">
                <button
                    type="button"
                    class="rounded border border-[var(--color-background)] bg-[var(--color-background)] px-3 py-1.5 text-xs font-medium text-white shadow-sm transition-colors hover:bg-[var(--color-highlight-dark)] disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="sendingReset || !canSend"
                    @click="sendPasswordResetEmail"
                >
                    <span class="inline-flex items-center gap-1.5">
                        <Icon
                            class="h-4 w-4"
                            :class="sendingReset ? 'animate-pulse' : ''"
                            icon="material-symbols:mail-rounded"
                        />
                        {{ sendingReset ? t('USER_SEND_PASSWORD_RESET_SENDING') : t('USER_SEND_PASSWORD_RESET_EMAIL') }}
                    </span>
                </button>
            </div>
        </div>

        <div class="mt-3 rounded border border-gray-200 bg-gray-50 p-3">
            <div class="grid grid-cols-1 gap-3 text-gray-700 md:grid-cols-3">
                <div class="flex items-start gap-2.5 rounded-md border border-gray-200/80 bg-white px-3 py-2 shadow-sm">
                    <Icon
                        class="mt-0.5 h-[18px] w-[18px] shrink-0 text-[var(--color-primary)]"
                        icon="material-symbols:mark-email-read-outline"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                            {{ t('USER_PASSWORD_RESET_SECTION_CARD_EMAIL_TITLE') }}
                        </div>
                        <div class="break-all text-sm font-medium text-gray-900">{{ displayEmail }}</div>
                    </div>
                </div>
                <div class="flex items-start gap-2.5 rounded-md border border-gray-200/80 bg-white px-3 py-2 shadow-sm">
                    <Icon
                        class="mt-0.5 h-[18px] w-[18px] shrink-0 text-[var(--color-primary)]"
                        icon="material-symbols:timer-outline"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                            {{ t('USER_PASSWORD_RESET_SECTION_CARD_EXPIRY_TITLE') }}
                        </div>
                        <div class="text-sm font-medium text-gray-900">
                            {{ t('USER_PASSWORD_RESET_SECTION_CARD_EXPIRY_BODY') }}
                        </div>
                    </div>
                </div>
                <div class="flex items-start gap-2.5 rounded-md border border-gray-200/80 bg-white px-3 py-2 shadow-sm">
                    <Icon
                        class="mt-0.5 h-[18px] w-[18px] shrink-0 text-[var(--color-primary)]"
                        icon="material-symbols:shield-lock-outline"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                            {{ t('USER_PASSWORD_RESET_SECTION_CARD_SECURITY_TITLE') }}
                        </div>
                        <div class="text-sm font-medium text-gray-900">
                            {{ t('USER_PASSWORD_RESET_SECTION_CARD_SECURITY_BODY') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p v-if="error" class="mt-2 text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import http from '@/utils/http';
    import { prepareApiCsrf } from '@/utils/prepareApiCsrf';
    import { useToastService } from '@/composables/useToastService';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

    defineComponent({ name: 'UserPasswordResetSection' });

    const props = defineProps<{
        options: BaseInputFieldOptions;
        error?: string;
        modelValue?: unknown;
        activityId?: number;
        formData?: Record<string, unknown>;
    }>();

    const { t } = useI18n();
    const toast = useToastService();
    const sendingReset = ref(false);

    const userId = computed((): number | null => {
        const raw = props.activityId;
        if (raw == null || !Number.isFinite(raw) || raw <= 0) return null;
        return raw;
    });

    const canSend = computed(() => userId.value != null);

    const displayEmail = computed(() => {
        const e = props.formData?.email;
        if (typeof e === 'string' && e.trim() !== '') return e;
        return '—';
    });

    async function sendPasswordResetEmail(): Promise<void> {
        const id = userId.value;
        if (id == null) return;
        sendingReset.value = true;
        try {
            await prepareApiCsrf();
            await http.post(`users/${id}/send-password-reset`);
            toast.success(t('USER_PASSWORD_RESET_EMAIL_SENT'));
        } catch {
            toast.error(t('USER_PASSWORD_RESET_EMAIL_FAILED'));
        } finally {
            sendingReset.value = false;
        }
    }
</script>
