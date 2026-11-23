import '@/modules/types/support/navigation/RouteMeta.ts';
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth.ts';
import { knowledgeBaseVisible } from '@/modules/knowledge/visibility';

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
            {
                path: 'forgot-password',
                name: 'forgot-password',
                component: () => import('@/pages/default/ForgotPasswordPage.vue'),
                meta: { requiresAuth: false, titleKey: 'FORGOT_PASSWORD_TITLE' },
            },
            {
                path: 'reset-password',
                name: 'reset-password',
                component: () => import('@/pages/default/ResetPasswordPage.vue'),
                meta: { requiresAuth: false, titleKey: 'RESET_PASSWORD_TITLE' },
            },
            {
                path: 'invitations/accept/:token',
                name: 'invitation-accept',
                component: () => import('@/pages/default/InvitationAcceptPage.vue'),
                meta: { requiresAuth: false, titleKey: 'INVITATION_ACCEPT_TITLE' },
            },
            {
                path: 'security/setup',
                name: 'security-setup',
                component: () => import('@/pages/default/SecuritySetupPage.vue'),
                meta: { requiresAuth: false, titleKey: 'SECURITY_SETUP_TITLE' },
            },
            {
                path: 'two-factor',
                name: 'two-factor',
                component: () => import('@/pages/default/TwoFactorChallengePage.vue'),
                meta: { requiresAuth: false, titleKey: 'TWO_FACTOR_TITLE' },
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
                component: () => import('@/pages/admin/DashboardPage.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_DASHBOARD',
                },
            },
            {
                path: 'search',
                name: 'admin-expertise-search',
                redirect: (to) => ({
                    name: 'admin-employees-overview',
                    query: typeof to.query.search === 'string' && to.query.search !== ''
                        ? { search: to.query.search }
                        : typeof to.query.query === 'string' && to.query.query !== ''
                          ? { search: to.query.query }
                          : {},
                }),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_EMPLOYEES',
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
                path: 'employees/:id/edit',
                name: 'admin-employees-edit',
                component: () => import('@/pages/admin/detail-views/EmployeeDetailView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'EMPLOYEE_EDIT',
                },
            },
            {
                path: 'employees/:id',
                name: 'admin-employees-detail',
                component: () => import('@/pages/admin/detail-views/EmployeeProfileView.vue'),
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
                path: 'organization-levels',
                name: 'admin-organization-levels-overview',
                component: () => import('@/pages/admin/list-views/OrganizationLevelListView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_ORGANIZATION_LEVELS',
                },
            },
            {
                path: 'organization-levels/:id',
                name: 'admin-organization-levels-detail',
                component: () => import('@/pages/admin/detail-views/OrganizationLevelDetailView.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_ORGANIZATION_LEVEL',
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
                path: 'transfer',
                name: 'admin-data-transfer',
                component: () => import('@/pages/admin/DataTransferPage.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'PAGE_TITLE_DATA_TRANSFER',
                },
            },
            {
                path: 'organogram',
                name: 'admin-organogram',
                component: () => import('@/pages/admin/OrganogramPage.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'ORGANOGRAM_TITLE',
                },
            },
            {
                path: 'knowledge',
                name: 'admin-knowledge',
                component: () => import('@/pages/admin/knowledge/KnowledgeHomePage.vue'),
                meta: { requiresAuth: true, titleKey: 'KNOWLEDGE_TITLE' },
            },
            {
                path: 'knowledge/search',
                name: 'admin-knowledge-search',
                component: () => import('@/pages/admin/knowledge/KnowledgeSearchPage.vue'),
                meta: { requiresAuth: true, titleKey: 'KNOWLEDGE_SEARCH_RESULTS' },
            },
            {
                path: 'knowledge/manage',
                name: 'admin-knowledge-manage',
                component: () => import('@/pages/admin/knowledge/KnowledgeManagePage.vue'),
                meta: { requiresAuth: true, titleKey: 'KNOWLEDGE_MANAGE' },
            },
            {
                path: 'knowledge/categories/:slug',
                name: 'admin-knowledge-category',
                component: () => import('@/pages/admin/knowledge/KnowledgeCategoryPage.vue'),
                meta: { requiresAuth: true, titleKey: 'KNOWLEDGE_CATEGORY' },
            },
            {
                path: 'knowledge/articles/create',
                name: 'admin-knowledge-create',
                component: () => import('@/pages/admin/knowledge/KnowledgeArticleEditorPage.vue'),
                meta: { requiresAuth: true, titleKey: 'KNOWLEDGE_NEW_ARTICLE' },
            },
            {
                path: 'knowledge/articles/:id/edit',
                name: 'admin-knowledge-edit',
                component: () => import('@/pages/admin/knowledge/KnowledgeArticleEditorPage.vue'),
                meta: { requiresAuth: true, titleKey: 'KNOWLEDGE_EDIT' },
            },
            {
                path: 'knowledge/articles/:slug',
                name: 'admin-knowledge-article',
                component: () => import('@/pages/admin/knowledge/KnowledgeArticlePage.vue'),
                meta: { requiresAuth: true, titleKey: 'KNOWLEDGE_TITLE' },
            },
            {
                path: 'help',
                name: 'admin-help',
                component: () => import('@/pages/admin/help/HelpHomePage.vue'),
                meta: { requiresAuth: true, titleKey: 'HELP_TITLE' },
            },
            {
                path: 'help/search',
                name: 'admin-help-search',
                component: () => import('@/pages/admin/help/HelpSearchPage.vue'),
                meta: { requiresAuth: true, titleKey: 'HELP_SEARCH_RESULTS' },
            },
            {
                path: 'help/manage',
                name: 'admin-help-manage',
                component: () => import('@/pages/admin/help/HelpManagePage.vue'),
                meta: { requiresAuth: true, titleKey: 'HELP_MANAGE' },
            },
            {
                path: 'help/categories/:slug',
                name: 'admin-help-category',
                component: () => import('@/pages/admin/help/HelpCategoryPage.vue'),
                meta: { requiresAuth: true, titleKey: 'HELP_CATEGORY' },
            },
            {
                path: 'help/articles/create',
                name: 'admin-help-create',
                component: () => import('@/pages/admin/help/HelpEditorPage.vue'),
                meta: { requiresAuth: true, titleKey: 'HELP_NEW' },
            },
            {
                path: 'help/articles/:id/edit',
                name: 'admin-help-edit',
                component: () => import('@/pages/admin/help/HelpEditorPage.vue'),
                meta: { requiresAuth: true, titleKey: 'HELP_EDIT' },
            },
            {
                path: 'help/articles/:slug',
                name: 'admin-help-article',
                component: () => import('@/pages/admin/help/HelpArticlePage.vue'),
                meta: { requiresAuth: true, titleKey: 'HELP_TITLE', breadcrumb: 'segment' },
            },
            {
                path: 'messages',
                name: 'admin-messages',
                component: () => import('@/pages/admin/MessagesPage.vue'),
                meta: {
                    requiresAuth: true,
                    titleKey: 'MESSAGES_TITLE',
                },
            },
            {
                path: 'settings/security',
                name: 'admin-settings-security',
                component: () => import('@/pages/admin/settings/AccountSecurityPage.vue'),
                meta: { requiresAuth: true, titleKey: 'ACCOUNT_SECURITY' },
            },
            {
                path: 'settings/users',
                name: 'admin-settings-users',
                component: () => import('@/pages/admin/settings/UsersSettingsPage.vue'),
                meta: { requiresAuth: true, titleKey: 'USERS' },
            },
            {
                path: 'settings/access-control',
                name: 'admin-settings-access-control',
                component: () => import('@/pages/admin/settings/AccessControlPage.vue'),
                meta: { requiresAuth: true, titleKey: 'ACCESS_CONTROL' },
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

    if (!knowledgeBaseVisible && to.path.startsWith('/admin/knowledge')) {
        return { name: 'admin-employees-overview' };
    }

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
