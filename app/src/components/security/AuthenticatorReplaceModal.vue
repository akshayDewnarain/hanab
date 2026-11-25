<template>
    <section ref="root" class="flex max-h-[80vh] flex-col bg-list-surface text-list-text" @keydown="onKeydown">
        <header class="flex items-start justify-between gap-3 border-b border-list-border px-4 py-3">
            <div class="min-w-0">
                <h2 class="m-0 text-base font-semibold">{{ t('ACCOUNT_REPLACE_AUTHENTICATOR') }}</h2>
                <p class="m-0 mt-1 text-sm text-list-text-muted">{{ t('ACCOUNT_AUTHENTICATOR_HELP') }}</p>
            </div>
            <button type="button" class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-list-text-muted hover:bg-list-row-hover disabled:cursor-not-allowed disabled:opacity-40" :aria-label="t('ACCOUNT_CLOSE')" :disabled="codes.length > 0 && !saved" @click="dismissModal">
                <Icon icon="material-symbols:close-rounded" class="size-5" />
            </button>
        </header>
        <div class="overflow-y-auto px-4 py-4">
            <UiStepper class="mb-4" :completed-label="t('SECURITY_STEP_DONE')" :current="step - 1" :current-label="t('SECURITY_STEP_CURRENT')" :label="t('ACCOUNT_REPLACE_AUTHENTICATOR')" :steps="steps" />
            <form v-if="step === 1" class="flex flex-col gap-4" @submit.prevent="start">
                <h3 class="m-0 text-sm font-semibold">{{ t('ACCOUNT_VERIFY_IDENTITY') }}</h3>
                <UiPasswordInput v-model="currentPassword" autocomplete="current-password" :caps-lock-label="t('PASSWORD_CAPS_LOCK')" :disabled="busy" :hide-label="t('PASSWORD_HIDE')" :label="t('ACCOUNT_CURRENT_PASSWORD')" name="current-password" :show-label="t('PASSWORD_SHOW')" />
                <label class="flex flex-col gap-1 text-sm font-semibold">
                    {{ t('ACCOUNT_CURRENT_CODE') }}
                    <input v-model="currentCode" autocomplete="one-time-code" class="h-11 rounded-lg border border-list-border bg-list-surface px-3 text-sm font-normal" :disabled="busy" />
                </label>
                <p v-if="error" class="m-0 text-sm text-error" role="alert">{{ error }}</p>
                <div class="flex justify-end">
                    <UiAuthButton type="submit" :disabled="busy || !currentPassword || !currentCode" :loading="busy">{{ t('ACCOUNT_CONTINUE') }}</UiAuthButton>
                </div>
            </form>
            <div v-else-if="step === 2" class="flex flex-col gap-4">
                <h3 class="m-0 text-sm font-semibold">{{ t('ACCOUNT_LINK_AUTHENTICATOR') }}</h3>
                <ol class="m-0 flex list-none flex-col gap-2 p-0">
                    <UiDocStep :number="1">{{ t('SECURITY_STEP_OPEN') }}</UiDocStep>
                    <UiDocStep :number="2">{{ t('SECURITY_STEP_SCAN') }}</UiDocStep>
                    <UiDocStep :number="3">{{ t('SECURITY_STEP_ENTER') }}</UiDocStep>
                </ol>
                <UiQrCode v-if="uri" :error-label="t('SECURITY_QR_FAILED')" :label="t('SECURITY_QR')" :value="uri" />
                <div>
                    <UiAuthButton variant="text" type="button" :aria-expanded="showKey" aria-controls="replace-manual-key" @click="showKey = !showKey">{{ t('SECURITY_CANT_SCAN') }}</UiAuthButton>
                    <Collapsible id="replace-manual-key" :open="showKey">
                        <p class="m-0 pt-2 break-all rounded-md border border-list-border px-3 py-2 font-mono text-[13px]">{{ manualKey }}</p>
                    </Collapsible>
                </div>
                <div class="flex justify-end">
                    <UiAuthButton type="button" @click="step = 3">{{ t('ACCOUNT_CONTINUE') }}</UiAuthButton>
                </div>
            </div>
            <form v-else-if="step === 3" class="flex flex-col gap-4" @submit.prevent="confirm">
                <h3 class="m-0 text-sm font-semibold">{{ t('ACCOUNT_VERIFY_CONNECTION') }}</h3>
                <UiOtpInput v-model="newCode" :disabled="busy" :error="error" :label="t('SECURITY_CODE')" />
                <div class="flex justify-end gap-2">
                    <UiAuthButton variant="secondary" type="button" :disabled="busy" @click="step = 2">{{ t('ACCOUNT_BACK') }}</UiAuthButton>
                    <UiAuthButton type="submit" :disabled="busy || newCode.length !== 6" :loading="busy">{{ t('SECURITY_VERIFY') }}</UiAuthButton>
                </div>
            </form>
            <form v-else class="flex flex-col gap-4" @submit.prevent="finish">
                <h3 class="m-0 text-sm font-semibold">{{ t('SECURITY_RECOVERY_TITLE') }}</h3>
                <UiSecurityNotice>{{ t('SECURITY_RECOVERY_ONCE') }}</UiSecurityNotice>
                <UiRecoveryCodeList :codes="codes" :copied-label="t('SECURITY_COPIED')" :copy-failed-label="t('SECURITY_COPY_FAILED')" :copy-label="t('SECURITY_COPY')" :download-label="t('SECURITY_DOWNLOAD')" />
                <label class="flex items-start gap-2.5 text-sm leading-5">
                    <input v-model="saved" class="mt-1 size-4 accent-primary" type="checkbox" />
                    <span>{{ t('SECURITY_RECOVERY_SAVED') }}</span>
                </label>
                <div class="flex justify-end">
                    <UiAuthButton type="submit" :disabled="!saved">{{ t('ACCOUNT_CLOSE') }}</UiAuthButton>
                </div>
            </form>
        </div>
    </section>
