<template>
    <nav class="topbar-breadcrumb" :aria-label="t('TOPBAR_BREADCRUMB')">
        <div class="topbar-breadcrumb-track">
            <router-link class="topbar-breadcrumb-home" :aria-label="t('SIDEBAR_ITEM_DASHBOARD')" to="/admin/dashboard">
                <Icon
                    class="topbar-breadcrumb-icon topbar-breadcrumb-icon--home"
                    icon="material-symbols:home-rounded"
                    aria-hidden="true"
                />
            </router-link>

            <template v-if="showBrand">
                <Icon
                    class="topbar-breadcrumb-icon topbar-breadcrumb-icon--separator"
                    icon="material-symbols:chevron-right-rounded"
                    aria-hidden="true"
                />
                <div class="topbar-breadcrumb-segment">
                    <router-link class="topbar-breadcrumb-link topbar-breadcrumb-link--ancestor" to="/admin/dashboard">
                        Hanab
                    </router-link>
                </div>
            </template>

            <template v-if="showEllipsis">
                <Icon
                    class="topbar-breadcrumb-icon topbar-breadcrumb-icon--separator"
                    icon="material-symbols:chevron-right-rounded"
                    aria-hidden="true"
                />
                <span class="topbar-breadcrumb-ellipsis" aria-hidden="true">…</span>
            </template>

            <template v-for="breadcrumb in visibleBreadcrumbs" :key="breadcrumb.path">
                <Icon
                    class="topbar-breadcrumb-icon topbar-breadcrumb-icon--separator"
                    icon="material-symbols:chevron-right-rounded"
                    aria-hidden="true"
                />
                <div class="topbar-breadcrumb-segment">
                    <router-link
                        v-if="!breadcrumb.isCurrent"
                        class="topbar-breadcrumb-link topbar-breadcrumb-link--ancestor"
                        :to="breadcrumb.path"
                        :title="breadcrumb.name"
                    >
                        {{ breadcrumb.name }}
                    </router-link>
                    <span
                        v-else
                        aria-current="page"
                        class="topbar-breadcrumb-link topbar-breadcrumb-link--current"
                        :title="breadcrumb.name"
                    >
                        {{ breadcrumb.name }}
                    </span>
                </div>
            </template>
        </div>
    </nav>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import { useViewport } from '@/composables/useViewport';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import type { BreadcrumbCrumb } from '@/modules/types/support/navigation/BreadcrumbCrumb';

    defineOptions({ name: 'BreadCrumb' });

    type VisibleCrumb = BreadcrumbCrumb & { isCurrent: boolean };

    const route = useRoute();
    const router = useRouter();
    const { t } = useI18n();
    const { width: viewportWidth } = useViewport();

    const breadcrumbs = computed<BreadcrumbCrumb[]>(() => {
        const segments = route.path.split('/').filter(Boolean);
        let currentPath = '';
        const result: BreadcrumbCrumb[] = [];
        for (const seg of segments) {
            currentPath += `/${seg}`;

            if (seg === 'admin') continue;

            const resolved = router.resolve(currentPath);
            if (resolved.name === 'admin-fallback') continue;
            const meta = resolved.meta;
            if (meta.breadcrumb === false) continue;

            if (meta.breadcrumb === 'segment' && currentPath !== route.path) continue;

            const pretty =
                meta.breadcrumb === 'segment'
                    ? decodeURIComponent(seg).replace(/-/g, ' ')
                    : typeof meta.titleKey === 'string'
                      ? t(meta.titleKey)
                      : typeof meta.breadcrumb === 'string'
                        ? meta.breadcrumb
                        : decodeURIComponent(seg).replace(/-/g, ' ');
            const name = pretty.charAt(0).toUpperCase() + pretty.slice(1);
            if (result.at(-1)?.name.toLowerCase() === name.toLowerCase()) continue;

            result.push({
                name,
                path: currentPath,
            });
        }

        return result;
    });

    const density = computed(() => {
        if (viewportWidth.value < 768) {
            return 'mobile';
        }
        if (viewportWidth.value < 1024) {
            return 'tablet';
        }
        return 'desktop';
    });

    const showBrand = computed(() => density.value === 'desktop');

    const visibleBreadcrumbs = computed<VisibleCrumb[]>(() => {
        const items = breadcrumbs.value;
        if (items.length === 0) {
            return [];
        }

        let slice = items;
        if (density.value === 'mobile') {
            slice = items.slice(-1);
        } else if (density.value === 'tablet') {
            slice = items.slice(-2);
        } else if (items.length > 3) {
            slice = items.slice(-2);
        }

        return slice.map((item, index) => ({
            ...item,
            isCurrent: index === slice.length - 1,
        }));
    });

    const showEllipsis = computed(() => density.value === 'desktop' && breadcrumbs.value.length > 3);
</script>
