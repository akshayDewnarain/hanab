<template>
    <section class="rounded-xl border border-list-border bg-list-surface px-5 py-4 shadow-sm">
        <h2 class="m-0 flex items-center gap-2 text-sm font-semibold text-list-text">
            <Icon class="size-4 text-list-text-muted" icon="material-symbols:verified-outline-rounded" />
            {{ t('EMPLOYEE_PROFILE_EXPERTISE') }}
        </h2>
        <div v-if="skills.length" class="mt-3 flex flex-wrap gap-2">
            <ColorLabel
                v-for="skill in skills"
                :key="skill.id"
                :label="skillLabel(skill)"
                :background-color="skill.label_background_color"
                :text-color="skill.label_text_color"
                :border-color="skill.label_border_color"
                :description="skillDetail(skill)"
            />
        </div>
        <p v-else class="m-0 mt-3 text-sm text-list-text-muted">
            {{ t('EMPLOYEE_NO_EXPERTISE') }}
            <router-link
                v-if="canEdit"
                class="ml-2 font-medium text-primary"
                :to="{ name: 'admin-employees-edit', params: { id: String(employeeId) } }"
            >
                {{ t('EMPLOYEE_EDIT') }}
            </router-link>
        </p>
    </section>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { ColorLabel } from '@freelance/vue-ui';
    import type { SkillRecord } from '@/modules/types/support/models/SkillRecord.ts';

    defineOptions({ name: 'EmployeeExpertiseSection' });

    defineProps<{
        employeeId: number;
        skills: SkillRecord[];
        canEdit: boolean;
    }>();

    const { t } = useI18n();

    function skillLabel(skill: SkillRecord): string {
        const code = skill.code?.trim() ?? '';
        return code || skill.name;
    }

    function skillDetail(skill: SkillRecord): string {
        const code = skill.code?.trim() ?? '';
        const name = skill.name.trim();
        const lines = [code && code.toLowerCase() !== name.toLowerCase() ? name : '', skill.description?.trim() ?? ''];
        const synonyms = skill.synonyms?.trim() ?? '';
        if (synonyms) lines.push(t('EMPLOYEE_ALSO_DISCOVERABLE', { terms: synonyms }));
        return lines.filter(Boolean).join('\n');
    }
</script>
