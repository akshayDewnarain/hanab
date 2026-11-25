<template>
    <PageLayout :label="t('ACCESS_CONTROL')">
        <div v-if="loading" class="flex flex-1 items-center justify-center" role="status">
            <span class="size-10 animate-spin rounded-full border-2 border-list-border border-t-primary motion-reduce:animate-none" />
            <span class="sr-only">{{ t('ACCESS_LOADING') }}</span>
        </div>

        <div v-else class="relative flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 overflow-y-auto">
                <div class="px-1 pt-1">
                    <h1 class="text-lg font-semibold text-list-text">{{ t('ACCESS_CONTROL') }}</h1>
                    <p class="mt-1 max-w-2xl text-sm text-list-text-muted">{{ t('ACCESS_CONTROL_INTRO') }}</p>
                </div>

                <UiStickySurface v-slot="{ scrolled }" class="px-1">
                    <div aria-hidden="true" class="pointer-events-none h-3"></div>
                    <div class="flex flex-col gap-2">
                        <section
                            class="rounded-xl border border-list-border bg-list-surface px-3 py-2.5 shadow-sm transition-[background-color,backdrop-filter] duration-200 ease-out motion-reduce:transition-none"
                            :style="scrolled ? frostStyle : undefined"
                        >
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-light text-emphasis">
                                        <Icon class="size-5" :icon="protectedRole ? 'material-symbols:shield-outline-rounded' : 'material-symbols:badge-outline-rounded'" />
                                    </span>
                                    <div class="w-full min-w-[14rem] max-w-xs">
                                        <ChoiceDropdown
                                            :model-value="selected?.id ?? null"
                                            :aria-label="t('ACCESS_SELECT_ROLE')"
                                            :empty-label="t('ACCESS_EMPTY_ROLES')"
                                            :options="roleOptions"
                                            :placeholder="t('ACCESS_SELECT_ROLE')"
                                            :search-placeholder="t('ACCESS_SEARCH_ROLES')"
                                            searchable
                                            @update:model-value="selectById"
                                        />
                                    </div>
                                    <div v-if="selected" class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-light px-2 py-0.5 text-xs font-medium text-emphasis">
                                            <Icon class="size-3.5" :icon="protectedRole ? 'material-symbols:lock-outline' : 'material-symbols:edit-outline-rounded'" />
                                            {{ roleKind(selected) }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-list-canvas px-2 py-0.5 text-xs font-medium text-list-text">
                                            <Icon class="size-3.5 text-list-text-muted" icon="material-symbols:group-outline-rounded" />
                                            {{ userCount(selected.users_count ?? 0) }}
                                        </span>
                                        <span v-if="protectedRole" class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-200">
                                            <Icon class="size-3.5" icon="material-symbols:verified-outline-rounded" />
                                            {{ t('ACCESS_FULL_ACCESS') }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-xs text-list-text-muted">
                                            <Icon class="size-3.5" icon="material-symbols:key-outline-rounded" />
                                            {{ summary }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-wrap items-center gap-2">
                                    <button v-if="canCreate" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand" type="button" @click="createRole">
                                        <Icon class="size-4" icon="material-symbols:add-rounded" />
                                        {{ t('ACCESS_NEW_ROLE') }}
                                    </button>
                                    <button v-if="canUpdate && !protectedRole" class="access-action" type="button" @click="renameRole">
                                        <Icon icon="material-symbols:edit-outline-rounded" />
                                        {{ t('ACCESS_EDIT') }}
                                    </button>
                                    <button v-if="canCreate && !protectedRole" class="access-action" type="button" @click="duplicateRole">
                                        <Icon icon="material-symbols:content-copy-outline-rounded" />
                                        {{ t('ACCESS_DUPLICATE') }}
                                    </button>
                                    <button v-if="canDelete && !protectedRole" class="access-action" type="button" @click="removeRole">
                                        <Icon icon="material-symbols:delete-outline-rounded" />
                                        {{ t('ACCESS_ROLE_DELETE') }}
                                    </button>
                                </div>
                            </div>
                        </section>
                        <section
                            class="rounded-xl border border-list-border bg-list-surface px-3 py-2.5 shadow-sm transition-[background-color,backdrop-filter] duration-200 ease-out motion-reduce:transition-none"
                            :style="scrolled ? frostStyle : undefined"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="relative min-w-[12rem] flex-1">
                                    <Icon class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-list-text-muted" icon="material-symbols:search-rounded" />
                                    <input
                                        v-model="permissionQuery"
                                        class="h-10 w-full rounded-lg border border-list-border bg-list-surface pr-9 pl-9 text-sm text-list-text"
                                        type="search"
                                        :placeholder="t('ACCESS_SEARCH_PERMISSIONS')"
                                        :aria-label="t('ACCESS_SEARCH_PERMISSIONS')"
                                    />
                                    <button
                                        class="absolute top-1/2 right-2 inline-flex size-6 -translate-y-1/2 items-center justify-center rounded text-list-text-muted hover:text-list-text"
                                        :class="permissionQuery ? '' : 'invisible'"
                                        type="button"
                                        :disabled="!permissionQuery"
                                        :tabindex="permissionQuery ? 0 : -1"
                                        :aria-label="t('ACCESS_CLEAR_SEARCH')"
                                        @click="permissionQuery = ''"
                                    >
                                        <Icon class="size-4" icon="material-symbols:close-rounded" />
                                    </button>
                                </div>
                                <SegmentedControl v-model="permissionFilter" :label="t('ACCESS_FILTER_LABEL')" :options="filterOptions" />
                                <p class="m-0 inline-flex w-28 items-center justify-end gap-1 text-xs font-medium tabular-nums text-list-text-muted">
                                    <Icon class="size-3.5" icon="material-symbols:filter-list-rounded" />
                                    {{ matchLabel }}
                                </p>
                                <button class="inline-flex w-36 items-center gap-1 text-left text-sm font-medium text-emphasis" :class="filtersActive ? '' : 'invisible'" type="button" :disabled="!filtersActive" :tabindex="filtersActive ? 0 : -1" @click="resetFilters">
                                    <Icon class="size-4" icon="material-symbols:filter-alt-off-outline-rounded" />
                                    {{ t('ACCESS_CLEAR_FILTERS') }}
                                </button>
                                <button class="inline-flex w-40 items-center gap-1 text-left text-sm font-medium text-emphasis" type="button" @click="setAllGroups(!everyGroupOpen)">
                                    <Icon class="size-4" :icon="everyGroupOpen ? 'material-symbols:unfold-less-rounded' : 'material-symbols:unfold-more-rounded'" />
                                    {{ everyGroupOpen ? t('ACCESS_COLLAPSE_ALL') : t('ACCESS_EXPAND_ALL') }}
                                </button>
                            </div>
                        </section>
                    </div>
                </UiStickySurface>

                <div class="flex flex-col gap-3 px-1 pt-3" :class="dirty ? 'pb-28' : 'pb-6'">
                    <UiCallout v-if="protectedRole" class="!my-0" kind="note" :label="t('ACCESS_PROTECTED_TITLE')">
                        <p>{{ t('ACCESS_PROTECTED_BODY') }}</p>
                    </UiCallout>
                    <p v-else-if="selected && !canUpdate" class="m-0 text-sm text-list-text-muted">{{ t('ACCESS_READONLY') }}</p>
                    <p v-if="selected && !protectedRole && riskEnabled > 0" class="m-0 text-xs text-amber-700 dark:text-amber-300">
                        {{ t('ACCESS_RISK_COUNT', { count: riskEnabled }) }}
                    </p>
                            <p v-if="visibleGroups.length === 0" class="m-0 rounded-xl border border-dashed border-list-border bg-list-surface px-4 py-8 text-center text-sm text-list-text-muted">
                                {{ t('ACCESS_EMPTY_PERMISSIONS') }}
                            </p>
                            <div v-else class="grid justify-start gap-3" style="grid-template-columns: repeat(auto-fill, minmax(min(100%, 22rem), 1fr))">
                                <section
                                    v-for="group in visibleGroups"
                                    :key="group.id"
                                    class="h-fit rounded-xl border border-list-border bg-list-surface shadow-[0_1px_2px_color-mix(in_srgb,var(--color-list-shadow-tint)_7%,transparent)] transition-[border-color,box-shadow] duration-200 hover:border-[color-mix(in_srgb,var(--color-emphasis)_28%,var(--color-list-border))] hover:shadow-[0_6px_16px_color-mix(in_srgb,var(--color-list-shadow-tint)_10%,transparent)] motion-reduce:transition-none"
                                >
                                    <button
                                        type="button"
                                        class="flex w-full items-start gap-3 px-4 py-3 text-left"
                                        :aria-expanded="isOpen(group.id)"
                                        :aria-controls="`access-group-${group.id}`"
                                        @click="toggleGroup(group.id)"
                                    >
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-light text-emphasis">
                                            <Icon class="size-5" :icon="group.icon" />
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-start justify-between gap-2">
                                                <span class="text-sm font-semibold text-list-text">{{ t(group.titleKey) }}</span>
                                                <span class="shrink-0 text-xs tabular-nums text-list-text-muted">{{ t('ACCESS_GROUP_COUNT', { enabled: group.enabled, total: group.total }) }}</span>
                                            </span>
                                            <span class="mt-0.5 block text-xs leading-5 text-list-text-muted">{{ t(group.bodyKey) }}</span>
                                        </span>
                                        <Icon
                                            class="mt-1 size-5 shrink-0 text-list-text-muted transition-transform duration-200 motion-reduce:transition-none"
                                            :class="isOpen(group.id) ? 'rotate-180' : ''"
                                            icon="material-symbols:expand-more-rounded"
                                        />
                                    </button>
                                    <Collapsible :id="`access-group-${group.id}`" :open="isOpen(group.id)">
                                        <div class="border-t border-list-border">
                                            <div
                                                v-for="item in group.items"
                                                :key="item.name"
                                                class="group flex items-start gap-3 border-b border-list-border px-4 py-3 last:border-b-0"
                                            >
                                                <Icon class="mt-0.5 size-5 shrink-0" :class="item.tone" :icon="item.icon" />
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="m-0 text-sm font-semibold text-list-text">
                                                            <template v-for="(part, index) in highlight(item.label)" :key="`${item.name}-label-${index}`">
                                                                <mark v-if="part.match" class="rounded-sm bg-primary-light text-inherit">{{ part.text }}</mark>
                                                                <template v-else>{{ part.text }}</template>
                                                            </template>
                                                        </p>
                                                        <span v-if="item.risk" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                                                            <Icon class="size-3.5" icon="material-symbols:warning-outline-rounded" />
                                                            {{ t('ACCESS_RISK') }}
                                                        </span>
                                                    </div>
                                                    <p class="m-0 mt-0.5 text-xs leading-5 text-wrap text-list-text-muted">{{ item.body }}</p>
                                                    <p class="m-0 mt-0.5 font-mono text-[0.7rem] text-list-text-muted opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100">
                                                        {{ item.name }}
                                                    </p>
                                                </div>
                                                <div class="flex w-36 shrink-0 justify-end self-center">
                                                    <span v-if="protectedRole" class="inline-flex items-center gap-1 text-xs font-medium text-list-text-muted">
                                                        <Icon class="size-3.5" icon="material-symbols:lock-outline" />
                                                        {{ t('ACCESS_ALWAYS_ALLOWED') }}
                                                    </span>
                                                    <ToggleSwitch
                                                        v-else
                                                        :model-value="item.enabled"
                                                        :disabled="!canEditPermissions || saving"
                                                        :label="item.label"
                                                        @update:model-value="setPermission(item.name, $event)"
                                                    />
                                                </div>
                                            </div>
                                            <div v-if="canEditPermissions && !saving" class="flex gap-3 px-4 py-2.5">
                                                <button class="text-xs font-semibold text-emphasis" type="button" @click="setGroup(group, true)">{{ t('ACCESS_GROUP_ALL_ON') }}</button>
                                                <button class="text-xs font-semibold text-list-text-muted" type="button" @click="setGroup(group, false)">{{ t('ACCESS_GROUP_ALL_OFF') }}</button>
                                            </div>
                                        </div>
                                    </Collapsible>
                                </section>
                            </div>
                </div>
            </div>
        </div>
        <DetailUnsavedBar
            :open="dirty"
            :busy="saving"
            :title="t('ACCESS_UNSAVED')"
            :hint="t('ACCESS_UNSAVED_HINT')"
            :revert-label="t('ACCESS_CANCEL')"
            :apply-label="saving ? t('ACCESS_SAVING') : t('ACCESS_SAVE')"
            @revert="revert"
            @apply="save"
        />
    </PageLayout>
</template>

<script lang="ts" setup>
    import { Collapsible, DetailUnsavedBar, PageLayout, SegmentedControl, ToggleSwitch, UiCallout, UiStickySurface } from '@freelance/vue-ui';
    import { Icon } from '@iconify/vue';
    import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
    import { onBeforeRouteLeave } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import AccessRoleNameModal from '@/components/access/AccessRoleNameModal.vue';
    import ChoiceDropdown from '@/components/inputs/ChoiceDropdown.vue';
    import ConfirmationModal from '@/components/modals/content-modals/ConfirmationModal.vue';
    import { useModalService } from '@/composables/useModalService';
    import { useToastService } from '@/composables/useToastService';
    import { useAuthStore } from '@/stores/auth';
    import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget';
    import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType';
    import { ModalType } from '@/modules/enums/support/modals/ModalType';
    import { ModalVariant } from '@/modules/enums/support/modals/ModalVariant';
    import { describePermission, isReservedContentPermission, PERMISSION_GROUPS, permissionSort, type DescribedPermission } from '@/modules/access/permissionCatalog';
    import http from '@/utils/http';

    defineOptions({ name: 'AccessControlPage' });

    interface PermissionOption {
        id: number;
        name: string;
    }
    interface RoleRecord {
        id: number;
        name: string;
        users_count?: number;
        permissions?: PermissionOption[];
    }
    interface VisiblePermission extends DescribedPermission {
        label: string;
        body: string;
        enabled: boolean;
    }
    interface VisibleGroup {
        id: string;
        icon: string;
        titleKey: string;
        bodyKey: string;
        enabled: number;
        total: number;
        items: VisiblePermission[];
    }

    const PROTECTED_ROLE = 'super-admin';
    const { t } = useI18n();
    const frostStyle = {
        backgroundColor: 'color-mix(in srgb, var(--color-list-surface) 94%, transparent)',
        backdropFilter: 'blur(12px)',
    };
    const auth = useAuthStore();
    const modalService = useModalService();
    const toast = useToastService();
    const loading = ref(true);
    const saving = ref(false);
    const roles = ref<RoleRecord[]>([]);
    const permissions = ref<PermissionOption[]>([]);
    const selected = ref<RoleRecord | null>(null);
    const granted = ref<string[]>([]);
    const baseline = ref<string[]>([]);
    const permissionQuery = ref('');
    const permissionFilter = ref('all');
    const openGroups = ref<Set<string>>(new Set());

    const canAssignContentPermissions = computed(() => auth.hasRole(PROTECTED_ROLE));
    const canCreate = computed(() => auth.hasPermission('create roles'));
    const canUpdate = computed(() => auth.hasPermission('update roles'));
    const canDelete = computed(() => auth.hasPermission('delete roles'));
    const protectedRole = computed(() => selected.value?.name === PROTECTED_ROLE);
    const canEditPermissions = computed(() => canUpdate.value && !protectedRole.value);
    const dirty = computed(() => !protectedRole.value && !sameSet(granted.value, baseline.value));
    const filtersActive = computed(() => permissionQuery.value.trim() !== '' || permissionFilter.value !== 'all');
    const filterOptions = computed(() => [
        { value: 'all', label: t('ACCESS_FILTER_ALL') },
        { value: 'on', label: t('ACCESS_FILTER_ON') },
        { value: 'off', label: t('ACCESS_FILTER_OFF') },
        { value: 'risk', label: t('ACCESS_FILTER_RISK') },
    ]);
    const described = computed(() =>
        permissions.value
            .filter((permission) => canAssignContentPermissions.value || !isReservedContentPermission(permission.name))
            .map((permission) => describePermission(permission.name)),
    );
    const enabledCount = computed(() => (protectedRole.value ? described.value.length : granted.value.length));
    const riskEnabled = computed(() => described.value.filter((item) => item.risk && (protectedRole.value || granted.value.includes(item.name))).length);
    const activeGroupCount = computed(() => {
        const ids = new Set<string>();
        for (const item of described.value) {
            if (protectedRole.value || granted.value.includes(item.name)) ids.add(item.groupId);
        }
        return ids.size;
    });
    const populatedGroupCount = computed(() => new Set(described.value.map((item) => item.groupId)).size);
    function groupLabel(count: number): string {
        return count === 1 ? t('ACCESS_GROUPS_ONE') : t('ACCESS_GROUPS_COUNT', { count });
    }

    const summary = computed(() =>
        protectedRole.value
            ? t('ACCESS_SUMMARY_FULL', { total: described.value.length, groups: groupLabel(populatedGroupCount.value) })
            : t('ACCESS_SUMMARY', { enabled: enabledCount.value, total: described.value.length, groups: groupLabel(activeGroupCount.value) }),
    );
    const matchCount = computed(() => visibleGroups.value.reduce((sum, group) => sum + group.items.length, 0));
    const matchLabel = computed(() => (matchCount.value === 1 ? t('ACCESS_MATCH_ONE') : t('ACCESS_MATCH_COUNT', { count: matchCount.value })));
    const everyGroupOpen = computed(() => visibleGroups.value.length > 0 && visibleGroups.value.every((group) => openGroups.value.has(group.id)));
    const roleOptions = computed(() =>
        roles.value.map((role) => ({
            value: role.id,
            label: role.name,
            help: `${roleKind(role)} · ${userCount(role.users_count ?? 0)}`,
        })),
    );
    const visibleGroups = computed<VisibleGroup[]>(() => {
        const query = permissionQuery.value.trim().toLowerCase();
        return PERMISSION_GROUPS.map((group) => {
            const members = described.value.filter((item) => item.groupId === group.id).sort(permissionSort);
            const enabled = members.filter((item) => protectedRole.value || granted.value.includes(item.name)).length;
            const items = members
                .map((item) => decorate(item))
                .filter((item) => {
                    if (query) {
                        const groupTitle = t(group.titleKey).toLowerCase();
                        const haystack = `${item.label} ${item.body} ${item.name} ${groupTitle}`.toLowerCase();
                        if (!haystack.includes(query)) return false;
                    }
                    if (permissionFilter.value === 'on') return item.enabled;
                    if (permissionFilter.value === 'off') return !item.enabled;
                    if (permissionFilter.value === 'risk') return item.risk;
                    return true;
                });
            return { id: group.id, icon: group.icon, titleKey: group.titleKey, bodyKey: group.bodyKey, enabled, total: members.length, items };
        }).filter((group) => group.total > 0 && group.items.length > 0);
    });

    function decorate(item: DescribedPermission): VisiblePermission {
        const action = t(`ACCESS_ACTION_${item.action}`);
        const subject = t(item.subjectKey);
        const label = item.primary
            ? action
            : capitalize(t('ACCESS_LABEL_SPECIFIC', { subject, action: t(`ACCESS_ACTION_LOWER_${item.action}`) }));
        return {
            ...item,
            label,
            body: capitalize(t(`ACCESS_BODY_${item.action}`, { subject, name: item.name })),
            enabled: protectedRole.value || granted.value.includes(item.name),
        };
    }

    function capitalize(value: string): string {
        return value ? value.charAt(0).toLocaleUpperCase() + value.slice(1) : value;
    }

    function sameSet(left: string[], right: string[]): boolean {
        if (left.length !== right.length) return false;
        const values = new Set(left);
        return right.every((item) => values.has(item));
    }

    function userCount(count: number): string {
        return count === 1 ? t('ACCESS_USERS_ONE') : t('ACCESS_USERS_COUNT', { count });
    }

    function roleKind(role: RoleRecord): string {
        return role.name === PROTECTED_ROLE ? t('ACCESS_ROLE_SYSTEM') : t('ACCESS_ROLE_CUSTOM');
    }

    function highlight(value: string): Array<{ text: string; match: boolean }> {
        const query = permissionQuery.value.trim();
        if (!query) return [{ text: value, match: false }];
        const haystack = value.toLowerCase();
        const needle = query.toLowerCase();
        const parts: Array<{ text: string; match: boolean }> = [];
        let cursor = 0;
        let index = haystack.indexOf(needle);
        while (index !== -1) {
            if (index > cursor) parts.push({ text: value.slice(cursor, index), match: false });
            parts.push({ text: value.slice(index, index + needle.length), match: true });
            cursor = index + needle.length;
            index = haystack.indexOf(needle, cursor);
        }
        if (cursor < value.length) parts.push({ text: value.slice(cursor), match: false });
        return parts.length > 0 ? parts : [{ text: value, match: false }];
    }

    function isOpen(id: string): boolean {
        return filtersActive.value || openGroups.value.has(id);
    }

    function toggleGroup(id: string): void {
        if (filtersActive.value) return;
        const next = new Set(openGroups.value);
        if (next.has(id)) next.delete(id);
        else next.add(id);
        openGroups.value = next;
    }

    function setAllGroups(open: boolean): void {
        openGroups.value = open ? new Set(visibleGroups.value.map((group) => group.id)) : new Set();
    }

    function applySelection(role: RoleRecord | null): void {
        selected.value = role;
        const names = (role?.permissions?.map((permission) => permission.name) ?? []).filter(
            (name) => canAssignContentPermissions.value || !isReservedContentPermission(name),
        );
        granted.value = [...names];
        baseline.value = [...names];
        openGroups.value = new Set(described.value.filter((item) => names.includes(item.name) || role?.name === PROTECTED_ROLE).map((item) => item.groupId));
    }

    async function confirmDiscard(): Promise<boolean> {
        if (!dirty.value) return true;
        const confirmed = await modalService.open({
            title: 'ACCESS_LEAVE_TITLE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: ConfirmationModal,
            props: { message: 'ACCESS_LEAVE_BODY', variant: ModalVariant.WARNING },
            actions: [
                { type: ModalResultType.DISMISS, name: 'ACCESS_CANCEL', variant: ModalActionType.DISMISS, target: ModalActionTarget.CANCEL },
                { type: ModalResultType.ACCEPT, name: 'ACCESS_LEAVE_CONFIRM', variant: ModalActionType.PRIMARY, target: ModalActionTarget.CONFIRM },
            ],
        });
        return confirmed.type === ModalResultType.ACCEPT;
    }

    async function selectRole(role: RoleRecord): Promise<void> {
        if (selected.value?.id === role.id) return;
        if (!(await confirmDiscard())) return;
        applySelection(role);
    }

    function selectById(value: string | number | null): void {
        if (value == null || value === '') return;
        const role = roles.value.find((item) => item.id === Number(value));
        if (role) void selectRole(role);
    }

    function setPermission(name: string, enabled: boolean): void {
        if (!canEditPermissions.value) return;
        const next = new Set(granted.value);
        if (enabled) next.add(name);
        else next.delete(name);
        granted.value = [...next];
    }

    function setGroup(group: VisibleGroup, enabled: boolean): void {
        const next = new Set(granted.value);
        for (const item of group.items) {
            if (enabled) next.add(item.name);
            else next.delete(item.name);
        }
        granted.value = [...next];
    }

    function resetFilters(): void {
        permissionQuery.value = '';
        permissionFilter.value = 'all';
    }

    function revert(): void {
        granted.value = [...baseline.value];
    }

    async function load(): Promise<void> {
        const [roleResponse, permissionResponse] = await Promise.all([http.get('roles'), http.get('permissions')]);
        roles.value = roleResponse.data.data ?? [];
        permissions.value = permissionResponse.data.data ?? [];
    }

    async function askName(current = ''): Promise<string | null> {
        const result = await modalService.open({
            title: current ? 'ACCESS_EDIT' : 'ACCESS_NEW_ROLE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: AccessRoleNameModal,
            props: { name: current },
            widthClass: 'w-full max-w-md',
            actions: [
                { type: ModalResultType.DISMISS, name: 'ACCESS_CANCEL', variant: ModalActionType.DISMISS, target: ModalActionTarget.CANCEL },
                { type: ModalResultType.ACCEPT, name: 'ACCESS_SAVE', variant: ModalActionType.PRIMARY, target: ModalActionTarget.CONFIRM },
            ],
        });
        if (result.type !== ModalResultType.ACCEPT) return null;
        const name = (result.data as { name?: string } | undefined)?.name?.trim() ?? '';
        return name || null;
    }

    async function createRole(): Promise<void> {
        if (!(await confirmDiscard())) return;
        const name = await askName();
        if (!name) return;
        try {
            const response = await http.post('roles', { name, permissions: [] });
            await load();
            const created = roles.value.find((role) => role.id === response.data.data?.id) ?? roles.value.find((role) => role.name === name) ?? null;
            applySelection(created);
            toast.success('ACCESS_CREATED');
        } catch {
            toast.error('ACCESS_SAVE_ERROR');
        }
    }

    async function renameRole(): Promise<void> {
        if (!selected.value || protectedRole.value) return;
        if (dirty.value) {
            toast.error('ACCESS_SAVE_FIRST');
            return;
        }
        const name = await askName(selected.value.name);
        if (!name || name === selected.value.name) return;
        try {
            await http.put(`roles/${selected.value.id}`, { name, permissions: baseline.value });
            await load();
            applySelection(roles.value.find((role) => role.id === selected.value?.id) ?? null);
            toast.success('ACCESS_ROLE_SAVED');
        } catch {
            toast.error('ACCESS_SAVE_ERROR');
        }
    }

    async function duplicateRole(): Promise<void> {
        if (!selected.value || protectedRole.value) return;
        if (dirty.value) {
            toast.error('ACCESS_SAVE_FIRST');
            return;
        }
        const suffix = t('ACCESS_COPY_SUFFIX');
        let name = `${selected.value.name} ${suffix}`;
        let index = 2;
        const taken = new Set(roles.value.map((role) => role.name));
        while (taken.has(name)) {
            name = `${selected.value.name} ${suffix} ${index}`;
            index += 1;
        }
        try {
            const response = await http.post('roles', { name, permissions: baseline.value });
            await load();
            applySelection(roles.value.find((role) => role.id === response.data.data?.id) ?? null);
            toast.success('ACCESS_DUPLICATED');
        } catch {
            toast.error('ACCESS_SAVE_ERROR');
        }
    }

    async function removeRole(): Promise<void> {
        if (!selected.value || protectedRole.value) return;
        if (!(await confirmDiscard())) return;
        const confirmed = await modalService.open({
            title: 'ACCESS_DELETE_TITLE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: ConfirmationModal,
            props: { message: 'ACCESS_DELETE_BODY', variant: ModalVariant.WARNING },
            actions: [
                { type: ModalResultType.DISMISS, name: 'ACCESS_CANCEL', variant: ModalActionType.DISMISS, target: ModalActionTarget.CANCEL },
                { type: ModalResultType.ACCEPT, name: 'ACCESS_ROLE_DELETE', variant: ModalActionType.PRIMARY, target: ModalActionTarget.CONFIRM },
            ],
        });
        if (confirmed.type !== ModalResultType.ACCEPT) return;
        try {
            await http.delete(`roles/${selected.value.id}`);
            const removed = selected.value.id;
            selected.value = null;
            await load();
            applySelection(roles.value.find((role) => role.id !== removed) ?? roles.value[0] ?? null);
            toast.success('ACCESS_DELETED');
        } catch {
            toast.error('ACCESS_SAVE_ERROR');
        }
    }

    async function save(): Promise<void> {
        if (!selected.value || !dirty.value || saving.value || protectedRole.value) return;
        const addedRisk = described.value.some((item) => item.risk && granted.value.includes(item.name) && !baseline.value.includes(item.name));
        if (addedRisk) {
            const confirmed = await modalService.open({
                title: 'ACCESS_SAVE_RISK_TITLE',
                type: ModalType.SLIDE_IN_TOP_CENTER,
                component: ConfirmationModal,
                props: { message: 'ACCESS_SAVE_RISK_BODY', variant: ModalVariant.WARNING },
                actions: [
                    { type: ModalResultType.DISMISS, name: 'ACCESS_CANCEL', variant: ModalActionType.DISMISS, target: ModalActionTarget.CANCEL },
                    { type: ModalResultType.ACCEPT, name: 'ACCESS_SAVE', variant: ModalActionType.PRIMARY, target: ModalActionTarget.CONFIRM },
                ],
            });
            if (confirmed.type !== ModalResultType.ACCEPT) return;
        }
        saving.value = true;
        try {
            await http.put(`roles/${selected.value.id}`, { name: selected.value.name, permissions: granted.value });
            await load();
            applySelection(roles.value.find((role) => role.id === selected.value?.id) ?? null);
            toast.success('ACCESS_ROLE_SAVED');
        } catch {
            toast.error('ACCESS_SAVE_ERROR');
        } finally {
            saving.value = false;
        }
    }

    function onBeforeUnload(event: BeforeUnloadEvent): void {
        if (!dirty.value) return;
        event.preventDefault();
        event.returnValue = '';
    }

    onBeforeRouteLeave(async () => confirmDiscard());

    onMounted(async () => {
        window.addEventListener('beforeunload', onBeforeUnload);
        try {
            await load();
            applySelection(roles.value[0] ?? null);
        } finally {
            loading.value = false;
        }
    });

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', onBeforeUnload);
    });
</script>

<style scoped>
    .access-action {
        display: inline-flex;
        height: 2.25rem;
        align-items: center;
        gap: 0.35rem;
        border: 1px solid var(--color-list-border, #e2e8f0);
        border-radius: 0.65rem;
        background: var(--color-list-surface, #fff);
        padding: 0 0.7rem;
        color: var(--color-list-text, #1e293b);
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .access-action :deep(svg) {
        width: 1rem;
        height: 1rem;
    }
</style>
