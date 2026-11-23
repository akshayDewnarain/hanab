<template>
    <BaseDetail
        v-if="allowed"
        :model="Employee"
        icon="person"
        :title="t('EMPLOYEE_EDIT')"
        @saved="onSaved"
    >
        <template #header>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <router-link
                        class="text-sm font-semibold text-primary"
                        :to="{ name: 'admin-employees-detail', params: { id: route.params.id } }"
                    >
                        {{ t('EMPLOYEE_BACK_TO_PROFILE') }}
                    </router-link>
                    <div class="mt-1 flex items-center gap-2">
                        <h1 class="m-0 text-xl font-semibold text-list-text">{{ t('EMPLOYEE_EDIT') }}</h1>
                        <HelpTrigger context="employees.edit" />
                    </div>
                </div>
            </div>
        </template>
    </BaseDetail>
</template>

<script setup lang="ts">
    import { onBeforeMount, ref } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import Employee from '@/models/employees/Employee.ts';
    import BaseDetail from '@/components/details/BaseDetail.vue';
    import { useAuthStore } from '@/stores/auth';
    import HelpTrigger from '@/components/help/HelpTrigger.vue';

    const { t } = useI18n();
    const route = useRoute();
    const router = useRouter();
    const auth = useAuthStore();
    const allowed = ref(auth.hasPermission('update employees'));

    onBeforeMount(() => {
        if (allowed.value) return;
        void router.replace({ name: 'admin-employees-detail', params: { id: route.params.id } });
    });

    function onSaved(): void {
        void router.push({ name: 'admin-employees-detail', params: { id: route.params.id } });
    }
</script>
