<template>
    <section class="h-full overflow-y-auto bg-list-canvas px-4 py-4 xl:px-6" :aria-label="t('EMPLOYEE_PROFILE')">
        <div v-if="loading" class="flex min-h-64 items-center justify-center">
            <span
                class="size-10 animate-spin rounded-full border-2 border-list-border border-t-primary motion-reduce:animate-none"
                role="status"
                :aria-label="t('EMPLOYEE_PROFILE_LOADING')"
            />
        </div>
        <p v-else-if="!employee" class="m-0 text-sm font-medium text-list-text">{{ t('EMPLOYEE_PROFILE_UNAVAILABLE') }}</p>
        <div v-else class="mx-auto flex w-full max-w-[84rem] flex-col gap-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <button
                    type="button"
                    class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg px-2 text-sm font-medium text-list-text transition-colors duration-200 hover:bg-list-surface motion-reduce:transition-none"
                    @click="goBack"
                >
                    <Icon class="size-[18px]" icon="material-symbols:arrow-back-rounded" />
                    {{ t('EMPLOYEE_BACK') }}
                </button>
                <div class="flex items-center gap-2">
                    <HelpTrigger context="employees.profile" />
                <router-link
                    v-if="canEdit"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-semibold text-white transition-colors duration-200 hover:brightness-95 motion-reduce:transition-none"
                    :to="{ name: 'admin-employees-edit', params: { id: String(employee.id) } }"
                >
                    <Icon class="size-[18px]" icon="material-symbols:edit-outline-rounded" />
                    {{ t('EMPLOYEE_EDIT') }}
                </router-link>
                </div>
            </div>
            <EmployeeProfileHeader
                :active="employee.is_active"
                :business-email="employee.business_email"
                :business-phone="employee.business_phone"
                :email="employee.email"
                :given-initials="employee.initials"
                :initials="initials"
                :location="employee.employee_location?.name ?? null"
                :name="name"
                :phone="employee.phone"
                :photo="photo"
                :role="employee.employee_role?.name ?? null"
            />
            <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
                <div class="flex flex-col gap-4">
                    <section
                        v-if="employee.introduction"
                        class="rounded-xl border border-list-border bg-list-surface px-5 py-4 shadow-sm"
                    >
                        <h2 class="m-0 flex items-center gap-2 text-sm font-semibold text-list-text">
                            <Icon class="size-4 text-list-text-muted" icon="material-symbols:notes-rounded" />
                            {{ t('EMPLOYEE_INTRODUCTION') }}
                        </h2>
                        <p class="m-0 mt-3 whitespace-pre-wrap text-sm leading-relaxed text-list-text">
                            {{ employee.introduction }}
                        </p>
                    </section>
                    <router-link
                        v-else-if="canEdit"
                        class="text-sm font-medium text-primary"
                        :to="{ name: 'admin-employees-edit', params: { id: String(employee.id) } }"
                    >
                        {{ t('EMPLOYEE_ADD_INTRODUCTION') }}
                    </router-link>
                    <EmployeeBusinessInformation
                        :location="employee.employee_location?.name ?? null"
                        :manager="manager"
                        :organization-level="employee.organization_level?.name ?? null"
                        :role="employee.employee_role?.name ?? null"
                    />
                </div>
                <div class="flex flex-col gap-4">
                    <EmployeeExpertiseSection
                        :can-edit="canEdit"
                        :employee-id="employee.id"
                        :skills="employee.skills ?? []"
                    />
                    <EmployeeCertificatesSection :certificates="employee.certificates ?? []" />
                </div>
            </div>
        </div>
    </section>
</template>

<script lang="ts" setup>
    import { computed, ref, watch } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { Icon } from '@iconify/vue';
    import http from '@/utils/http';
    import { useAuthStore } from '@/stores/auth';
    import type { EmployeeRecord } from '@/modules/types/support/models/EmployeeRecord.ts';
    import EmployeeProfileHeader from '@/components/employees/EmployeeProfileHeader.vue';
    import HelpTrigger from '@/components/help/HelpTrigger.vue';
    import EmployeeBusinessInformation from '@/components/employees/EmployeeBusinessInformation.vue';
    import EmployeeExpertiseSection from '@/components/employees/EmployeeExpertiseSection.vue';
    import EmployeeCertificatesSection from '@/components/employees/EmployeeCertificatesSection.vue';

    defineOptions({ name: 'EmployeeProfileView' });

    const { t } = useI18n();
    const route = useRoute();
    const router = useRouter();
    const auth = useAuthStore();
    const employee = ref<EmployeeRecord | null>(null);
    const loading = ref(true);

    const canEdit = computed(() => auth.hasPermission('update employees'));
    const name = computed(() => (employee.value ? `${employee.value.first_name} ${employee.value.last_name}` : ''));
    const initials = computed(() => {
        if (employee.value?.initials) return employee.value.initials;
        const person = employee.value;
        if (!person) return '';
        return `${person.first_name.charAt(0)}${person.last_name.charAt(0)}`.toUpperCase();
    });
    const photo = computed(() => employee.value?.image?.thumb_url || employee.value?.image?.url || null);
    const manager = computed(() => {
        const person = employee.value?.manager;
        if (!person) return null;
        return {
            id: person.id,
            name: `${person.first_name} ${person.last_name}`,
            role: person.role,
        };
    });

    async function load(id: number): Promise<void> {
        loading.value = true;
        employee.value = null;
        try {
            const response = await http.get<{ data: EmployeeRecord }>(`employees/${id}`);
            employee.value = response.data.data;
        } catch {
            employee.value = null;
        } finally {
            loading.value = false;
        }
    }

    function goBack(): void {
        if (window.history.state?.back) {
            router.back();
            return;
        }
        void router.push({ name: 'admin-employees-overview' });
    }

    watch(
        () => Number(route.params.id),
        (id) => {
            if (Number.isFinite(id)) void load(id);
        },
        { immediate: true },
    );
</script>
