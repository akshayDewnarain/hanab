<template>
    <AuthLayout width="regular">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <header class="text-center">
                <h1 class="text-2xl font-semibold tracking-tight text-list-text">{{ t('RESET_PASSWORD_TITLE') }}</h1>
                <p class="mt-1.5 text-sm leading-5 text-list-text-muted">{{ email }}</p>
            </header>
            <PasswordFields v-model:confirmation="confirmation" v-model:password="password" v-model:valid="passwordValid" :disabled="submitting || done" />
            <p v-if="error" class="m-0 text-sm text-error" role="alert">{{ error }}</p>
            <p v-if="done" class="m-0 text-sm text-list-text" role="status">{{ t('RESET_PASSWORD_DONE') }}</p>
            <UiAuthButton type="submit" :disabled="submitting || done || !passwordValid" :loading="submitting">
                {{ submitting ? t('RESET_PASSWORD_SUBMITTING') : t('RESET_PASSWORD_SUBMIT') }}
            </UiAuthButton>
            <RouterLink class="text-center text-sm font-medium text-emphasis underline-offset-2 hover:underline" to="/login">
                {{ t('INVITATION_GO_TO_LOGIN') }}
            </RouterLink>
        </form>
    </AuthLayout>
</template>

<script lang="ts" setup>
    import { UiAuthButton } from '@freelance/vue-ui';
    import { ref } from 'vue';
    import { RouterLink, useRoute } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import AuthLayout from '@/components/auth/AuthLayout.vue';
    import PasswordFields from '@/components/auth/PasswordFields.vue';
    import http from '@/utils/http';
    import { prepareApiCsrf } from '@/utils/prepareApiCsrf';

    defineOptions({ name: 'ResetPasswordPage' });

    const route = useRoute();
    const { t } = useI18n();
    const email = ref(typeof route.query.email === 'string' ? route.query.email : '');
    const token = typeof route.query.token === 'string' ? route.query.token : '';
    const password = ref('');
    const confirmation = ref('');
    const error = ref('');
    const done = ref(false);
    const passwordValid = ref(false);
    const submitting = ref(false);

    async function submit(): Promise<void> {
        if (submitting.value || done.value) return;
        error.value = '';
        submitting.value = true;
        try {
            await prepareApiCsrf();
            await http.post('public/reset-password', {
                email: email.value,
                token,
                password: password.value,
                password_confirmation: confirmation.value,
            });
            done.value = true;
        } catch {
            error.value = t('RESET_PASSWORD_FAILED');
        } finally {
            submitting.value = false;
        }
    }
</script>
