<template>
    <AuthLayout :width="token ? 'wide' : 'compact'">
        <template v-if="token" #progress>
            <UiStepper
                :completed-label="t('SECURITY_STEP_DONE')"
                :current="step"
                :current-label="t('SECURITY_STEP_CURRENT')"
                :label="t('SECURITY_PROGRESS')"
                :steps="steps"
            />
        </template>
        <div v-if="!token" class="text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-list-text">{{ t('SECURITY_SETUP_TITLE') }}</h1>
            <p class="mt-2 text-sm leading-6 text-list-text-muted sm:text-base">{{ t('SECURITY_SETUP_RESUME') }}</p>
            <RouterLink class="mt-6 inline-block text-sm font-medium text-emphasis underline-offset-2 hover:underline" to="/login">
                {{ t('INVITATION_GO_TO_LOGIN') }}
            </RouterLink>
        </div>
        <Transition
            v-else
            mode="out-in"
            @after-enter="focusHeading"
            enter-active-class="transition duration-200 ease-out motion-reduce:transition-none"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <form v-if="step === 1" key="authenticator" class="flex flex-col gap-4" @submit.prevent="confirm">
                <header class="text-center">
                    <h1 ref="heading" class="text-2xl font-semibold tracking-tight text-list-text" tabindex="-1">
                        {{ t('SECURITY_AUTHENTICATOR_TITLE') }}
                    </h1>
                    <p class="mt-1.5 text-sm leading-5 text-list-text-muted">{{ t('SECURITY_AUTHENTICATOR_INTRO') }}</p>
                </header>
                <div class="grid items-center gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:gap-6">
                    <div class="flex flex-col gap-3">
                        <ol class="m-0 flex list-none flex-col gap-2 p-0">
                            <UiDocStep :number="1">{{ t('SECURITY_STEP_OPEN') }}</UiDocStep>
                            <UiDocStep :number="2">{{ t('SECURITY_STEP_SCAN') }}</UiDocStep>
                            <UiDocStep :number="3">{{ t('SECURITY_STEP_ENTER') }}</UiDocStep>
                        </ol>
                        <div>
                            <UiAuthButton variant="text" type="button" :aria-expanded="showKey" aria-controls="manual-key" :disabled="!manualKey" @click="showKey = !showKey">
                                {{ t('SECURITY_CANT_SCAN') }}
                            </UiAuthButton>
                            <Collapsible id="manual-key" :open="showKey">
                                <div class="flex flex-col gap-2 pt-1">
                                    <p class="m-0 text-sm leading-5 text-list-text-muted">{{ t('SECURITY_MANUAL_HELP') }}</p>
                                    <p class="m-0 break-all rounded-md border border-list-border px-3 py-2 font-mono text-[13px] tracking-wide text-list-text">
                                        {{ manualKey }}
                                    </p>
                                    <UiAuthButton variant="secondary" type="button" @click="copyKey">
                                        {{ keyCopied ? t('SECURITY_KEY_COPIED') : t('SECURITY_COPY_KEY') }}
                                    </UiAuthButton>
                                </div>
                            </Collapsible>
                        </div>
                    </div>
                    <UiQrCode v-if="uri" :error-label="t('SECURITY_QR_FAILED')" :label="t('SECURITY_QR')" :value="uri" />
                    <p v-else class="m-0 text-sm text-list-text-muted" :role="error ? 'alert' : 'status'">{{ error || t('SECURITY_LOADING') }}</p>
                </div>
                <div class="h-px bg-list-border"></div>
                <div class="flex flex-col gap-3">
                    <UiOtpInput v-model="code" :disabled="submitting || !uri" :error="uri ? error : ''" :label="t('SECURITY_CODE')" />
                    <UiAuthButton type="submit" :disabled="submitting || code.length !== 6" :loading="submitting">
                        {{ submitting ? t('SECURITY_VERIFYING') : t('SECURITY_VERIFY') }}
                    </UiAuthButton>
                </div>
            </form>
            <form v-else key="recovery" class="flex flex-col gap-4" @submit.prevent="finish">
                <header class="text-center">
                    <h1 ref="heading" class="text-2xl font-semibold tracking-tight text-list-text" tabindex="-1">
                        {{ t('SECURITY_RECOVERY_TITLE') }}
                    </h1>
                    <p class="mt-1.5 text-sm leading-5 text-list-text-muted">{{ t('SECURITY_RECOVERY_BODY') }}</p>
                </header>
                <UiSecurityNotice>{{ t('SECURITY_RECOVERY_ONCE') }}</UiSecurityNotice>
                <UiRecoveryCodeList
                    :codes="codes"
                    :copied-label="t('SECURITY_COPIED')"
                    :copy-failed-label="t('SECURITY_COPY_FAILED')"
                    :copy-label="t('SECURITY_COPY')"
                    :download-label="t('SECURITY_DOWNLOAD')"
                />
                <label class="flex items-start gap-2.5 text-sm leading-5 text-list-text">
                    <input v-model="saved" class="mt-1 size-4 accent-primary" type="checkbox" />
                    <span>{{ t('SECURITY_RECOVERY_SAVED') }}</span>
                </label>
                <p v-if="error" class="m-0 text-sm text-error" role="alert">{{ error }}</p>
                <UiAuthButton type="submit" :disabled="submitting || !saved" :loading="submitting">
                    {{ submitting ? t('SECURITY_FINISHING') : t('SECURITY_FINISH') }}
                </UiAuthButton>
            </form>
        </Transition>
    </AuthLayout>
