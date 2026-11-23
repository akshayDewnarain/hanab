<template>
    <div
        class="organogram-node organogram-role"
        :class="{ 'is-context': data.context, 'is-highlighted': data.highlighted }"
        :style="{ width: `${data.width}px`, borderTopColor: data.level?.color ?? '#8B95A4' }"
    >
        <Handle v-if="data.connected" type="target" :position="Position.Top" class="organogram-handle" />
        <div class="organogram-copy organogram-role-head">
            <span class="organogram-kicker">{{ t('ORGANOGRAM_VIEW_ROLES') }}</span>
            <span class="organogram-name">{{ data.name }}</span>
            <span v-if="data.description" class="organogram-role">{{ data.description }}</span>
            <span v-if="data.level" class="organogram-level">{{ data.level.name }}</span>
        </div>
        <ul v-if="people.length" class="organogram-people">
            <li v-for="person in people" :key="person.id">
                <button type="button" @click="actions?.openEmployee(person.id)">
                    <span>{{ person.name }}</span>
                    <span v-if="data.showLocation && person.location">{{ person.location }}</span>
                </button>
            </li>
            <li v-if="extra > 0" class="organogram-note">{{ t('ORGANOGRAM_MORE_PEOPLE', { count: extra }) }}</li>
        </ul>
        <p v-else class="organogram-note">{{ t('ORGANOGRAM_EMPTY_PEOPLE') }}</p>
        <footer class="organogram-foot">
            <span>{{ t('ORGANOGRAM_VIEW_ROLES') }}</span>
            <button
                v-if="data.expandable"
                type="button"
                class="organogram-toggle"
                :aria-expanded="!data.collapsed"
                :aria-label="data.collapsed ? t('ORGANOGRAM_EXPAND') : t('ORGANOGRAM_COLLAPSE')"
                @click.stop="actions?.toggleNode(data.id)"
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
    import { computed, inject } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { organogramActionsKey } from '@/components/organogram/actions';
    import type { LaidOutOrganogramNode } from '@/composables/useOrganogram';

    const props = defineProps<{ data: LaidOutOrganogramNode }>();
    const { t } = useI18n();
    const actions = inject(organogramActionsKey);
    const people = computed(() => (props.data.people ?? []).slice(0, 4));
    const extra = computed(() => Math.max((props.data.people?.length ?? 0) - people.value.length, 0));
</script>
