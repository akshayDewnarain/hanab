import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import type { GlobalSearchGroup, GlobalSearchLabels, GlobalSearchQuickLink, GlobalSearchResult } from '@freelance/vue-ui';
import http from '@/utils/http';
import { knowledgeBaseVisible } from '@/modules/knowledge/visibility';

export type ExpertiseMatch = {
    kind: 'skill' | 'certificate';
    name: string;
    code: string | null;
    matched_on: 'name' | 'code' | 'synonym';
    synonym: string | null;
};

export type ExpertisePerson = {
    id: number;
    name: string;
    role: string | null;
    location: string | null;
    email: string | null;
    phone: string | null;
    matches: ExpertiseMatch[];
};

const query = ref('');
const people = ref<ExpertisePerson[]>([]);
const articles = ref<Array<{ slug: string; title: string; summary: string | null }>>([]);
const guides = ref<Array<{ slug: string; title: string; summary: string | null }>>([]);
const isLoading = ref(false);
const failed = ref(false);

let requestId = 0;
let timer: ReturnType<typeof setTimeout> | null = null;

// Module scope, so switching between the desktop top bar and the phone
// shell does not drop the watcher with the component that created it.
if (typeof window !== 'undefined') {
    watch(query, schedule);
}

function schedule(): void {
    if (timer !== null) {
        clearTimeout(timer);
        timer = null;
    }

    const term = query.value.trim();
    if (term === '') {
        requestId += 1;
        people.value = [];
        articles.value = [];
        guides.value = [];
        isLoading.value = false;
        failed.value = false;
        return;
    }

    people.value = [];
    articles.value = [];
    guides.value = [];
    failed.value = false;
    isLoading.value = true;
    timer = setTimeout(() => {
        void run();
    }, 280);
}

async function run(): Promise<void> {
    const current = ++requestId;
    const term = query.value.trim();
    if (term === '') {
        people.value = [];
        articles.value = [];
        guides.value = [];
        isLoading.value = false;
        return;
    }

    try {
        const articleRequest = knowledgeBaseVisible
            ? http.get<{ data: Array<{ slug: string; title: string; summary: string | null }> }>('knowledge/search', { params: { q: term } })
            : Promise.resolve(null);
        const helpRequest = http.get<{ data: Array<{ slug: string; title: string; summary: string | null }> }>('help/search', { params: { q: term } });
        const [peopleResult, articleResult, helpResult] = await Promise.allSettled([
            http.get<{ data: ExpertisePerson[] }>('expertise-search', { params: { query: term } }),
            articleRequest,
            helpRequest,
        ]);
        if (current !== requestId) {
            return;
        }
        people.value = peopleResult.status === 'fulfilled' ? (peopleResult.value.data.data ?? []) : [];
        articles.value =
            knowledgeBaseVisible && articleResult.status === 'fulfilled' && articleResult.value
                ? (articleResult.value.data.data ?? [])
                : [];
        guides.value = helpResult.status === 'fulfilled' ? (helpResult.value.data.data ?? []) : [];
        failed.value = peopleResult.status === 'rejected';
    } catch {
        if (current !== requestId) {
            return;
        }
        people.value = [];
        articles.value = [];
        guides.value = [];
        failed.value = true;
    } finally {
        if (current === requestId) {
            isLoading.value = false;
        }
    }
}

function matchLine(match: ExpertiseMatch, translate: (key: string, values?: Record<string, string>) => string): string {
    const kind = match.kind === 'certificate' ? translate('GENERAL_CERTIFICATE') : translate('GENERAL_SKILL');
    if (match.matched_on === 'synonym' && match.synonym) {
        return translate('EXPERTISE_SEARCH_MATCH_SYNONYM', { kind, name: match.name, synonym: match.synonym });
    }
    if (match.matched_on === 'code' && match.code) {
        return translate('EXPERTISE_SEARCH_MATCH_CODE', { kind, name: match.name, code: match.code });
    }
    return translate('EXPERTISE_SEARCH_MATCH_NAME', { kind, name: match.name });
}

