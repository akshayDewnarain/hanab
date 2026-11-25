<template>
    <UiWidget v-if="employeeId" :title="t('DASHBOARD_PROFILE_TITLE')">
        <div v-if="loading" class="flex flex-col gap-2">
            <Skeleton class="h-16 rounded-lg" />
            <Skeleton class="h-8 rounded-lg" />
        </div>
        <div v-else-if="error" class="flex items-center justify-between gap-3">
            <p class="text-sm text-list-text">{{ t('DASHBOARD_PROFILE_ERROR') }}</p>
            <button type="button" class="cursor-pointer text-sm font-semibold text-emphasis" @click="load">{{ t('DASHBOARD_RETRY') }}</button>
        </div>
        <div v-else-if="employee" class="flex flex-col gap-3">
            <div class="flex items-center gap-3">
                <span class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-light text-sm font-bold text-emphasis">
                    <img v-if="photo" :alt="name" class="size-full object-cover" :src="photo" />
                    <span v-else>{{ initials }}</span>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-list-text">{{ name }}</p>
                    <p v-if="role" class="truncate text-sm text-list-text-muted">{{ role }}</p>
                    <p v-if="location" class="truncate text-xs text-list-text-muted">{{ location }}</p>
                </div>
            </div>
            <p class="text-sm text-list-text">{{ t('DASHBOARD_PROFILE_COMPLETE', { percent }) }}</p>
            <div class="h-2 overflow-hidden rounded-full bg-list-canvas" role="progressbar" :aria-valuenow="percent" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full bg-primary transition-[width] duration-300 ease-out motion-reduce:transition-none" :style="{ width: `${percent}%` }" />
            </div>
            <ul class="flex list-none flex-wrap gap-1.5 p-0">
                <li
                    v-for="check in checks"
                    :key="check.key"
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="check.filled ? 'bg-primary-light text-emphasis' : 'bg-list-canvas text-list-text-muted'"
                >
                    {{ t(check.label) }}
                </li>
            </ul>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand" @click="openProfile">
                    {{ t('DASHBOARD_PROFILE_OPEN') }}
                </button>
                <button
                    v-if="canEdit"
                    type="button"
                    class="inline-flex h-9 cursor-pointer items-center rounded-lg border border-list-border px-3 text-sm font-medium text-list-text"
                    @click="openEditor"
                >
                    {{ t('DASHBOARD_PROFILE_EDIT') }}
                </button>
            </div>
        </div>
    </UiWidget>
</template>

<script lang="ts" setup>
    import { computed, onMounted, ref } from 'vue';
    import { UiWidget } from '@freelance/vue-ui';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import Skeleton from '@/components/skeleton/Skeleton.vue';
    import { profileChecks, profilePercent, type ProfileSnapshot } from '@/modules/dashboard/profileCompleteness';
    import { useAuthStore } from '@/stores/auth';
    import http from '@/utils/http';

    defineOptions({ name: 'DashboardProfile' });

    const { t } = useI18n();
    const router = useRouter();
    const auth = useAuthStore();
    const employeeId = computed(() => auth.user?.employee_id ?? null);
    const canEdit = computed(() => auth.hasPermission('update employees'));
    const loading = ref(false);
    const error = ref(false);
    const employee = ref<ProfileSnapshot | null>(null);
    let requestId = 0;

    const checks = computed(() => (employee.value ? profileChecks(employee.value) : []));
    const percent = computed(() => (employee.value ? profilePercent(employee.value) : 0));
    const name = computed(() => [employee.value?.first_name, employee.value?.last_name].filter(Boolean).join(' '));
    const initials = computed(() => {
        const stored = employee.value?.initials?.trim();
        if (stored) return stored;
        return name.value
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part.charAt(0).toUpperCase())
            .join('');
    });
    const photo = computed(() => employee.value?.image?.thumb_url || employee.value?.image?.url || '');
    const role = computed(() => employee.value?.employee_role?.name ?? '');
    const location = computed(() => employee.value?.employee_location?.name ?? '');

    function openProfile(): void {
        if (!employeeId.value) return;
        void router.push({ name: 'admin-employees-detail', params: { id: String(employeeId.value) } });
    }

    function openEditor(): void {
        if (!employeeId.value) return;
        void router.push({ name: 'admin-employees-edit', params: { id: String(employeeId.value) } });
    }

    async function load(): Promise<void> {
        if (!employeeId.value) return;
        const current = ++requestId;
        loading.value = employee.value === null;
        error.value = false;
        try {
            const response = await http.get<{ data: ProfileSnapshot }>(`employees/${employeeId.value}`);
            if (current !== requestId) return;
            employee.value = response.data.data;
        } catch {
            if (current !== requestId) return;
            error.value = true;
        } finally {
            if (current === requestId) loading.value = false;
        }
    }

    onMounted(() => {
        void load();
    });
</script>
