<template>
    <div
        class="organogram-node"
        :class="{ 'is-context': data.context, 'is-highlighted': data.highlighted }"
        :style="{ width: `${data.width}px`, borderTopColor: data.level?.color ?? '#8B95A4' }"
    >
        <Handle v-if="data.connected" type="target" :position="Position.Top" class="organogram-handle" />
        <button type="button" class="organogram-node-main" @click="open">
            <span v-if="data.showAvatar" class="organogram-avatar" :style="avatarStyle" aria-hidden="true">
                <img v-if="showPhoto && data.photoUrl" :src="data.photoUrl" alt="" @error="showPhoto = false" />
                <span v-else>{{ data.initials }}</span>
            </span>
            <span class="organogram-copy">
                <span class="organogram-name">{{ data.name }}</span>
                <span v-if="data.role" class="organogram-role">{{ data.role }}</span>
                <span v-if="data.showLocation && data.location" class="organogram-meta">
                    <Icon icon="material-symbols:location-on-outline-rounded" />
                    {{ data.location }}
                </span>
                <span v-if="data.level" class="organogram-level">{{ data.level.name }}</span>
                <span v-if="data.managerNote === 'inactive'" class="organogram-note">
                    {{ t('ORGANOGRAM_INACTIVE_MANAGER', { name: data.managerName ?? '' }) }}
                </span>
                <span v-else-if="data.reportsElsewhere" class="organogram-note">{{ t('ORGANOGRAM_CROSS_LOCATION') }}</span>
                <span v-else-if="data.context" class="organogram-note">{{ t('ORGANOGRAM_CONTEXT') }}</span>
            </span>
        </button>
        <footer v-if="reportCount > 0 || data.expandable" class="organogram-foot">
            <span v-if="reportCount > 0">{{ t('ORGANOGRAM_REPORTS', { count: reportCount }) }}</span>
            <span v-else></span>
            <button
                v-if="data.expandable"
                type="button"
                class="organogram-toggle"
                :aria-expanded="!data.collapsed"
                :aria-label="data.collapsed ? t('ORGANOGRAM_EXPAND') : t('ORGANOGRAM_COLLAPSE')"
                @click.stop="toggle"
            >
                <Icon :icon="data.collapsed ? 'material-symbols:expand-more-rounded' : 'material-symbols:expand-less-rounded'" />
            </button>
        </footer>
        <Handle v-if="data.connected" type="source" :position="Position.Bottom" class="organogram-handle" />
    </div>
</template>

<script setup lang="ts">
    import { Handle, Position } from '@vue-flow/core';
    import { Icon } from '@iconify/vue';
    import { computed, inject, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { organogramActionsKey } from '@/components/organogram/actions';
    import type { LaidOutOrganogramNode } from '@/composables/useOrganogram';

    const props = defineProps<{ data: LaidOutOrganogramNode }>();
    const { t } = useI18n();
    const actions = inject(organogramActionsKey);
    const showPhoto = ref(Boolean(props.data.photoUrl));
    const reportCount = computed(() => props.data.directReportsCount ?? 0);
    const avatarStyle = computed(() => ({
        background: props.data.level?.color ?? '#8B95A4',
        color: '#ffffff',
    }));

    function open(): void {
        if (props.data.employeeId) {
            actions?.openEmployee(props.data.employeeId);
        }
    }

    function toggle(): void {
        actions?.toggleNode(props.data.id);
    }
</script>
