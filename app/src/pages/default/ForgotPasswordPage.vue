<template>
    <AuthLayout width="compact">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <header class="text-center">
                <h1 class="text-2xl font-semibold tracking-tight text-list-text">{{ t('FORGOT_PASSWORD_TITLE') }}</h1>
                <p class="mt-1.5 text-sm leading-5 text-list-text-muted">{{ t('FORGOT_PASSWORD_SUBTITLE') }}</p>
            </header>
            <AuthTextField
                id="forgot-email"
                v-model="email"
                autocomplete="email"
                :disabled="submitting"
                :label="t('LOGIN_EMAIL')"
                name="email"
                required
                type="email"
            />
            <p v-if="message" class="m-0 text-sm text-list-text" role="status">{{ message }}</p>
            <UiAuthButton type="submit" :disabled="submitting" :loading="submitting">
                {{ submitting ? t('FORGOT_PASSWORD_SENDING') : t('FORGOT_PASSWORD_SUBMIT') }}
            </UiAuthButton>
            <RouterLink class="text-center text-sm font-medium text-emphasis underline-offset-2 hover:underline" to="/login">
                {{ t('FORGOT_PASSWORD_BACK_TO_LOGIN') }}
            </RouterLink>
        </form>
    </AuthLayout>
</template>

<script lang="ts" setup>
    import { UiAuthButton } from '@freelance/vue-ui';
    import { ref } from 'vue';
    import { RouterLink } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import AuthLayout from '@/components/auth/AuthLayout.vue';
    import AuthTextField from '@/components/auth/AuthTextField.vue';
    import http from '@/utils/http';
    import { prepareApiCsrf } from '@/utils/prepareApiCsrf';

    defineOptions({ name: 'ForgotPasswordPage' });

    const { t } = useI18n();
    const email = ref('');
    const message = ref('');
    const submitting = ref(false);

    async function submit(): Promise<void> {
        if (submitting.value) return;
        submitting.value = true;
        try {
            await prepareApiCsrf();
            await http.post('public/forgot-password', { email: email.value });
        } catch {
            // The response stays generic even when the request is refused.
        } finally {
            message.value = t('FORGOT_PASSWORD_SENT');
            submitting.value = false;
        }
    }
</script>
