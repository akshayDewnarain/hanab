<template>
    <nav class="mx-2">
        <div class="flex items-center bg-gray-100 pr-2 rounded border border-gray-200 shadow">
            <div class="flex items-center">
                <router-link
                    class="bg-[var(--color-background)] px-2 py-1 rounded hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                    to="/admin/dashboard"
                >
                    <Icon class="w-6 h-6 text-white" icon="material-symbols:home-rounded" />
                </router-link>

                <Icon class="w-6 h-6 text-gray-500" icon="material-symbols:chevron-right-rounded" />
            </div>

            <div class="flex items-center">
                <router-link to="/admin/dashboard">
                    <span class="text-gray-500 font-bold">{{ 'cest-ca' }}</span>
                </router-link>

                <Icon class="w-6 h-6 text-gray-500 my-1" icon="material-symbols:chevron-right-rounded" />
            </div>

            <div v-for="(breadcrumb, index) in breadcrumbs" :key="index" class="flex items-center">
                <router-link :to="breadcrumb.path">
                    <span class="text-gray-500 font-bold">{{ breadcrumb.name }}</span>
                </router-link>
                <Icon
                    v-if="index < breadcrumbs.length - 1"
                    class="w-6 h-6 text-gray-500"
                    icon="material-symbols:chevron-right-rounded"
                />
            </div>
        </div>
    </nav>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { useRoute } from 'vue-router';
    import { Icon } from '@iconify/vue';
    import type { BreadcrumbCrumb } from '@/modules/types/support/navigation/BreadcrumbCrumb';

    const route = useRoute();

    const breadcrumbs = computed<BreadcrumbCrumb[]>(() => {
        const segments = route.path.split('/').filter(Boolean); // keep 'admin' here
        let currentPath = '';
        const result: BreadcrumbCrumb[] = [];

        for (const seg of segments) {
            currentPath += `/${seg}`;

            if (seg === 'admin') continue;

            const matched = route.matched.find((r) => currentPath.startsWith(r.path));
            const pretty =
                (matched?.meta as Record<string, unknown>)?.breadcrumb ?? decodeURIComponent(seg).replace(/-/g, ' ');

            result.push({
                name: (pretty as string).charAt(0).toUpperCase() + (pretty as string).slice(1),
                path: currentPath,
            });
        }

        return result;
    });
</script>
