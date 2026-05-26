import '@/modules/types/support/navigation/RouteMeta.ts';
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth.ts';

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        redirect: '/login',
        meta: {
            requiresAuth: false,
        },
    },
    {
        path: '/',
        component: () => import('@/layouts/DefaultLayout.vue'),
        meta: {
            requiresAuth: false,
        },
        children: [
            {
                path: 'login',
                name: 'login',
                component: () => import('@/pages/default/LoginPage.vue'),
                meta: {
                    requiresAuth: false,
                    titleKey: 'LOGIN_TITLE',
                },
            },
        ]
    },
    {
        path: '/admin',
        component: () => import('../layouts/AdminLayout.vue'),
        meta: {
            requiresAuth: true,
            titleKey: 'PAGE_TITLE_ADMIN',
        },
        children: [
            {
                path: '',
                name: 'admin-home',
                meta: { requiresAuth: true, requiresScope: false, titleKey: 'PAGE_TITLE_ADMIN' },
                redirect: { name: 'admin-employees-overview' },
            },
            {
                path: 'development',
                name: 'admin-development',
                component: () => import('../pages/admin/DevelopmentPage.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_DEVELOPMENT',
                },
            },
            {
                path: 'dashboard',
                name: 'admin-dashboard',
                redirect: { name: 'admin-employees-overview' },
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_DASHBOARD',
                },
            },
            {
                path: 'employees',
                name: 'admin-employees-overview',
                component: () => import('@/pages/admin/list-views/EmployeeListView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_EMPLOYEES',
                },
            },
            {
                path: 'employees/:id',
                name: 'admin-employees-detail',
                component: () => import('@/pages/admin/detail-views/EmployeeDetailView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_EMPLOYEE',
                },
            },
            {
                path: 'employee-roles',
                name: 'admin-employee-roles-overview',
                component: () => import('@/pages/admin/list-views/EmployeeRoleListView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_EMPLOYEE_ROLES',
                },
            },
            {
                path: 'employee-roles/:id',
                name: 'admin-employee-roles-detail',
                component: () => import('@/pages/admin/detail-views/EmployeeRoleDetailView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_EMPLOYEE_ROLE',
                },
            },
            {
                path: 'employee-locations',
                name: 'admin-employee-locations-overview',
                component: () => import('@/pages/admin/list-views/EmployeeLocationListView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_EMPLOYEE_LOCATIONS',
                },
            },
            {
                path: 'employee-locations/:id',
                name: 'admin-employee-locations-detail',
                component: () => import('@/pages/admin/detail-views/EmployeeLocationDetailView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_EMPLOYEE_LOCATION',
                },
            },
            {
                path: 'skills',
                name: 'admin-skills-overview',
                component: () => import('@/pages/admin/list-views/SkillListView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_SKILLS',
                },
            },
            {
                path: 'skills/:id',
                name: 'admin-skills-detail',
                component: () => import('@/pages/admin/detail-views/SkillDetailView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_SKILL',
                },
            },
            {
                path: 'certificates',
                name: 'admin-certificates-overview',
                component: () => import('@/pages/admin/list-views/CertificateListView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_CERTIFICATES',
                },
            },
            {
                path: 'certificates/:id',
                name: 'admin-certificates-detail',
                component: () => import('@/pages/admin/detail-views/CertificateDetailView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_CERTIFICATE',
                },
            },
            {
                path: 'employee-skills',
                name: 'admin-employee-skills-overview',
                component: () => import('@/pages/admin/list-views/EmployeeSkillListView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_EMPLOYEE_SKILLS',
                },
            },
            {
                path: 'employee-skills/:id',
                name: 'admin-employee-skills-detail',
                component: () => import('@/pages/admin/detail-views/EmployeeSkillDetailView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_EMPLOYEE_SKILL',
                },
            },
            {
                path: 'employee-certificates',
                name: 'admin-employee-certificates-overview',
                component: () => import('@/pages/admin/list-views/EmployeeCertificateListView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_EMPLOYEE_CERTIFICATES',
                },
            },
            {
                path: 'employee-certificates/:id',
                name: 'admin-employee-certificates-detail',
                component: () => import('@/pages/admin/detail-views/EmployeeCertificateDetailView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_EMPLOYEE_CERTIFICATE',
                },
            },
            {
                path: ':pathMatch(.*)*',
                name: 'admin-fallback',
                redirect: { name: 'admin-employees-overview' },
                meta: { requiresAuth: true, titleKey: 'PAGE_TITLE_ADMIN' },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
});

router.beforeEach(async (to) => {
    const authStore = useAuthStore();
    const requiresAuth = to.matched.some((route) => route.meta.requiresAuth);

    if (!requiresAuth) {
        return true;
    }

    try {
        if (!authStore.user) {
            await authStore.fetchUser();
        }

        if (!authStore.user) {
            return { name: 'login' };
        }

        return true;
    } catch (error) {
        console.error('fetchUser failed in guard:', error);
        return { name: 'login' };
    }
});

export default router;
