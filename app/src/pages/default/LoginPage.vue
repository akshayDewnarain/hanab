
<template>
    <div class="flex h-full w-full bg-gray-200">
        <Transition
            appear
            enter-active-class="transition-[width,opacity] duration-300 ease-out overflow-hidden"
            enter-from-class="!w-0 opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-[width,opacity] duration-200 ease-in overflow-hidden"
            leave-to-class="!w-0 opacity-0"
        >
            <div
                v-if="!isLoading"
                class="h-full shrink-0 w-[var(--panel-w)]"
                style="--panel-w: clamp(550px, 40vw, 600px)"
            >
                <div
                    class="flex h-full flex-col justify-center bg-[var(--color-primary)] border-r border-gray-700 shadow-lg"
                >
                    <form class="flex flex-col bg-white rounded p-6 mx-10 gap-4 shadow-lg">
                        <div class="flex flex-col items-center justify-center">
                            <img alt="Logo" class="w-1/2 h-auto max-w-[180px]" src="/cesta-ca-logo-long.png" />
                            <div>
                                <h1 class="text-3xl font-bold text-[var(--color-primary)]">
                                    {{ t('LOGIN_TITLE') }}
                                </h1>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[var(--color-primary)] font-bold px-2" for="email">{{
                                    t('LOGIN_EMAIL')
                                }}</label>
                            <div class="flex flex-row bg-white rounded shadow relative">
                                <input
                                    v-model="email"
                                    :placeholder="t('LOGIN_EMAIL_PLACEHOLDER')"
                                    class="input-base"
                                    type="email"
                                />
                                <div
                                    class="absolute right-0 h-full flex items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded-r px-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                                >
                                    <Icon class="text-white w-4 h-4" icon="material-symbols:person-rounded" />
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[var(--color-primary)] font-bold px-2" for="password">{{
                                    t('LOGIN_PASSWORD')
                                }}</label>
                            <div class="flex flex-row bg-white rounded shadow relative">
                                <input
                                    v-model="password"
                                    :placeholder="t('LOGIN_PASSWORD_PLACEHOLDER')"
                                    class="input-base"
                                    type="password"
                                />
                                <div
                                    class="absolute right-0 h-full flex items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded-r px-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                                >
                                    <Icon class="text-white w-4 h-4" icon="material-symbols:key-rounded" />
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <RouterLink
                                class="text-[var(--color-primary)] font-bold px-2 hover:text-[var(--color-highlight-dark)] cursor-pointer"
                                to="/forgot-password"
                            >
                                {{ t('LOGIN_FORGOT_PASSWORD') }}
                            </RouterLink>
                        </div>
                        <div class="flex justify-center">
                            <button
                                class="bg-[var(--color-primary)] cursor-pointer text-white font-bold px-8 py-2 rounded hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                                type="submit"
                                @click.prevent="login()"
                            >
                                {{ t('LOGIN_SIGN_IN') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
        <div class="flex flex-col w-full">
            <div class="relative flex justify-center items-center w-full h-full overflow-hidden">
                <!-- Background image -->
                <div class="absolute inset-0 bg-cover bg-center"></div>

                <!-- Blur + gradient overlay -->
                <div
                    class="absolute inset-0 backdrop-blur-md bg-gradient-to-r from-black/80 via-black/60 to-white/10"
                ></div>

                <!-- Optional: content on top -->
                <div class="z-10">
                    <div class="max-w-md text-white">
                        <h2 class="text-3xl font-semibold">Welcome back</h2>
                        <p class="mt-2 text-white/80">Manage orders, products and settings in one place.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { Icon } from '@iconify/vue';
import http from '@/utils/http';
import { onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useComponentState } from '@/composables/components/useComponentState';
import { useAuthStore } from '@/stores/auth.ts';

defineOptions({
    name: 'login-page',
});

const email = ref<string>('');
const password = ref<string>('');

const { isLoading, setLoading } = useComponentState();

setLoading(true);

const router = useRouter();
const { t } = useI18n();
const authStore = useAuthStore();

async function login(): Promise<void> {
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
    }
}

async function getCookie(): Promise<void> {
    await http.get('/sanctum/csrf-cookie', {
        baseURL: import.meta.env.VITE_API_URL,
    });
}

onMounted(() => {
    setLoading(false);
});
</script>

