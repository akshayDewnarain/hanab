<template>
    <header class="flex flex-col gap-5 rounded-xl border border-list-border bg-list-surface p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-center gap-4">
            <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full border border-list-border bg-list-canvas text-xl font-semibold text-list-text sm:size-24">
                <img v-if="photo" :alt="name" :src="photo" class="size-full object-cover" />
                <span v-else>{{ initials }}</span>
            </div>
            <div class="min-w-0">
                <h1 class="m-0 text-2xl font-semibold tracking-tight break-words text-list-text sm:text-3xl">{{ name }}</h1>
                <p v-if="givenInitials" class="m-0 mt-1 text-sm text-list-text-muted">
                    {{ t('EMPLOYEE_INITIALS') }}: {{ givenInitials }}
                </p>
                <p v-if="role" class="m-0 mt-1 text-base text-list-text-muted">{{ role }}</p>
                <p class="m-0 mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-list-text-muted">
                    <span v-if="location" class="inline-flex min-w-0 items-center gap-1">
                        <Icon class="size-4 shrink-0" icon="material-symbols:location-on-outline-rounded" />
                        <span class="break-words">{{ location }}</span>
                    </span>
                    <span v-if="location" aria-hidden="true">·</span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="size-2 rounded-full" :class="active ? 'bg-emerald-500' : 'bg-slate-400'" />
                        {{ active ? t('GENERAL_ACTIVE') : t('EMPLOYEE_STATUS_INACTIVE') }}
                    </span>
                </p>
            </div>
        </div>
        <div class="flex min-w-0 flex-col items-start gap-3 sm:items-end">
            <dl class="m-0 flex flex-col gap-1 text-sm">
                <div v-if="email" class="flex flex-wrap gap-x-2">
                    <dt class="font-medium text-list-text-muted">{{ t('EMPLOYEE_PERSONAL_EMAIL') }}</dt>
                    <dd class="m-0 break-all text-list-text">
                        <a class="hover:text-primary" :href="`mailto:${email}`">{{ email }}</a>
                    </dd>
                </div>
                <div v-if="phone" class="flex flex-wrap gap-x-2">
                    <dt class="font-medium text-list-text-muted">{{ t('EMPLOYEE_PERSONAL_PHONE') }}</dt>
                    <dd class="m-0 text-list-text">
                        <a class="hover:text-primary" :href="`tel:${phone}`">{{ phone }}</a>
                    </dd>
                </div>
                <div v-if="businessEmail" class="flex flex-wrap gap-x-2">
                    <dt class="font-medium text-list-text-muted">{{ t('EMPLOYEE_BUSINESS_EMAIL') }}</dt>
                    <dd class="m-0 break-all text-list-text">
                        <a class="hover:text-primary" :href="`mailto:${businessEmail}`">{{ businessEmail }}</a>
                    </dd>
                </div>
                <div v-if="businessPhone" class="flex flex-wrap gap-x-2">
                    <dt class="font-medium text-list-text-muted">{{ t('EMPLOYEE_BUSINESS_PHONE') }}</dt>
                    <dd class="m-0 text-list-text">
                        <a class="hover:text-primary" :href="`tel:${businessPhone}`">{{ businessPhone }}</a>
                    </dd>
                </div>
            </dl>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <a
                v-if="businessEmail || email"
                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-list-border bg-list-surface px-3 text-sm font-medium text-list-text transition-colors duration-200 hover:border-primary hover:text-primary motion-reduce:transition-none"
                :href="`mailto:${businessEmail || email}`"
            >
                <Icon class="size-[18px]" icon="material-symbols:mail-outline-rounded" />
                {{ t('EMPLOYEE_SEND_EMAIL') }}
            </a>
            <a
                v-if="businessPhone || phone"
                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-list-border bg-list-surface px-3 text-sm font-medium text-list-text transition-colors duration-200 hover:border-primary hover:text-primary motion-reduce:transition-none"
                :href="`tel:${businessPhone || phone}`"
            >
                <Icon class="size-[18px]" icon="material-symbols:call-outline-rounded" />
                {{ t('EMPLOYEE_CALL') }}
            </a>
        </div>
        </div>
    </header>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';

    defineOptions({ name: 'EmployeeProfileHeader' });

    defineProps<{
        name: string;
        initials: string;
        givenInitials: string | null;
        photo: string | null;
        role: string | null;
        location: string | null;
        email: string | null;
        phone: string | null;
        businessEmail: string | null;
        businessPhone: string | null;
        active: boolean;
    }>();

    const { t } = useI18n();
</script>
