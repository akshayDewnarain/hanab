<template>
    <Transition
        enter-active-class="transition duration-200 ease-out motion-reduce:transition-none"
        enter-from-class="translate-x-4 opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-4 opacity-0"
    >
        <div v-if="open" class="fixed inset-y-0 right-0 z-40 flex w-[min(26rem,100vw)] shadow-xl">
            <Drawer class="h-full w-full" open :label="t('HELP_TITLE')" :close-label="t('HELP_CLOSE')" side="end" @close="close">
                <template #title>
                    <p class="m-0 truncate text-sm font-semibold">{{ t('HELP_TITLE') }}</p>
                </template>
                <p v-if="loading" class="m-0 text-sm text-list-text-muted">{{ t('HELP_LOADING') }}</p>
                <p v-else-if="!articles.length" class="m-0 text-sm text-list-text-muted">{{ t('HELP_CONTEXT_EMPTY') }}</p>
                <div v-else class="flex flex-col gap-6">
                    <article v-for="article in articles" :key="article.id">
                        <h2 class="m-0 text-base font-semibold">{{ article.title }}</h2>
                        <p v-if="article.summary" class="m-0 mt-1 text-sm text-list-text-muted">{{ article.summary }}</p>
                        <UiRichTextContent
                            class="help-copy mt-3 text-sm"
                            :document="article.content"
                            :images="images[article.id] ?? {}"
                            :destinations="destinations"
                            :callout-labels="calloutLabels"
                            @enlarge="enlarged = $event"
                        />
                        <router-link
                            class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-primary"
                            :to="{ name: 'admin-help-article', params: { slug: article.slug } }"
                            @click="close"
                        >
                            {{ t('HELP_OPEN_ARTICLE') }}
                            <Icon icon="material-symbols:arrow-forward-rounded" class="size-4" aria-hidden="true" />
                        </router-link>
                    </article>
                </div>
            </Drawer>
        </div>
    </Transition>
    <UiImageLightbox
        :open="enlarged !== null"
        :src="enlarged?.src ?? ''"
        :alt="enlarged?.alt ?? ''"
        :caption="enlarged?.caption"
        :close-label="t('HELP_CLOSE')"
        @close="enlarged = null"
    />
</template>

<script lang="ts" setup>
    import { computed, onBeforeUnmount, ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { Icon } from '@iconify/vue';
    import { Drawer, UiImageLightbox } from '@freelance/vue-ui';
    import { UiRichTextContent } from '@freelance/vue-ui/editor';
    import http from '@/utils/http';
    import { useHelpCenter } from '@/composables/useHelpCenter';
    import { useHelpDestinations } from '@/composables/useHelpDestinations';
    import type { HelpArticle } from '@/modules/types/help/Help.ts';

    const { t } = useI18n();
    const { open, contextKey, close } = useHelpCenter();
    const { destinations, load: loadDestinations } = useHelpDestinations();
    const articles = ref<HelpArticle[]>([]);
    const loading = ref(false);
    const images = ref<Record<number, Record<string, string>>>({});
    const enlarged = ref<{ src: string; alt: string; caption: string | null } | null>(null);
    const owned: string[] = [];
    let request = 0;
    const calloutLabels = computed(() => ({
        note: t('HELP_NOTE'),
        tip: t('HELP_TIP'),
        warning: t('HELP_WARNING'),
    }));

    function revoke(): void {
        for (const url of owned) URL.revokeObjectURL(url);
        owned.length = 0;
        images.value = {};
        enlarged.value = null;
    }

    async function loadImages(list: HelpArticle[]): Promise<void> {
        const next: Record<number, Record<string, string>> = {};
        for (const article of list) {
            const map: Record<string, string> = {};
            for (const file of article.attachments ?? []) {
                const image = await http.get(`help/screenshots/${file.id}`, { responseType: 'blob' });
                const url = URL.createObjectURL(image.data);
                owned.push(url);
                map[String(file.id)] = url;
            }
            next[article.id] = map;
        }
        images.value = next;
    }

    watch([open, contextKey], async ([isOpen, key]) => {
        const current = ++request;
        if (!isOpen || !key) {
            revoke();
            articles.value = [];
            return;
        }
        void loadDestinations();
        loading.value = true;
        articles.value = [];
        revoke();
        try {
            const response = await http.get<{ data: HelpArticle[] }>('help/context', { params: { key } });
            if (current !== request) return;
            articles.value = response.data.data ?? [];
            await loadImages(articles.value);
        } catch {
            if (current === request) articles.value = [];
        } finally {
            if (current === request) loading.value = false;
        }
    });

    onBeforeUnmount(revoke);
</script>

<style scoped>
    .help-copy :deep(p) {
        margin: 0.5rem 0;
    }
</style>
