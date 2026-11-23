<template>
    <AuthLayout width="compact">
        <form class="flex flex-col gap-4" @submit.prevent="login">
            <header class="text-center">
                <h1 class="text-2xl font-semibold tracking-tight text-list-text">{{ t('LOGIN_TITLE') }}</h1>
                <p class="mt-1.5 text-sm leading-5 text-list-text-muted">{{ t('LOGIN_SUBTITLE') }}</p>
            </header>
            <AuthTextField
                id="login-email"
                v-model="email"
                autocomplete="email"
                :label="t('LOGIN_EMAIL')"
                name="email"
                :placeholder="t('LOGIN_EMAIL_PLACEHOLDER')"
                required
                type="email"
            />
            <UiPasswordInput
                v-model="password"
                autocomplete="current-password"
                :caps-lock-label="t('PASSWORD_CAPS_LOCK')"
                :disabled="isSubmitting"
                :hide-label="t('PASSWORD_HIDE')"
                :label="t('LOGIN_PASSWORD')"
                name="password"
                :show-label="t('PASSWORD_SHOW')"
            />
            <p v-if="error" class="m-0 text-sm text-error" role="alert">{{ error }}</p>
            <label class="flex items-center gap-2.5 text-sm text-list-text" for="remember">
                <input id="remember" v-model="remember" class="size-4 shrink-0 accent-primary" name="remember" type="checkbox" />
                <span>{{ t('LOGIN_REMEMBER_ME') }}</span>
            </label>
            <UiAuthButton type="submit" :disabled="isSubmitting" :loading="isSubmitting">
                {{ isSubmitting ? t('LOGIN_SIGNING_IN') : t('LOGIN_SIGN_IN') }}
            </UiAuthButton>
            <RouterLink class="text-center text-sm font-medium text-emphasis underline-offset-2 hover:underline" to="/forgot-password">
                {{ t('LOGIN_FORGOT_PASSWORD') }}
            </RouterLink>
        </form>
    </AuthLayout>
</template>

<script lang="ts" setup>
    import { UiAuthButton, UiPasswordInput } from '@freelance/vue-ui';
    import http from '@/utils/http';
    import { ref } from 'vue';
    import { RouterLink, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import AuthLayout from '@/components/auth/AuthLayout.vue';
    import AuthTextField from '@/components/auth/AuthTextField.vue';
    import { useAuthStore } from '@/stores/auth.ts';

    defineOptions({
        name: 'login-page',
    });

    const email = ref<string>('');
    const password = ref<string>('');
    const remember = ref(false);
    const error = ref('');
    const isSubmitting = ref(false);

    const router = useRouter();
    const { t } = useI18n();
    const authStore = useAuthStore();

    async function login(): Promise<void> {
        if (isSubmitting.value) {
            return;
        }

        isSubmitting.value = true;
        error.value = '';

        try {
            await getCookie();

            const response = await http.post(
                'login',
                {
                    email: email.value,
                    password: password.value,
                    remember: remember.value,
                },
                {
                    baseURL: import.meta.env.VITE_API_URL + '/api',
                },
            );

            const data = response.data.data ?? {};
            if (data.step === 'enroll' && data.onboarding_token) {
                authStore.onboardingToken = data.onboarding_token;
                await router.push({ name: 'security-setup' });
                return;
            }
            if (data.step === 'challenge' && data.challenge) {
                authStore.challengeToken = data.challenge;
                await router.push({ name: 'two-factor' });
                return;
            }
            error.value = t('LOGIN_FAILED');
        } catch {
            error.value = t('LOGIN_FAILED');
        } finally {
            isSubmitting.value = false;
        }
    }

    async function getCookie(): Promise<void> {
        await http.get('/sanctum/csrf-cookie', {
            baseURL: import.meta.env.VITE_API_URL,
        });
    }
</script>
