import type { OrganogramEdge, OrganogramNode } from '../modules/support/organogram/types';

type LaidOutNode = OrganogramNode & {
    x: number;
    y: number;
    width: number;
    height: number;
};

type ElkResult = {
    children?: Array<{ id: string; x?: number; y?: number }>;
};

type Box = {
    width: number;
    height: number;
    nodes: LaidOutNode[];
};

export type OrganogramLayoutOptions = {
    compact?: boolean;
    groupByLocation?: boolean;
    locationOrder?: number[];
    unassignedLabel?: string;
    noLocationLabel?: string;
};

const GROUP_GAP = 56;
const SHARED_GAP = 72;
const GROUP_PAD = 28;
const GROUP_HEADER = 44;
const ROW_LIMIT = 1180;

function nodeHeight(node: OrganogramNode, compact: boolean): number {
    if (node.type === 'group') {
        return 36;
    }
    if (node.type === 'role') {
        const people = Math.min(node.people?.length ?? 0, 4);
        return (compact ? 96 : 108) + people * 22;
    }

    const note = Boolean(node.managerNote || node.reportsElsewhere);
    return compact ? 104 : note ? 136 : 112;
}

export function nodeSize(node: OrganogramNode, compact = false): { width: number; height: number } {
    return { width: compact ? 210 : 232, height: nodeHeight(node, compact) };
}

function place(nodes: OrganogramNode[], positions: Map<string, { x?: number; y?: number }>, compact: boolean): LaidOutNode[] {
    return nodes.map((node) => {
        const size = nodeSize(node, compact);
        const position = positions.get(node.id);
        return {
            ...node,
            x: position?.x ?? 0,
            y: position?.y ?? 0,
            width: size.width,
            height: size.height,
        };
    });
}

function shift(nodes: LaidOutNode[], dx: number, dy: number): LaidOutNode[] {
    return nodes.map((node) => ({ ...node, x: node.x + dx, y: node.y + dy }));
}

function boundsOf(nodes: LaidOutNode[]): { width: number; height: number } {
    let width = 0;
    let height = 0;
    for (const node of nodes) {
        width = Math.max(width, node.x + node.width);
        height = Math.max(height, node.y + node.height);
    }
    return { width, height };
}

function rowWidth(boxes: Box[], gap: number): number {
    if (boxes.length === 0) {
        return 0;
    }
    return boxes.reduce((sum, box) => sum + box.width, 0) + gap * (boxes.length - 1);
}

/** One horizontal sequence. Every box keeps the same top edge. */
function placeRow(boxes: Box[], gap: number, originX: number, originY: number): LaidOutNode[] {
    let x = originX;
    const nodes: LaidOutNode[] = [];
    for (const box of boxes) {
        nodes.push(...shift(box.nodes, x, originY));
        x += box.width + gap;
    }
    return nodes;
}

function packRows(boxes: Box[], gap: number, limit: number): { nodes: LaidOutNode[]; width: number; height: number } {
    const rows: Array<{ nodes: LaidOutNode[]; width: number; height: number }> = [];
    let current: LaidOutNode[] = [];
    let x = 0;
    let rowHeight = 0;
    const flush = (): void => {
        if (current.length === 0) {
            return;
        }
        rows.push({ nodes: current, width: Math.max(0, x - gap), height: rowHeight });
        current = [];
        x = 0;
        rowHeight = 0;
    };
    for (const box of boxes) {
        if (x > 0 && x + box.width > limit) {
            flush();
        }
        current.push(...shift(box.nodes, x, 0));
        x += box.width + gap;
        rowHeight = Math.max(rowHeight, box.height);
    }
    flush();

    const width = rows.reduce((max, row) => Math.max(max, row.width), 0);
    let y = 0;
    const nodes: LaidOutNode[] = [];
    for (const row of rows) {
        nodes.push(...shift(row.nodes, Math.max(0, (width - row.width) / 2), y));
        y += row.height + (rows.length > 1 ? gap : 0);
    }
    return { nodes, width, height: rows.length > 0 ? y - (rows.length > 1 ? gap : 0) : 0 };
}

