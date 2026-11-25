<template>
    <PageLayout :label="article?.title || t('HELP_TITLE')" class="overflow-y-auto">
        <div class="grid w-full items-start gap-4 xl:grid-cols-[minmax(0,1fr)_16rem] xl:gap-6">
            <article
                ref="surface"
                class="min-w-0 overflow-hidden rounded-xl border border-list-border bg-list-surface shadow-[0_1px_2px_color-mix(in_srgb,var(--color-list-shadow-tint)_6%,transparent)]"
            >
                <p v-if="missing" class="m-0 px-4 py-4 text-sm sm:px-5">{{ t('HELP_UNAVAILABLE') }}</p>
                <template v-else-if="article">
                    <header class="border-b border-list-border px-4 py-4 sm:px-5">
                        <span class="-mx-4 -mt-4 mb-4 block h-1 sm:-mx-5" :class="helpBarClass(article.category?.slug)" aria-hidden="true"></span>
                        <router-link
                            v-if="article.category"
                            class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="helpTileClass(article.category.slug)"
                            :to="{ name: 'admin-help-category', params: { slug: article.category.slug } }"
                        >
                            <Icon class="size-3.5" icon="material-symbols:menu-book-outline-rounded" />
                            {{ article.category.name }}
                        </router-link>
                        <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
                            <h1 class="m-0 text-2xl font-semibold text-list-text">{{ article.title }}</h1>
                            <router-link
                                v-if="article.can_edit"
                                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border border-list-border px-3 text-sm font-medium text-list-text transition-colors duration-200 hover:border-primary hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none"
                                :to="{ name: 'admin-help-edit', params: { id: article.id } }"
                            >
                                <Icon icon="material-symbols:edit-outline-rounded" class="size-4" aria-hidden="true" />
                                {{ t('HELP_EDIT') }}
                            </router-link>
                        </div>
                        <p v-if="article.summary" class="m-0 mt-2 text-sm leading-6 text-list-text-muted">{{ article.summary }}</p>
                        <p class="m-0 mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-list-text-muted">
                            <span>{{ t('HELP_UPDATED', { date: updated }) }}</span>
                            <span>{{ t('HELP_READING_TIME', { count: minutes }) }}</span>
                        </p>
                    </header>
                    <div class="px-4 py-4 sm:px-5">
                        <div v-if="headings.length >= 2" class="mb-4 xl:hidden">
                            <button
                                type="button"
                                class="flex h-9 w-full cursor-pointer items-center justify-between rounded-lg border border-list-border px-3 text-sm font-medium text-list-text transition-colors duration-200 hover:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none"
                                :aria-expanded="contentsOpen"
                                aria-controls="help-contents"
                                @click="contentsOpen = !contentsOpen"
                            >
                                {{ t('HELP_IN_ARTICLE') }}
                                <Icon
                                    :icon="contentsOpen ? 'material-symbols:expand-less-rounded' : 'material-symbols:expand-more-rounded'"
                                    class="size-5 text-list-text-muted"
                                    aria-hidden="true"
                                />
                            </button>
                            <Collapsible id="help-contents" :open="contentsOpen">
                                <UiTableOfContents
                                    class="pt-2"
                                    :label="t('HELP_CONTENTS')"
                                    :items="headings"
                                    :active-id="activeId"
                                    :show-label="false"
                                    @select="goTo"
                                />
                            </Collapsible>
                        </div>
                        <div class="max-w-[52rem]">
                    <UiRichTextContent
                        :document="article.content"
                        :images="images"
                        :destinations="destinations"
                        :callout-labels="calloutLabels"
                        @enlarge="enlarged = $event"
                    />
                    <section v-if="leftover.length" class="mt-5 flex flex-col gap-3">
                        <h2 class="m-0 text-sm font-semibold">{{ t('HELP_SCREENSHOTS') }}</h2>
                        <figure v-for="shot in leftover" :key="shot.id" class="m-0 max-w-xl">
                            <button
                                type="button"
                                class="block w-full cursor-zoom-in overflow-hidden rounded-xl border border-list-border bg-list-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                                :aria-label="shot.alt"
                                @click="enlarged = { src: shot.url, alt: shot.alt, caption: shot.caption }"
                            >
                                <img class="h-auto max-h-80 w-full object-contain" :src="shot.url" :alt="shot.alt" />
                            </button>
                            <figcaption v-if="shot.caption" class="mt-2 text-sm text-list-text-muted">{{ shot.caption }}</figcaption>
                        </figure>
                    </section>
                    <section class="mt-6 border-t border-list-border pt-4">
                        <h2 class="m-0 text-sm font-semibold text-list-text">{{ t('HELP_FEEDBACK_QUESTION') }}</h2>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border px-3 text-sm font-medium transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-not-allowed disabled:opacity-60 motion-reduce:transition-none"
                                :class="article.feedback.mine === true ? 'border-primary bg-primary-light text-primary' : 'border-list-border text-list-text hover:border-primary'"
                                :disabled="savingFeedback"
                                :aria-pressed="article.feedback.mine === true"
                                @click="feedback(true)"
                            >
                                <Icon icon="material-symbols:thumb-up-outline-rounded" class="size-4" aria-hidden="true" />
                                {{ t('HELP_HELPFUL') }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border px-3 text-sm font-medium transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-not-allowed disabled:opacity-60 motion-reduce:transition-none"
                                :class="article.feedback.mine === false ? 'border-primary bg-primary-light text-primary' : 'border-list-border text-list-text hover:border-primary'"
                                :disabled="savingFeedback"
                                :aria-pressed="article.feedback.mine === false"
                                @click="feedback(false)"
                            >
                                <Icon icon="material-symbols:thumb-down-outline-rounded" class="size-4" aria-hidden="true" />
                                {{ t('HELP_NOT_HELPFUL') }}
                            </button>
                            <router-link
                                v-if="featurePath"
                                class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-semibold text-on-brand transition-colors duration-200 hover:bg-highlight-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none"
                                :to="featurePath"
                            >
                                {{ t('HELP_OPEN_FEATURE') }}
                                <Icon icon="material-symbols:arrow-forward-rounded" class="size-4" aria-hidden="true" />
                            </router-link>
                        </div>
                        <p v-if="thanks" class="m-0 mt-2 text-sm text-list-text-muted">{{ t('HELP_FEEDBACK_THANKS') }}</p>
                    </section>
                    <section v-if="related.length" class="mt-6 border-t border-list-border pt-4">
                        <h2 class="m-0 text-sm font-semibold text-list-text">{{ t('HELP_RELATED') }}</h2>
                        <div class="mt-3 grid gap-2">
                            <HelpArticleCard v-for="item in related" :key="item.id" compact :article="item" />
                        </div>
                    </section>
                        </div>
                    </div>
                </template>
            </article>
            <aside v-if="headings.length >= 2" class="hidden xl:block">
                <div
                    class="sticky top-0 rounded-xl border border-list-border bg-list-surface p-4 shadow-[0_1px_2px_color-mix(in_srgb,var(--color-list-shadow-tint)_6%,transparent)]"
                >
                    <UiTableOfContents :label="t('HELP_CONTENTS')" :items="headings" :active-id="activeId" @select="goTo" />
                </div>
            </aside>
        </div>
        <UiImageLightbox
            :open="enlarged !== null"
            :src="enlarged?.src ?? ''"
            :alt="enlarged?.alt ?? ''"
            :caption="enlarged?.caption"
            :close-label="t('HELP_CLOSE')"
            @close="enlarged = null"
        />
    </PageLayout>
</template>

<script lang="ts" setup>
    import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { useRoute } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { Icon } from '@iconify/vue';
    import { Collapsible, PageLayout, UiImageLightbox, UiTableOfContents } from '@freelance/vue-ui';
    import { documentText, richTextHeadings, UiRichTextContent } from '@freelance/vue-ui/editor';
    import http from '@/utils/http';
    import HelpArticleCard from '@/components/help/HelpArticleCard.vue';
    import { helpBarClass, helpTileClass } from '@/modules/help/appearance';
    import { imageIds } from '@/modules/help/document';
    import { useHelpDestinations } from '@/composables/useHelpDestinations';
    import type { HelpArticle } from '@/modules/types/help/Help.ts';

    type Shot = { id: number; url: string; alt: string; caption: string | null };
    type Enlarged = { src: string; alt: string; caption: string | null };

    const { t, locale } = useI18n();
    const { destinations, load: loadDestinations } = useHelpDestinations();
    const route = useRoute();
    const article = ref<HelpArticle | null>(null);
    const missing = ref(false);
    const shots = ref<Shot[]>([]);
    const enlarged = ref<Enlarged | null>(null);
    const thanks = ref(false);
    const contentsOpen = ref(false);
    const savingFeedback = ref(false);
    const activeId = ref<string | null>(null);
    const surface = ref<HTMLElement | null>(null);
    let observer: IntersectionObserver | null = null;
    const headings = computed(() => (article.value ? richTextHeadings(article.value.content) : []));
    const images = computed(() => Object.fromEntries(shots.value.map((shot) => [String(shot.id), shot.url])));
    const leftover = computed(() => {
        if (!article.value) return [];
        const used = imageIds(article.value.content);
        return shots.value.filter((shot) => !used.has(String(shot.id)));
    });
    const related = computed(() => (article.value?.related ?? []).slice(0, 3));
    const calloutLabels = computed(() => ({
        note: t('HELP_NOTE'),
        tip: t('HELP_TIP'),
        warning: t('HELP_WARNING'),
    }));
    const minutes = computed(() => {
        if (!article.value) return 1;
        const words = documentText(article.value.content).split(/\s+/).filter(Boolean).length;
        return Math.max(1, Math.round(words / 200));
    });
    const featurePath = computed(() => {
        const path = article.value?.feature_path ?? '';
        if (!path.startsWith('/') || path.startsWith('//') || path.includes('..')) return '';
        return path;
    });
    const updated = computed(() => {
        if (!article.value?.updated_at) return '';
        return new Intl.DateTimeFormat(locale.value === 'nl' ? 'nl-NL' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' }).format(
            new Date(article.value.updated_at),
        );
    });

    function revoke(): void {
        for (const shot of shots.value) URL.revokeObjectURL(shot.url);
        shots.value = [];
    }

    function scrollParent(element: HTMLElement | null): HTMLElement | null {
        let node = element?.parentElement ?? null;
        while (node) {
            const overflow = getComputedStyle(node).overflowY;
            if (overflow === 'auto' || overflow === 'scroll') return node;
            node = node.parentElement;
        }
        return null;
    }

    function observe(): void {
        observer?.disconnect();
        observer = null;
        if (headings.value.length < 2 || typeof IntersectionObserver === 'undefined') return;
        observer = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((entry) => entry.isIntersecting)
                    .sort((left, right) => right.intersectionRatio - left.intersectionRatio)[0];
                if (visible?.target.id) activeId.value = visible.target.id;
            },
            { root: scrollParent(surface.value), rootMargin: '-10% 0px -70% 0px', threshold: [0, 1] },
        );
        for (const heading of headings.value) {
            const element = document.getElementById(heading.id);
            if (element) observer.observe(element);
        }
    }

    function goTo(id: string): void {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.getElementById(id)?.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
        activeId.value = id;
    }

    async function load(): Promise<void> {
        revoke();
        observer?.disconnect();
        missing.value = false;
        article.value = null;
        enlarged.value = null;
        thanks.value = false;
        try {
            const response = await http.get<{ data: HelpArticle }>(`help/articles/slug/${route.params.slug}`);
            article.value = response.data.data;
            for (const file of article.value.attachments ?? []) {
                try {
                    const image = await http.get(`help/screenshots/${file.id}`, { responseType: 'blob' });
                    shots.value.push({
                        id: file.id,
                        url: URL.createObjectURL(image.data),
                        alt: file.alt_text,
                        caption: file.caption,
                    });
                } catch {
                    // A missing screenshot leaves the guide readable.
                }
            }
            activeId.value = headings.value[0]?.id ?? null;
            await nextTick();
            observe();
        } catch {
            missing.value = true;
        }
    }

    async function feedback(helpful: boolean): Promise<void> {
        if (!article.value || savingFeedback.value || article.value.feedback.mine === helpful) return;
        savingFeedback.value = true;
        try {
            await http.post(`help/articles/${article.value.id}/feedback`, { helpful });
            const previous = article.value.feedback.mine;
            if (previous === true) article.value.feedback.helpful = Math.max(0, article.value.feedback.helpful - 1);
            if (previous === false) article.value.feedback.not_helpful = Math.max(0, article.value.feedback.not_helpful - 1);
            if (helpful) article.value.feedback.helpful += 1;
            else article.value.feedback.not_helpful += 1;
            article.value.feedback.mine = helpful;
            thanks.value = true;
        } finally {
            savingFeedback.value = false;
        }
    }

    watch(() => route.params.slug, () => void load());
    onMounted(() => {
        void loadDestinations();
        void load();
    });
    onBeforeUnmount(() => {
        observer?.disconnect();
        revoke();
    });
</script>
