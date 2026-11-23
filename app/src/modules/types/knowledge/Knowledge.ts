import type { RichTextDocument } from '@freelance/vue-ui/editor';

export type KnowledgeTag = { name: string; slug: string };
export type KnowledgePerson = { id: number; name: string };
export type KnowledgeCategory = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    sort_order: number;
    article_count: number;
    parent_id: number | null;
    is_active?: boolean;
    children?: KnowledgeCategory[];
};

export type KnowledgeCard = {
    id: number;
    title: string;
    slug: string;
    summary: string | null;
    snippet?: string | null;
    status: 'draft' | 'published' | 'archived';
    visibility: 'private' | 'authenticated' | 'public';
    featured: boolean;
    updated_at: string | null;
    category: { id: number; name: string; slug: string } | null;
    tags: KnowledgeTag[];
    author: KnowledgePerson | null;
};

export type KnowledgeArticle = KnowledgeCard & {
    content: RichTextDocument;
    version: number;
    review_due_on: string | null;
    published_at: string | null;
    can_edit: boolean;
    can_publish: boolean;
    attachments: Array<{ id: number; filename: string; mime: string; size: number }>;
    skill_ids: number[];
    certificate_ids: number[];
    related: KnowledgeCard[];
    colleagues: Array<{ id: number; name: string; role: string | null }>;
    feedback: { helpful: number; unhelpful: number; mine: boolean | null };
};

export type KnowledgeRevision = {
    number: number;
    title: string;
    summary: string | null;
    note: string | null;
    created_at: string | null;
    editor: KnowledgePerson | null;
};
