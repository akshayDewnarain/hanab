<template>
    <UiPreferenceGroup id="message-preferences" class="scroll-mt-4" icon="material-symbols:notifications-outline-rounded" tabindex="-1" :title="t('MESSAGE_PREFERENCES_TITLE')" :description="t('MESSAGE_PREFERENCES_INTRO')">
        <UiPreferenceRow icon="material-symbols:groups-outline-rounded" :title="t('MESSAGE_CATEGORY_PEOPLE')" :description="t('MESSAGE_CATEGORY_PEOPLE_HELP')" :feedback="feedbackFor('people')" :saved="savedField === 'people'">
            <ToggleSwitch v-model="people" :label="t('MESSAGE_CATEGORY_PEOPLE')" />
        </UiPreferenceRow>
        <UiPreferenceRow icon="material-symbols:cake-outline-rounded" :title="t('MESSAGE_CATEGORY_MILESTONES')" :description="t('MESSAGE_CATEGORY_MILESTONES_HELP')" :feedback="feedbackFor('milestones')" :saved="savedField === 'milestones'">
            <ToggleSwitch v-model="milestones" :label="t('MESSAGE_CATEGORY_MILESTONES')" />
        </UiPreferenceRow>
        <UiPreferenceRow icon="material-symbols:campaign-outline-rounded" :title="t('MESSAGE_CATEGORY_COMPANY')" :description="t('MESSAGE_PREFERENCES_COMPANY_LOCKED')">
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                <Icon class="size-3.5" icon="material-symbols:lock-outline" aria-hidden="true" />
                <Icon class="size-3.5" icon="material-symbols:check-circle-rounded" aria-hidden="true" />
                {{ t('ACCOUNT_ALWAYS_ON') }}
            </span>
        </UiPreferenceRow>
    </UiPreferenceGroup>
</template>

<script lang="ts" setup>
    import { ToggleSwitch, UiPreferenceGroup, UiPreferenceRow } from '@freelance/vue-ui';
    import { Icon } from '@iconify/vue';
    import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useMessages } from '@/composables/useMessages';
    import { useToastService } from '@/composables/useToastService';

    defineOptions({ name: 'MessagePreferencesSection' });

    const { t } = useI18n();
    const toast = useToastService();
    const { preference, loadPreferences, savePreference } = useMessages();
    const ready = ref(false);
    const echoing = ref(false);
    const people = ref(true);
    const milestones = ref(true);
    const activeField = ref<'people' | 'milestones' | null>(null);
    const phase = ref<'idle' | 'saving' | 'saved'>('idle');
    const savedField = ref<'people' | 'milestones' | null>(null);
    let generation = 0;
    let savedTimer: ReturnType<typeof setTimeout> | null = null;

    function feedbackFor(field: 'people' | 'milestones'): string {
        if (activeField.value !== field || phase.value === 'idle') return '';
        return phase.value === 'saving' ? t('MESSAGE_PREFERENCES_SAVING') : t('MESSAGE_PREFERENCES_SAVED_SHORT');
    }

    async function applyServer(next: { people: boolean; milestones: boolean }): Promise<void> {
        echoing.value = true;
        people.value = next.people;
        milestones.value = next.milestones;
        await nextTick();
        echoing.value = false;
    }

    async function persist(field: 'people' | 'milestones'): Promise<void> {
        const id = ++generation;
        activeField.value = field;
        phase.value = 'saving';
        savedField.value = null;
        if (savedTimer) clearTimeout(savedTimer);
        try {
            const saved = await savePreference(
                {
                    ...preference.value,
                    people: people.value,
                    milestones: milestones.value,
                    company: true,
                    company_locked: true,
                },
                false,
            );
            if (id !== generation) return;
            preference.value = saved;
            await applyServer(saved);
            phase.value = 'saved';
            savedField.value = field;
            savedTimer = setTimeout(() => {
                if (generation === id) {
                    phase.value = 'idle';
                    savedField.value = null;
                }
            }, 1600);
        } catch {
            if (id !== generation) return;
            await applyServer(preference.value);
            phase.value = 'idle';
            savedField.value = null;
            toast.error('MESSAGE_PREFERENCES_ERROR');
        }
    }

    watch(people, (value) => {
        if (!ready.value || echoing.value || value === preference.value.people) return;
        void persist('people');
    });

    watch(milestones, (value) => {
        if (!ready.value || echoing.value || value === preference.value.milestones) return;
        void persist('milestones');
    });

    onMounted(async () => {
        await loadPreferences();
        await applyServer(preference.value);
        ready.value = true;
    });

    onBeforeUnmount(() => {
        if (savedTimer) clearTimeout(savedTimer);
    });
</script>
