<template>
    <div class="relative min-h-screen w-full overflow-hidden">
        <div
            aria-hidden="true"
            class="absolute inset-0 scale-105 bg-[url('/background-cover.png')] bg-cover bg-center"
        />
        <div
            aria-hidden="true"
            class="absolute inset-0 bg-gradient-to-r from-[var(--color-primary)]/70 via-[var(--color-primary)]/35 to-black/25 backdrop-blur-[3px]"
        />
        <div aria-hidden="true" class="absolute inset-0 bg-black/20 backdrop-blur-sm" />

        <div
            class="relative z-10 flex min-h-screen items-center justify-center p-4 sm:p-8 md:justify-start md:pl-16 lg:pl-24 xl:pl-[clamp(4rem,14vw,10rem)]"
        >
            <div
                class="w-full max-w-[26rem] shrink-0 overflow-hidden rounded-2xl border border-white/10 border-t-2 border-t-[var(--color-secondary)]/60 bg-[var(--color-highlight-dark)]/92 p-6 shadow-[0_32px_88px_rgba(0,0,0,0.55),0_10px_28px_rgba(0,0,0,0.38)] sm:p-8"
            >
                    <div
                        class="mx-auto mb-6 flex w-fit items-center justify-center rounded-xl border border-white/35 bg-white px-4 py-3 ring-1 ring-[var(--color-secondary)]/25 shadow-[0_2px_0_0_color-mix(in_srgb,var(--color-secondary)_35%,transparent),0_4px_14px_rgba(0,0,0,0.14)]"
                    >
                        <img
                            alt="Hanab"
                            class="h-10 w-auto max-w-[200px] object-contain"
                            src="/hanab-logo-full.png"
                        />
                    </div>

                    <div class="mb-8 mt-6 text-center">
                        <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            {{ t('LOGIN_TITLE') }}
                        </h1>
                        <p class="mt-2 text-sm text-white/70">
                            {{ t('LOGIN_SUBTITLE') }}
                        </p>
                    </div>

                    <form class="flex flex-col gap-5" @submit.prevent="login">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-semibold text-white/90" for="login-email">
                                {{ t('LOGIN_EMAIL') }}
                            </label>
                            <div class="relative">
                                <Icon
                                    class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-[var(--color-primary)]"
                                    icon="material-symbols:mail-outline-rounded"
                                />
                                <input
                                    id="login-email"
                                    v-model="email"
                                    autocomplete="email"
                                    :placeholder="t('LOGIN_EMAIL_PLACEHOLDER')"
                                    class="w-full rounded-lg border border-white/20 bg-white py-3 pr-4 pl-11 text-sm text-slate-800 shadow-sm outline-none transition focus:border-[var(--color-secondary)] focus:ring-2 focus:ring-[var(--color-secondary)]/30"
                                    required
                                    type="email"
                                />
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-semibold text-white/90" for="login-password">
                                {{ t('LOGIN_PASSWORD') }}
                            </label>
                            <div class="relative">
                                <Icon
                                    class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-[var(--color-primary)]"
                                    icon="material-symbols:lock-outline-rounded"
                                />
                                <input
                                    id="login-password"
                                    v-model="password"
                                    autocomplete="current-password"
                                    :placeholder="t('LOGIN_PASSWORD_PLACEHOLDER')"
                                    class="w-full rounded-lg border border-white/20 bg-white py-3 pr-4 pl-11 text-sm text-slate-800 shadow-sm outline-none transition focus:border-[var(--color-secondary)] focus:ring-2 focus:ring-[var(--color-secondary)]/30"
                                    required
                                    type="password"
                                />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <RouterLink
                                class="text-sm font-medium text-white/80 underline-offset-2 transition hover:text-[var(--color-secondary)] hover:underline"
                                to="/forgot-password"
                            >
                                {{ t('LOGIN_FORGOT_PASSWORD') }}
                            </RouterLink>
                        </div>

                        <button
                            class="mt-1 flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-[var(--color-primary)] px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-[var(--color-sidebar-background)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-secondary)]/40 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isSubmitting"
                            type="submit"
                        >
                            <Icon
                                v-if="isSubmitting"
                                class="size-5 animate-spin"
                                icon="material-symbols:progress-activity-rounded"
                            />
                            {{ isSubmitting ? t('LOGIN_SIGNING_IN') : t('LOGIN_SIGN_IN') }}
                        </button>
                    </form>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import http from '@/utils/http';
    import { ref } from 'vue';
    import { RouterLink, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { useAuthStore } from '@/stores/auth.ts';

    defineOptions({
        name: 'login-page',
    });

    const email = ref<string>('');
    const password = ref<string>('');
    const isSubmitting = ref(false);

    const router = useRouter();
    const { t } = useI18n();
    const authStore = useAuthStore();

    async function login(): Promise<void> {
        if (isSubmitting.value) {
            return;
        }

        isSubmitting.value = true;

        try {
            await getCookie();

            const response = await http.post(
                'login',
                {
                    email: email.value,
                    password: password.value,
                },
                {
                    baseURL: import.meta.env.VITE_API_URL + '/api',
                },
            );

            localStorage.setItem('token', response.data.data.token);

            await authStore.fetchUser();

            await router.push({ name: 'admin-employees-overview' });
        } catch (error) {
            console.error('Error logging in:', error);
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
