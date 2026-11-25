import { computed, ref } from 'vue';
import type { MessageCard, RichTextDocument } from '@freelance/vue-ui';
import { parseMessageBody } from '@freelance/vue-ui';
import { useI18n } from 'vue-i18n';
import http from '@/utils/http';

type ApiMessage = {
    id: number;
    type: string;
    category: string;
    status: string;
    title: string | null;
    body: string | null;
    subject_name: string | null;
    detail: string | null;
    years: number | null;
    author_name: string | null;
    published_at: string | null;
    updated_at: string | null;
    reaction: string | null;
    reactions: { emoji: string; count: number }[];
};

type Preference = {
    people: boolean;
    milestones: boolean;
    company: boolean;
    company_locked: boolean;
    push_notifications: boolean;
};

type Inbox = {
    data: ApiMessage[];
    drafts: ApiMessage[];
    can_publish: boolean;
    can_create: boolean;
    can_update: boolean;
    can_delete: boolean;
};

export type FeedMessage = {
    id: string;
    type: string;
    category: string;
    status: string;
    title: string;
    text: string;
    rawBody: string;
    format: 'text' | 'document';
    document: RichTextDocument | null;
    subjectName: string | null;
    detail: string | null;
    authorName: string | null;
    publishedAt: string | null;
    updatedAt: string | null;
    reaction: string | null;
    reactions: { emoji: string; count: number }[];
};

export type MessageDraftInput = {
    id?: string;
    title: string;
    body: string;
    status?: 'draft' | 'published';
};

const messages = ref<ApiMessage[]>([]);
const library = ref<ApiMessage[]>([]);
const drafts = ref<ApiMessage[]>([]);
const canPublish = ref(false);
const canCreate = ref(false);
const canUpdate = ref(false);
const canDelete = ref(false);
const manageReady = ref(false);
const preference = ref<Preference>({
    people: true,
    milestones: true,
    company: true,
    company_locked: true,
    push_notifications: true,
});
const failed = ref(false);
const manageFailed = ref(false);
const isLoading = ref(false);

function clip(text: string): string {
    const flat = text.replace(/\s+/g, ' ').trim();
    return flat.length > 180 ? `${flat.slice(0, 179)}…` : flat;
}

function display(message: ApiMessage, translate: (key: string, values?: Record<string, unknown>) => string) {
    const name = message.subject_name ?? '';
    if (message.type === 'birthday') {
        return { title: translate('MESSAGE_BIRTHDAY_TITLE'), text: translate('MESSAGE_BIRTHDAY_BODY', { name }), format: 'text' as const, document: null };
    }
    if (message.type === 'anniversary') {
        return {
            title: translate('MESSAGE_ANNIVERSARY_TITLE'),
            text: translate('MESSAGE_ANNIVERSARY_BODY', { name, years: message.years ?? 0 }),
            format: 'text' as const,
            document: null,
        };
    }
    if (message.type === 'introduction') {
        return {
            title: translate('MESSAGE_INTRODUCTION_TITLE'),
            text: message.detail
                ? translate('MESSAGE_INTRODUCTION_BODY', { name, role: message.detail })
                : translate('MESSAGE_INTRODUCTION_BODY_PLAIN', { name }),
            format: 'text' as const,
            document: null,
        };
    }
    if (message.type === 'certificate') {
        return {
            title: translate('MESSAGE_CERTIFICATE_TITLE'),
            text: translate('MESSAGE_CERTIFICATE_BODY', { name, certificate: message.detail ?? '' }),
            format: 'text' as const,
            document: null,
        };
    }

    const parsed = parseMessageBody(message.body);
    return { title: message.title ?? '', text: parsed.text, format: parsed.format, document: parsed.document };
}

function toFeed(message: ApiMessage, translate: (key: string, values?: Record<string, unknown>) => string): FeedMessage {
    const shown = display(message, translate);
    return {
        id: String(message.id),
        type: message.type,
        category: message.category,
        status: message.status,
        title: shown.title,
        text: shown.text,
        rawBody: message.body ?? '',
        format: shown.format,
        document: shown.document,
        subjectName: message.subject_name,
        detail: message.detail,
        authorName: message.author_name,
        publishedAt: message.published_at,
        updatedAt: message.updated_at,
        reaction: message.reaction,
        reactions: message.reactions ?? [],
    };
}

