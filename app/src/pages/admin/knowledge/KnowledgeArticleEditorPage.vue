<template>
    <PageLayout :label="t('KNOWLEDGE_EDIT')" class="overflow-y-auto">
        <form class="mx-auto grid w-full max-w-6xl gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]" @submit.prevent="save(false)">
            <div>
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <router-link v-if="slug" class="text-sm font-medium text-primary" :to="{ name: 'admin-knowledge-article', params: { slug } }">
                        {{ t('KNOWLEDGE_BACK_TO_ARTICLE') }}
                    </router-link>
                    <router-link v-else class="text-sm font-medium text-primary" :to="{ name: 'admin-knowledge' }">{{ t('KNOWLEDGE_BACK') }}</router-link>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="h-9 rounded-lg border border-list-border px-3 text-sm" @click="previewing = !previewing">
                            {{ t('KNOWLEDGE_PREVIEW') }}
                        </button>
                        <button type="submit" class="h-9 rounded-lg border border-list-border px-3 text-sm font-medium" :disabled="busy">
                            {{ t('KNOWLEDGE_SAVE_DRAFT') }}
                        </button>
                        <button v-if="canPublish" type="button" class="h-9 rounded-lg bg-primary px-3 text-sm font-semibold text-white" :disabled="busy" @click="save(true)">
                            {{ t('KNOWLEDGE_PUBLISH') }}
                        </button>
                    </div>
                </div>
                <p v-if="notice" class="text-sm text-list-text-muted">{{ notice }}</p>
                <p v-if="conflict" class="text-sm text-red-700">{{ t('KNOWLEDGE_CONFLICT') }}</p>
                <input v-model="title" class="mt-2 h-12 w-full rounded-xl border border-list-border bg-list-surface px-4 text-xl font-semibold" :aria-label="t('KNOWLEDGE_ARTICLE_TITLE')" />
                <div v-if="previewing" class="mt-4 rounded-xl border border-list-border bg-list-surface p-5">
                    <UiRichTextContent :document="document" />
                </div>
                <div v-else class="mt-4 rounded-xl border border-list-border bg-list-surface p-3">
                    <UiRichTextEditor v-model="document" :labels="editorLabels" :max-length="40000" />
                </div>
                <section v-if="revisions.length" class="mt-4 rounded-xl border border-list-border bg-list-surface p-4">
                    <h2 class="m-0 text-sm font-semibold">{{ t('KNOWLEDGE_REVISIONS') }}</h2>
                    <ul class="mt-2 list-none p-0">
                        <li v-for="revision in revisions" :key="revision.number" class="flex items-center justify-between gap-3 py-1 text-sm">
                            <span>{{ revision.number }} · {{ revision.editor?.name }} · {{ revision.note }}</span>
                            <button v-if="canRestore" type="button" class="font-medium text-primary" @click="restore(revision.number)">
                                {{ t('KNOWLEDGE_RESTORE') }}
                            </button>
                        </li>
                    </ul>
                </section>
            </div>
            <aside class="flex flex-col gap-3 rounded-xl border border-list-border bg-list-surface p-4">
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('KNOWLEDGE_SUMMARY') }}</span>
                    <textarea v-model="summary" rows="4" class="mt-1 w-full rounded-lg border border-list-border px-3 py-2" />
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('KNOWLEDGE_CATEGORY') }}</span>
                    <ChoiceDropdown
                        v-model="categoryId"
                        class="mt-1"
                        :options="[{ value: null, label: t('KNOWLEDGE_NO_CATEGORY') }, ...categories.map((category) => ({ value: category.id, label: category.name }))]"
                    />
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('KNOWLEDGE_TAGS') }}</span>
                    <input v-model="tags" class="mt-1 h-10 w-full rounded-lg border border-list-border px-3" />
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('KNOWLEDGE_VISIBILITY') }}</span>
                    <ChoiceDropdown
                        v-model="visibility"
                        class="mt-1"
                        :options="[
                            { value: 'authenticated', label: t('KNOWLEDGE_VISIBILITY_AUTHENTICATED') },
                            { value: 'private', label: t('KNOWLEDGE_VISIBILITY_PRIVATE') },
                        ]"
                    />
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('KNOWLEDGE_REVIEW_DATE') }}</span>
                    <input v-model="reviewDue" type="date" class="mt-1 h-10 w-full rounded-lg border border-list-border px-3" />
                </label>
                <label class="text-sm">
                    <span class="text-list-text-muted">{{ t('KNOWLEDGE_CHANGE_NOTE') }}</span>
                    <input v-model="note" class="mt-1 h-10 w-full rounded-lg border border-list-border px-3" />
                </label>
                <label v-if="skills.length" class="text-sm">
                    <span class="text-list-text-muted">{{ t('KNOWLEDGE_SKILLS') }}</span>
                    <ChoiceDropdown
                        v-model="skillIds"
                        multiple
                        class="mt-1"
                        :placeholder="t('KNOWLEDGE_SKILLS')"
                        :options="skills.map((skill) => ({ value: skill.id, label: skill.name }))"
                    />
                </label>
                <label v-if="certificates.length" class="text-sm">
                    <span class="text-list-text-muted">{{ t('KNOWLEDGE_CERTIFICATES') }}</span>
                    <ChoiceDropdown
                        v-model="certificateIds"
                        multiple
                        class="mt-1"
                        :placeholder="t('KNOWLEDGE_CERTIFICATES')"
                        :options="certificates.map((certificate) => ({ value: certificate.id, label: certificate.name }))"
                    />
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="featured" type="checkbox" />
                    {{ t('KNOWLEDGE_FEATURED') }}
                </label>
                <label v-if="articleId" class="text-sm">
                    <span class="text-list-text-muted">{{ t('KNOWLEDGE_UPLOAD') }}</span>
                    <input class="mt-1 block w-full text-sm" type="file" @change="upload" />
                </label>
                <button v-if="articleId && canPublish" type="button" class="h-9 rounded-lg border border-list-border text-sm" @click="archive">
                    {{ t('KNOWLEDGE_ARCHIVE') }}
                </button>
            </aside>
        </form>
    </PageLayout>
