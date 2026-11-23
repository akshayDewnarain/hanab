<template>
    <PageLayout :label="t('USERS')">
        <div class="min-h-0 flex-1 overflow-auto">
            <header class="mb-4">
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-semibold text-list-text">{{ t('USERS') }}</h1>
                    <HelpTrigger context="settings.users" />
                </div>
                <p class="mt-1 text-sm text-list-text-muted">{{ t('USERS_INTRO') }}</p>
            </header>

            <form class="mb-4 grid gap-3 rounded-xl border border-list-border bg-list-surface p-4 md:grid-cols-2" @submit.prevent="invite">
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-list-text">{{ t('LOGIN_EMAIL') }}</span>
                    <input v-model="form.email" class="h-10 rounded-lg border border-list-border bg-list-surface px-3" required type="email" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-list-text">{{ t('INVITATION_FULL_NAME') }}</span>
                    <input v-model="form.name" class="h-10 rounded-lg border border-list-border bg-list-surface px-3" type="text" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-list-text">{{ t('USERS_ROLE') }}</span>
                    <select v-model="form.role_id" class="h-10 rounded-lg border border-list-border bg-list-surface px-3" required>
                        <option disabled value="">{{ t('USERS_CHOOSE_ROLE') }}</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-list-text">{{ t('USERS_EMPLOYEE') }}</span>
                    <select v-model="form.employee_id" class="h-10 rounded-lg border border-list-border bg-list-surface px-3">
                        <option value="">{{ t('USERS_NO_EMPLOYEE') }}</option>
                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                            {{ employee.first_name }} {{ employee.last_name }}
                        </option>
                    </select>
                </label>
                <div class="md:col-span-2 flex items-center justify-between gap-3">
                    <p v-if="notice" class="text-sm text-list-text-muted">{{ notice }}</p>
                    <button class="ml-auto inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-semibold text-white" type="submit">
                        {{ t('USERS_INVITE') }}
                    </button>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-list-border bg-list-surface">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-list-border text-list-text-muted">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('INVITATION_FULL_NAME') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('LOGIN_EMAIL') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('USERS_STATUS') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('USERS_ROLE') }}</th>
                            <th class="px-4 py-3 font-medium" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id" class="border-b border-list-border last:border-0">
                            <td class="px-4 py-3 text-list-text">{{ user.name }}</td>
                            <td class="px-4 py-3 text-list-text">{{ user.email }}</td>
                            <td class="px-4 py-3 text-list-text">{{ user.status }}</td>
                            <td class="px-4 py-3">
                                <select class="h-9 rounded-lg border border-list-border bg-list-surface px-2" :value="user.role_id ?? ''" @change="changeRole(user, $event)">
                                    <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button v-if="!user.has_password" class="text-sm font-medium text-primary" type="button" @click="resend(user.id)">{{ t('USERS_RESEND') }}</button>
                                    <button v-if="user.status === 'invited'" class="text-sm font-medium text-list-text-muted" type="button" @click="revoke(user.id)">{{ t('USERS_REVOKE') }}</button>
                                    <button v-if="user.status === 'active'" class="text-sm font-medium text-list-text-muted" type="button" @click="disable(user)">{{ t('USERS_DISABLE') }}</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { onMounted, reactive, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { PageLayout } from '@freelance/vue-ui';
    import http from '@/utils/http';
    import HelpTrigger from '@/components/help/HelpTrigger.vue';

    defineOptions({ name: 'UsersSettingsPage' });

    interface RoleOption { id: number; name: string }
    interface EmployeeOption { id: number; first_name: string; last_name: string }
    interface Account {
        id: number;
        name: string;
        email: string;
        status: string;
        role_id?: number | null;
        has_password?: boolean;
    }

    const { t } = useI18n();
    const users = ref<Account[]>([]);
    const roles = ref<RoleOption[]>([]);
    const employees = ref<EmployeeOption[]>([]);
    const notice = ref('');
    const form = reactive({ email: '', name: '', role_id: '', employee_id: '' });

    async function load(): Promise<void> {
        const [userResponse, roleResponse, employeeResponse] = await Promise.all([
            http.get('users', { params: { per_page: 100 } }),
            http.get('roles'),
            http.get('employees', { params: { per_page: 100 } }),
        ]);
        users.value = userResponse.data.data ?? [];
        roles.value = roleResponse.data.data ?? [];
        employees.value = employeeResponse.data.data ?? [];
    }

    async function invite(): Promise<void> {
        notice.value = '';
        try {
            await http.post('users', {
                email: form.email,
                name: form.name || null,
                role_id: Number(form.role_id),
                employee_id: form.employee_id ? Number(form.employee_id) : null,
            });
            form.email = '';
            form.name = '';
            form.employee_id = '';
            notice.value = t('USERS_INVITED');
            await load();
        } catch (error: unknown) {
            notice.value = readError(error) || t('USERS_INVITE_FAILED');
        }
    }

    async function resend(id: number): Promise<void> {
        try {
            await http.post(`users/${id}/invitation`);
            notice.value = t('USERS_INVITED');
            await load();
        } catch (error: unknown) {
            notice.value = readError(error) || t('USERS_INVITE_FAILED');
        }
    }

    async function revoke(id: number): Promise<void> {
        try {
            await http.delete(`users/${id}/invitation`);
            notice.value = t('USERS_REVOKED');
            await load();
        } catch (error: unknown) {
            notice.value = readError(error) || t('USERS_INVITE_FAILED');
        }
    }

    async function disable(user: Account): Promise<void> {
        try {
            await http.put(`users/${user.id}`, {
                name: user.name,
                email: user.email,
                status: 'disabled',
            });
            await load();
        } catch (error: unknown) {
            notice.value = readError(error) || t('USERS_INVITE_FAILED');
        }
    }

    async function changeRole(user: Account, event: Event): Promise<void> {
        const roleId = Number((event.target as HTMLSelectElement).value);
        try {
            await http.put(`users/${user.id}`, {
                name: user.name,
                email: user.email,
                role_id: roleId,
            });
            await load();
        } catch (error: unknown) {
            notice.value = readError(error) || t('USERS_INVITE_FAILED');
            await load();
        }
    }

    function readError(error: unknown): string {
        const response = (error as { response?: { data?: { message?: string } } }).response;
        return response?.data?.message ?? '';
    }

    onMounted(() => {
        void load();
    });
</script>