function grid(nodes: OrganogramNode[], columns: number, compact: boolean): LaidOutNode[] {
    if (nodes.length === 0) {
        return [];
    }
    const gap = compact ? 16 : 20;
    const sizes = nodes.map((node) => nodeSize(node, compact));
    const columnWidth = Math.max(...sizes.map((size) => size.width));
    const rowHeight = Math.max(...sizes.map((size) => size.height));
    const fullWidth = columns * columnWidth + Math.max(0, columns - 1) * gap;
    return nodes.map((node, index) => {
        const size = sizes[index] ?? nodeSize(node, compact);
        const column = index % columns;
        const row = Math.floor(index / columns);
        const rowSize = Math.min(columns, nodes.length - row * columns);
        const used = rowSize * columnWidth + Math.max(0, rowSize - 1) * gap;
        return {
            ...node,
            x: (fullWidth - used) / 2 + column * (columnWidth + gap),
            y: row * (rowHeight + gap),
            width: size.width,
            height: size.height,
        };
    });
}

function hasReportingTie(node: OrganogramNode, linked: ReadonlySet<string>): boolean {
    return linked.has(node.id) || node.managerNote === 'inactive' || node.managerNote === 'missing';
}

function components(nodes: OrganogramNode[], edges: OrganogramEdge[]): OrganogramNode[][] {
    const ids = new Set(nodes.map((node) => node.id));
    const adjacent = new Map<string, string[]>();
    for (const node of nodes) {
        adjacent.set(node.id, []);
    }
    for (const edge of edges) {
        if (!ids.has(edge.source) || !ids.has(edge.target) || edge.source === edge.target) {
            continue;
        }
        adjacent.get(edge.source)?.push(edge.target);
        adjacent.get(edge.target)?.push(edge.source);
    }

    const seen = new Set<string>();
    const byId = new Map(nodes.map((node) => [node.id, node]));
    const groups: OrganogramNode[][] = [];
    for (const node of nodes) {
        if (seen.has(node.id)) {
            continue;
        }
        const stack = [node.id];
        const group: OrganogramNode[] = [];
        seen.add(node.id);
        while (stack.length > 0) {
            const id = stack.pop();
            if (!id) {
                break;
            }
            const item = byId.get(id);
            if (item) {
                group.push(item);
            }
            for (const next of adjacent.get(id) ?? []) {
                if (!seen.has(next)) {
                    seen.add(next);
                    stack.push(next);
                }
            }
        }
        groups.push(group);
    }
    return groups;
}

async function elkLayout(nodes: OrganogramNode[], edges: OrganogramEdge[], compact: boolean): Promise<LaidOutNode[]> {
    const { default: ELK } = await import('elkjs/lib/elk.bundled.js');
    const elk = new ELK();
    const horizontal = compact ? 32 : 40;
    const vertical = compact ? 68 : 80;
    const result = (await elk.layout({
        id: 'organogram',
        layoutOptions: {
            'elk.algorithm': 'layered',
            'elk.direction': 'DOWN',
            'elk.edgeRouting': 'ORTHOGONAL',
            'elk.aspectRatio': '1.15',
            'elk.spacing.nodeNode': String(horizontal),
            'elk.layered.spacing.nodeNodeBetweenLayers': String(vertical),
            'elk.layered.spacing.edgeNodeBetweenLayers': '24',
            'elk.layered.nodePlacement.strategy': 'BRANDES_KOEPF',
            'elk.layered.nodePlacement.bk.fixedAlignment': 'BALANCED',
            'elk.layered.crossingMinimization.strategy': 'LAYER_SWEEP',
            'elk.separateConnectedComponents': 'false',
            'elk.padding': '[top=0,left=0,bottom=0,right=0]',
        },
        children: nodes.map((node) => {
            const size = nodeSize(node, compact);
            return { id: node.id, width: size.width, height: size.height };
        }),
        edges: edges.map((edge) => ({
            id: edge.id,
            sources: [edge.source],
            targets: [edge.target],
        })),
    })) as ElkResult;

    return place(nodes, new Map((result.children ?? []).map((child) => [child.id, child])), compact);
}

