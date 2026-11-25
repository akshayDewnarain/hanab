<template>
    <UiWidget :description="synced ? t('DASHBOARD_ACTIVITY_UPDATED') : ''" :title="t('DASHBOARD_ACTIVITY_TITLE')">
        <template #action>
            <button type="button" class="cursor-pointer text-xs font-semibold text-emphasis" @click="openMessages">
                {{ t('DASHBOARD_ACTIVITY_ALL') }}
            </button>
        </template>
        <div ref="root">
            <UiActivityTimeline
                :empty-hint="t('DASHBOARD_ACTIVITY_EMPTY_HINT')"
                :empty-title="t('DASHBOARD_ACTIVITY_EMPTY')"
                :error="error"
                :error-label="t('DASHBOARD_ACTIVITY_ERROR')"
                :groups="groups"
                :has-more="hasMore"
                :load-more-error="loadMoreError"
                :load-more-label="t('DASHBOARD_ACTIVITY_MORE')"
                :loading="loading"
                :loading-label="t('DASHBOARD_ACTIVITY_MORE_LOADING')"
                :loading-more="loadingMore"
                :pending-count="pending.length"
                :pending-label="t('DASHBOARD_ACTIVITY_NEW', { count: pending.length })"
                :retry-label="t('DASHBOARD_RETRY')"
                @load-more="loadOlder"
                @retry="loadInitial"
                @reveal="revealPending"
            >
                <template #action="{ item }">
                    <button type="button" class="cursor-pointer text-xs font-semibold text-emphasis" @click="open(item.id)">
                        {{ actionLabel(item.id) }}
                    </button>
                </template>
            </UiActivityTimeline>
        </div>
    </UiWidget>
</template>

