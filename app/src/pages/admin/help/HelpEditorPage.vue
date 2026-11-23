<template>
    <PageLayout :label="t('HELP_EDIT')" class="overflow-y-auto">
        <div class="mx-auto grid w-full max-w-6xl gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <router-link class="text-sm font-medium text-primary" :to="slug ? { name: 'admin-help-article', params: { slug } } : { name: 'admin-help' }">
                        {{ slug ? t('HELP_BACK_TO_ARTICLE') : t('HELP_BACK') }}
                    </router-link>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="h-9 rounded-lg border border-list-border px-3 text-sm" @click="preview = !preview">{{ t('HELP_PREVIEW') }}</button>
                        <button type="button" class="h-9 rounded-lg border border-list-border px-3 text-sm" @click="save(false)">{{ t('HELP_SAVE_DRAFT') }}</button>
                        <button v-if="canPublish" type="button" class="h-9 rounded-lg bg-primary px-3 text-sm font-semibold text-white" @click="save(true)">{{ t('HELP_PUBLISH') }}</button>
                    </div>
                </div>
                <p v-if="conflict" class="text-sm text-red-700">{{ t('HELP_CONFLICT') }}</p>
                <p v-else-if="notice" class="text-sm text-list-text-muted">{{ notice }}</p>
                <input v-model="title" class="mt-3 h-12 w-full rounded-xl border border-list-border bg-list-surface px-4 text-xl font-semibold" :aria-label="t('HELP_ARTICLE_TITLE')" />
                <UiRichTextContent
                    v-if="preview"
                    class="mt-4 rounded-xl border border-list-border bg-list-surface p-4 sm:p-5"
                    :document="document"
                    :images="images"
                    :destinations="destinations"
                    :callout-labels="calloutLabels"
                />
                <UiRichTextEditor
                    v-else
                    ref="editor"
                    v-model="document"
                    class="mt-4"
                    documentation
                    :labels="editorLabels"
                    :destinations="destinationOptions"
                    :max-length="40000"
                    :placeholder="t('HELP_EDITOR_PLACEHOLDER')"
                    @request-image="focusScreenshot"
                />
            </div>
            <aside class="flex flex-col gap-3 rounded-xl border border-list-border bg-list-surface p-4">
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('HELP_SUMMARY') }}</span>
                    <textarea v-model="summary" rows="3" class="mt-1 w-full rounded-lg border border-list-border px-3 py-2" />
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('HELP_CATEGORY') }}</span>
                    <select v-model="categoryId" class="mt-1 h-9 w-full rounded-lg border border-list-border px-2">
                        <option :value="null">{{ t('HELP_NO_CATEGORY') }}</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('HELP_CONTEXT') }}</span>
                    <select v-model="contextKey" class="mt-1 h-9 w-full rounded-lg border border-list-border px-2">
                        <option :value="null">{{ t('HELP_NO_CONTEXT') }}</option>
                        <option v-for="key in contexts" :key="key" :value="key">{{ key }}</option>
                    </select>
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('HELP_AUDIENCE') }}</span>
                    <input v-model="audience" class="mt-1 h-9 w-full rounded-lg border border-list-border px-3" :placeholder="t('HELP_AUDIENCE_HINT')" />
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('HELP_FEATURE_PATH') }}</span>
                    <input v-model="featurePath" class="mt-1 h-9 w-full rounded-lg border border-list-border px-3" placeholder="/admin/employees" />
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('HELP_SORT') }}</span>
                    <input v-model.number="sortOrder" type="number" min="0" class="mt-1 h-9 w-full rounded-lg border border-list-border px-3" />
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="gettingStarted" type="checkbox" />
                    {{ t('HELP_GETTING_STARTED') }}
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('HELP_VISIBILITY') }}</span>
                    <select v-model="visibility" class="mt-1 h-9 w-full rounded-lg border border-list-border px-2">
                        <option value="authenticated">{{ t('HELP_VISIBILITY_AUTHENTICATED') }}</option>
                        <option value="private">{{ t('HELP_VISIBILITY_PRIVATE') }}</option>
                    </select>
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('HELP_REVIEW_DATE') }}</span>
                    <input v-model="reviewDue" type="date" class="mt-1 h-9 w-full rounded-lg border border-list-border px-3" />
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('HELP_CHANGE_NOTE') }}</span>
                    <input v-model="note" class="mt-1 h-9 w-full rounded-lg border border-list-border px-3" />
                </label>
                <div v-if="articleId" class="text-sm">
                    <p class="m-0 text-list-text-muted">{{ t('HELP_SCREENSHOT') }}</p>
                    <input v-model="altText" class="mt-1 h-9 w-full rounded-lg border border-list-border px-3" :placeholder="t('HELP_ALT_TEXT')" />
                    <input v-model="caption" class="mt-2 h-9 w-full rounded-lg border border-list-border px-3" :placeholder="t('HELP_IMAGE_CAPTION')" />
                    <label class="mt-2 block">
                        <span class="text-list-text-muted">{{ t('HELP_IMAGE_SIZE') }}</span>
                        <select v-model="imageSize" class="mt-1 h-9 w-full rounded-lg border border-list-border px-2">
                            <option value="sm">{{ t('HELP_IMAGE_SIZE_SM') }}</option>
                            <option value="md">{{ t('HELP_IMAGE_SIZE_MD') }}</option>
                            <option value="lg">{{ t('HELP_IMAGE_SIZE_LG') }}</option>
                        </select>
                    </label>
                    <input ref="fileInput" class="mt-2 block w-full text-sm" type="file" accept="image/png,image/jpeg,image/webp" @change="upload" />
                    <ul v-if="attachments.length" class="mt-3 list-none p-0">
                        <li v-for="file in attachments" :key="file.id" class="flex items-center justify-between gap-2 border-t border-list-border py-2">
                            <span class="min-w-0 truncate">{{ file.alt_text }}</span>
                            <button type="button" class="shrink-0 text-primary" @click="removeShot(file.id)">{{ t('HELP_IMAGE_REMOVE') }}</button>
                        </li>
                    </ul>
                </div>
                <p v-else class="m-0 text-sm text-list-text-muted">{{ t('HELP_IMAGE_AFTER_SAVE') }}</p>
                <button v-if="articleId && canPublish" type="button" class="h-9 rounded-lg border border-list-border px-3 text-sm" @click="archive">{{ t('HELP_ARCHIVE') }}</button>
                <section v-if="revisions.length">
                    <h2 class="m-0 text-sm font-semibold">{{ t('HELP_REVISIONS') }}</h2>
                    <ul class="mt-2 list-none p-0 text-sm">
                        <li v-for="revision in revisions" :key="revision.number" class="flex items-center justify-between gap-2 border-t border-list-border py-2">
                            <span>{{ revision.number }} · {{ revision.editor?.name }}</span>
                            <button v-if="canRestore" type="button" class="text-primary" @click="restore(revision.number)">{{ t('HELP_RESTORE') }}</button>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { PageLayout } from '@freelance/vue-ui';
    import { emptyDocument, UiRichTextContent, UiRichTextEditor, type RichTextDocument, type RichTextEditorLabels } from '@freelance/vue-ui/editor';
    import http from '@/utils/http';
    import { useAuthStore } from '@/stores/auth';
    import { withImageSources, withoutImage, withoutImageSources } from '@/modules/help/document';
    import { useHelpDestinations } from '@/composables/useHelpDestinations';
    import type { HelpArticle, HelpCategory, HelpRevision, HelpScreenshot } from '@/modules/types/help/Help.ts';

    const fallbackContexts = [
        'auth.sign-in',
        'employees.directory',
        'employees.profile',
        'employees.edit',
        'employees.import',
        'employees.export',
        'organogram.management',
        'organogram.location',
        'skills.certificates',
        'messages.compose',
        'settings.users',
    ];

    const { t } = useI18n();
    const { destinations, options: destinationOptions, load: loadDestinations } = useHelpDestinations();
    const contexts = computed(() => (destinationOptions.value.length ? destinationOptions.value.map((item) => item.key) : fallbackContexts));
    const route = useRoute();
    const router = useRouter();
    const auth = useAuthStore();
    const title = ref('');
    const summary = ref('');
    const document = ref<RichTextDocument>(emptyDocument());
    const categoryId = ref<number | null>(null);
    const contextKey = ref<string | null>(null);
    const audience = ref('');
    const featurePath = ref('');
    const sortOrder = ref(0);
    const gettingStarted = ref(false);
    const visibility = ref<'private' | 'authenticated'>('authenticated');
    const reviewDue = ref('');
    const note = ref('');
    const altText = ref('');
    const caption = ref('');
    const imageSize = ref<'sm' | 'md' | 'lg'>('md');
    const attachments = ref<HelpScreenshot[]>([]);
    const images = ref<Record<string, string>>({});
    const editor = ref<{ insertImage: (attrs: { id: number; alt: string; caption?: string | null; size?: string | null; align?: string | null; src?: string | null }) => void } | null>(null);
    const fileInput = ref<HTMLInputElement | null>(null);
    const applying = ref(false);
    const version = ref(1);
    const slug = ref('');
    const articleId = ref<number | null>(null);
    const categories = ref<HelpCategory[]>([]);
    const revisions = ref<HelpRevision[]>([]);
    const preview = ref(false);
    const notice = ref('');
    const conflict = ref(false);
    const tracking = ref(false);
    const dirty = ref(false);
    const canPublish = computed(() => auth.hasPermission('publish help articles'));
    const canRestore = computed(() => auth.hasPermission('restore help revisions'));
    const editorLabels = computed<Partial<RichTextEditorLabels>>(() => ({
        content: t('EDITOR_CONTENT'),
        bold: t('EDITOR_BOLD'),
        italic: t('EDITOR_ITALIC'),
        underline: t('EDITOR_UNDERLINE'),
        heading: t('EDITOR_HEADING'),
        bulletList: t('EDITOR_BULLET_LIST'),
        orderedList: t('EDITOR_ORDERED_LIST'),
        link: t('EDITOR_LINK'),
        blockquote: t('EDITOR_BLOCKQUOTE'),
        clear: t('EDITOR_CLEAR'),
        undo: t('EDITOR_UNDO'),
        redo: t('EDITOR_REDO'),
        linkUrl: t('EDITOR_LINK_URL'),
        linkApply: t('EDITOR_LINK_APPLY'),
        linkRemove: t('EDITOR_LINK_REMOVE'),
        linkInvalid: t('EDITOR_LINK_INVALID'),
        tooLong: t('EDITOR_TOO_LONG'),
        steps: t('HELP_STEPS'),
        tip: t('HELP_TIP'),
        warning: t('HELP_WARNING'),
        note: t('HELP_NOTE'),
        addBlock: t('HELP_BLOCK_ADD'),
        blockActions: t('HELP_BLOCK_ACTIONS'),
        blockEmpty: t('HELP_BLOCK_EMPTY'),
        moveUp: t('HELP_BLOCK_MOVE_UP'),
        moveDown: t('HELP_BLOCK_MOVE_DOWN'),
        duplicate: t('HELP_BLOCK_DUPLICATE'),
        deleteBlock: t('HELP_BLOCK_DELETE'),
        convert: t('HELP_BLOCK_CONVERT'),
        properties: t('HELP_BLOCK_PROPERTIES'),
        alt: t('HELP_ALT_TEXT'),
        caption: t('HELP_IMAGE_CAPTION'),
        align: t('HELP_IMAGE_ALIGN'),
        alignStart: t('HELP_IMAGE_ALIGN_START'),
        alignCenter: t('HELP_IMAGE_ALIGN_CENTER'),
        alignEnd: t('HELP_IMAGE_ALIGN_END'),
        featureLabel: t('HELP_FEATURE_LABEL'),
        featureDescription: t('HELP_FEATURE_DESCRIPTION'),
        featureDestination: t('HELP_FEATURE_DESTINATION'),
        blockText: t('HELP_BLOCK_TEXT'),
        blockHeading: t('HELP_BLOCK_HEADING'),
        blockStep: t('HELP_BLOCK_STEP'),
        blockScreenshot: t('HELP_BLOCK_SCREENSHOT'),
        blockTip: t('HELP_TIP'),
        blockInfo: t('HELP_NOTE'),
        blockWarning: t('HELP_WARNING'),
        blockDivider: t('HELP_BLOCK_DIVIDER'),
        blockFeature: t('HELP_BLOCK_FEATURE'),
        size: t('HELP_IMAGE_SIZE'),
        sizeSmall: t('HELP_IMAGE_SIZE_SM'),
        sizeMedium: t('HELP_IMAGE_SIZE_MD'),
        sizeLarge: t('HELP_IMAGE_SIZE_LG'),
    }));
    const calloutLabels = computed(() => ({
        note: t('HELP_NOTE'),
        tip: t('HELP_TIP'),
        warning: t('HELP_WARNING'),
    }));

    function payload() {
        return {
            title: title.value,
            summary: summary.value,
            content: withoutImageSources(document.value),
            category_id: categoryId.value,
            context_key: contextKey.value,
            audience_permission: audience.value.trim() || null,
            feature_path: featurePath.value.trim() || null,
            sort_order: Number(sortOrder.value) || 0,
            is_getting_started: gettingStarted.value,
            visibility: visibility.value,
            review_due_on: reviewDue.value || null,
            note: note.value,
            version: version.value,
        };
    }

    function apply(article: HelpArticle): void {
        applying.value = true;
        title.value = article.title;
        summary.value = article.summary ?? '';
        attachments.value = article.attachments ?? [];
        document.value = withImageSources(article.content, images.value);
        categoryId.value = article.category?.id ?? null;
        contextKey.value = article.context_key;
        audience.value = article.audience_permission ?? '';
        featurePath.value = article.feature_path ?? '';
        sortOrder.value = article.sort_order ?? 0;
        gettingStarted.value = article.is_getting_started;
        visibility.value = article.visibility === 'private' ? 'private' : 'authenticated';
        reviewDue.value = article.review_due_on ?? '';
        version.value = article.version;
        slug.value = article.slug;
        articleId.value = article.id;
        dirty.value = false;
        void nextTick(() => {
            applying.value = false;
            dirty.value = false;
        });
    }

    async function loadImages(files: HelpScreenshot[]): Promise<void> {
        for (const file of files) {
            if (images.value[String(file.id)]) continue;
            const image = await http.get(`help/screenshots/${file.id}`, { responseType: 'blob' });
            images.value[String(file.id)] = URL.createObjectURL(image.data);
        }
    }

    async function save(publish: boolean): Promise<void> {
        conflict.value = false;
        try {
            const response = articleId.value
                ? await http.request<{ data: HelpArticle }>({
                    method: publish ? 'post' : 'put',
                    url: publish ? `help/articles/${articleId.value}/publish` : `help/articles/${articleId.value}`,
                    data: payload(),
                })
                : await http.post<{ data: HelpArticle }>('help/articles', payload());
            apply(response.data.data);
            notice.value = publish ? t('HELP_PUBLISHED') : t('HELP_SAVED');
            if (!route.params.id) {
                await router.replace({ name: 'admin-help-edit', params: { id: response.data.data.id } });
            }
            await loadRevisions();
        } catch (error: unknown) {
            const status = (error as { response?: { status?: number } }).response?.status;
            conflict.value = status === 409;
        }
    }

    async function archive(): Promise<void> {
        if (!articleId.value) return;
        await http.post(`help/articles/${articleId.value}/archive`, { version: version.value });
        dirty.value = false;
        await router.push({ name: 'admin-help-manage' });
    }

    async function restore(number: number): Promise<void> {
        if (!articleId.value || !window.confirm(t('HELP_RESTORE_CONFIRM'))) return;
        const response = await http.post<{ data: HelpArticle }>(`help/articles/${articleId.value}/revisions/${number}/restore`, { version: version.value });
        apply(response.data.data);
        await loadRevisions();
    }

    async function upload(event: Event): Promise<void> {
        const input = event.target as HTMLInputElement;
        const file = input.files?.[0];
        if (!file || !articleId.value) return;
        if (altText.value.trim() === '') {
            notice.value = t('HELP_ALT_TEXT');
            input.value = '';
            return;
        }
        const body = new FormData();
        body.append('file', file);
        body.append('alt_text', altText.value.trim());
        if (caption.value.trim() !== '') body.append('caption', caption.value.trim());
        const response = await http.post<{ data: HelpScreenshot }>(`help/articles/${articleId.value}/screenshots`, body);
        const saved = response.data.data;
        const url = URL.createObjectURL(file);
        images.value[String(saved.id)] = url;
        attachments.value.push(saved);
        editor.value?.insertImage({
            id: saved.id,
            alt: saved.alt_text,
            caption: saved.caption,
            size: imageSize.value,
            src: url,
        });
        input.value = '';
        altText.value = '';
        caption.value = '';
        notice.value = t('HELP_SCREENSHOT_SAVED');
    }

    async function removeShot(id: number): Promise<void> {
        await http.delete(`help/screenshots/${id}`);
        const url = images.value[String(id)];
        if (url?.startsWith('blob:')) URL.revokeObjectURL(url);
        delete images.value[String(id)];
        attachments.value = attachments.value.filter((file) => file.id !== id);
        document.value = withoutImage(document.value, id);
    }

    async function loadRevisions(): Promise<void> {
        if (!articleId.value || !auth.hasPermission('view help revisions')) return;
        const response = await http.get<{ data: HelpRevision[] }>(`help/articles/${articleId.value}/revisions`);
        revisions.value = response.data.data ?? [];
    }

    watch([title, summary, document, categoryId, contextKey, audience, featurePath, visibility], () => {
        if (tracking.value && !applying.value) dirty.value = true;
    });

    onBeforeRouteLeave(() => {
        if (!dirty.value || !tracking.value) return true;
        return window.confirm(t('HELP_UNSAVED'));
    });

    function focusScreenshot(): void {
        if (!articleId.value) {
            notice.value = t('HELP_IMAGE_AFTER_SAVE');
            return;
        }
        fileInput.value?.click();
    }

    onMounted(async () => {
        await loadDestinations();
        const listed = await http.get<{ data: HelpCategory[] }>('help/categories');
        categories.value = listed.data.data ?? [];
        const id = Number(route.params.id);
        if (Number.isFinite(id)) {
            const response = await http.get<{ data: HelpArticle }>(`help/articles/${id}`);
            await loadImages(response.data.data.attachments ?? []);
            apply(response.data.data);
            await loadRevisions();
        }
        tracking.value = true;
    });

    onBeforeUnmount(() => {
        for (const url of Object.values(images.value)) {
            if (url.startsWith('blob:')) URL.revokeObjectURL(url);
        }
    });
</script>
