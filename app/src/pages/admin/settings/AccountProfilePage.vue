<template>
    <PageLayout :label="t('ACCOUNT_PROFILE')">
        <div class="relative flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 overflow-y-auto">
                <div class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-1 pt-1 pb-10">
                    <header class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary-light text-emphasis">
                            <Icon class="size-5" icon="material-symbols:account-circle-outline" />
                        </span>
                        <div class="min-w-0">
                            <h1 class="m-0 text-lg font-semibold text-list-text">{{ t('ACCOUNT_PROFILE') }}</h1>
                            <p class="m-0 mt-1 max-w-2xl text-sm text-list-text-muted">{{ t('ACCOUNT_PROFILE_INTRO') }}</p>
                        </div>
                    </header>

                    <section class="rounded-xl border border-list-border bg-list-surface p-4 shadow-[0_1px_2px_color-mix(in_srgb,var(--color-list-shadow-tint)_6%,transparent)]">
                        <div class="flex items-center gap-4">
                            <span class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-light text-base font-semibold text-emphasis">
                                <img v-if="avatar" :src="avatar" alt="" class="size-full object-cover" />
                                <template v-else>{{ initials }}</template>
                            </span>
                            <div class="min-w-0">
                                <h2 class="m-0 truncate text-lg font-semibold text-list-text">{{ user?.name || '—' }}</h2>
                                <p class="m-0 mt-0.5 truncate text-sm text-list-text-muted">{{ user?.email || '—' }}</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <span v-for="item in roleLabels" :key="item" class="inline-flex rounded-full bg-list-canvas px-2 py-0.5 text-xs font-medium text-list-text">{{ item }}</span>
                                    <span v-if="statusLabel" class="inline-flex rounded-full bg-primary-light px-2 py-0.5 text-xs font-medium text-emphasis">{{ statusLabel }}</span>
                                </div>
                            </div>
                        </div>
                        <dl class="mt-4 grid gap-3 border-t border-list-border pt-4 sm:grid-cols-3">
                            <div>
                                <dt class="text-xs font-medium text-list-text-muted">{{ t('INVITATION_FULL_NAME') }}</dt>
                                <dd class="m-0 mt-1 text-sm font-medium text-list-text">{{ user?.name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-list-text-muted">{{ t('LOGIN_EMAIL') }}</dt>
                                <dd class="m-0 mt-1 truncate text-sm font-medium text-list-text">{{ user?.email || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-list-text-muted">{{ t('USERS_ROLE') }}</dt>
                                <dd class="m-0 mt-1 text-sm font-medium text-list-text">{{ roleLabels.join(', ') || '—' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <div class="grid items-start gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(18rem,0.8fr)]">
                        <MessagePreferencesSection />
                        <UiSecurityActionCard icon="material-symbols:shield-outline-rounded" :title="t('ACCOUNT_SECURITY_CARD_TITLE')" :status="securityStatus" :description="t('ACCOUNT_SECURITY_CARD_HELP')">
                            <template #action>
                                <router-link class="inline-flex h-9 items-center rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand" :to="{ name: 'admin-settings-security' }">
                                    {{ t('ACCOUNT_SECURITY_OPEN') }}
                                </router-link>
                            </template>
                        </UiSecurityActionCard>
                    </div>
                </div>
            </div>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { PageLayout, UiSecurityActionCard } from '@freelance/vue-ui';
    import { Icon } from '@iconify/vue';
    import { computed, nextTick, onMounted, ref, watch } from 'vue';
    import { useRoute } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import MessagePreferencesSection from '@/components/messages/MessagePreferencesSection.vue';
    import { useAuthStore } from '@/stores/auth';
    import http from '@/utils/http';

    defineOptions({ name: 'AccountProfilePage' });

    const { t } = useI18n();
    const route = useRoute();
    const auth = useAuthStore();
    const user = computed(() => auth.user);
    const security = ref<{ password_set_at: string | null; two_factor_enabled: boolean } | null>(null);

    const avatar = computed(() => user.value?.profile_picture_url || '');
    const initials = computed(() => {
        const parts = (user.value?.name || '').trim().split(/\s+/).filter(Boolean);
        if (parts.length > 1) return `${parts[0]?.[0] ?? ''}${parts.at(-1)?.[0] ?? ''}`.toUpperCase();
        return parts[0]?.slice(0, 2).toUpperCase() || '?';
    });
    const roleLabels = computed(() => {
        const names = user.value?.roles?.map((role) => role.name).filter(Boolean) ?? [];
        const source = names.length ? names : user.value?.role_name ? [user.value.role_name] : [];
        return source.map((name) => {
            const key = `GENERAL_${name.toUpperCase()}`;
            return t(key) !== key ? t(key) : name;
        });
    });
    const statusLabel = computed(() => {
        const status = user.value?.status;
        if (status === 'active') return t('ACCOUNT_STATUS_ACTIVE');
        if (status === 'invited') return t('ACCOUNT_STATUS_INVITED');
        if (status === 'disabled') return t('ACCOUNT_STATUS_DISABLED');
        return '';
    });
    const securityStatus = computed(() => {
        if (!security.value) return '';
        const password = security.value.password_set_at ? t('ACCOUNT_PASSWORD_SET') : t('ACCOUNT_PASSWORD_MISSING');
        const authenticator = security.value.two_factor_enabled ? t('ACCOUNT_2FA_ACTIVE') : t('ACCOUNT_2FA_OFF');
        return `${t('ACCOUNT_PASSWORD_STATUS')}: ${password}\n${t('ACCOUNT_2FA_STATUS')}: ${authenticator}`;
    });

    function revealPreferences(): void {
        if (route.hash !== '#message-preferences') return;
        const target = document.getElementById('message-preferences');
        if (!target) return;
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        target.scrollIntoView({ block: 'start', behavior: reduce ? 'auto' : 'smooth' });
        if (target instanceof HTMLElement) target.focus({ preventScroll: true });
    }

    watch(
        () => route.hash,
        () => {
            void nextTick(revealPreferences);
        },
    );

    onMounted(async () => {
        void http
            .get('security/account')
            .then((response) => {
                security.value = response.data.data;
            })
            .catch(() => undefined);
        await nextTick();
        revealPreferences();
    });
</script>