</template>

<script lang="ts" setup>
    import { Collapsible, UiAuthButton, UiDocStep, UiOtpInput, UiPasswordInput, UiQrCode, UiRecoveryCodeList, UiSecurityNotice, UiStepper } from '@freelance/vue-ui';
    import { Icon } from '@iconify/vue';
    import { computed, inject, onBeforeUnmount, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useToastService } from '@/composables/useToastService';
    import { securityErrorMessage } from '@/modules/auth/securityError';
    import http from '@/utils/http';

    defineOptions({ name: 'AuthenticatorReplaceModal' });

    const props = defineProps<{
        props: { onUpdated?: () => void };
    }>();

    const dismissModal = inject<() => void>('dismissModal', () => undefined);
    const { t } = useI18n();
    const toast = useToastService();
    const root = ref<HTMLElement | null>(null);
    const step = ref(1);
    const currentPassword = ref('');
    const currentCode = ref('');
    const uri = ref('');
    const manualKey = ref('');
    const showKey = ref(false);
    const newCode = ref('');
    const codes = ref<string[]>([]);
    const saved = ref(false);
    const started = ref(false);
    const finished = ref(false);
    const busy = ref(false);
    const error = ref('');
    const steps = computed(() => [
        t('ACCOUNT_STEP_IDENTITY'),
        t('ACCOUNT_STEP_LINK'),
        t('ACCOUNT_STEP_VERIFY'),
        t('ACCOUNT_STEP_CODES'),
    ]);

    function onKeydown(event: KeyboardEvent): void {
        if (event.key !== 'Tab' || !root.value) return;
        const nodes = [...root.value.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]), a[href]')];
        const first = nodes[0];
        const last = nodes.at(-1);
        if (!first || !last) return;
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    async function start(): Promise<void> {
        if (busy.value) return;
        error.value = '';
        busy.value = true;
        try {
            const response = await http.post('security/two-factor/replace', {
                current_password: currentPassword.value,
                code: currentCode.value,
            });
            uri.value = response.data.data.otpauth_uri;
            manualKey.value = response.data.data.manual_key;
            currentPassword.value = '';
            currentCode.value = '';
            started.value = true;
            step.value = 2;
        } catch (caught) {
            error.value = securityErrorMessage(caught, t);
        } finally {
            busy.value = false;
        }
    }

    async function confirm(): Promise<void> {
        if (busy.value || newCode.value.length !== 6) return;
        error.value = '';
        busy.value = true;
        try {
            const response = await http.post('security/two-factor/replace/confirm', { code: newCode.value });
            codes.value = response.data.data.recovery_codes ?? [];
            newCode.value = '';
            finished.value = true;
            step.value = 4;
            props.props.onUpdated?.();
            toast.success('ACCOUNT_AUTHENTICATOR_REPLACED');
        } catch (caught) {
            error.value = securityErrorMessage(caught, t);
        } finally {
            busy.value = false;
        }
    }

    function finish(): void {
        if (!saved.value) return;
        codes.value = [];
        dismissModal();
    }

    function allowDismiss(): boolean {
        return codes.value.length === 0 || saved.value;
    }

    defineExpose({ allowDismiss });

    onMounted(() => {
        root.value?.querySelector('input')?.focus();
    });

    onBeforeUnmount(() => {
        codes.value = [];
        currentPassword.value = '';
        currentCode.value = '';
        newCode.value = '';
        if (started.value && !finished.value) void http.post('security/two-factor/replace/cancel').catch(() => undefined);
    });
</script>
