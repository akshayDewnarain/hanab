<template>
    <PageLayout :label="t('ACCESS_CONTROL')">
        <div class="min-h-0 flex-1 overflow-auto">
            <header class="mb-4">
                <h1 class="text-lg font-semibold text-list-text">{{ t('ACCESS_CONTROL') }}</h1>
                <p class="mt-1 text-sm text-list-text-muted">{{ t('ACCESS_CONTROL_INTRO') }}</p>
            </header>

            <div class="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)]">
                <aside class="rounded-xl border border-list-border bg-list-surface p-2">
                    <button
                        v-for="role in roles"
                        :key="role.id"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm"
                        :class="selected?.id === role.id ? 'bg-list-row-hover font-semibold text-list-text' : 'text-list-text'"
                        type="button"
                        @click="select(role)"
                    >
                        <span>{{ role.name }}</span>
                        <span class="text-xs text-list-text-muted">{{ role.users_count ?? 0 }}</span>
                    </button>
                    <form class="mt-3 flex gap-2 border-t border-list-border p-2" @submit.prevent="createRole">
                        <input v-model="newRole" class="h-9 min-w-0 flex-1 rounded-lg border border-list-border px-2 text-sm" :placeholder="t('ACCESS_ROLE_NAME')" required />
                        <button class="h-9 rounded-lg bg-primary px-3 text-sm font-semibold text-white" type="submit">{{ t('ACCESS_ROLE_ADD') }}</button>
                    </form>
                </aside>

                <section v-if="selected" class="rounded-xl border border-list-border bg-list-surface p-4">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-list-text">{{ selected.name }}</h2>
                        <button
                            v-if="selected.name !== 'super-admin'"
                            class="text-sm font-medium text-list-text-muted"
                            type="button"
                            @click="removeRole"
                        >
                            {{ t('ACCESS_ROLE_DELETE') }}
                        </button>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label v-for="permission in permissions" :key="permission.name" class="flex items-center gap-2 text-sm text-list-text">
                            <input v-model="selectedPermissions" :value="permission.name" type="checkbox" />
                            {{ permission.name }}
                        </label>
                    </div>
                    <p v-if="notice" class="mt-4 text-sm text-list-text-muted">{{ notice }}</p>
                    <button class="mt-4 inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-semibold text-white" type="button" @click="save">
                        {{ t('ACCESS_ROLE_SAVE') }}
                    </button>
                </section>
            </div>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { onMounted, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { PageLayout } from '@freelance/vue-ui';
    import http from '@/utils/http';

    defineOptions({ name: 'AccessControlPage' });

    interface PermissionOption { id: number; name: string }
    interface RoleRecord {
        id: number;
        name: string;
        users_count?: number;
        permissions?: PermissionOption[];
    }

    const { t } = useI18n();
    const roles = ref<RoleRecord[]>([]);
    const permissions = ref<PermissionOption[]>([]);
    const selected = ref<RoleRecord | null>(null);
    const selectedPermissions = ref<string[]>([]);
    const newRole = ref('');
    const notice = ref('');

    async function load(): Promise<void> {
        const [roleResponse, permissionResponse] = await Promise.all([
            http.get('roles'),
            http.get('permissions'),
        ]);
        roles.value = roleResponse.data.data ?? [];
        permissions.value = permissionResponse.data.data ?? [];
        if (selected.value) {
            select(roles.value.find((role) => role.id === selected.value?.id) ?? roles.value[0] ?? null);
        }
    }

    function select(role: RoleRecord | null): void {
        selected.value = role;
        selectedPermissions.value = role?.permissions?.map((permission) => permission.name) ?? [];
        notice.value = '';
    }

    async function createRole(): Promise<void> {
        await http.post('roles', { name: newRole.value, permissions: [] });
        newRole.value = '';
        await load();
    }

    async function save(): Promise<void> {
        if (!selected.value) return;
        await http.put(`roles/${selected.value.id}`, {
            name: selected.value.name,
            permissions: selectedPermissions.value,
        });
        notice.value = t('ACCESS_ROLE_SAVED');
        await load();
    }

    async function removeRole(): Promise<void> {
        if (!selected.value) return;
        await http.delete(`roles/${selected.value.id}`);
        selected.value = null;
        await load();
    }

    onMounted(async () => {
        await load();
        select(roles.value[0] ?? null);
    });
</script>
