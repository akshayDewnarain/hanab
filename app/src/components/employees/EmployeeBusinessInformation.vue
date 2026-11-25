<template>
    <section class="rounded-xl border border-list-border bg-list-surface px-5 py-4 shadow-sm">
        <h2 class="m-0 flex items-center gap-2 text-base font-semibold text-list-text">
            <Icon class="size-5 text-list-text-muted" icon="material-symbols:work-outline" />
            {{ t('EMPLOYEE_BUSINESS_INFORMATION') }}
        </h2>
        <dl class="m-0 mt-4 divide-y divide-list-border">
            <div v-if="role" class="py-3 first:pt-0 last:pb-0">
                <dt class="text-sm text-list-text-muted">{{ t('EMPLOYEE_PROFILE_ROLE') }}</dt>
                <dd class="m-0 mt-1 text-base font-semibold text-list-text">{{ role }}</dd>
            </div>
            <div v-if="location" class="py-3 first:pt-0 last:pb-0">
                <dt class="text-sm text-list-text-muted">{{ t('EMPLOYEE_PROFILE_LOCATION') }}</dt>
                <dd class="m-0 mt-1 text-base font-semibold text-list-text">{{ location }}</dd>
            </div>
            <div v-if="manager" class="py-3 first:pt-0 last:pb-0">
                <dt class="text-sm text-list-text-muted">{{ t('EMPLOYEE_MANAGER') }}</dt>
                <dd class="m-0 mt-1 text-base font-semibold">
                    <router-link
                        class="text-list-text transition-colors duration-200 hover:text-emphasis motion-reduce:transition-none"
                        :to="{ name: 'admin-employees-detail', params: { id: String(manager.id) } }"
                    >
                        {{ manager.name }}
                    </router-link>
                    <span v-if="manager.role" class="mt-0.5 block text-sm font-medium text-list-text-muted">{{ manager.role }}</span>
                </dd>
            </div>
            <div v-if="organizationLevel" class="py-3 first:pt-0 last:pb-0">
                <dt class="text-sm text-list-text-muted">{{ t('EMPLOYEE_ORGANIZATION_LEVEL') }}</dt>
                <dd class="m-0 mt-1 text-base font-semibold text-list-text">{{ organizationLevel }}</dd>
            </div>
        </dl>
    </section>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';

    defineOptions({ name: 'EmployeeBusinessInformation' });

    defineProps<{
        role: string | null;
        location: string | null;
        organizationLevel: string | null;
        manager: { id: number; name: string; role: string | null } | null;
    }>();

    const { t } = useI18n();
</script>
