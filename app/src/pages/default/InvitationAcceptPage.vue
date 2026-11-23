<template>
    <AuthLayout :width="state === 'valid' && !loading ? 'regular' : 'compact'">
        <template v-if="state === 'valid' && !loading" #progress>
            <UiStepper
                :completed-label="t('SECURITY_STEP_DONE')"
                :current="0"
                :current-label="t('SECURITY_STEP_CURRENT')"
                :label="t('SECURITY_PROGRESS')"
                :steps="steps"
            />
        </template>
        <div v-if="loading" class="py-6 text-center text-sm text-list-text-muted">{{ t('INVITATION_LOADING') }}</div>
        <div v-else-if="state !== 'valid'" class="py-2 text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-list-text">{{ t(stateTitle) }}</h1>
            <p class="mt-2 text-sm leading-6 text-list-text-muted sm:text-base">{{ t(stateBody) }}</p>
            <RouterLink class="mt-6 inline-block text-sm font-medium text-emphasis underline-offset-2 hover:underline" to="/login">
                {{ t('INVITATION_GO_TO_LOGIN') }}
            </RouterLink>
        </div>
        <form v-else class="flex flex-col gap-4" @submit.prevent="accept">
            <header class="text-center">
                <h1 class="text-2xl font-semibold tracking-tight text-list-text">{{ t('INVITATION_ACCEPT_TITLE') }}</h1>
                <p class="mt-1.5 text-sm leading-5 text-list-text-muted">{{ t('INVITATION_ACCEPT_SUBTITLE', { email }) }}</p>
            </header>
            <AuthTextField id="invitation-name" v-model="name" autocomplete="name" :disabled="submitting" :label="t('INVITATION_FULL_NAME')" name="name" />
            <PasswordFields v-model:confirmation="confirmation" v-model:password="password" v-model:valid="passwordValid" :disabled="submitting" />
            <p v-if="error" class="m-0 text-sm text-error" role="alert">{{ error }}</p>
            <UiAuthButton type="submit" :disabled="submitting || !passwordValid" :loading="submitting">
                {{ submitting ? t('INVITATION_SUBMITTING') : t('SECURITY_CONTINUE') }}
            </UiAuthButton>
        </form>
    </AuthLayout>
</template>

<script lang="ts" setup>
    import { UiAuthButton, UiStepper } from '@freelance/vue-ui';
    import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
    import { RouterLink, useRoute, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import AuthLayout from '@/components/auth/AuthLayout.vue';
    import AuthTextField from '@/components/auth/AuthTextField.vue';
    import PasswordFields from '@/components/auth/PasswordFields.vue';
    import { useAuthStore } from '@/stores/auth';
    import http from '@/utils/http';
    import { prepareApiCsrf } from '@/utils/prepareApiCsrf';

    defineOptions({ name: 'InvitationAcceptPage' });

    const route = useRoute();
    const router = useRouter();
    const { t } = useI18n();
    const authStore = useAuthStore();
    const token = String(route.params.token ?? '');
    const loading = ref(true);
    const submitting = ref(false);
    const state = ref('invalid');
    const email = ref('');
    const name = ref('');
    const password = ref('');
    const confirmation = ref('');
    const error = ref('');
    const passwordValid = ref(false);
    let referrerMeta: HTMLMetaElement | null = null;
    const steps = computed(() => [t('SECURITY_STEP_PASSWORD'), t('SECURITY_STEP_AUTHENTICATOR'), t('SECURITY_STEP_RECOVERY')]);

    const stateTitle = computed(() => {
        if (state.value === 'expired') return 'INVITATION_EXPIRED_TITLE';
        if (state.value === 'revoked') return 'INVITATION_REVOKED_TITLE';
        if (state.value === 'used') return 'INVITATION_USED_TITLE';
        return 'INVITATION_INVALID_TITLE';
    });

    const stateBody = computed(() => {
        if (state.value === 'expired') return 'INVITATION_EXPIRED_BODY';
        if (state.value === 'revoked') return 'INVITATION_REVOKED_BODY';
        if (state.value === 'used') return 'INVITATION_USED_BODY';
        return 'INVITATION_INVALID_BODY';
    });

    onMounted(async () => {
        referrerMeta = document.createElement('meta');
        referrerMeta.name = 'referrer';
        referrerMeta.content = 'no-referrer';
        document.head.appendChild(referrerMeta);
        try {
            await prepareApiCsrf();
            const response = await http.post('public/user-invitations/preview', { token });
            const data = response.data.data ?? {};
            state.value = data.state ?? 'invalid';
            email.value = data.email ?? '';
            name.value = data.name ?? '';
        } catch {
            state.value = 'invalid';
        } finally {
            loading.value = false;
        }
    });

    onBeforeUnmount(() => {
        referrerMeta?.remove();
        referrerMeta = null;
    });

    async function accept(): Promise<void> {
        if (submitting.value || !passwordValid.value) return;
        error.value = '';
        if (password.value !== confirmation.value) {
            error.value = t('INVITATION_PASSWORD_MISMATCH');
            return;
        }
        submitting.value = true;
        try {
            await prepareApiCsrf();
            const response = await http.post('public/user-invitations/accept', {
                token,
                name: name.value,
                password: password.value,
                password_confirmation: confirmation.value,
            });
            authStore.onboardingToken = response.data.data.onboarding_token;
            await router.push({ name: 'security-setup' });
        } catch {
            error.value = t('INVITATION_ACCEPT_FAILED');
        } finally {
            submitting.value = false;
        }
    }
</script>
