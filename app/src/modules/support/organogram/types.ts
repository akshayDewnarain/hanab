export type OrganogramLevel = {
    id: number;
    name: string;
    color: string;
};

export type OrganogramPerson = {
    id: number;
    name: string;
    location: string | null;
};

export type OrganogramNode = {
    id: string;
    type: 'employee' | 'role' | 'group';
    groupKind?: 'location' | 'unassigned';
    memberCount?: number;
    reportsElsewhere?: boolean;
    name: string;
    description?: string | null;
    employeeId?: number;
    initials?: string;
    photoUrl?: string | null;
    role?: string | null;
    roleId?: number;
    location?: string | null;
    locationId?: number | null;
    level?: OrganogramLevel | null;
    directReportsCount?: number;
    context?: boolean;
    managerNote?: 'inactive' | 'missing' | null;
    managerName?: string | null;
    employeeNumber?: string | null;
    people?: OrganogramPerson[];
};

export type OrganogramEdge = {
    id: string;
    source: string;
    target: string;
    type: 'reports_to' | 'role_parent';
    /** True when a reporting line joins employees at different work locations. */
    crossLocation?: boolean;
};

export type OrganogramGraph = {
    nodes: OrganogramNode[];
    edges: OrganogramEdge[];
};

export type OrganogramResponse = {
    data: OrganogramGraph;
    meta: {
        view: string;
        totalNodes: number;
    };
};

export type OrganogramView = 'management' | 'location' | 'roles';
