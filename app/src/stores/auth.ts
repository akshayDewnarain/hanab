import { defineStore } from 'pinia';
import type { UserRecord } from '@/modules/types/support/models/UserRecord.ts';
import http from '../utils/http';
import type { AxiosResponse } from 'axios';

/** API `/user` payloads are plain objects; they are not `User` class instances (no Model `entity` fields). */
function normalizePermissionNames(user: Pick<UserRecord, 'permissions'> | null): string[] {
    const permissions = user?.permissions;
    if (!permissions || permissions.length === 0) return [];

    if (typeof permissions[0] === 'string') {
        return permissions as string[];
    }

    return (permissions as Array<{ id?: number; name: string }>).map((permission) => permission.name);
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as UserRecord | null,
    }),
    actions: {
        async fetchUser() {
            try {
                const response: AxiosResponse<{ data: UserRecord }> = await http.get('/api/user', {
                    baseURL: `${import.meta.env.VITE_API_URL}`,
                });
                this.user = response.data.data;
            } catch {
                this.user = null;
            }
        },
        logout() {
            this.user = null;
            http.post('/logout');
        },
    },
    getters: {
        getUser: (state): UserRecord | null => state.user as UserRecord | null,
        isAuthenticated: (state): boolean => !!state.user,
        isAdmin: (state): boolean => Boolean(state.user?.roles?.some((role) => role.name === 'admin')),
        getRole: (state): string | null => state.user?.roles?.[0]?.name || null,
        getPermissionNames: (state): string[] => normalizePermissionNames(state.user),
        hasPermission:
            (state) =>
            (permissionName: string): boolean => {
                return normalizePermissionNames(state.user).includes(permissionName);
            },
        canViewEntity:
            (state) =>
            (entityKey: string): boolean => {
                return normalizePermissionNames(state.user).includes(`${entityKey}_VIEW`);
            },
        hasRole:
            (state) =>
            (roleName: string): boolean =>
                Boolean(state.user?.roles?.some((role) => role.name === roleName)),
    },
});