async function layoutCluster(
    nodes: OrganogramNode[],
    edges: OrganogramEdge[],
    compact: boolean,
    scope: string,
    unassignedLabel: string,
): Promise<{ nodes: LaidOutNode[]; width: number; height: number }> {
    const ids = new Set(nodes.map((node) => node.id));
    const linked = new Set<string>();
    for (const edge of edges) {
        if (ids.has(edge.source)) {
            linked.add(edge.source);
        }
        if (ids.has(edge.target)) {
            linked.add(edge.target);
        }
    }

    const loose = nodes.filter((node) => !hasReportingTie(node, linked));
    const attached = nodes.filter((node) => hasReportingTie(node, linked));
    const intra = edges.filter((edge) => ids.has(edge.source) && ids.has(edge.target));
    const boxes: Box[] = [];

    for (const component of components(attached, intra)) {
        const componentIds = new Set(component.map((node) => node.id));
        const componentEdges = intra.filter((edge) => componentIds.has(edge.source) && componentIds.has(edge.target));
        const laid =
            componentEdges.length > 0
                ? await elkLayout(component, componentEdges, compact)
                : component.map((node) => ({ ...node, ...nodeSize(node, compact), x: 0, y: 0 }));
        const bounds = boundsOf(laid);
        boxes.push({ width: bounds.width, height: bounds.height, nodes: laid });
    }

    const forest = packRows(boxes, 40, ROW_LIMIT);
    let looseBlock: LaidOutNode[] = [];
    let looseWidth = 0;
    let looseHeight = 0;

    if (loose.length > 0) {
        const sample = nodeSize(loose[0]!, compact);
        const gap = compact ? 16 : 20;
        const basis = Math.max(forest.width, sample.width * 2 + gap, 480);
        const columns = Math.max(1, Math.min(loose.length, Math.floor((basis + gap) / (sample.width + gap))));
        const cells = grid(loose, columns, compact);
        const cellBounds = boundsOf(cells);
        const bannerWidth = Math.max(cellBounds.width, unassignedLabel ? 280 : 0);
        const cellOffset = Math.max(0, (bannerWidth - cellBounds.width) / 2);
        if (unassignedLabel) {
            looseBlock.push({
                id: `unassigned-${scope}`,
                type: 'group',
                groupKind: 'unassigned',
                name: unassignedLabel,
                memberCount: loose.length,
                x: 0,
                y: 0,
                width: bannerWidth,
                height: 32,
            });
            looseBlock.push(...shift(cells, cellOffset, 40));
            looseHeight = 40 + cellBounds.height;
        } else {
            looseBlock = shift(cells, cellOffset, 0);
            looseHeight = cellBounds.height;
        }
        looseWidth = bannerWidth;
    }

    const width = Math.max(forest.width, looseWidth);
    const forestY = 0;
    const looseY = forest.height > 0 && looseHeight > 0 ? forest.height + 22 : 0;
    const result = [
        ...shift(forest.nodes, Math.max(0, (width - forest.width) / 2), forestY),
        ...shift(looseBlock, Math.max(0, (width - looseWidth) / 2), looseY),
    ];
    const height = looseHeight > 0 ? looseY + looseHeight : forest.height;

    return { nodes: result, width, height };
}

function locationKey(node: OrganogramNode): string {
    if (typeof node.locationId !== 'number' || !Number.isFinite(node.locationId)) {
        return 'none';
    }
    return String(node.locationId);
}

/** A manager is shared when their direct reports work at more than one location. */
function sharedManagerIds(nodes: OrganogramNode[], edges: OrganogramEdge[]): Set<string> {
    const byId = new Map(nodes.map((node) => [node.id, node]));
    const reports = new Map<string, string[]>();
    for (const edge of edges) {
        if (edge.type !== 'reports_to') {
            continue;
        }
        const list = reports.get(edge.source) ?? [];
        list.push(edge.target);
        reports.set(edge.source, list);
    }

    const shared = new Set<string>();
    for (const [id, targets] of reports) {
        const places = new Set<string>();
        for (const target of targets) {
            const report = byId.get(target);
            if (report) {
                places.add(locationKey(report));
            }
        }
        if (places.size >= 2) {
            shared.add(id);
        }
    }
    return shared;
}

