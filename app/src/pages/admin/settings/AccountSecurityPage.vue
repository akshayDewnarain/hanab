<template>
    <PageLayout :label="t('ACCOUNT_SECURITY')">
        <div class="mx-auto flex w-full max-w-2xl flex-col gap-4">
            <section class="rounded-xl border border-list-border bg-list-surface p-4 shadow-sm">
                <h1 class="text-lg font-semibold text-list-text">{{ t('ACCOUNT_SECURITY') }}</h1>
                <dl class="mt-3 grid gap-2 text-sm text-list-text">
                    <div class="flex justify-between gap-4"><dt>{{ t('ACCOUNT_PASSWORD_STATUS') }}</dt><dd>{{ status?.password_set_at ? t('ACCOUNT_PASSWORD_SET') : t('ACCOUNT_PASSWORD_MISSING') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>{{ t('ACCOUNT_2FA_STATUS') }}</dt><dd>{{ status?.two_factor_enabled ? t('ACCOUNT_2FA_ON') : t('ACCOUNT_2FA_OFF') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>{{ t('ACCOUNT_AUTHENTICATOR_DATE') }}</dt><dd>{{ confirmed }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>{{ t('ACCOUNT_RECOVERY_LEFT') }}</dt><dd>{{ status?.recovery_codes_remaining ?? '—' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-list-border bg-list-surface p-4 shadow-sm">
                <h2 class="text-base font-semibold text-list-text">{{ t('ACCOUNT_CHANGE_PASSWORD') }}</h2>
                <form class="mt-3 flex flex-col gap-3" @submit.prevent="changePassword">
                    <label class="flex flex-col gap-1 text-sm font-medium text-list-text">
                        {{ t('ACCOUNT_CURRENT_PASSWORD') }}
                        <input v-model="currentPassword" autocomplete="current-password" class="h-11 rounded-lg border border-list-border bg-list-surface px-3" type="password" />
                    </label>
                    <PasswordFields v-model:confirmation="confirmation" v-model:password="password" v-model:valid="passwordValid" />
                    <p v-if="message" class="text-sm text-list-text" role="status">{{ message }}</p>
                    <button class="inline-flex h-10 items-center justify-center rounded-lg bg-primary px-4 text-sm font-semibold text-white disabled:opacity-60" :disabled="busy || !passwordValid" type="submit">{{ t('ACCOUNT_CHANGE_PASSWORD') }}</button>
                </form>
            </section>

            <section class="rounded-xl border border-list-border bg-list-surface p-4 shadow-sm">
                <h2 class="text-base font-semibold text-list-text">{{ t('ACCOUNT_REPLACE_AUTHENTICATOR') }}</h2>
                <p class="mt-1 text-sm text-list-text-muted">{{ t('ACCOUNT_REPLACE_HELP') }}</p>
                <form v-if="!replacementUri" class="mt-3 flex flex-col gap-3" @submit.prevent="startReplace">
                    <label class="flex flex-col gap-1 text-sm font-medium text-list-text">
                        {{ t('ACCOUNT_CURRENT_PASSWORD') }}
                        <input v-model="replacePassword" autocomplete="current-password" class="h-11 rounded-lg border border-list-border px-3" type="password" />
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium text-list-text">
                        {{ t('ACCOUNT_CURRENT_CODE') }}
                        <input v-model="replaceCode" autocomplete="one-time-code" class="h-11 rounded-lg border border-list-border px-3" type="text" />
                    </label>
                    <button class="inline-flex h-10 items-center justify-center rounded-lg border border-list-border px-4 text-sm font-semibold text-list-text" :disabled="busy" type="submit">{{ t('ACCOUNT_REPLACE_AUTHENTICATOR') }}</button>
                </form>
                <div v-else class="mt-3 flex flex-col gap-3">
                    <UiQrCode :label="t('SECURITY_QR')" :value="replacementUri" />
                    <p class="break-all font-mono text-sm text-list-text">{{ replacementKey }}</p>
                    <UiOtpInput v-model="newCode" :label="t('SECURITY_CODE')" />
                    <button class="inline-flex h-10 items-center justify-center rounded-lg bg-primary px-4 text-sm font-semibold text-white" :disabled="busy || newCode.length !== 6" type="button" @click="confirmReplace">{{ t('SECURITY_VERIFY') }}</button>
                </div>
                <form class="mt-4 flex flex-col gap-3 border-t border-list-border pt-4" @submit.prevent="regenerate">
                    <h3 class="text-sm font-semibold text-list-text">{{ t('ACCOUNT_REGENERATE') }}</h3>
                    <p class="text-sm text-list-text-muted">{{ t('ACCOUNT_REGENERATE_HELP') }}</p>
                    <label class="flex flex-col gap-1 text-sm font-medium text-list-text">
                        {{ t('ACCOUNT_CURRENT_PASSWORD') }}
                        <input v-model="regenPassword" autocomplete="current-password" class="h-11 rounded-lg border border-list-border px-3" type="password" />
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium text-list-text">
                        {{ t('ACCOUNT_CURRENT_CODE') }}
                        <input v-model="regenCode" class="h-11 rounded-lg border border-list-border px-3" type="text" />
                    </label>
                    <button class="inline-flex h-10 items-center justify-center rounded-lg border border-list-border px-4 text-sm font-semibold text-list-text" :disabled="busy" type="submit">{{ t('ACCOUNT_REGENERATE') }}</button>
                </form>
                <div v-if="recoveryCodes.length" class="mt-4">
                    <UiRecoveryCodeList :codes="recoveryCodes" :copied-label="t('SECURITY_COPIED')" :copy-label="t('SECURITY_COPY')" :download-label="t('SECURITY_DOWNLOAD')" />
                </div>
            </section>
            <p class="text-sm text-list-text-muted">{{ t('ACCOUNT_LOCKOUT_HELP') }}</p>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { PageLayout, UiOtpInput, UiQrCode, UiRecoveryCodeList } from '@freelance/vue-ui';
    import { computed, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import PasswordFields from '@/components/auth/PasswordFields.vue';
    import http from '@/utils/http';

    defineOptions({ name: 'AccountSecurityPage' });

    type SecurityStatus = {
        password_set_at: string | null;
        two_factor_confirmed_at: string | null;
        two_factor_enabled: boolean;
        recovery_codes_remaining: number;
    };

    const { t, locale } = useI18n();
    const status = ref<SecurityStatus | null>(null);
    const currentPassword = ref('');
    const password = ref('');
    const confirmation = ref('');
    const passwordValid = ref(false);
    const replacePassword = ref('');
    const replaceCode = ref('');
    const replacementUri = ref('');
    const replacementKey = ref('');
    const newCode = ref('');
    const regenPassword = ref('');
    const regenCode = ref('');
    const recoveryCodes = ref<string[]>([]);
    const message = ref('');
    const busy = ref(false);
    const confirmed = computed(() => {
        const value = status.value?.two_factor_confirmed_at;
        if (!value) return '—';
        return new Intl.DateTimeFormat(locale.value).format(new Date(value));
    });

    async function load(): Promise<void> {
        const response = await http.get('security/account');
        status.value = response.data.data;
    }

    onMounted(() => {
        void load();
    });

    async function changePassword(): Promise<void> {
        message.value = '';
        busy.value = true;
        try {
            await http.put('security/password', {
                current_password: currentPassword.value,
                password: password.value,
                password_confirmation: confirmation.value,
            });
            password.value = '';
            confirmation.value = '';
            currentPassword.value = '';
            message.value = t('ACCOUNT_PASSWORD_UPDATED');
            await load();
        } catch {
            message.value = t('ACCOUNT_ACTION_FAILED');
        } finally {
            busy.value = false;
        }
    }

    async function startReplace(): Promise<void> {
        message.value = '';
        busy.value = true;
        try {
            const response = await http.post('security/two-factor/replace', {
                current_password: replacePassword.value,
                code: replaceCode.value,
            });
            replacementUri.value = response.data.data.otpauth_uri;
            replacementKey.value = response.data.data.manual_key;
        } catch {
            message.value = t('ACCOUNT_ACTION_FAILED');
        } finally {
            busy.value = false;
        }
    }

    async function confirmReplace(): Promise<void> {
        busy.value = true;
        try {
            const response = await http.post('security/two-factor/replace/confirm', { code: newCode.value });
            recoveryCodes.value = response.data.data.recovery_codes ?? [];
            replacementUri.value = '';
            newCode.value = '';
            await load();
        } catch {
            message.value = t('SECURITY_CODE_INVALID');
        } finally {
            busy.value = false;
        }
    }

    async function regenerate(): Promise<void> {
        busy.value = true;
        try {
            const response = await http.post('security/recovery-codes', {
                current_password: regenPassword.value,
                code: regenCode.value,
            });
            recoveryCodes.value = response.data.data.recovery_codes ?? [];
            regenPassword.value = '';
            regenCode.value = '';
            await load();
        } catch {
            message.value = t('ACCOUNT_ACTION_FAILED');
        } finally {
            busy.value = false;
        }
    }
</script>
