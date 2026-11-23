<template>
    <article
        class="grid items-center gap-3 rounded-xl border border-list-border bg-list-surface px-3 py-2.5 shadow-sm transition-[border-color,box-shadow] duration-200 ease-out motion-reduce:transition-none hover:border-[color-mix(in_srgb,var(--color-primary)_35%,var(--color-list-border))] hover:shadow-md focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-primary"
        :class="compact ? 'grid-cols-[auto_minmax(0,1fr)]' : 'grid-cols-1 sm:grid-cols-[auto_minmax(0,1fr)_auto]'"
    >
        <div class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-list-canvas text-xs font-semibold text-list-text">
            <img v-if="photo" :alt="name" :src="photo" class="size-full object-cover" />
            <span v-else>{{ initials }}</span>
        </div>
        <div class="min-w-0">
            <h3 class="truncate text-sm font-semibold text-list-text">{{ name }}</h3>
            <p v-if="role" class="truncate text-sm text-list-text-muted">{{ role }}</p>
            <p v-if="location" class="mt-0.5 inline-flex max-w-full items-center gap-1 text-xs text-list-text-muted">
                <Icon class="size-3.5 shrink-0" icon="material-symbols:location-on-outline-rounded" />
                <span class="truncate">{{ location }}</span>
            </p>
            <ul v-if="tags.length" class="mt-1.5 flex list-none flex-wrap gap-1 p-0">
                <li
                    v-for="tag in tags"
                    :key="tag"
                    class="inline-flex max-w-full items-center gap-1 rounded-full border border-list-border bg-list-canvas px-2 py-0.5 text-[11px] font-medium text-list-text"
                >
                    <Icon class="size-3.5 shrink-0 text-list-text-muted" icon="material-symbols:verified-outline-rounded" />
                    <span class="truncate">{{ tag }}</span>
                </li>
            </ul>
            <p v-if="explanation" class="mt-1 line-clamp-2 text-xs text-list-text-muted">{{ explanation }}</p>
        </div>
        <div v-if="compact" class="col-span-2 flex justify-self-end gap-1.5">
            <IconButton variant="primary" :label="t('DIRECTORY_VIEW_PROFILE')" @click="emit('view')">
                <Icon class="size-[18px]" icon="material-symbols:arrow-outward-rounded" />
            </IconButton>
            <IconButton
                variant="neutral"
                :disabled="!email"
                :href="email ? `mailto:${email}` : undefined"
                :label="email ? t('DIRECTORY_MAIL') : t('DIRECTORY_EMAIL_UNAVAILABLE')"
            >
                <Icon class="size-[18px]" icon="material-symbols:mail-outline-rounded" />
            </IconButton>
        </div>
        <div v-else class="flex flex-wrap justify-self-start gap-1.5 sm:justify-self-end">
            <button
                type="button"
                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border border-primary bg-primary px-3 text-sm font-medium text-white"
                @click="emit('view')"
            >
                <Icon class="size-[18px]" icon="material-symbols:arrow-outward-rounded" />
                {{ t('DIRECTORY_VIEW_PROFILE') }}
            </button>
            <a
                v-if="email"
                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border border-list-border bg-list-surface px-3 text-sm font-medium text-list-text hover:border-primary hover:text-primary"
                :href="`mailto:${email}`"
            >
                <Icon class="size-[18px]" icon="material-symbols:mail-outline-rounded" />
                {{ t('DIRECTORY_MAIL') }}
            </a>
            <button
                v-else
                type="button"
                class="inline-flex h-9 cursor-not-allowed items-center gap-1.5 rounded-lg border border-list-border bg-list-surface px-3 text-sm font-medium text-list-text opacity-40"
                disabled
            >
                <Icon class="size-[18px]" icon="material-symbols:mail-outline-rounded" />
                {{ t('DIRECTORY_MAIL') }}
            </button>
        </div>
    </article>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { Icon } from '@iconify/vue';
    import { IconButton } from '@freelance/vue-ui';
    import { useI18n } from 'vue-i18n';

    defineOptions({ name: 'EmployeeDirectoryCard' });

    type Match = {
        kind?: string;
        name?: string;
        code?: string | null;
        matched_on?: string;
        synonym?: string | null;
    };

    const props = defineProps<{
        employee: Record<string, unknown>;
        query?: string;
        compact?: boolean;
    }>();

    const emit = defineEmits<{
        view: [];
    }>();

    const { t } = useI18n();

    const name = computed(() => {
        const first = String(props.employee.first_name ?? '');
        const last = String(props.employee.last_name ?? '');
        return `${first} ${last}`.trim();
    });

    const role = computed(() => {
        const value = props.employee.employee_role;
        if (value && typeof value === 'object' && 'name' in value) {
            return String((value as { name?: string }).name ?? '');
        }
        return '';
    });

    const location = computed(() => {
        const value = props.employee.employee_location;
        if (value && typeof value === 'object' && 'name' in value) {
            return String((value as { name?: string }).name ?? '');
        }
        return '';
    });

    const email = computed(() => (typeof props.employee.email === 'string' ? props.employee.email : ''));

    const photo = computed(() => {
        const image = props.employee.image;
        if (!image || typeof image !== 'object') {
            return '';
        }
        const record = image as { thumb_url?: string; url?: string };
        return record.thumb_url || record.url || '';
    });

    const initials = computed(() => {
        if (typeof props.employee.initials === 'string' && props.employee.initials !== '') {
            return props.employee.initials;
        }
        return name.value
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0]?.toUpperCase() ?? '')
            .join('');
    });

    const matches = computed(() => (Array.isArray(props.employee.expertise_matches) ? (props.employee.expertise_matches as Match[]) : []));

    const tags = computed(() => {
        const fromMatches = matches.value.map((match) => match.name).filter((value): value is string => Boolean(value));
        if (fromMatches.length > 0) {
            return fromMatches.slice(0, 4);
        }
        if (props.query) {
            return [];
        }
        const skills = Array.isArray(props.employee.skills) ? props.employee.skills : [];
        return skills
            .map((skill) => (skill && typeof skill === 'object' && 'name' in skill ? String((skill as { name?: string }).name ?? '') : ''))
            .filter(Boolean)
            .slice(0, 3);
    });

    const explanation = computed(() => {
        if (!props.query) {
            return '';
        }
        const match = matches.value[0];
        if (match?.name) {
            if (match.matched_on === 'synonym' && match.synonym) {
                return t('DIRECTORY_MATCH_SYNONYM', { name: match.name, synonym: match.synonym });
            }
            if (match.matched_on === 'code' && match.code) {
                return t('DIRECTORY_MATCH_CODE', { name: match.name, code: match.code });
            }
            if (match.kind === 'certificate') {
                return t('DIRECTORY_MATCH_CERTIFICATE', { name: match.name });
            }
            return t('DIRECTORY_MATCH_SKILL', { name: match.name });
        }

        const reason = props.employee.match_reason;
        if (reason === 'name') {
            return t('DIRECTORY_MATCH_PERSON');
        }
        if (reason === 'role' && role.value) {
            return t('DIRECTORY_MATCH_ROLE', { name: role.value });
        }
        if (reason === 'location' && location.value) {
            return t('DIRECTORY_MATCH_LOCATION', { name: location.value });
        }
        return '';
    });
</script>
