<template>
    <section ref="root" class="flex max-h-[80vh] flex-col bg-list-surface text-list-text" @keydown="onKeydown">
        <header class="flex items-start justify-between gap-3 border-b border-list-border px-4 py-3">
            <div>
                <h2 class="m-0 text-base font-semibold">{{ t('ACCOUNT_REGENERATE') }}</h2>
                <p class="m-0 mt-1 text-sm text-list-text-muted">{{ t('ACCOUNT_RECOVERY_HELP') }}</p>
            </div>
            <button type="button" class="inline-flex size-8 cursor-pointer items-center justify-center rounded-lg text-list-text-muted hover:bg-list-row-hover disabled:cursor-not-allowed disabled:opacity-40" :aria-label="t('ACCOUNT_CLOSE')" :disabled="codes.length > 0 && !saved" @click="dismissModal">
                <Icon icon="material-symbols:close-rounded" class="size-5" />
            </button>
        </header>
        <div class="overflow-y-auto px-4 py-4">
            <form v-if="!codes.length" class="flex flex-col gap-4" @submit.prevent="regenerate">
                <UiCallout kind="warning" :label="t('ACCOUNT_RECOVERY_WARNING_TITLE')">{{ t('ACCOUNT_RECOVERY_WARNING') }}</UiCallout>
                <UiPasswordInput v-model="currentPassword" autocomplete="current-password" :caps-lock-label="t('PASSWORD_CAPS_LOCK')" :disabled="busy" :hide-label="t('PASSWORD_HIDE')" :label="t('ACCOUNT_CURRENT_PASSWORD')" name="current-password" :show-label="t('PASSWORD_SHOW')" />
                <label class="flex flex-col gap-1 text-sm font-semibold">
                    {{ t('ACCOUNT_CURRENT_CODE') }}
                    <input v-model="currentCode" autocomplete="one-time-code" class="h-11 rounded-lg border border-list-border bg-list-surface px-3 text-sm font-normal" :disabled="busy" />
                </label>
                <p v-if="error" class="m-0 text-sm text-error" role="alert">{{ error }}</p>
                <div class="flex justify-end gap-2">
                    <UiAuthButton variant="secondary" type="button" :disabled="busy" @click="dismissModal">{{ t('ACCOUNT_CANCEL') }}</UiAuthButton>
                    <UiAuthButton type="submit" :disabled="busy || !currentPassword || !currentCode" :loading="busy">{{ t('ACCOUNT_REGENERATE') }}</UiAuthButton>
                </div>
            </form>
            <form v-else class="flex flex-col gap-4" @submit.prevent="finish">
                <UiSecurityNotice>{{ t('SECURITY_RECOVERY_ONCE') }}</UiSecurityNotice>
                <UiRecoveryCodeList :codes="codes" :copied-label="t('SECURITY_COPIED')" :copy-failed-label="t('SECURITY_COPY_FAILED')" :copy-label="t('SECURITY_COPY')" :download-label="t('SECURITY_DOWNLOAD')" />
                <label class="flex items-start gap-2.5 text-sm leading-5">
                    <input v-model="saved" class="mt-1 size-4 accent-primary" type="checkbox" />
                    <span>{{ t('SECURITY_RECOVERY_SAVED') }}</span>
                </label>
                <div class="flex justify-end">
                    <UiAuthButton type="submit" :disabled="!saved">{{ t('ACCOUNT_CLOSE') }}</UiAuthButton>
                </div>
            </form>
        </div>
    </section>
</template>

<script lang="ts" setup>
    import { UiAuthButton, UiCallout, UiPasswordInput, UiRecoveryCodeList, UiSecurityNotice } from '@freelance/vue-ui';
    import { Icon } from '@iconify/vue';
    import { inject, onBeforeUnmount, onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useToastService } from '@/composables/useToastService';
    import { securityErrorMessage } from '@/modules/auth/securityError';
    import http from '@/utils/http';

    defineOptions({ name: 'RecoveryCodesModal' });

    const props = defineProps<{
        props: { onUpdated?: () => void };
    }>();

    const dismissModal = inject<() => void>('dismissModal', () => undefined);
    const { t } = useI18n();
    const toast = useToastService();
    const root = ref<HTMLElement | null>(null);
    const currentPassword = ref('');
    const currentCode = ref('');
    const codes = ref<string[]>([]);
    const saved = ref(false);
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

    async function regenerate(): Promise<void> {
        if (busy.value) return;
        error.value = '';
        busy.value = true;
        try {
            const response = await http.post('security/recovery-codes', {
                current_password: currentPassword.value,
                code: currentCode.value,
            });
            codes.value = response.data.data.recovery_codes ?? [];
            currentPassword.value = '';
            currentCode.value = '';
            props.props.onUpdated?.();
            toast.success('ACCOUNT_CODES_READY');
        } catch (caught) {
            error.value = securityErrorMessage(caught, t);
        } finally {
            busy.value = false;
        }
    }

    function finish(): void {
        if (!saved.value) return;
        codes.value = [];
        dismissModal();
    }

    function allowDismiss(): boolean {
        return codes.value.length === 0 || saved.value;
    }

    defineExpose({ allowDismiss });

    onMounted(() => {
        root.value?.querySelector('input')?.focus();
    });

    onBeforeUnmount(() => {
        codes.value = [];
        currentPassword.value = '';
        currentCode.value = '';
    });
</script>