export function useExpertiseSearch() {
    const { t } = useI18n();
    const router = useRouter();

    const groups = computed<GlobalSearchGroup[]>(() => {
        if (!query.value.trim()) {
            return [];
        }

        const next: GlobalSearchGroup[] = [];
        if (people.value.length > 0) {
            next.push({
                id: 'people',
                label: t('EXPERTISE_SEARCH_PEOPLE'),
                results: people.value.map((person) => ({
                    id: String(person.id),
                    title: person.name,
                    icon: 'material-symbols:person-outline-rounded',
                    subtitle: [person.role, person.location].filter((part): part is string => Boolean(part)).join(' · '),
                    detail: (person.matches ?? []).map((match) => matchLine(match, t)).join(' · '),
                    disclosures: (person.matches ?? [])
                        .filter((match) => Boolean(match.code))
                        .map((match) => ({
                            id: `${person.id}-${match.kind}-${match.code}`,
                            trigger: match.code as string,
                            text: match.name,
                            label: t('EXPERTISE_CODE_REVEAL', { code: match.code, name: match.name }),
                        })),
                    links: [
                        person.email ? { label: person.email, href: `mailto:${person.email}` } : null,
                        person.phone ? { label: person.phone, href: `tel:${person.phone}` } : null,
                    ].filter((link): link is { label: string; href: string } => link !== null),
                })),
            });
        }
        if (guides.value.length > 0) {
            next.push({
                id: 'help',
                label: t('HELP_SEARCH_GROUP'),
                results: guides.value.slice(0, 5).map((guide) => ({
                    id: `help:${guide.slug}`,
                    title: guide.title,
                    subtitle: guide.summary ?? undefined,
                    icon: 'material-symbols:help-outline-rounded',
                })),
            });
        }
        if (knowledgeBaseVisible && articles.value.length > 0) {
            next.push({
                id: 'knowledge',
                label: t('KNOWLEDGE_SEARCH_GROUP'),
                results: articles.value.slice(0, 5).map((article) => ({
                    id: `article:${article.slug}`,
                    title: article.title,
                    subtitle: article.summary ?? undefined,
                    icon: 'material-symbols:menu-book-outline-rounded',
                })),
            });
        }
        return next;
    });

    const labels = computed<GlobalSearchLabels>(() => ({
        placeholder: t('EXPERTISE_SEARCH_PLACEHOLDER'),
        ariaLabel: t('EXPERTISE_SEARCH_ARIA'),
        searching: t('EXPERTISE_SEARCH_SEARCHING'),
        prompt: t('EXPERTISE_SEARCH_PROMPT'),
        noResults: t('EXPERTISE_SEARCH_NO_RESULTS', { query: query.value.trim() }),
        noResultsHint: t('EXPERTISE_SEARCH_NO_RESULTS_HINT'),
        tips: [t('EXPERTISE_SEARCH_TIP_SYNONYM'), t('EXPERTISE_SEARCH_TIP_SHORTER')],
        openAdvanced: t('EXPERTISE_SEARCH_OPEN_PAGE'),
        clear: t('EXPERTISE_SEARCH_CLEAR'),
        close: t('EXPERTISE_SEARCH_CLOSE'),
        navigate: t('EXPERTISE_SEARCH_NAVIGATE'),
        openResult: t('EXPERTISE_SEARCH_OPEN_RESULT'),
        quickAccess: t('EXPERTISE_SEARCH_QUICK_ACCESS'),
    }));

    const errorMessage = computed(() => (failed.value ? t('EXPERTISE_SEARCH_ERROR') : null));

    const shortcutKeys = computed(() =>
        typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform) ? ['⌘', 'K'] : ['Ctrl', 'K'],
    );

    const quickLinks = computed<GlobalSearchQuickLink[]>(() => [
        {
            id: 'search',
            label: t('SIDEBAR_ITEM_EXPERTISE_SEARCH'),
            icon: 'material-symbols:person-search-rounded',
            to: '/admin/employees',
        },
        {
            id: 'employees',
            label: t('GENERAL_EMPLOYEE'),
            icon: 'material-symbols:badge-outline-rounded',
            to: '/admin/employees',
        },
        {
            id: 'organogram',
            label: t('SIDEBAR_ITEM_ORGANOGRAM'),
            icon: 'material-symbols:account-tree-outline-rounded',
            to: '/admin/organogram',
        },
        {
            id: 'help',
            label: t('HELP_TITLE'),
            icon: 'material-symbols:help-outline-rounded',
            to: '/admin/help',
        },
        ...(knowledgeBaseVisible
            ? [
                  {
                      id: 'knowledge',
                      label: t('KNOWLEDGE_TITLE'),
                      icon: 'material-symbols:menu-book-outline-rounded',
                      to: '/admin/knowledge',
                  },
              ]
            : []),
    ]);

    function onSelect(result: GlobalSearchResult): void {
        if (result.id.startsWith('help:')) {
            void router.push({ name: 'admin-help-article', params: { slug: result.id.slice('help:'.length) } });
            return;
        }
        if (result.id.startsWith('article:')) {
            void router.push({ name: 'admin-knowledge-article', params: { slug: result.id.slice('article:'.length) } });
            return;
        }
        void router.push({ name: 'admin-employees-detail', params: { id: result.id } });
    }

    function onOpenAdvanced(): void {
        const term = query.value.trim();
        void router.push({
            name: 'admin-employees-overview',
            query: term ? { search: term } : {},
        });
    }

    function onQuickLink(link: GlobalSearchQuickLink): void {
        if (link.to) {
            void router.push(link.to);
        }
    }

    return {
        query,
        groups,
        labels,
        isLoading,
        errorMessage,
        shortcutKeys,
        quickLinks,
        onSelect,
        onOpenAdvanced,
        onQuickLink,
    };
}
