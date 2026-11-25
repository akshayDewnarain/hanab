<template>
    <section ref="root" class="flex max-h-[80vh] flex-col bg-list-surface text-list-text" @keydown="onKeydown">
        <header class="flex items-start justify-between gap-3 border-b border-list-border px-4 py-3">
            <div>
                <h2 class="m-0 text-base font-semibold">{{ t('ACCOUNT_CHANGE_PASSWORD') }}</h2>
                <p class="m-0 mt-1 text-sm text-list-text-muted">{{ t('ACCOUNT_PASSWORD_HELP') }}</p>
            </div>
            <button type="button" class="inline-flex size-8 cursor-pointer items-center justify-center rounded-lg text-list-text-muted hover:bg-list-row-hover" :aria-label="t('ACCOUNT_CLOSE')" @click="dismissModal">
                <Icon icon="material-symbols:close-rounded" class="size-5" />
            </button>
        </header>
        <form class="flex flex-col gap-4 overflow-y-auto px-4 py-4" @submit.prevent="submit">
            <UiPasswordInput
                v-model="currentPassword"
                autocomplete="current-password"
                :caps-lock-label="t('PASSWORD_CAPS_LOCK')"
                :disabled="busy"
                :error="error"
                :hide-label="t('PASSWORD_HIDE')"
                :label="t('ACCOUNT_CURRENT_PASSWORD')"
                name="current-password"
                :show-label="t('PASSWORD_SHOW')"
            />
            <PasswordFields v-model:confirmation="confirmation" v-model:password="password" v-model:valid="passwordValid" :disabled="busy" />
            <div class="flex justify-end gap-2">
                <UiAuthButton variant="secondary" type="button" :disabled="busy" @click="dismissModal">{{ t('ACCOUNT_CANCEL') }}</UiAuthButton>
                <UiAuthButton type="submit" :disabled="busy || !passwordValid || !currentPassword" :loading="busy">{{ t('ACCOUNT_CHANGE_PASSWORD') }}</UiAuthButton>
            </div>
        </form>
    </section>
</template>

<script lang="ts" setup>
    import { UiAuthButton, UiPasswordInput } from '@freelance/vue-ui';
    import { Icon } from '@iconify/vue';
    import { inject, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import PasswordFields from '@/components/auth/PasswordFields.vue';
    import { useToastService } from '@/composables/useToastService';
    import { securityErrorMessage } from '@/modules/auth/securityError';
    import http from '@/utils/http';

    defineOptions({ name: 'PasswordChangeModal' });

    const props = defineProps<{
        props: { onUpdated?: () => void };
    }>();

    const dismissModal = inject<() => void>('dismissModal', () => undefined);
    const { t } = useI18n();
    const toast = useToastService();
    const root = ref<HTMLElement | null>(null);
    const currentPassword = ref('');
    const password = ref('');
    const confirmation = ref('');
    const passwordValid = ref(false);
    const busy = ref(false);
    const error = ref('');

    function onKeydown(event: KeyboardEvent): void {
        if (event.key !== 'Tab' || !root.value) return;
        const nodes = [...root.value.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]), a[href]')];
        const first = nodes[0];
        const last = nodes.at(-1);
        if (!first || !last) return;
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    async function submit(): Promise<void> {
        if (busy.value || !passwordValid.value) return;
        error.value = '';
        busy.value = true;
        try {
            await http.put('security/password', {
                current_password: currentPassword.value,
                password: password.value,
                password_confirmation: confirmation.value,
            });
            currentPassword.value = '';
            password.value = '';
            confirmation.value = '';
            props.props.onUpdated?.();
            toast.success('ACCOUNT_PASSWORD_UPDATED');
            dismissModal();
        } catch (caught) {
            error.value = securityErrorMessage(caught, t);
        } finally {
            busy.value = false;
        }
    }

    onMounted(() => {
        root.value?.querySelector('input')?.focus();
    });
</script>
