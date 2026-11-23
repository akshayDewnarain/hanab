<template>
    <div class="organogram-group" :class="`is-${data.groupKind ?? 'location'}`">
        <button v-if="data.groupKind === 'location'" type="button" class="organogram-group-head" @click="focus">
            <span>{{ data.name }}</span>
            <span>{{ t('ORGANOGRAM_LOCATION_COUNT', { count: data.memberCount ?? 0 }) }}</span>
        </button>
        <p v-else class="organogram-group-head">
            <span>{{ data.name }}</span>
            <span>{{ data.memberCount ?? 0 }}</span>
        </p>
    </div>
</template>

<script setup lang="ts">
    import { inject } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { organogramActionsKey } from '@/components/organogram/actions';
    import type { LaidOutOrganogramNode } from '@/composables/useOrganogram';

    const props = defineProps<{ data: LaidOutOrganogramNode }>();
    const { t } = useI18n();
    const actions = inject(organogramActionsKey);

    function focus(): void {
        actions?.focusGroup(props.data.id);
    }
</script>
