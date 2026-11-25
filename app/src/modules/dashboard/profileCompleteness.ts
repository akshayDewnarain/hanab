export type ProfileSnapshot = {
    image?: { thumb_url?: string | null; url?: string | null } | null;
    introduction?: string | null;
    skills?: unknown[] | null;
    certificates?: unknown[] | null;
    business_email?: string | null;
    business_phone?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    initials?: string | null;
    employee_role?: { name?: string | null } | null;
    employee_location?: { name?: string | null } | null;
};

export type ProfileCheck = {
    key: string;
    label: string;
    filled: boolean;
};

const checks: { key: string; label: string; filled: (employee: ProfileSnapshot) => boolean }[] = [
    { key: 'photo', label: 'DASHBOARD_PROFILE_PHOTO', filled: (employee) => Boolean(employee.image?.thumb_url || employee.image?.url) },
    { key: 'introduction', label: 'DASHBOARD_PROFILE_INTRODUCTION', filled: (employee) => Boolean(employee.introduction?.trim()) },
    { key: 'skills', label: 'DASHBOARD_PROFILE_SKILLS', filled: (employee) => (employee.skills?.length ?? 0) > 0 },
    { key: 'certificates', label: 'DASHBOARD_PROFILE_CERTIFICATES', filled: (employee) => (employee.certificates?.length ?? 0) > 0 },
    { key: 'email', label: 'DASHBOARD_PROFILE_EMAIL', filled: (employee) => Boolean(employee.business_email?.trim()) },
    { key: 'phone', label: 'DASHBOARD_PROFILE_PHONE', filled: (employee) => Boolean(employee.business_phone?.trim()) },
];

export function profileChecks(employee: ProfileSnapshot): ProfileCheck[] {
    return checks.map((check) => ({ key: check.key, label: check.label, filled: check.filled(employee) }));
}

export function profilePercent(employee: ProfileSnapshot): number {
    const rows = profileChecks(employee);
    if (rows.length === 0) return 0;
    const filled = rows.filter((row) => row.filled).length;
    return Math.round((filled / rows.length) * 100);
}
