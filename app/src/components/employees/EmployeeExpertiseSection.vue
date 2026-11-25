<template>
    <section class="rounded-xl border border-list-border bg-list-surface px-5 py-4 shadow-sm">
        <h2 class="m-0 flex items-center gap-2 text-base font-semibold text-list-text">
            <Icon class="size-5 text-list-text-muted" icon="material-symbols:verified-outline-rounded" />
            {{ t('EMPLOYEE_PROFILE_EXPERTISE') }}
        </h2>
        <p v-if="skills.length" class="m-0 mt-1 max-w-prose text-sm leading-relaxed text-list-text-muted">
            {{ t('EMPLOYEE_EXPERTISE_HINT') }}
        </p>
        <div v-if="skills.length" class="mt-3 flex flex-wrap gap-2">
            <ColorLabel
                v-for="skill in visibleSkills"
                :key="skill.id"
                pressable
                size="md"
                :label="skillLabel(skill)"
                :background-color="skill.label_background_color"
                :text-color="skill.label_text_color"
                :border-color="skill.label_border_color"
                :description="skillDetail(skill)"
                @select="openColleagues(skill.id)"
            />
        </div>
        <button
            v-if="skills.length > limit"
            type="button"
            class="mt-3 cursor-pointer text-sm font-medium text-emphasis"
            @click="expanded = !expanded"
        >
            {{ expanded ? t('EMPLOYEE_PROFILE_LESS') : t('EMPLOYEE_PROFILE_MORE', { count: skills.length - limit }) }}
        </button>
        <p v-if="!skills.length" class="m-0 mt-3 max-w-prose text-sm text-list-text-muted">
            {{ t('EMPLOYEE_NO_EXPERTISE') }}
            <router-link
                v-if="canEdit"
                class="ml-2 font-medium text-emphasis"
                :to="{ name: 'admin-employees-edit', params: { id: String(employeeId) } }"
            >
                {{ t('EMPLOYEE_ADD_EXPERTISE') }}
            </router-link>
        </p>
    </section>
</template>

<script lang="ts" setup>
    import { computed, ref } from 'vue';
    import { useRouter } from 'vue-router';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { ColorLabel } from '@freelance/vue-ui';
    import type { SkillRecord } from '@/modules/types/support/models/SkillRecord.ts';

    defineOptions({ name: 'EmployeeExpertiseSection' });

    const props = defineProps<{
        employeeId: number;
        skills: SkillRecord[];
        canEdit: boolean;
    }>();

    const { t } = useI18n();
    const router = useRouter();
    const limit = 8;
    const expanded = ref(false);
    const visibleSkills = computed(() => (expanded.value ? props.skills : props.skills.slice(0, limit)));

    function skillLabel(skill: SkillRecord): string {
        return skill.name.trim() || skill.code?.trim() || '';
    }

    function skillDetail(skill: SkillRecord): string {
        const code = skill.code?.trim() ?? '';
        const name = skill.name.trim();
        const lines = [code && code.toLowerCase() !== name.toLowerCase() ? code : '', skill.description?.trim() ?? ''];
        const synonyms = skill.synonyms?.trim() ?? '';
        if (synonyms) lines.push(t('EMPLOYEE_ALSO_DISCOVERABLE', { terms: synonyms }));
        return lines.filter(Boolean).join('\n');
    }

    function openColleagues(skillId: number): void {
        void router.push({
            name: 'admin-employees-overview',
            query: { page: '1', 'filter[skill_ids]': String(skillId) },
        });
    }
</script>
