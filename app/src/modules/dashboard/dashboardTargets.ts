export const dashboardRoutes = {
    employees: 'admin-employees-overview',
    locations: 'admin-employee-locations-overview',
} as const;

export type DashboardRouteName = keyof typeof dashboardRoutes;

export type DashboardAction = {
    key: string;
    title_key: string;
    description_key: string;
    icon: string;
    count: number;
    priority: string;
    target: {
        name: string;
        query: Record<string, string>;
    };
};

export function dashboardRoute(name: string): string | null {
    if (name in dashboardRoutes) {
        return dashboardRoutes[name as DashboardRouteName];
    }
    return null;
}
