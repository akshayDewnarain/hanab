<template>
    <header class="rounded-xl border border-list-border bg-list-surface p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <button
                type="button"
                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg px-2 text-sm font-medium text-list-text transition-colors duration-200 hover:bg-list-canvas motion-reduce:transition-none"
                @click="emit('back')"
            >
                <Icon class="size-[18px]" icon="material-symbols:arrow-back-rounded" />
                {{ t('EMPLOYEE_BACK') }}
            </button>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <HelpTrigger context="employees.profile" />
                <router-link
                    class="inline-flex h-9 items-center gap-1.5 rounded-full border border-list-border bg-list-surface px-3 text-sm font-medium text-list-text transition-colors duration-200 hover:bg-list-row-hover motion-reduce:transition-none"
                    :to="{ name: 'admin-organogram', query: { view: 'management', focus: `employee-${employeeId}` } }"
                >
                    <Icon class="size-[18px]" icon="material-symbols:account-tree-outline-rounded" />
                    {{ t('EMPLOYEE_VIEW_ORGANOGRAM') }}
                </router-link>
                <router-link
                    v-if="canEdit"
                    class="inline-flex h-9 items-center gap-1.5 rounded-full bg-primary px-3 text-sm font-semibold text-on-brand transition-colors duration-200 hover:brightness-110 motion-reduce:transition-none"
                    :to="{ name: 'admin-employees-edit', params: { id: String(employeeId) } }"
                >
                    <Icon class="size-[18px]" icon="material-symbols:edit-outline-rounded" />
                    {{ t('EMPLOYEE_PROFILE_EDIT') }}
                </router-link>
            </div>
        </div>

        <div class="mt-4 flex min-w-0 items-center gap-4">
            <div class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-full border border-list-border bg-list-canvas text-lg font-semibold text-list-text">
                <img v-if="photo" :alt="name" :src="photo" class="size-full object-cover" />
                <span v-else>{{ initials }}</span>
            </div>
            <div class="min-w-0">
                <h1 class="m-0 text-2xl font-semibold tracking-tight break-words text-list-text">{{ name }}</h1>
                <p v-if="role" class="m-0 mt-1 text-sm text-list-text-muted">{{ role }}</p>
                <p class="m-0 mt-2 flex flex-wrap items-center gap-2 text-sm text-list-text">
                    <span v-if="location" class="inline-flex min-w-0 items-center gap-1">
                        <Icon class="size-4 shrink-0 text-list-text-muted" icon="material-symbols:location-on-outline-rounded" />
                        <span class="break-words">{{ location }}</span>
                    </span>
                    <span
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                        :class="active ? 'bg-success-light text-success' : 'bg-list-canvas text-list-text-muted'"
                    >
                        {{ active ? t('GENERAL_ACTIVE') : t('EMPLOYEE_STATUS_INACTIVE') }}
                    </span>
                </p>
            </div>
        </div>

        <div v-if="businessEmail || businessPhone" class="mt-5 flex flex-col gap-3 sm:flex-row">
            <a
                v-if="businessEmail"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-primary px-4 text-sm font-semibold text-on-brand transition-colors duration-200 hover:brightness-110 motion-reduce:transition-none sm:flex-1"
                :href="`mailto:${businessEmail}`"
            >
                <Icon class="size-[18px]" icon="material-symbols:mail-outline-rounded" />
                {{ t('EMPLOYEE_EMAIL_ACTION') }}
            </a>
            <a
                v-if="businessPhone"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-full border border-list-border bg-list-surface px-4 text-sm font-semibold text-list-text transition-colors duration-200 hover:bg-list-row-hover motion-reduce:transition-none"
                :class="businessEmail ? 'sm:flex-1' : 'sm:max-w-xs sm:self-start'"
                :href="`tel:${businessPhone}`"
            >
                <Icon class="size-[18px]" icon="material-symbols:call-outline-rounded" />
                {{ t('EMPLOYEE_CALL') }}
            </a>
        </div>
    </header>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import HelpTrigger from '@/components/help/HelpTrigger.vue';

    defineOptions({ name: 'EmployeeProfileHeader' });

    defineProps<{
        employeeId: number;
        name: string;
        initials: string;
        photo: string | null;
        role: string | null;
        location: string | null;
        businessEmail: string | null;
        businessPhone: string | null;
        active: boolean;
        canEdit: boolean;
    }>();

    const emit = defineEmits<{
        back: [];
    }>();

    const { t } = useI18n();
</script>
