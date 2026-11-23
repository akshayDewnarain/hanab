<template>
    <AuthLayout width="compact">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <header class="text-center">
                <h1 class="text-2xl font-semibold tracking-tight text-list-text">{{ t('TWO_FACTOR_TITLE') }}</h1>
                <p class="mt-1.5 text-sm leading-5 text-list-text-muted">
                    {{ recovery ? t('TWO_FACTOR_RECOVERY_HINT') : t('TWO_FACTOR_HINT') }}
                </p>
            </header>
            <UiOtpInput
                v-if="!recovery"
                ref="otp"
                v-model="code"
                autofocus
                :disabled="submitting"
                :error="error"
                :label="t('SECURITY_CODE')"
            />
            <AuthTextField
                v-else
                id="recovery-code"
                v-model="code"
                autocomplete="off"
                :disabled="submitting"
                :label="t('TWO_FACTOR_RECOVERY')"
                name="recovery-code"
            />
            <p v-if="recovery && error" class="m-0 text-sm text-error" role="alert">{{ error }}</p>
            <UiAuthButton type="submit" :disabled="!canSubmit" :loading="submitting">
                {{ submitting ? t('SECURITY_VERIFYING') : t('SECURITY_VERIFY') }}
            </UiAuthButton>
            <div class="flex flex-col items-center">
                <UiAuthButton variant="text" type="button" @click="toggle">
                    {{ recovery ? t('TWO_FACTOR_USE_APP') : t('TWO_FACTOR_USE_RECOVERY') }}
                </UiAuthButton>
                <UiAuthButton variant="text" type="button" @click="cancel">{{ t('TWO_FACTOR_CANCEL') }}</UiAuthButton>
            </div>
        </form>
    </AuthLayout>
</template>

<script lang="ts" setup>
    import { UiAuthButton, UiOtpInput } from '@freelance/vue-ui';
    import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import AuthLayout from '@/components/auth/AuthLayout.vue';
    import AuthTextField from '@/components/auth/AuthTextField.vue';
    import { useAuthStore } from '@/stores/auth';
    import http from '@/utils/http';
    import { prepareApiCsrf } from '@/utils/prepareApiCsrf';

    defineOptions({ name: 'TwoFactorChallengePage' });

    const { t } = useI18n();
    const router = useRouter();
    const authStore = useAuthStore();
    const code = ref('');
    const recovery = ref(false);
    const error = ref('');
    const submitting = ref(false);
    const otp = ref<{ focus: () => void } | null>(null);
    const canSubmit = computed(() => !submitting.value && (recovery.value ? code.value.trim().length >= 8 : code.value.length === 6));

    onMounted(() => {
        if (!authStore.challengeToken) void router.push({ name: 'login' });
    });

    onBeforeUnmount(() => {
        code.value = '';
    });

    watch(code, () => {
        error.value = '';
    });

    async function toggle(): Promise<void> {
        recovery.value = !recovery.value;
        code.value = '';
        error.value = '';
        await nextTick();
        if (!recovery.value) otp.value?.focus();
        else document.getElementById('recovery-code')?.focus();
    }

    function cancel(): void {
        authStore.challengeToken = null;
        void router.push({ name: 'login' });
    }

    async function submit(): Promise<void> {
        if (!canSubmit.value || submitting.value) return;
        error.value = '';
        submitting.value = true;
        try {
            await prepareApiCsrf();
            const response = await http.post('public/two-factor/challenge', {
                challenge: authStore.challengeToken,
                code: code.value.trim(),
            });
            const data = response.data.data ?? {};
            if (data.step === 'enroll' && data.onboarding_token) {
                authStore.challengeToken = null;
                authStore.onboardingToken = data.onboarding_token;
                await router.push({ name: 'security-setup' });
                return;
            }
            localStorage.setItem('token', data.token);
            authStore.challengeToken = null;
            await authStore.fetchUser();
            await router.push({ name: 'admin-employees-overview' });
        } catch {
            error.value = t('SECURITY_CODE_INVALID');
        } finally {
            submitting.value = false;
        }
    }
</script>
