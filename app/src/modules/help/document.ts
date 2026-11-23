import type { RichTextDocument, RichTextNode } from '@freelance/vue-ui/editor';

function visit(node: RichTextNode, images: Record<string, string> | null): RichTextNode {
    if (node.type === 'docImage') {
        const attrs = { ...(node.attrs ?? {}) };
        const id = attrs.id == null ? '' : String(attrs.id);
        if (images) attrs.src = images[id] ?? null;
        else delete attrs.src;
        return { ...node, attrs };
    }
    if (!node.content) return node;
    return { ...node, content: node.content.map((child) => visit(child, images)) };
}

/** Adds authorized preview addresses for the editor. The server never stores them. */
export function withImageSources(document: RichTextDocument, images: Record<string, string>): RichTextDocument {
    return { type: 'doc', content: (document.content ?? []).map((node) => visit(node, images)) };
}

/** Removes preview addresses before a document is saved. */
export function withoutImageSources(document: RichTextDocument): RichTextDocument {
    return { type: 'doc', content: (document.content ?? []).map((node) => visit(node, null)) };
}

export function withoutImage(document: RichTextDocument, id: number): RichTextDocument {
    const drop = (nodes: RichTextNode[] | undefined): RichTextNode[] =>
        (nodes ?? [])
            .filter((node) => !(node.type === 'docImage' && String(node.attrs?.id ?? '') === String(id)))
            .map((node) => (node.content ? { ...node, content: drop(node.content) } : node));

    return { type: 'doc', content: drop(document.content) };
}

export function imageIds(document: RichTextDocument): Set<string> {
    const ids = new Set<string>();
    const walk = (node: RichTextNode): void => {
        if (node.type === 'docImage' && node.attrs?.id != null) ids.add(String(node.attrs.id));
        for (const child of node.content ?? []) walk(child);
    };
    for (const node of document.content ?? []) walk(node);
    return ids;
}
