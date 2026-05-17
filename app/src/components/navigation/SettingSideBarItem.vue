<template>
    <router-link :to="to" class="w-full my-1 items-center relative group">
        <div v-if="isActive" class="absolute left-0 top-0 bottom-0 w-1 bg-white rounded"></div>

        <div
            :class="{ 'bg-gray-200': isActive }"
            class="flex items-center p-2 mx-2 gap-1 text-[var(--color-primary)] rounded-md hover:bg-gray-200 transition-colors duration-200 ease-in relative"
        >
            <span class="font-bold text-base whitespace-nowrap">{{ t(label) }}</span>
        </div>
    </router-link>
</template>

<script lang="ts" setup>
    import { useRoute, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { computed } from 'vue';
    import type { SettingsSidebarItemProps } from '@/modules/types/support/navigation/SettingsSidebarItemProps';

    const currentRoute = useRoute();
    const router = useRouter();
    const { t } = useI18n();

    const props = defineProps<SettingsSidebarItemProps>();

    function topSegment(path: string, base = '/admin'): string {
        const [pathname] = path.split(/[?#]/);
        const clean = pathname.replace(/\/+$/, '');
        const parts = clean.split('/').filter(Boolean);
        const baseParts = base.replace(/\/+$/, '').split('/').filter(Boolean);

        let i = 0;
        while (i < baseParts.length && parts[i] === baseParts[i]) i++;

        if (parts[i] === 'settings' && parts[i + 1]) {
            return `settings-${parts[i + 1]}`;
        }

        return parts[i] ?? '';
    }

    const targetPath = computed(() => router.resolve(props.to).path);
    const currentTop = computed(() => topSegment(currentRoute.path, '/admin'));
    const targetTop = computed(() => topSegment(targetPath.value, '/admin'));

    const isActive = computed(() => currentTop.value === targetTop.value);
</script>
