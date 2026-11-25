export type PermissionAction = 'view' | 'create' | 'update' | 'delete' | 'import' | 'publish' | 'manage' | 'restore' | 'other';

export interface PermissionGroupDefinition {
    id: string;
    icon: string;
    titleKey: string;
    bodyKey: string;
    /** Permission tail that uses the short action label. Other tails in the group stay specific. */
    primary: string;
    subjectKey: string;
}

const VERBS: PermissionAction[] = ['view', 'create', 'update', 'delete', 'import', 'publish', 'manage', 'restore'];

export const PERMISSION_GROUPS: PermissionGroupDefinition[] = [
    { id: 'employees', icon: 'material-symbols:group-outline-rounded', titleKey: 'ACCESS_GROUP_EMPLOYEES', bodyKey: 'ACCESS_GROUP_EMPLOYEES_BODY', primary: 'employees', subjectKey: 'ACCESS_SUBJECT_EMPLOYEES' },
    { id: 'users', icon: 'material-symbols:manage-accounts-outline-rounded', titleKey: 'ACCESS_GROUP_USERS', bodyKey: 'ACCESS_GROUP_USERS_BODY', primary: 'users', subjectKey: 'ACCESS_SUBJECT_USERS' },
    { id: 'roles', icon: 'material-symbols:shield-lock-outline-rounded', titleKey: 'ACCESS_GROUP_ROLES', bodyKey: 'ACCESS_GROUP_ROLES_BODY', primary: 'roles', subjectKey: 'ACCESS_SUBJECT_ROLES' },
    { id: 'invitations', icon: 'material-symbols:mail-outline-rounded', titleKey: 'ACCESS_GROUP_INVITATIONS', bodyKey: 'ACCESS_GROUP_INVITATIONS_BODY', primary: 'user_invitations', subjectKey: 'ACCESS_SUBJECT_INVITATIONS' },
    { id: 'functions', icon: 'material-symbols:work-outline-rounded', titleKey: 'ACCESS_GROUP_FUNCTIONS', bodyKey: 'ACCESS_GROUP_FUNCTIONS_BODY', primary: 'employee_roles', subjectKey: 'ACCESS_SUBJECT_FUNCTIONS' },
    { id: 'levels', icon: 'material-symbols:account-tree-outline-rounded', titleKey: 'ACCESS_GROUP_LEVELS', bodyKey: 'ACCESS_GROUP_LEVELS_BODY', primary: 'organization_levels', subjectKey: 'ACCESS_SUBJECT_LEVELS' },
    { id: 'locations', icon: 'material-symbols:location-on-outline-rounded', titleKey: 'ACCESS_GROUP_LOCATIONS', bodyKey: 'ACCESS_GROUP_LOCATIONS_BODY', primary: 'employee_locations', subjectKey: 'ACCESS_SUBJECT_LOCATIONS' },
    { id: 'skills', icon: 'material-symbols:verified-outline-rounded', titleKey: 'ACCESS_GROUP_SKILLS', bodyKey: 'ACCESS_GROUP_SKILLS_BODY', primary: 'skills', subjectKey: 'ACCESS_SUBJECT_SKILLS' },
    { id: 'employee_skills', icon: 'material-symbols:psychology-outline-rounded', titleKey: 'ACCESS_GROUP_EMPLOYEE_SKILLS', bodyKey: 'ACCESS_GROUP_EMPLOYEE_SKILLS_BODY', primary: 'employee_skills', subjectKey: 'ACCESS_SUBJECT_EMPLOYEE_SKILLS' },
    { id: 'certificates', icon: 'material-symbols:workspace-premium-outline-rounded', titleKey: 'ACCESS_GROUP_CERTIFICATES', bodyKey: 'ACCESS_GROUP_CERTIFICATES_BODY', primary: 'certificates', subjectKey: 'ACCESS_SUBJECT_CERTIFICATES' },
    { id: 'employee_certificates', icon: 'material-symbols:badge-outline-rounded', titleKey: 'ACCESS_GROUP_EMPLOYEE_CERTIFICATES', bodyKey: 'ACCESS_GROUP_EMPLOYEE_CERTIFICATES_BODY', primary: 'employee_certificates', subjectKey: 'ACCESS_SUBJECT_EMPLOYEE_CERTIFICATES' },
    { id: 'messages', icon: 'material-symbols:chat-outline-rounded', titleKey: 'ACCESS_GROUP_MESSAGES', bodyKey: 'ACCESS_GROUP_MESSAGES_BODY', primary: 'messages', subjectKey: 'ACCESS_SUBJECT_MESSAGES' },
    { id: 'help', icon: 'material-symbols:help-outline-rounded', titleKey: 'ACCESS_GROUP_HELP', bodyKey: 'ACCESS_GROUP_HELP_BODY', primary: 'help articles', subjectKey: 'ACCESS_SUBJECT_HELP_ARTICLES' },
    { id: 'knowledge', icon: 'material-symbols:menu-book-outline-rounded', titleKey: 'ACCESS_GROUP_KNOWLEDGE', bodyKey: 'ACCESS_GROUP_KNOWLEDGE_BODY', primary: 'knowledge articles', subjectKey: 'ACCESS_SUBJECT_KNOWLEDGE_ARTICLES' },
    { id: 'other', icon: 'material-symbols:more-horiz', titleKey: 'ACCESS_GROUP_OTHER', bodyKey: 'ACCESS_GROUP_OTHER_BODY', primary: '', subjectKey: 'ACCESS_SUBJECT_OTHER' },
];

