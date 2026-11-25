<template>
    <PageLayout :label="t('ACCOUNT_SECURITY')">
        <div v-if="loading" class="flex flex-1 items-center justify-center" role="status">
            <span class="size-10 animate-spin rounded-full border-2 border-list-border border-t-primary motion-reduce:animate-none" />
            <span class="sr-only">{{ t('ACCOUNT_LOADING') }}</span>
        </div>
        <div v-else class="relative flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 overflow-y-auto">
                <div class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-1 pt-1 pb-10">
                    <header class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary-light text-emphasis">
                            <Icon class="size-5" icon="material-symbols:shield-outline-rounded" />
                        </span>
                        <div class="min-w-0">
                            <h1 class="m-0 text-lg font-semibold text-list-text">{{ t('ACCOUNT_SECURITY') }}</h1>
                            <p class="m-0 mt-1 max-w-2xl text-sm text-list-text-muted">{{ t('ACCOUNT_SECURITY_INTRO') }}</p>
                        </div>
                    </header>

                    <div v-if="failed" class="rounded-xl border border-list-border bg-list-surface p-4" role="alert">
                        <p class="m-0 text-sm text-list-text">{{ t('ACCOUNT_UNAVAILABLE') }}</p>
                        <button type="button" class="mt-3 inline-flex h-9 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand" @click="load">{{ t('ACCOUNT_RETRY') }}</button>
                    </div>

                    <template v-else>
                        <section>
                            <h2 class="m-0 mb-3 text-xs font-semibold tracking-wide text-list-text-muted uppercase">{{ t('ACCOUNT_OVERVIEW') }}</h2>
                            <UiSecurityOverview :items="overview" />
                        </section>

                        <div class="grid items-stretch gap-4 lg:grid-cols-2">
                            <UiSecurityActionCard
                                icon="material-symbols:key-outline-rounded"
                                :title="t('ACCOUNT_PASSWORD_STATUS')"
                                :status="passwordStatus"
                                :description="t('ACCOUNT_PASSWORD_HELP')"
                            >
                                <template #action>
                                    <button type="button" class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand" @click="openPassword">{{ t('ACCOUNT_CHANGE_PASSWORD') }}</button>
                                </template>
                            </UiSecurityActionCard>
                            <UiSecurityActionCard
                                icon="material-symbols:phonelink-lock-outline-rounded"
                                :title="t('ACCOUNT_AUTHENTICATOR')"
                                :status="authenticatorStatus"
                                :description="t('ACCOUNT_AUTHENTICATOR_HELP')"
                            >
                                <template #action>
                                    <button type="button" class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand" @click="openAuthenticator">{{ t('ACCOUNT_REPLACE_AUTHENTICATOR') }}</button>
                                </template>
                            </UiSecurityActionCard>
                        </div>

                        <UiSecurityActionCard
                            icon="material-symbols:vpn-key-outline-rounded"
                            :title="t('ACCOUNT_RECOVERY_TITLE')"
                            :status="recoveryStatus"
                            :description="t('ACCOUNT_RECOVERY_HELP')"
                        >
                            <template #action>
                                <button type="button" class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand" @click="openRecovery">{{ t('ACCOUNT_REGENERATE') }}</button>
                            </template>
                        </UiSecurityActionCard>

                        <UiCallout>{{ t('ACCOUNT_LOCKOUT_HELP') }}</UiCallout>
                    </template>
                </div>
            </div>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { PageLayout, UiCallout, UiSecurityActionCard, UiSecurityOverview, type SecurityOverviewItem } from '@freelance/vue-ui';
    import { Icon } from '@iconify/vue';
    import { computed, onMounted, ref, type Component } from 'vue';
    import { useI18n } from 'vue-i18n';
    import AuthenticatorReplaceModal from '@/components/security/AuthenticatorReplaceModal.vue';
    import PasswordChangeModal from '@/components/security/PasswordChangeModal.vue';
    import RecoveryCodesModal from '@/components/security/RecoveryCodesModal.vue';
    import { useModalService } from '@/composables/useModalService';
    import { ModalType } from '@/modules/enums/support/modals/ModalType';
    import http from '@/utils/http';

    defineOptions({ name: 'AccountSecurityPage' });

    type SecurityStatus = {
        password_set_at: string | null;
        two_factor_confirmed_at: string | null;
        two_factor_enabled: boolean;
        recovery_codes_remaining: number;
    };

    const { t, locale } = useI18n();
    const modalService = useModalService();
    const status = ref<SecurityStatus | null>(null);
    const loading = ref(true);
    const failed = ref(false);

    const passwordStatus = computed(() => {
        if (!status.value?.password_set_at) return t('ACCOUNT_PASSWORD_MISSING');
        return t('ACCOUNT_PASSWORD_CHANGED', { date: formatDate(status.value.password_set_at) });
    });
    const authenticatorStatus = computed(() => {
        if (!status.value?.two_factor_enabled) return t('ACCOUNT_2FA_OFF');
        if (!status.value.two_factor_confirmed_at) return t('ACCOUNT_2FA_ACTIVE');
        return t('ACCOUNT_AUTHENTICATOR_SET_ON', { date: formatDate(status.value.two_factor_confirmed_at) });
    });
    const recoveryStatus = computed(() => t('ACCOUNT_RECOVERY_AVAILABLE', { count: status.value?.recovery_codes_remaining ?? 0 }));
    const overview = computed<SecurityOverviewItem[]>(() => [
        {
            id: 'password',
            icon: 'material-symbols:key-outline-rounded',
            label: t('ACCOUNT_PASSWORD_STATUS'),
            value: status.value?.password_set_at ? t('ACCOUNT_PASSWORD_SET') : t('ACCOUNT_PASSWORD_MISSING'),
            tone: status.value?.password_set_at ? 'success' : 'warning',
        },
        {
            id: 'mfa',
            icon: 'material-symbols:verified-user-outline-rounded',
            label: t('ACCOUNT_2FA_STATUS'),
            value: status.value?.two_factor_enabled ? t('ACCOUNT_2FA_ACTIVE') : t('ACCOUNT_2FA_OFF'),
            tone: status.value?.two_factor_enabled ? 'success' : 'warning',
        },
        {
            id: 'authenticator',
            icon: 'material-symbols:phonelink-lock-outline-rounded',
            label: t('ACCOUNT_AUTHENTICATOR'),
            value: status.value?.two_factor_confirmed_at ? formatDate(status.value.two_factor_confirmed_at) : t('ACCOUNT_2FA_OFF'),
            tone: status.value?.two_factor_confirmed_at ? 'success' : 'warning',
        },
        {
            id: 'recovery',
            icon: 'material-symbols:vpn-key-outline-rounded',
            label: t('ACCOUNT_RECOVERY_TITLE'),
            value: recoveryStatus.value,
            tone: (status.value?.recovery_codes_remaining ?? 0) > 0 ? 'success' : 'warning',
        },
    ]);

    function formatDate(value: string): string {
        return new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(value));
    }

    async function load(): Promise<void> {
        loading.value = status.value === null;
        failed.value = false;
        try {
            const response = await http.get('security/account');
            status.value = response.data.data;
        } catch {
            failed.value = status.value === null;
        } finally {
            loading.value = false;
        }
    }

    function openSecurity(component: Component, widthClass: string): void {
        void modalService.open({
            title: 'ACCOUNT_SECURITY',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component,
            bare: true,
            disableDefaultPadding: true,
            widthClass,
            panelClass: '!h-auto !rounded-2xl !border-list-border !bg-list-surface !text-list-text',
            props: { onUpdated: () => void load() },
        });
    }

    function openPassword(): void {
        openSecurity(PasswordChangeModal, 'w-full max-w-lg');
    }

    function openAuthenticator(): void {
        openSecurity(AuthenticatorReplaceModal, 'w-full max-w-2xl');
    }

    function openRecovery(): void {
        openSecurity(RecoveryCodesModal, 'w-full max-w-lg');
    }

    onMounted(() => {
        void load();
    });
</script>
