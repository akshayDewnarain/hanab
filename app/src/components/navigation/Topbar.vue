<template>
    <Transition
        appear
        enter-active-class="transform transition duration-300 ease-out"
        enter-from-class="-translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transform transition duration-200 ease-in"
        leave-to-class="-translate-y-full opacity-0"
    >
        <nav class="relative flex-shrink-0 h-14 bg-white shadow-sm">
            <div class="h-full w-full flex-shrink-0 flex justify-between items-center relative">
                <!-- Left Section -->
                <div class="flex h-full items-center space-x-4">
                    <BreadCrumb />
                </div>

                <!-- Middle Section -->
                <div class="absolute left-1/2 transform -translate-x-1/2 flex h-full items-center px-2">
                    <div class="flex flex-row w-64 bg-white rounded shadow relative">
                        <input :placeholder="t('GENERAL_SEARCH_PLACEHOLDER')" class="input-base" type="text" />
                        <div
                            class="absolute right-0 h-full flex items-center justify-center bg-[var(--color-background)] border border-gray-200 rounded-r px-2 cursor-pointer hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                        >
                            <Icon class="text-white w-4 h-4" icon="material-symbols:search" />
                        </div>
                    </div>
                </div>

                <!-- Right Section -->
                <div class="flex h-full items-center space-x-4">
                    <div class="flex items-center mx-2 space-x-2">
                        <div class="flex flex-col items-end">
                            <span class="text-gray-700 font-semibold">{{
                                `${user?.first_name ?? ''}  ${user?.last_name ?? ''}`
                            }}</span>
                            <span class="text-gray-500 text-sm">{{
                                `${user?.role_name ? t('GENERAL_' + user?.role_name.toUpperCase()) : ''}`
                            }}</span>
                        </div>
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                            <Icon class="text-gray-700 w-6 h-6" icon="material-symbols:person-rounded" />
                        </div>
                    </div>
                    <AdvancedDropdown
                        :align="'right'"
                        :show-caret="false"
                        customClass="!bg-[var(--color-background)]"
                        customDropdownClass="w-40"
                    >
                        <!-- Custom trigger -->
                        <template #trigger="{ onToggleDropdown }">
                            <div
                                class="mx-2 flex items-center justify-center rounded cursor-pointer bg-[var(--color-background)] hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in"
                                @click="onToggleDropdown"
                            >
                                <Icon
                                    class="w-6 h-6 text-white rotate-90"
                                    icon="material-symbols:chevron-right-rounded"
                                />
                            </div>
                        </template>

                        <!-- Dropdown content -->
                        <template #dropdown-content>
                            <ul class="text-sm text-gray-700">
                                <li class="w-full my-1 items-center relative group">
                                    <button
                                        class="w-full text-left flex items-center p-2 mx-2 gap-1 text-[var(--color-primary)] rounded-md hover:bg-gray-200 transition-colors duration-200 ease-in relative cursor-pointer"
                                        type="button"
                                        @click="goToProfile"
                                    >
                                        <Icon class="w-5 h-5" icon="material-symbols:person-rounded" />
                                        <span class="font-bold text-base whitespace-nowrap">{{ t('PROFILE') }}</span>
                                    </button>
                                </li>
                                <li class="w-full my-1 items-center relative group">
                                    <button
                                        class="w-full text-left flex items-center p-2 mx-2 gap-1 text-[var(--color-primary)] rounded-md hover:bg-gray-200 transition-colors duration-200 ease-in relative cursor-pointer"
                                        type="button"
                                    >
                                        <Icon class="w-5 h-5" icon="material-symbols:settings-rounded" />
                                        <span class="font-bold text-base whitespace-nowrap">{{ t('SETTINGS') }}</span>
                                    </button>
                                </li>
                                <li class="w-full my-1 items-center relative group">
                                    <button
                                        class="w-full text-left flex items-center p-2 mx-2 gap-1 text-[var(--color-primary)] rounded-md hover:bg-gray-200 transition-colors duration-200 ease-in relative cursor-pointer"
                                        type="button"
                                    >
                                        <Icon class="w-5 h-5" icon="material-symbols:logout-rounded" />
                                        <span class="font-bold text-base whitespace-nowrap">{{
                                            t('SIDEBAR_ITEM_LOGOUT')
                                        }}</span>
                                    </button>
                                </li>
                            </ul>
                        </template>
                    </AdvancedDropdown>
                </div>
            </div>
        </nav>
    </Transition>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import BreadCrumb from '@/components/navigation/BreadCrumb.vue';
    import { computed, defineComponent } from 'vue';
    import { useRouter } from 'vue-router';
    import { useAuthStore } from '@/stores/auth';
    import { useI18n } from 'vue-i18n';
    import AdvancedDropdown from '@/components/inputs/AdvancedDropdown.vue';

    defineComponent({
        name: 'Topbar',
    });

    const authStore = useAuthStore();
    const router = useRouter();
    const { t } = useI18n();

    const user = computed(() => authStore.user);

    function goToProfile(): void {
        if (!user.value?.id) return;
        router.push({
            name: 'admin-settings-users-detail',
            params: { id: user.value.id },
        });
    }
</script>