</template>

<script lang="ts" setup>
    import { computed, nextTick, onMounted, ref, watch } from 'vue';
    import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { PageLayout } from '@freelance/vue-ui';
    import ChoiceDropdown from '@/components/inputs/ChoiceDropdown.vue';
    import { emptyDocument, UiRichTextContent, UiRichTextEditor, type RichTextDocument, type RichTextEditorLabels } from '@freelance/vue-ui/editor';
    import http from '@/utils/http';
    import { useAuthStore } from '@/stores/auth';
    import type { KnowledgeArticle, KnowledgeCategory, KnowledgeRevision } from '@/modules/types/knowledge/Knowledge.ts';

    const { t } = useI18n();
    const route = useRoute();
    const router = useRouter();
    const auth = useAuthStore();
    const title = ref('');
    const summary = ref('');
    const document = ref<RichTextDocument>(emptyDocument());
    const categoryId = ref<number | null>(null);
    const tags = ref('');
    const visibility = ref<'private' | 'authenticated'>('authenticated');
    const reviewDue = ref('');
    const note = ref('');
    const featured = ref(false);
    const version = ref(1);
    const slug = ref('');
    const articleId = ref<number | null>(null);
    const categories = ref<KnowledgeCategory[]>([]);
    const skills = ref<Array<{ id: number; name: string }>>([]);
    const certificates = ref<Array<{ id: number; name: string }>>([]);
    const skillIds = ref<number[]>([]);
    const certificateIds = ref<number[]>([]);
    const revisions = ref<KnowledgeRevision[]>([]);
    const previewing = ref(false);
    const busy = ref(false);
    const dirty = ref(false);
    const notice = ref('');
    const conflict = ref(false);
    const tracking = ref(false);
    const canPublish = computed(() => auth.hasPermission('publish knowledge articles'));
    const canRestore = computed(() => auth.hasPermission('restore knowledge revisions'));
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
    }));

    function payload() {
        return {
            title: title.value,
            summary: summary.value,
            content: document.value,
            category_id: categoryId.value,
            tags: tags.value.split(',').map((tag) => tag.trim()).filter(Boolean),
            visibility: visibility.value,
            featured: featured.value,
            review_due_on: reviewDue.value || null,
            note: note.value || null,
            version: version.value,
            skill_ids: skillIds.value.map((id) => Number(id)),
            certificate_ids: certificateIds.value.map((id) => Number(id)),
        };
    }

    function apply(article: KnowledgeArticle): void {
        articleId.value = article.id;
        slug.value = article.slug;
        title.value = article.title;
        summary.value = article.summary ?? '';
        document.value = article.content;
        categoryId.value = article.category?.id ?? null;
        tags.value = article.tags.map((tag) => tag.name).join(', ');
        visibility.value = article.visibility === 'private' ? 'private' : 'authenticated';
        reviewDue.value = article.review_due_on ?? '';
        featured.value = article.featured;
        skillIds.value = article.skill_ids ?? [];
        certificateIds.value = article.certificate_ids ?? [];
        version.value = article.version;
        void nextTick(() => {
            dirty.value = false;
        });
    }

    async function save(publish: boolean): Promise<void> {
        busy.value = true;
        conflict.value = false;
        notice.value = '';
        try {
            const response = articleId.value
                ? await http.request<{ data: KnowledgeArticle }>({
                    url: publish ? `knowledge/articles/${articleId.value}/publish` : `knowledge/articles/${articleId.value}`,
                    method: publish ? 'post' : 'put',
                    data: payload(),
                })
                : await http.post<{ data: KnowledgeArticle }>('knowledge/articles', payload());
            apply(response.data.data);
            notice.value = publish ? t('KNOWLEDGE_PUBLISHED') : t('KNOWLEDGE_SAVED');
            if (!route.params.id) {
                await router.replace({ name: 'admin-knowledge-edit', params: { id: response.data.data.id } });
            }
            await loadRevisions();
        } catch (error: unknown) {
            const status = (error as { response?: { status?: number } }).response?.status;
            conflict.value = status === 409;
        } finally {
            busy.value = false;
        }
    }

    async function archive(): Promise<void> {
        if (!articleId.value) return;
        await http.post(`knowledge/articles/${articleId.value}/archive`, { version: version.value });
        dirty.value = false;
        await router.push({ name: 'admin-knowledge-manage' });
    }

    async function restore(number: number): Promise<void> {
        if (!articleId.value || !window.confirm(t('KNOWLEDGE_RESTORE_CONFIRM'))) return;
        const response = await http.post<{ data: KnowledgeArticle }>(`knowledge/articles/${articleId.value}/revisions/${number}/restore`, {
            version: version.value,
        });
        apply(response.data.data);
        await loadRevisions();
    }

    async function upload(event: Event): Promise<void> {
        const input = event.target as HTMLInputElement;
        const file = input.files?.[0];
        if (!file || !articleId.value) return;
        const body = new FormData();
        body.append('file', file);
        await http.post(`knowledge/articles/${articleId.value}/attachments`, body);
        input.value = '';
    }

    async function loadRevisions(): Promise<void> {
        if (!articleId.value || !auth.hasPermission('view knowledge revisions')) return;
        const response = await http.get<{ data: KnowledgeRevision[] }>(`knowledge/articles/${articleId.value}/revisions`);
        revisions.value = response.data.data ?? [];
    }

    watch([title, summary, document, tags, visibility, featured], () => {
        if (tracking.value) dirty.value = true;
    }, { deep: true });

    onBeforeRouteLeave(() => {
        if (!dirty.value) return true;
        return window.confirm(t('KNOWLEDGE_UNSAVED'));
    });

    onMounted(async () => {
        const listed = await http.get<{ data: KnowledgeCategory[] }>('knowledge/categories');
        categories.value = listed.data.data ?? [];
        try {
            const [skillResponse, certificateResponse] = await Promise.all([
                http.get<{ data: Array<{ id: number; name: string }> }>('skills', { params: { per_page: 100 } }),
                http.get<{ data: Array<{ id: number; name: string }> }>('certificates', { params: { per_page: 100 } }),
            ]);
            skills.value = skillResponse.data.data ?? [];
            certificates.value = certificateResponse.data.data ?? [];
        } catch {
            skills.value = [];
            certificates.value = [];
        }
        const id = Number(route.params.id);
        if (Number.isFinite(id)) {
            const response = await http.get<{ data: KnowledgeArticle }>(`knowledge/articles/${id}`);
            apply(response.data.data);
            await loadRevisions();
        }
        tracking.value = true;
    });
</script>
