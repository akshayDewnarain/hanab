<template>
    <section class="rounded-xl border border-list-border bg-list-surface px-5 py-4 shadow-sm">
        <h2 class="m-0 flex items-center gap-2 text-sm font-semibold text-list-text">
            <Icon class="size-4 text-list-text-muted" icon="material-symbols:work-outline" />
            {{ t('EMPLOYEE_BUSINESS_INFORMATION') }}
        </h2>
        <dl class="m-0 mt-2 divide-y divide-list-border/70">
            <DetailInfoItem :label="t('EMPLOYEE_ROLE')">
                <template #icon>
                    <Icon class="size-4" icon="material-symbols:badge-outline" />
                </template>
                {{ role || '—' }}
            </DetailInfoItem>
            <DetailInfoItem :label="t('EMPLOYEE_LOCATION')">
                <template #icon>
                    <Icon class="size-4" icon="material-symbols:location-on-outline-rounded" />
                </template>
                {{ location || '—' }}
            </DetailInfoItem>
            <DetailInfoItem :label="t('EMPLOYEE_MANAGER')">
                <template #icon>
                    <Icon class="size-4" icon="material-symbols:account-tree-outline-rounded" />
                </template>
                <router-link
                    v-if="manager"
                    class="text-primary transition-colors duration-200 hover:underline motion-reduce:transition-none"
                    :to="{ name: 'admin-employees-detail', params: { id: String(manager.id) } }"
                >
                    {{ manager.name }}
                </router-link>
                <span v-else>{{ t('EMPLOYEE_NOT_ASSIGNED') }}</span>
            </DetailInfoItem>
            <DetailInfoItem v-if="organizationLevel" :label="t('EMPLOYEE_ORGANIZATION_LEVEL')">
                <template #icon>
                    <Icon class="size-4" icon="material-symbols:layers-outline-rounded" />
                </template>
                {{ organizationLevel }}
            </DetailInfoItem>
        </dl>
    </section>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { DetailInfoItem } from '@freelance/vue-ui';

    defineOptions({ name: 'EmployeeBusinessInformation' });

    defineProps<{
        role: string | null;
        location: string | null;
        organizationLevel: string | null;
        manager: { id: number; name: string; role: string | null } | null;
    }>();

    const { t } = useI18n();
</script>