const TAIL_GROUP: Record<string, string> = {
    employees: 'employees',
    users: 'users',
    roles: 'roles',
    user_invitations: 'invitations',
    employee_roles: 'functions',
    organization_levels: 'levels',
    employee_locations: 'locations',
    skills: 'skills',
    employee_skills: 'employee_skills',
    certificates: 'certificates',
    employee_certificates: 'employee_certificates',
    messages: 'messages',
    'help articles': 'help',
    'help categories': 'help',
    'help revisions': 'help',
    'knowledge articles': 'knowledge',
    'knowledge categories': 'knowledge',
    'knowledge attachments': 'knowledge',
    'knowledge revisions': 'knowledge',
};

const TAIL_SUBJECT: Record<string, string> = {
    employees: 'ACCESS_SUBJECT_EMPLOYEES',
    users: 'ACCESS_SUBJECT_USERS',
    roles: 'ACCESS_SUBJECT_ROLES',
    user_invitations: 'ACCESS_SUBJECT_INVITATIONS',
    employee_roles: 'ACCESS_SUBJECT_FUNCTIONS',
    organization_levels: 'ACCESS_SUBJECT_LEVELS',
    employee_locations: 'ACCESS_SUBJECT_LOCATIONS',
    skills: 'ACCESS_SUBJECT_SKILLS',
    employee_skills: 'ACCESS_SUBJECT_EMPLOYEE_SKILLS',
    certificates: 'ACCESS_SUBJECT_CERTIFICATES',
    employee_certificates: 'ACCESS_SUBJECT_EMPLOYEE_CERTIFICATES',
    messages: 'ACCESS_SUBJECT_MESSAGES',
    'help articles': 'ACCESS_SUBJECT_HELP_ARTICLES',
    'help categories': 'ACCESS_SUBJECT_HELP_CATEGORIES',
    'help revisions': 'ACCESS_SUBJECT_HELP_REVISIONS',
    'knowledge articles': 'ACCESS_SUBJECT_KNOWLEDGE_ARTICLES',
    'knowledge categories': 'ACCESS_SUBJECT_KNOWLEDGE_CATEGORIES',
    'knowledge attachments': 'ACCESS_SUBJECT_KNOWLEDGE_ATTACHMENTS',
    'knowledge revisions': 'ACCESS_SUBJECT_KNOWLEDGE_REVISIONS',
};

const EXTRA_RISK = new Set([
    'update roles',
    'create user_invitations',
    'update user_invitations',
    'delete user_invitations',
    'publish messages',
    'publish help articles',
    'publish knowledge articles',
    'import employees',
]);

const ACTION_ICON: Record<PermissionAction, string> = {
    view: 'material-symbols:visibility-outline-rounded',
    create: 'material-symbols:add-circle-outline-rounded',
    update: 'material-symbols:edit-outline-rounded',
    delete: 'material-symbols:delete-outline-rounded',
    import: 'material-symbols:upload-rounded',
    publish: 'material-symbols:send-outline-rounded',
    manage: 'material-symbols:settings-outline-rounded',
    restore: 'material-symbols:history-rounded',
    other: 'material-symbols:key-outline-rounded',
};

const ACTION_TONE: Record<PermissionAction, string> = {
    view: 'text-emphasis',
    create: 'text-emerald-700 dark:text-emerald-300',
    update: 'text-amber-700 dark:text-amber-300',
    delete: 'text-red-700 dark:text-red-300',
    import: 'text-teal-700 dark:text-teal-300',
    publish: 'text-purple-700 dark:text-purple-300',
    manage: 'text-indigo-700 dark:text-indigo-300',
    restore: 'text-orange-700 dark:text-orange-300',
    other: 'text-list-text-muted',
};

export interface DescribedPermission {
    name: string;
    groupId: string;
    action: PermissionAction;
    primary: boolean;
    subjectKey: string;
    risk: boolean;
    icon: string;
    tone: string;
}

const RESERVED_CONTENT_GROUPS = new Set(['help', 'knowledge']);

export function isReservedContentPermission(name: string): boolean {
    return RESERVED_CONTENT_GROUPS.has(describePermission(name).groupId);
}

export function describePermission(name: string): DescribedPermission {
    const verb = VERBS.find((item) => name.startsWith(`${item} `));
    const action: PermissionAction = verb ?? 'other';
    const tail = verb ? name.slice(verb.length + 1) : name;
    const groupId = TAIL_GROUP[tail] ?? 'other';
    const group = PERMISSION_GROUPS.find((item) => item.id === groupId) ?? PERMISSION_GROUPS[PERMISSION_GROUPS.length - 1];
    return {
        name,
        groupId,
        action,
        primary: tail === group.primary,
        subjectKey: TAIL_SUBJECT[tail] ?? 'ACCESS_SUBJECT_OTHER',
        risk: action === 'delete' || EXTRA_RISK.has(name),
        icon: ACTION_ICON[action],
        tone: ACTION_TONE[action],
    };
}

export function permissionSort(left: DescribedPermission, right: DescribedPermission): number {
    const order = VERBS.indexOf(left.action) - VERBS.indexOf(right.action);
    if (order !== 0 && left.action !== 'other' && right.action !== 'other') return order;
    return left.name.localeCompare(right.name);
}
