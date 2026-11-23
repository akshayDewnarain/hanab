import type { RichTextDocument } from '@freelance/vue-ui/editor';

export type HelpCategory = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    sort_order: number;
    parent_id: number | null;
    is_active?: boolean;
    article_count: number;
};

export type HelpCard = {
    id: number;
    title: string;
    slug: string;
    summary: string | null;
    snippet?: string | null;
    status: 'draft' | 'published' | 'archived';
    visibility: 'private' | 'authenticated' | 'public';
    context_key: string | null;
    feature_path: string | null;
    sort_order: number;
    is_getting_started: boolean;
    updated_at: string | null;
    category: { id: number; name: string; slug: string } | null;
    author: { id: number; name: string } | null;
};

export type HelpScreenshot = {
    id: number;
    filename: string;
    mime: string;
    size: number;
    caption: string | null;
    alt_text: string;
};

export type HelpArticle = HelpCard & {
    content: RichTextDocument;
    keywords: string | null;
    audience_permission: string | null;
    app_version: string | null;
    version: number;
    review_due_on: string | null;
    can_edit: boolean;
    can_publish: boolean;
    attachments: HelpScreenshot[];
    related: HelpCard[];
    feedback: { helpful: number; not_helpful: number; mine: boolean | null };
};

export type HelpRevision = {
    number: number;
    title: string;
    note: string | null;
    created_at: string | null;
    editor: { id: number; name: string } | null;
};
