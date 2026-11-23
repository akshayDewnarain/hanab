<template>
    <section class="shrink-0 rounded-xl border border-list-border bg-list-surface shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <h1 class="inline-flex min-w-0 items-center gap-2 text-base font-semibold text-list-text">
                <Icon class="size-5 shrink-0 text-list-text-muted" icon="material-symbols:groups-outline-rounded" />
                <span class="truncate">{{ t('DIRECTORY_TITLE') }}</span>
            </h1>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <SegmentedControl
                    :model-value="presentation"
                    :label="t('DIRECTORY_VIEW')"
                    :options="viewOptions"
                    @update:model-value="emit('update:presentation', $event as 'directory' | 'cards')"
                >
                    <template #icon="{ option }">
                        <Icon
                            class="size-5"
                            :icon="
                                option.value === 'directory'
                                    ? 'material-symbols:format-list-bulleted-rounded'
                                    : 'material-symbols:grid-view-rounded'
                            "
                        />
                    </template>
                </SegmentedControl>
                <slot name="actions" />
            </div>
        </div>

        <div v-if="showSearch" class="space-y-3 border-t border-list-border px-4 py-3">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-list-text">{{ t('DIRECTORY_FIND_TITLE') }}</h2>
                    <HelpTrigger context="employees.directory" />
                </div>
                <p class="mt-0.5 text-sm text-list-text-muted">{{ t('DIRECTORY_FIND_INTRO') }}</p>
            </div>

            <form class="flex items-center gap-2" @submit.prevent="emit('search')">
                <label class="sr-only" for="employee-directory-search">{{ t('DIRECTORY_SEARCH_LABEL') }}</label>
                <div class="relative min-w-0 flex-1">
                    <Icon
                        class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-list-text-muted"
                        icon="material-symbols:search-rounded"
                    />
                    <input
                        id="employee-directory-search"
                        :value="query"
                        type="text"
                        autocomplete="off"
                        :placeholder="t('DIRECTORY_SEARCH_PLACEHOLDER')"
                        class="input-base h-10 w-full rounded-lg border-list-border bg-list-surface pr-10 pl-10 text-sm shadow-sm transition-[border-color,box-shadow] duration-200 ease-out placeholder:text-list-text-muted focus:border-primary focus:ring-2 focus:ring-primary/15 motion-reduce:transition-none"
                        @input="emit('update:query', ($event.target as HTMLInputElement).value)"
                    />
                    <button
                        v-if="query"
                        type="button"
                        class="absolute right-2 top-1/2 inline-flex size-6 -translate-y-1/2 cursor-pointer items-center justify-center rounded text-list-text-muted hover:text-list-text"
                        :aria-label="t('DIRECTORY_CLEAR')"
                        :title="t('DIRECTORY_CLEAR')"
                        @click="emit('clear')"
                    >
                        <Icon class="size-4" icon="material-symbols:close-rounded" />
                    </button>
                </div>
                <button
                    type="submit"
                    class="inline-flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg bg-[var(--color-primary)] text-on-brand shadow-sm hover:bg-[var(--color-primary-hover)]"
                    :aria-label="t('DIRECTORY_SEARCH_ACTION')"
                    :title="t('DIRECTORY_SEARCH_ACTION')"
                >
                    <Icon class="size-5" icon="material-symbols:arrow-forward-rounded" />
                </button>
            </form>

            <div v-if="suggestions.length" class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-list-text-muted">{{ t('DIRECTORY_SUGGESTIONS') }}</span>
                <button
                    v-for="suggestion in suggestions"
                    :key="suggestion"
                    type="button"
                    class="inline-flex cursor-pointer items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold transition-colors duration-200 ease-out motion-reduce:transition-none"
                    :class="
                        selected(suggestion)
                            ? 'border-[var(--color-primary)] bg-[var(--color-primary-light)] text-emphasis'
                            : 'border-list-border bg-list-canvas text-list-text hover:border-[var(--color-primary)]'
                    "
                    @click="emit('suggest', suggestion)"
                >
                    <Icon class="size-3.5" icon="material-symbols:search-rounded" />
                    {{ suggestion }}
                </button>
            </div>
        </div>
    </section>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { computed } from 'vue';
    import { SegmentedControl } from '@freelance/vue-ui';
    import { useI18n } from 'vue-i18n';
    import HelpTrigger from '@/components/help/HelpTrigger.vue';

    defineOptions({ name: 'EmployeeDirectoryHeader' });

    const props = withDefaults(
        defineProps<{
            query: string;
            presentation: 'directory' | 'cards';
            suggestions: readonly string[];
            /** Directory keeps the large search card. Cards supplies its own toolbar. */
            showSearch?: boolean;
        }>(),
        { showSearch: true },
    );

    const emit = defineEmits<{
        'update:query': [value: string];
        'update:presentation': [value: 'directory' | 'cards'];
        search: [];
        clear: [];
        suggest: [value: string];
    }>();

    const { t } = useI18n();

    const viewOptions = computed(() => [
        { value: 'directory', label: t('DIRECTORY_VIEW_DIRECTORY') },
        { value: 'cards', label: t('DIRECTORY_VIEW_CARDS') },
    ]);

    function selected(suggestion: string): boolean {
        return suggestion.trim().toLowerCase() === props.query.trim().toLowerCase();
    }
</script>
