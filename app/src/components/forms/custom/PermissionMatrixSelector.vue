<template>
    <div class="flex flex-col gap-1 w-full">
        <label v-if="options?.label" class="text-sm font-medium" :for="options.name">{{ t(options.label) }}</label>
        <div class="permission-matrix-columns flex flex-col gap-4 w-full md:flex-row md:items-start md:gap-4">
            <div
                class="permission-matrix-column permission-matrix-column--left flex flex-1 flex-col gap-4 min-w-0 w-full"
            >
                <div
                    v-for="group in leftGroups"
                    :key="group.key"
                    class="permission-matrix-group-card w-full rounded border border-gray-200 bg-white shadow-sm overflow-hidden self-stretch"
                >
                    <div
                        class="flex items-center gap-3 px-3 py-2 border-b border-gray-200 bg-[var(--color-background)]/10"
                    >
                        <button
                            type="button"
                            class="p-0.5 rounded text-gray-600 hover:bg-gray-100 shrink-0"
                            :aria-expanded="isExpanded(group.key)"
                            @click="toggleExpanded(group.key)"
                        >
                            <Icon
                                :class="['w-5 h-5 transition-transform', isExpanded(group.key) ? 'rotate-180' : '']"
                                icon="material-symbols:expand-more-rounded"
                            />
                        </button>
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-semibold text-gray-900">
                                {{ groupLabel(group.key) }} ({{ getSelectedCount(group) }}/{{
                                    group.permissions.length
                                }})
                            </span>
                        </div>
                        <ToggleSwitch
                            :model-value="isGroupFullySelected(group)"
                            @update:model-value="(v) => setGroupAll(group, !!v)"
                        />
                    </div>
                    <Collapse :duration="200" :max-height="'2000px'" :show="isExpanded(group.key)">
                        <div class="divide-y divide-gray-100 px-3 py-1">
                            <div
                                v-for="row in group.permissions"
                                :key="row.name"
                                class="flex items-center justify-between gap-3 py-2"
                            >
                                <span class="text-sm text-gray-800">{{ actionLabel(row.action) }}</span>
                                <ToggleSwitch
                                    :model-value="isPermissionSelected(row.name)"
                                    @update:model-value="(v) => togglePermission(row.name, !!v)"
                                />
                            </div>
                        </div>
                    </Collapse>
                </div>
            </div>
            <div
                class="permission-matrix-column permission-matrix-column--right flex flex-1 flex-col gap-4 min-w-0 w-full"
            >
                <div
                    v-for="group in rightGroups"
                    :key="group.key"
                    class="permission-matrix-group-card w-full rounded border border-gray-200 bg-white shadow-sm overflow-hidden self-stretch"
                >
                    <div
                        class="flex items-center gap-3 px-3 py-2 border-b border-gray-200 bg-[var(--color-background)]/10"
                    >
                        <button
                            type="button"
                            class="p-0.5 rounded text-gray-600 hover:bg-gray-100 shrink-0"
                            :aria-expanded="isExpanded(group.key)"
                            @click="toggleExpanded(group.key)"
                        >
                            <Icon
                                :class="['w-5 h-5 transition-transform', isExpanded(group.key) ? 'rotate-180' : '']"
                                icon="material-symbols:expand-more-rounded"
                            />
                        </button>
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-semibold text-gray-900">
                                {{ groupLabel(group.key) }} ({{ getSelectedCount(group) }}/{{
                                    group.permissions.length
                                }})
                            </span>
                        </div>
                        <ToggleSwitch
                            :model-value="isGroupFullySelected(group)"
                            @update:model-value="(v) => setGroupAll(group, !!v)"
                        />
                    </div>
                    <Collapse :duration="200" :max-height="'2000px'" :show="isExpanded(group.key)">
                        <div class="divide-y divide-gray-100 px-3 py-1">
                            <div
                                v-for="row in group.permissions"
                                :key="row.name"
                                class="flex items-center justify-between gap-3 py-2"
                            >
                                <span class="text-sm text-gray-800">{{ actionLabel(row.action) }}</span>
                                <ToggleSwitch
                                    :model-value="isPermissionSelected(row.name)"
                                    @update:model-value="(v) => togglePermission(row.name, !!v)"
                                />
                            </div>
                        </div>
                    </Collapse>
                </div>
            </div>
        </div>
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<script lang="ts" setup>
    import { computed, reactive, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { Icon } from '@iconify/vue';
    import ToggleSwitch from '@/components/inputs/ToggleSwitch.vue';
    import Collapse from '@/components/transitions/Collapse.vue';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
    import { getActionKey, getGroupKey } from '@/components/forms/custom/permissionMatrixHelpers';

    const ACTION_ORDER = ['VIEW', 'CREATE', 'UPDATE', 'DELETE', 'DOWNLOAD', 'CSV_EXPORT', 'MASS_UPDATE', 'ARCHIVE'];

    type PermissionRow = { name: string; action: string };
    type PermissionGroup = { key: string; permissions: PermissionRow[] };

    const props = withDefaults(
        defineProps<{
            /** Full permission names shown in the matrix */
            permissions: string[];
            /** Selected permission names */
            modelValue: string[];
            /** @deprecated Kept for API compatibility; layout uses two flex columns (stacked on small screens). */
            columns?: number;
            collapsedByDefault?: boolean;
            options?: BaseInputFieldOptions;
            error?: string;
        }>(),
        {
            columns: 2,
            collapsedByDefault: true,
        },
    );

    const emit = defineEmits<{
        (e: 'update:modelValue', v: string[]): void;
    }>();

    const { t, te } = useI18n();

    const expandedByGroup = reactive<Record<string, boolean>>({});

    function actionSortIndex(action: string): number {
        const i = ACTION_ORDER.indexOf(action);

        return i === -1 ? ACTION_ORDER.length : i;
    }

    const groupedPermissions = computed<PermissionGroup[]>(() => {
        const map = new Map<string, PermissionRow[]>();
        const catalog = [...new Set(props.permissions)].sort((a, b) => a.localeCompare(b));

        for (const name of catalog) {
            const key = getGroupKey(name);
            const action = getActionKey(name);
            const list = map.get(key) ?? [];
            list.push({ name, action });
            map.set(key, list);
        }

        const groups: PermissionGroup[] = [...map.entries()]
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([key, perms]) => ({
                key,
                permissions: [...perms].sort((p, q) => {
                    const da = actionSortIndex(p.action);
                    const db = actionSortIndex(q.action);
                    if (da !== db) {
                        return da - db;
                    }

                    return p.action.localeCompare(q.action);
                }),
            }));

        return groups;
    });

    const leftGroups = computed(() => groupedPermissions.value.filter((_, index) => index % 2 === 0));

    const rightGroups = computed(() => groupedPermissions.value.filter((_, index) => index % 2 === 1));

    watch(
        groupedPermissions,
        (groups) => {
            for (const g of groups) {
                if (expandedByGroup[g.key] === undefined) {
                    expandedByGroup[g.key] = !props.collapsedByDefault;
                }
            }
        },
        { immediate: true },
    );

    function isExpanded(groupKey: string): boolean {
        return expandedByGroup[groupKey] ?? !props.collapsedByDefault;
    }

    function toggleExpanded(groupKey: string): void {
        expandedByGroup[groupKey] = !isExpanded(groupKey);
    }

    function groupLabel(groupKey: string): string {
        const k = `PERMISSIONS.ENTITIES.${groupKey}`;

        return te(k) ? t(k) : groupKey;
    }

    function actionLabel(actionKey: string): string {
        const k = `PERMISSIONS.ACTIONS.${actionKey}`;

        return te(k) ? t(k) : actionKey;
    }

    function isPermissionSelected(permission: string): boolean {
        return props.modelValue.includes(permission);
    }

    function togglePermission(permission: string, enabled: boolean): void {
        const set = new Set(props.modelValue);
        if (enabled) {
            set.add(permission);
        } else {
            set.delete(permission);
        }
        emit('update:modelValue', [...set]);
    }

    function getSelectedCount(group: PermissionGroup): number {
        return group.permissions.filter((p) => props.modelValue.includes(p.name)).length;
    }

    function isGroupFullySelected(group: PermissionGroup): boolean {
        if (group.permissions.length === 0) {
            return false;
        }

        return group.permissions.every((p) => props.modelValue.includes(p.name));
    }

    function setGroupAll(group: PermissionGroup, enabled: boolean): void {
        const set = new Set(props.modelValue);
        for (const p of group.permissions) {
            if (enabled) {
                set.add(p.name);
            } else {
                set.delete(p.name);
            }
        }
        emit('update:modelValue', [...set]);
    }
</script>