export function useMessages() {
    const { t } = useI18n();

    const cards = computed<MessageCard[]>(() =>
        messages.value.map((message) => {
            const shown = display(message, t);
            return {
                id: String(message.id),
                title: shown.title,
                body: clip(shown.text),
                meta: t(`MESSAGE_CATEGORY_${message.category.toUpperCase()}`),
                status: message.status,
                reaction: message.reaction,
                reactions: (message.reactions ?? []).map((item) => ({ id: item.emoji, count: item.count })),
            };
        }),
    );
    const feed = computed(() => messages.value.map((message) => toFeed(message, t)));
    const managed = computed(() => library.value.map((message) => toFeed(message, t)));
    const draftItems = computed(() => drafts.value.map((message) => toFeed(message, t)));

    async function loadPreferences(): Promise<void> {
        const preferences = await http.get<{ data: Preference }>('message-preferences');
        preference.value = preferences.data.data;
    }

    async function load(scope: 'feed' | 'manage' = 'feed'): Promise<void> {
        isLoading.value = true;
        if (scope === 'manage') manageFailed.value = false;
        else failed.value = false;
        try {
            const [inbox, preferences] = await Promise.all([
                http.get<Inbox>('messages', { params: scope === 'manage' ? { scope: 'manage' } : {} }),
                http.get<{ data: Preference }>('message-preferences'),
            ]);
            if (scope === 'manage') {
                library.value = inbox.data.data ?? [];
                manageReady.value = true;
            } else {
                messages.value = inbox.data.data ?? [];
            }
            drafts.value = inbox.data.drafts ?? [];
            canPublish.value = inbox.data.can_publish;
            canCreate.value = inbox.data.can_create;
            canUpdate.value = inbox.data.can_update;
            canDelete.value = inbox.data.can_delete;
            preference.value = preferences.data.data;
        } catch {
            if (scope === 'manage') manageFailed.value = true;
            else failed.value = true;
        } finally {
            isLoading.value = false;
        }
    }

    async function reload(): Promise<void> {
        await load('feed');
        if (manageReady.value) await load('manage');
    }

    type ReactionJob = {
        want: string | null;
        baseline: ApiMessage;
        settled: string | null;
        running: boolean;
    };

    const reactionJobs = new Map<string, ReactionJob>();

    function cloneMessage(message: ApiMessage): ApiMessage {
        return { ...message, reactions: (message.reactions ?? []).map((item) => ({ ...item })) };
    }

    function withReaction(message: ApiMessage, emoji: string | null): ApiMessage {
        if ((message.reaction ?? null) === emoji) return message;
        const reactions = (message.reactions ?? []).map((item) => ({ ...item }));
        const adjust = (value: string, delta: number) => {
            const row = reactions.find((item) => item.emoji === value);
            if (row) row.count = Math.max(0, row.count + delta);
            else if (delta > 0) reactions.push({ emoji: value, count: delta });
        };
        if (message.reaction) adjust(message.reaction, -1);
        if (emoji) adjust(emoji, 1);
        return { ...message, reaction: emoji, reactions };
    }

    function replaceMessage(next: ApiMessage): void {
        const id = String(next.id);
        const swap = (list: ApiMessage[]) => list.map((item) => (String(item.id) === id ? next : item));
        messages.value = swap(messages.value);
        library.value = swap(library.value);
    }

    function findMessage(id: string): ApiMessage | undefined {
        return messages.value.find((item) => String(item.id) === id) ?? library.value.find((item) => String(item.id) === id);
    }

    async function pumpReaction(id: string): Promise<void> {
        const job = reactionJobs.get(id);
        if (!job || job.running) return;
        job.running = true;
        let failed = false;
        try {
            while (job.want !== job.settled) {
                const sending = job.want;
                try {
                    const response = await http.put<{ data: ApiMessage }>(`messages/${id}/reaction`, { emoji: sending });
                    job.baseline = response.data.data;
                    job.settled = response.data.data.reaction ?? null;
                    replaceMessage(job.want === sending ? job.baseline : withReaction(job.baseline, job.want));
                    if (job.want === sending) break;
                } catch (error) {
                    if (job.want !== sending) continue;
                    replaceMessage(job.baseline);
                    failed = true;
                    throw error;
                }
            }
        } finally {
            job.running = false;
            if (!failed && job.want !== job.settled) void pumpReaction(id);
            else reactionJobs.delete(id);
        }
    }

    async function react(id: string, emoji: string | null): Promise<void> {
        const current = findMessage(id);
        if (!current) return;
        let job = reactionJobs.get(id);
        if (!job) {
            const baseline = cloneMessage(current);
            job = { want: emoji, baseline, settled: baseline.reaction ?? null, running: false };
            reactionJobs.set(id, job);
        } else {
            job.want = emoji;
        }
        replaceMessage(withReaction(job.baseline, job.want));
        await pumpReaction(id);
    }

    async function publish(id: string): Promise<void> {
        await http.post(`messages/${id}/publish`);
        await reload();
    }

    async function saveCompanyMessage(input: MessageDraftInput): Promise<void> {
        if (input.id) {
            await http.put(`messages/${input.id}`, { title: input.title, body: input.body });
            if (input.status === 'published') await http.post(`messages/${input.id}/publish`);
        } else {
            await http.post('messages', {
                title: input.title,
                body: input.body,
                status: input.status ?? 'draft',
            });
        }
        await reload();
    }

    async function removeDraft(id: string): Promise<void> {
        await http.delete(`messages/${id}`);
        await reload();
    }

    async function savePreference(next: Preference, refreshFeed = true): Promise<Preference> {
        const response = await http.put<{ data: Preference }>('message-preferences', next);
        const saved = response.data.data;
        if (refreshFeed) {
            preference.value = saved;
            await reload();
        }
        return saved;
    }

    return {
        cards,
        feed,
        managed,
        draftItems,
        canPublish,
        canCreate,
        canUpdate,
        canDelete,
        preference,
        failed,
        manageFailed,
        isLoading,
        load,
        loadPreferences,
        react,
        publish,
        saveCompanyMessage,
        removeDraft,
        savePreference,
    };
}