</template>

<script lang="ts" setup>
    import { Collapsible, UiAuthButton, UiDocStep, UiOtpInput, UiQrCode, UiRecoveryCodeList, UiSecurityNotice, UiStepper } from '@freelance/vue-ui';
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { RouterLink, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import AuthLayout from '@/components/auth/AuthLayout.vue';
    import { useAuthStore } from '@/stores/auth';
    import { useToastService } from '@/composables/useToastService';
    import http from '@/utils/http';
    import { prepareApiCsrf } from '@/utils/prepareApiCsrf';

    defineOptions({ name: 'SecuritySetupPage' });

    const { t } = useI18n();
    const router = useRouter();
    const authStore = useAuthStore();
    const toast = useToastService();
    const token = computed(() => authStore.onboardingToken);
    const step = ref(1);
    const uri = ref('');
    const manualKey = ref('');
    const showKey = ref(false);
    const keyCopied = ref(false);
    const code = ref('');
    const codes = ref<string[]>([]);
    const saved = ref(false);
    const error = ref('');
    const submitting = ref(false);
    const heading = ref<HTMLElement | null>(null);
    const steps = computed(() => [t('SECURITY_STEP_PASSWORD'), t('SECURITY_STEP_AUTHENTICATOR'), t('SECURITY_STEP_RECOVERY')]);

    function bearer() {
        return { headers: { Authorization: `Bearer ${token.value}` } };
    }

    function clearSecrets(): void {
        uri.value = '';
        manualKey.value = '';
        code.value = '';
        codes.value = [];
    }

    onMounted(async () => {
        if (!token.value) return;
        try {
            await prepareApiCsrf();
            const response = await http.post('security/two-factor', {}, bearer());
            uri.value = response.data.data.otpauth_uri;
            manualKey.value = response.data.data.manual_key;
        } catch {
            error.value = t('SECURITY_SETUP_FAILED');
        }
    });

    onBeforeUnmount(clearSecrets);

    watch(code, () => {
        if (step.value === 1) error.value = '';
    });

    function focusHeading(): void {
        if (step.value === 2) heading.value?.focus();
    }

    async function copyKey(): Promise<void> {
        if (!manualKey.value) return;
        try {
            await navigator.clipboard.writeText(manualKey.value);
            keyCopied.value = true;
        } catch {
            keyCopied.value = false;
        }
    }

    async function confirm(): Promise<void> {
        if (submitting.value || code.value.length !== 6) return;
        error.value = '';
        submitting.value = true;
        try {
            await prepareApiCsrf();
            const response = await http.post('security/two-factor/confirm', { code: code.value }, bearer());
            codes.value = response.data.data.recovery_codes ?? [];
            code.value = '';
            step.value = 2;
        } catch {
            error.value = t('SECURITY_CODE_INVALID');
        } finally {
            submitting.value = false;
        }
    }

    async function finish(): Promise<void> {
        if (submitting.value || !saved.value) return;
        error.value = '';
        submitting.value = true;
        try {
            await prepareApiCsrf();
            const response = await http.post('security/two-factor/acknowledge', { saved: true }, bearer());
            localStorage.setItem('token', response.data.data.token);
            authStore.onboardingToken = null;
            clearSecrets();
            await authStore.fetchUser();
            toast.success(t('SECURITY_ACTIVATED'));
            await router.push({ name: 'admin-employees-overview' });
        } catch {
            error.value = t('SECURITY_SETUP_FAILED');
        } finally {
            submitting.value = false;
        }
    }
</script>