async function layoutByLocation(
    nodes: OrganogramNode[],
    edges: OrganogramEdge[],
    options: OrganogramLayoutOptions,
): Promise<LaidOutNode[]> {
    const compact = options.compact === true;
    const order = new Map((options.locationOrder ?? []).map((id, index) => [id, index]));
    const sharedIds = sharedManagerIds(nodes, edges);
    const sharedNodes = nodes.filter((node) => sharedIds.has(node.id));
    const buckets = new Map<string, { key: string; name: string; order: number; nodes: OrganogramNode[] }>();

    for (const node of nodes) {
        if (sharedIds.has(node.id)) {
            continue;
        }
        const key = locationKey(node);
        const numericId = key === 'none' ? null : Number(key);
        let bucket = buckets.get(key);
        if (!bucket) {
            const name = node.location?.trim() || (key === 'none' ? (options.noLocationLabel ?? '') : '');
            bucket = {
                key,
                name,
                order: numericId == null ? 100_000 : (order.get(numericId) ?? 90_000),
                nodes: [],
            };
            buckets.set(key, bucket);
        }
        bucket.nodes.push(node);
    }

    const groups = [...buckets.values()]
        .filter((group) => group.nodes.length > 0)
        .sort((left, right) => left.order - right.order || left.name.localeCompare(right.name));
    const boxes: Box[] = [];
    for (const group of groups) {
        const content = await layoutCluster(group.nodes, edges, compact, group.key, options.unassignedLabel ?? '');
        const inner = Math.max(content.width, 220);
        const width = inner + GROUP_PAD * 2;
        const height = content.height + GROUP_PAD * 2 + GROUP_HEADER;
        const container: LaidOutNode = {
            id: `location-${group.key}`,
            type: 'group',
            groupKind: 'location',
            name: group.name,
            memberCount: group.nodes.length,
            x: 0,
            y: 0,
            width,
            height,
        };
        boxes.push({
            width,
            height,
            nodes: [container, ...shift(content.nodes, GROUP_PAD + (inner - content.width) / 2, GROUP_PAD + GROUP_HEADER)],
        });
    }

    const shared =
        sharedNodes.length > 0 ? await layoutCluster(sharedNodes, edges, compact, 'shared', '') : { nodes: [], width: 0, height: 0 };
    const locationsWidth = rowWidth(boxes, GROUP_GAP);
    const totalWidth = Math.max(locationsWidth, shared.width);
    const locationsY = shared.height > 0 ? shared.height + SHARED_GAP : 0;

    return [
        ...shift(shared.nodes, Math.max(0, (totalWidth - shared.width) / 2), 0),
        ...placeRow(boxes, GROUP_GAP, Math.max(0, (totalWidth - locationsWidth) / 2), locationsY),
    ];
}

export async function layoutOrganogram(
    nodes: OrganogramNode[],
    edges: OrganogramEdge[],
    options: OrganogramLayoutOptions = {},
): Promise<LaidOutNode[]> {
    if (nodes.length === 0) {
        return [];
    }

    if (options.groupByLocation) {
        return layoutByLocation(nodes, edges, options);
    }

    const cluster = await layoutCluster(nodes, edges, options.compact === true, 'chart', options.unassignedLabel ?? '');
    return cluster.nodes;
}

export function hiddenByCollapse(edges: OrganogramEdge[], collapsed: ReadonlySet<string>): Set<string> {
    const children = new Map<string, string[]>();
    for (const edge of edges) {
        const list = children.get(edge.source) ?? [];
        list.push(edge.target);
        children.set(edge.source, list);
    }

    const hidden = new Set<string>();
    const visit = (id: string): void => {
        for (const child of children.get(id) ?? []) {
            if (hidden.has(child)) {
                continue;
            }
            hidden.add(child);
            visit(child);
        }
    };

    for (const id of collapsed) {
        visit(id);
    }

    return hidden;
}

export function ancestorIds(edges: OrganogramEdge[], nodeId: string): string[] {
    const parents = new Map(edges.map((edge) => [edge.target, edge.source]));
    const ancestors: string[] = [];
    let current = parents.get(nodeId);
    const seen = new Set<string>();
    while (current && !seen.has(current)) {
        ancestors.push(current);
        seen.add(current);
        current = parents.get(current);
    }

    return ancestors;
}

/** Collapse branches below the second level when the chart is large. */
export function initialCollapsed(nodes: OrganogramNode[], edges: OrganogramEdge[]): Set<string> {
    if (nodes.length <= 40) {
        return new Set();
    }

    const children = new Map<string, string[]>();
    const incoming = new Set<string>();
    for (const edge of edges) {
        incoming.add(edge.target);
        const list = children.get(edge.source) ?? [];
        list.push(edge.target);
        children.set(edge.source, list);
    }

    const depth = new Map<string, number>();
    const queue: Array<{ id: string; depth: number }> = [];
    for (const node of nodes) {
        if (!incoming.has(node.id)) {
            queue.push({ id: node.id, depth: 0 });
        }
    }
    while (queue.length > 0) {
        const current = queue.shift();
        if (!current || depth.has(current.id)) {
            continue;
        }
        depth.set(current.id, current.depth);
        for (const child of children.get(current.id) ?? []) {
            queue.push({ id: child, depth: current.depth + 1 });
        }
    }

    const collapsed = new Set<string>();
    for (const [id, level] of depth) {
        if (level >= 2 && (children.get(id)?.length ?? 0) > 0) {
            collapsed.add(id);
        }
    }
    return collapsed;
}