<script lang="ts" setup>
    import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
    import { UiActivityTimeline, UiWidget, type UiTimelineGroup, type UiTimelineItem, type UiTimelineTone } from '@freelance/vue-ui';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { formatMessageDate, messageIcon, messageSummary } from '@/modules/messages/messagePresentation';
    import http from '@/utils/http';

    defineOptions({ name: 'DashboardActivity' });

    type ActivityRecord = {
        id: string;
        type: string;
        title: string | null;
        body: string | null;
        subject_name: string | null;
        detail: string | null;
        years: number | null;
        subject_employee_id: number | null;
        published_at: string | null;
    };

    type ActivityPage = {
        data: ActivityRecord[];
        meta: {
            has_more: boolean;
            next_cursor: string | null;
            newest_cursor: string | null;
        };
    };

    const pageSize = 8;
    const { t, locale } = useI18n();
    const router = useRouter();
    const root = ref<HTMLElement | null>(null);
    const loading = ref(true);
    const loadingMore = ref(false);
    const error = ref(false);
    const loadMoreError = ref(false);
    const synced = ref(false);
    const hasMore = ref(false);
    const nextCursor = ref<string | null>(null);
    const newestCursor = ref<string | null>(null);
    const items = ref<ActivityRecord[]>([]);
    const pending = ref<ActivityRecord[]>([]);
    const freshIds = ref<Set<string>>(new Set());
    let requestId = 0;
    let polling = false;
    let timer: ReturnType<typeof setInterval> | null = null;
    let freshTimer: ReturnType<typeof setTimeout> | null = null;

    const groups = computed<UiTimelineGroup[]>(() => {
        const buckets = new Map<string, { label: string; items: UiTimelineItem[] }>();
        for (const record of items.value) {
            const key = dayKey(record.published_at);
            const bucket = buckets.get(key) ?? { label: dayLabel(record.published_at), items: [] };
            bucket.items.push(toItem(record));
            buckets.set(key, bucket);
        }
        return [...buckets.entries()].map(([id, bucket]) => ({ id, label: bucket.label, items: bucket.items }));
    });

    function toneFor(type: string): UiTimelineTone {
        if (type === 'introduction') return 'info';
        if (type === 'certificate') return 'accent';
        if (type === 'birthday' || type === 'anniversary') return 'warning';
        if (type === 'contract') return 'primary';
        return 'neutral';
    }

    function toItem(record: ActivityRecord): UiTimelineItem {
        const summary = messageSummary(record, t);
        return {
            id: record.id,
            title: summary.title,
            description: summary.text,
            timeLabel: whenLabel(record.published_at),
            icon: messageIcon(record.type),
            tone: toneFor(record.type),
            fresh: freshIds.value.has(record.id),
        };
    }

    function dayKey(value: string | null): string {
        const date = value ? new Date(value) : new Date();
        if (Number.isNaN(date.getTime())) return 'unknown';
        return `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
    }

    function dayLabel(value: string | null): string {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        const start = (item: Date) => new Date(item.getFullYear(), item.getMonth(), item.getDate()).getTime();
        const diff = Math.round((start(new Date()) - start(date)) / 86400000);
        if (diff === 0) return t('DASHBOARD_TODAY');
        if (diff === 1) return t('DASHBOARD_YESTERDAY');
        return new Intl.DateTimeFormat(locale.value === 'nl' ? 'nl' : 'en', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(date);
    }

    function whenLabel(value: string | null): string {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        const minutes = Math.round((date.getTime() - Date.now()) / 60000);
        const formatter = new Intl.RelativeTimeFormat(locale.value === 'nl' ? 'nl' : 'en', { numeric: 'auto' });
        const abs = Math.abs(minutes);
        if (abs < 60) return formatter.format(minutes, 'minute');
        const hours = Math.round(minutes / 60);
        if (Math.abs(hours) < 24) return formatter.format(hours, 'hour');
        const days = Math.round(hours / 24);
        if (Math.abs(days) < 7) return formatter.format(days, 'day');
        return formatMessageDate(value, locale.value);
    }

    function actionLabel(id: string): string {
        const record = items.value.find((item) => item.id === id) ?? pending.value.find((item) => item.id === id);
        return record?.subject_employee_id ? t('DASHBOARD_VIEW_PROFILE') : t('DASHBOARD_READ_MESSAGE');
    }

    function open(id: string): void {
        const record = items.value.find((item) => item.id === id);
        if (record?.subject_employee_id) {
            void router.push({ name: 'admin-employees-detail', params: { id: String(record.subject_employee_id) } });
            return;
        }
        openMessages();
    }

    function openMessages(): void {
        void router.push({ name: 'admin-messages' });
    }

    function markFresh(records: ActivityRecord[]): void {
        const next = new Set(freshIds.value);
        records.forEach((record) => next.add(record.id));
        freshIds.value = next;
        if (freshTimer) clearTimeout(freshTimer);
        freshTimer = setTimeout(() => {
            freshIds.value = new Set();
        }, 700);
    }

    function mergeNewest(records: ActivityRecord[]): void {
        const known = new Set(items.value.map((item) => item.id));
        const incoming = records.filter((record) => !known.has(record.id));
        if (incoming.length === 0) return;
        items.value = [...incoming, ...items.value];
        markFresh(incoming);
    }

    function readingOlder(): boolean {
        const element = root.value;
        if (!element) return false;
        let node: HTMLElement | null = element.parentElement;
        while (node) {
            const style = getComputedStyle(node);
            if (style.overflowY === 'auto' || style.overflowY === 'scroll') {
                return element.getBoundingClientRect().top < node.getBoundingClientRect().top - 8;
            }
            node = node.parentElement;
        }
        return element.getBoundingClientRect().top < 0;
    }

    async function fetchPage(params: Record<string, string | number>): Promise<ActivityPage> {
        const response = await http.get<ActivityPage>('dashboard/activity', { params });
        return response.data;
    }

    async function loadInitial(): Promise<void> {
        const current = ++requestId;
        loading.value = items.value.length === 0;
        error.value = false;
        try {
            const page = await fetchPage({ limit: pageSize });
            if (current !== requestId) return;
            items.value = page.data ?? [];
            hasMore.value = page.meta?.has_more === true;
            nextCursor.value = page.meta?.next_cursor ?? null;
            newestCursor.value = page.meta?.newest_cursor ?? null;
            pending.value = [];
        } catch {
            if (current !== requestId) return;
            error.value = true;
        } finally {
            if (current === requestId) loading.value = false;
        }
    }

    async function loadOlder(): Promise<void> {
        if (loadingMore.value || !nextCursor.value) return;
        loadingMore.value = true;
        loadMoreError.value = false;
        try {
            const page = await fetchPage({ limit: pageSize, cursor: nextCursor.value });
            const known = new Set(items.value.map((item) => item.id));
            items.value = [...items.value, ...(page.data ?? []).filter((record) => !known.has(record.id))];
            hasMore.value = page.meta?.has_more === true;
            nextCursor.value = page.meta?.next_cursor ?? null;
        } catch {
            loadMoreError.value = true;
        } finally {
            loadingMore.value = false;
        }
    }

    async function poll(): Promise<void> {
        if (polling || document.hidden || !newestCursor.value || loading.value) return;
        polling = true;
        try {
            const page = await fetchPage({ limit: 20, after: newestCursor.value });
            synced.value = true;
            const known = new Set([...items.value, ...pending.value].map((item) => item.id));
            const incoming = (page.data ?? []).filter((record) => !known.has(record.id));
            if (incoming.length > 0 && page.meta?.newest_cursor) newestCursor.value = page.meta.newest_cursor;
            if (incoming.length === 0) return;
            if (readingOlder()) {
                pending.value = [...incoming, ...pending.value];
                return;
            }
            mergeNewest(incoming);
        } catch {
            synced.value = false;
        } finally {
            polling = false;
        }
    }

    function revealPending(): void {
        if (pending.value.length === 0) return;
        const incoming = pending.value;
        pending.value = [];
        mergeNewest(incoming);
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        root.value?.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
    }

    function onVisible(): void {
        if (!document.hidden) void poll();
    }

    onMounted(() => {
        void loadInitial();
        timer = setInterval(() => void poll(), 30000);
        document.addEventListener('visibilitychange', onVisible);
        window.addEventListener('focus', onVisible);
    });

    onBeforeUnmount(() => {
        if (timer) clearInterval(timer);
        if (freshTimer) clearTimeout(freshTimer);
        document.removeEventListener('visibilitychange', onVisible);
        window.removeEventListener('focus', onVisible);
    });
</script>
