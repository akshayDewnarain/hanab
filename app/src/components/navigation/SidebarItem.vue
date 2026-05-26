<template>
    <router-link :to="to" class="w-full my-1 items-center relative group">
        <div
            v-if="isActive"
            class="absolute top-0 bottom-0 left-0 w-1 rounded bg-[var(--color-sidebar-icon)]"
        ></div>

        <div
            :class="{ 'bg-[var(--color-sidebar-item-highlight-dark)]': isActive && collapsed }"
            class="relative mx-2 flex items-center gap-1 rounded-md text-[var(--color-sidebar-item-text)] transition-colors duration-200 ease-in hover:bg-[var(--color-sidebar-item-highlight-dark)]"
        >
            <div
                :class="{ 'bg-[var(--color-sidebar-item-highlight-dark)]': isActive }"
                class="flex items-center rounded-md p-2"
            >
                <Icon :icon="icon" class="h-6 w-6 text-[var(--color-sidebar-icon)]" />
            </div>

            <transition mode="out-in" name="fade">
                <span v-if="collapsed" class="text-base font-bold whitespace-nowrap">{{ t(label) }}</span>
            </transition>
        </div>

        <div
            v-if="!collapsed"
            class="absolute left-full top-1/2 transform -translate-y-1/2 ml-2 hidden group-hover:block opacity-0 group-hover:opacity-100 transition-opacity duration-200 ease-in bg-gray-800 text-white text-sm font-medium py-1 px-2 rounded shadow-lg whitespace-nowrap"
        >
            {{ t(label) }}
            <div
                class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-full w-0 h-0 border-y-6 border-y-transparent border-r-6 border-r-gray-800"
            ></div>
        </div>
    </router-link>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { useRoute, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { computed } from 'vue';
    import type { SidebarItemComponentProps } from '@/modules/types/support/navigation/SidebarItemComponentProps';

    const currentRoute = useRoute();
    const router = useRouter();
    const { t } = useI18n();

    const props = defineProps<SidebarItemComponentProps>();

    function topSegment(path: string, base = '/admin'): string {
        const pathname = path.split(/[?#]/)[0] ?? '';
        const clean = pathname.replace(/\/+$/, '');
        const parts = clean.split('/').filter(Boolean);
        const baseParts = base.replace(/\/+$/, '').split('/').filter(Boolean);

        let i = 0;
        while (i < baseParts.length && parts[i] === baseParts[i]) i++;
        return parts[i] ?? '';
    }

    function normalizePath(path: string): string {
        return path.replace(/\/+$/, '') || '/';
    }

    const targetPath = computed(() => router.resolve(props.to).path);
    const currentTop = computed(() => topSegment(currentRoute.path, '/admin'));
    const targetTop = computed(() => topSegment(targetPath.value, '/admin'));

    const isActive = computed(() => {
        const currentPath = currentRoute.path;
        if (props.inactiveWhenPathStartsWith && currentPath.startsWith(props.inactiveWhenPathStartsWith)) {
            return false;
        }
        if (props.activeExact) {
            return normalizePath(currentPath) === normalizePath(targetPath.value);
        }
        return currentTop.value === targetTop.value;
    });
</script>
