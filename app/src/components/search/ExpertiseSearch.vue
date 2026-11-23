<template>
    <GlobalSearchInput
        v-model="query"
        :presentation="presentation"
        :groups="groups"
        :labels="labels"
        :loading="isLoading"
        :error="errorMessage"
        :shortcut="shortcut"
        :shortcut-keys="shortcutKeys"
        :quick-links="quickLinks"
        @select="onSelect"
        @open-advanced="onOpenAdvanced"
        @quick-link="onQuickLink"
    />
</template>

<script lang="ts" setup>
    import { GlobalSearchInput, type GlobalSearchPresentation } from '@freelance/vue-ui';
    import { useExpertiseSearch } from '@/composables/useExpertiseSearch';

    defineOptions({ name: 'ExpertiseSearch' });

    withDefaults(
        defineProps<{
            presentation?: GlobalSearchPresentation;
            shortcut?: boolean;
        }>(),
        {
            presentation: 'field',
            shortcut: false,
        },
    );

    const { query, groups, labels, isLoading, errorMessage, shortcutKeys, quickLinks, onSelect, onOpenAdvanced, onQuickLink } =
        useExpertiseSearch();
</script>
