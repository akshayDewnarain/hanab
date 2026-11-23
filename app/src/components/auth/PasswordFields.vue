<template>
    <div class="flex flex-col gap-4">
        <UiPasswordInput
            v-model="password"
            autocomplete="new-password"
            :caps-lock-label="t('PASSWORD_CAPS_LOCK')"
            :disabled="disabled"
            :hide-label="t('PASSWORD_HIDE')"
            :label="t('INVITATION_PASSWORD')"
            :maxlength="policy.max"
            name="new-password"
            :show-label="t('PASSWORD_SHOW')"
            @focus="focused = true"
        />
        <UiPasswordRequirements
            :interacted="password.length > 0"
            :invalid-label="t('PASSWORD_RULE_NOT_MET')"
            :label="t('PASSWORD_REQUIREMENTS')"
            :neutral-label="t('PASSWORD_RULE_NEUTRAL')"
            :revealed="focused || password.length > 0"
            :rules="rules"
            :valid-label="t('PASSWORD_RULE_MET')"
            :value="password"
        />
        <UiPasswordStrength :hints="hints" :labels="labels" :title="t('PASSWORD_STRENGTH')" :value="password" />
        <UiPasswordInput
            v-model="confirmation"
            autocomplete="new-password"
            :caps-lock-label="t('PASSWORD_CAPS_LOCK')"
            :disabled="disabled"
            :error="mismatch"
            :hide-label="t('PASSWORD_HIDE')"
            :label="t('INVITATION_PASSWORD_CONFIRM')"
            :maxlength="policy.max"
            name="confirm-password"
            :show-label="t('PASSWORD_SHOW')"
            @focus="confirmationStarted = true"
        />
        <p v-if="matches" class="m-0 flex items-center gap-2 text-xs text-emerald-700" role="status">
            <Icon icon="material-symbols:check-circle-rounded" />
            {{ t('PASSWORD_MATCH') }}
        </p>
    </div>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { UiPasswordInput, UiPasswordRequirements, UiPasswordStrength } from '@freelance/vue-ui';
    import { computed, onMounted, ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { loadPasswordPolicy, passwordRules, type PasswordPolicyDescriptor } from '@/modules/auth/passwordPolicy';

    defineOptions({ name: 'PasswordFields' });

    const password = defineModel<string>('password', { required: true });
    const confirmation = defineModel<string>('confirmation', { required: true });
    const valid = defineModel<boolean>('valid', { default: false });

    defineProps<{ disabled?: boolean }>();

    const { t } = useI18n();
    const focused = ref(false);
    const confirmationStarted = ref(false);
    const policy = ref<PasswordPolicyDescriptor>({ min: 12, max: 128, uppercase: true, lowercase: true, numbers: true, symbols: true });

    const rules = computed(() => passwordRules(policy.value, (key, values) => t(key, values ?? {})));
    const satisfied = computed(() => password.value.length > 0 && rules.value.every((rule) => rule.test(password.value)));
    const matches = computed(() => confirmation.value.length > 0 && password.value === confirmation.value);
    const mismatch = computed(() => (confirmationStarted.value && confirmation.value.length > 0 && !matches.value ? t('INVITATION_PASSWORD_MISMATCH') : ''));

    const labels = computed(() => ({
        veryWeak: t('PASSWORD_STRENGTH_VERY_WEAK'),
        weak: t('PASSWORD_STRENGTH_WEAK'),
        fair: t('PASSWORD_STRENGTH_FAIR'),
        good: t('PASSWORD_STRENGTH_GOOD'),
        strong: t('PASSWORD_STRENGTH_STRONG'),
    }));
    const hints = computed(() => ({
        veryWeak: t('PASSWORD_HINT_VERY_WEAK'),
        weak: t('PASSWORD_HINT_WEAK'),
        fair: t('PASSWORD_HINT_FAIR'),
        good: t('PASSWORD_HINT_GOOD'),
        strong: t('PASSWORD_HINT_STRONG'),
    }));

    watch([satisfied, matches], () => {
        valid.value = satisfied.value && matches.value;
    }, { immediate: true });

    onMounted(async () => {
        policy.value = await loadPasswordPolicy();
    });
</script>
