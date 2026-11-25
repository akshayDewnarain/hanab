<template>
    <article
        v-if="profile"
        class="group flex h-full flex-col rounded-xl border border-list-border bg-list-surface shadow-sm transition-[border-color,box-shadow] duration-200 ease-out motion-reduce:transition-none hover:border-[color-mix(in_srgb,var(--color-primary)_28%,var(--color-list-border))] hover:shadow-md"
        :class="{ 'cursor-pointer': !disableNavigation }"
        role="button"
        tabindex="0"
        @click="onCardClick"
        @keydown.enter.prevent="onCardClick"
    >
        <div class="group/identity flex items-center gap-3.5 px-4 pt-4 pb-1">
            <div
                class="flex size-[4.5rem] shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-light text-xl font-bold tracking-tight text-emphasis shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-emphasis)_18%,transparent)] transition duration-200 ease-out group-hover/identity:scale-[1.04] group-hover/identity:shadow-[inset_0_0_0_2px_color-mix(in_srgb,var(--color-emphasis)_45%,transparent)] motion-reduce:transition-none motion-reduce:group-hover/identity:scale-100"
            >
                <img v-if="imageUrl" :alt="title" :src="imageUrl" class="size-full object-cover" loading="lazy" />
                <span v-else>{{ avatarInitials }}</span>
            </div>
            <div class="min-w-0">
                <h3 class="relative m-0 text-lg font-bold leading-tight tracking-tight break-words text-list-text">
                    <span class="peer transition-colors duration-200 group-hover/identity:text-emphasis motion-reduce:transition-none">{{ title || '—' }}</span>
                    <span
                        v-if="avatarInitials"
                        role="tooltip"
                        class="pointer-events-none absolute bottom-[calc(100%+0.35rem)] left-0 z-20 hidden w-max max-w-56 rounded-lg border border-list-border bg-list-surface px-2.5 py-1.5 text-xs font-medium text-list-text shadow-md peer-hover:block peer-focus-visible:block"
                    >
                        {{ t('EMPLOYEE_INITIALS') }}: {{ avatarInitials }}
                    </span>
                </h3>
                <p v-if="profileRole" class="m-0 mt-0.5 text-sm text-list-text-muted">{{ profileRole }}</p>
                <p v-if="profileLocation" class="m-0 mt-1 inline-flex max-w-full items-center gap-1 text-sm text-list-text-muted">
                    <Icon class="size-4 shrink-0 text-emphasis" icon="material-symbols:location-on-outline-rounded" />
                    <span class="break-words">{{ profileLocation }}</span>
                </p>
            </div>
        </div>

        <dl v-if="infoRows.length" class="mt-3 border-t border-list-border px-4 py-2.5">
            <div
                v-for="row in infoRows"
                :key="row.key"
                class="grid grid-cols-[1.75rem_max-content_minmax(0,1fr)] items-center gap-x-2.5 py-1.5"
            >
                <span class="flex size-7 items-center justify-center rounded-lg bg-primary-light text-emphasis">
                    <Icon class="size-4" :icon="row.icon ?? 'material-symbols:info-outline-rounded'" />
                </span>
                <dt class="text-sm text-list-text-muted">{{ row.label }}</dt>
                <dd class="m-0 min-w-0 text-right text-sm font-semibold leading-5 break-words text-list-text">
                    <a
                        v-if="row.href"
                        class="hover:text-emphasis"
                        :href="row.href"
                        @click.stop
                    >{{ row.text }}</a>
                    <span v-else>{{ row.text }}</span>
                </dd>
            </div>
        </dl>

        <div v-if="badgeRows.length" class="space-y-3.5 border-t border-list-border px-4 py-3.5">
            <section v-for="row in badgeRows" :key="row.key">
                <h4 class="m-0 flex items-center gap-2 text-sm font-medium text-list-text-muted">
                    <span class="flex size-7 items-center justify-center rounded-lg bg-primary-light text-emphasis">
                        <Icon class="size-4" :icon="row.icon ?? 'material-symbols:label-outline-rounded'" />
                    </span>
                    {{ row.label }}
                </h4>
                <p v-if="row.warning" class="m-0 mt-1.5 text-sm text-list-text-muted">{{ row.warning }}</p>
                <div v-else class="mt-1.5 flex flex-wrap gap-1.5">
                    <ColorLabel
                        v-for="(badge, index) in visibleBadges(row)"
                        :key="`${row.key}-${index}`"
                        :label="badge.label"
                        :description="badge.description"
                        :background-color="badge.chipStyle?.backgroundColor"
                        :text-color="badge.chipStyle?.color"
                        :border-color="badge.chipStyle?.borderColor"
                    />
                    <button
                        v-if="hiddenBadgeCount(row) > 0"
                        type="button"
                        class="cursor-pointer text-xs font-semibold text-emphasis"
                        @click.stop="expandedBadges[row.key] = true"
                    >
                        {{ t('EMPLOYEE_CARD_MORE', { count: hiddenBadgeCount(row) }) }}
                    </button>
                </div>
            </section>
        </div>

        <div v-if="showFooter" class="mt-auto px-4 pt-1 pb-4">
            <span class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-[color-mix(in_srgb,var(--color-emphasis)_16%,var(--color-list-surface))] text-sm font-semibold text-emphasis shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-emphasis)_16%,transparent)] transition duration-200 ease-out group-hover:bg-[color-mix(in_srgb,var(--color-emphasis)_24%,var(--color-list-surface))] motion-reduce:transition-none">
                {{ t('CARD_LIST_VIEW_DETAIL') }}
                <Icon class="size-4 transition-transform duration-200 group-hover:translate-x-0.5 motion-reduce:transition-none" icon="material-symbols:arrow-forward-rounded" />
            </span>
        </div>
    </article>
    <article
        v-else
        class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-lg"
        :class="{ 'cursor-pointer': !disableNavigation }"
        role="button"
        tabindex="0"
        @click="onCardClick"
        @keydown.enter.prevent="onCardClick"
    >
        <div class="px-4 py-3" :style="headerGradientStyle">
            <h3 class="text-center text-base font-semibold leading-snug text-white drop-shadow-sm">
                <span class="line-clamp-2">{{ title || '—' }}</span>
            </h3>
        </div>

        <div class="flex flex-1 flex-col px-5 pb-5 pt-5">
            <div class="mb-4 flex justify-center">
                <div
                    v-if="imageUrl"
                    class="size-32 overflow-hidden rounded-full border-4 border-slate-100 bg-white shadow-md ring-2 ring-slate-100"
                >
                    <img :src="imageUrl" :alt="title" class="size-full object-cover" loading="lazy" />
                </div>
                <div
                    v-else
                    class="flex size-32 items-center justify-center rounded-full border-4 border-slate-100 bg-gradient-to-br from-slate-100 to-slate-200 shadow-md ring-2 ring-slate-100"
                >
                    <Icon class="size-14 text-slate-400" icon="material-symbols:person-rounded" />
                </div>
            </div>

            <div
                v-if="subtitle"
                class="mb-5 flex items-center justify-center gap-2 rounded-lg border border-slate-100 bg-slate-50/90 px-3 py-2"
            >
                <span
                    class="flex size-7 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary)]/12 text-[var(--color-primary)]"
                >
                    <Icon class="size-4" icon="material-symbols:mail-outline-rounded" />
                </span>
                <span class="truncate text-sm font-medium text-slate-700">{{ subtitle }}</span>
            </div>

            <dl v-if="visibleRows.length" class="min-h-0 flex-1 space-y-2">
                <div
                    v-for="row in visibleRows"
                    :key="row.key"
                    class="flex items-start gap-3 rounded-lg border border-transparent px-1 py-1.5 transition-colors group-hover:border-slate-50"
                >
                    <span
                        class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-[var(--color-primary)]/10 text-[var(--color-primary)]"
                    >
                        <Icon :icon="row.icon ?? 'material-symbols:info-outline-rounded'" class="size-[18px]" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            {{ row.label }}
                        </dt>
                        <dd class="mt-0.5 text-sm font-medium leading-snug text-slate-800">
                            <span v-if="row.warning" class="text-sm font-medium leading-snug text-amber-800">
                                {{ row.warning }}
                            </span>
                            <div v-else-if="row.type === 'badges' && row.badges?.length" class="flex flex-wrap gap-1.5">
                                <ColorLabel
                                    v-for="(badge, index) in row.badges"
                                    :key="`${row.key}-${index}`"
                                    :label="badge.label"
                                    :description="badge.description"
                                    :background-color="badge.chipStyle?.backgroundColor ?? 'color-mix(in srgb, var(--color-primary) 8%, white)'"
                                    :text-color="badge.chipStyle?.color ?? 'var(--color-primary)'"
                                    :border-color="badge.chipStyle?.borderColor ?? 'color-mix(in srgb, var(--color-primary) 20%, white)'"
                                />
                            </div>
                            <span v-else class="break-words">{{ row.text }}</span>
                        </dd>
                    </div>
                </div>
            </dl>

            <div v-if="showFooter" class="mt-5 flex items-center justify-center border-t border-dashed border-slate-200 pt-4">
                <span
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-[var(--color-primary)] transition-colors group-hover:gap-2.5"
                >
                    {{ t('CARD_LIST_VIEW_DETAIL') }}
                    <Icon
                        class="size-4 transition-transform group-hover:translate-x-0.5"
                        icon="material-symbols:arrow-forward-rounded"
                    />
                </span>
            </div>
        </div>
    </article>
</template>

<script lang="ts" setup>
    import { computed, reactive } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { ColorLabel } from '@freelance/vue-ui';
    import type { CardListFieldConfig } from '@/modules/types/support/list-views/CardListConfig.ts';
    import type { TableColumnInterface } from '@/modules/types/support/list-views/TableColumnInterface.ts';
    import {
        formatCardFieldValue,
        isEmptyFieldValue,
        normalizeToColoredBadges,
        type CardListBadge,
        resolveCardHeaderColor,
        resolveCardImageUrl,
        resolveCardSubtitle,
        resolveCardTitle,
    } from '@/modules/utils/cardListView.ts';
    import type { CardListConfig } from '@/modules/types/support/list-views/CardListConfig.ts';

    const props = withDefaults(
        defineProps<{
            item: Record<string, unknown>;
            cardConfig: CardListConfig;
            fields: CardListFieldConfig[];
            columnsByName: Record<string, TableColumnInterface>;
            disableNavigation?: boolean;
            showFooter?: boolean;
            getColumnValue: (item: unknown, col: TableColumnInterface) => unknown;
            convertToString: (value: unknown) => string;
        }>(),
        {
            disableNavigation: false,
            showFooter: true,
        },
    );

    const emit = defineEmits<{
        (e: 'click'): void;
        (e: 'view'): void;
    }>();

    const { t, te } = useI18n();

    const title = computed(() => resolveCardTitle(props.item, props.cardConfig));
    const subtitle = computed(() => resolveCardSubtitle(props.item, props.cardConfig));
    const imageUrl = computed(() => resolveCardImageUrl(props.item, props.cardConfig));
    const headerColor = computed(() => resolveCardHeaderColor(props.item, props.cardConfig));

    const headerGradientStyle = computed(() => ({
        background: `linear-gradient(135deg, ${headerColor.value} 0%, color-mix(in srgb, ${headerColor.value} 78%, #0f172a) 100%)`,
    }));

    type DisplayRow = {
        key: string;
        label: string;
        icon?: string;
        type?: CardListFieldConfig['type'];
        text?: string;
        href?: string;
        badges?: CardListBadge[];
        warning?: string;
    };

    const profile = computed(() => props.cardConfig.variant === 'profile');

    function withCategoryTips(raw: unknown, badges: CardListBadge[], fieldKey: string): CardListBadge[] {
        if (!Array.isArray(raw)) return badges;
        const prefix = fieldKey === 'certificates' ? 'CERTIFICATE_CATEGORY' : fieldKey === 'skills' ? 'SKILL_CATEGORY' : '';
        if (!prefix) return badges;
        const records = raw.filter((item): item is Record<string, unknown> => Boolean(item) && typeof item === 'object');
        return badges.map((badge) => {
            if (badge.description?.trim()) return badge;
            const record = records.find((item) => {
                const code = typeof item.code === 'string' ? item.code.trim() : '';
                const name = typeof item.name === 'string' ? item.name.trim() : '';
                return badge.label === code || badge.label === name;
            });
            const category = typeof record?.category === 'string' ? record.category.trim() : '';
            if (!category) return badge;
            const key = `${prefix}_${category.toUpperCase()}`;
            return te(key) ? { ...badge, description: t(key) } : badge;
        });
    }
    const expandedBadges = reactive<Record<string, boolean>>({});
    const badgeLimit = computed(() => props.cardConfig.profileBadgeLimit ?? 6);

    const visibleRows = computed((): DisplayRow[] => {
        const rows: DisplayRow[] = [];

        for (const field of props.fields) {
            const column = field.columnName ? props.columnsByName[field.columnName] : undefined;
            let raw: unknown;

            if (field.badgeKey && field.key in props.item) {
                raw = props.item[field.key];
            } else if (column) {
                raw = props.getColumnValue(props.item, column);
            } else {
                raw = props.item[field.key];
            }

            const labelKey = field.label ?? column?.label ?? `GENERAL_${field.key.toUpperCase()}`;
            const label = t(labelKey);
            const isEmpty = isEmptyFieldValue(raw);

            if (isEmpty && field.hideEmpty !== false) {
                continue;
            }

            if (isEmpty && field.emptyWarningKey) {
                rows.push({
                    key: field.key,
                    label,
                    icon: field.icon,
                    warning: t(field.emptyWarningKey),
                });
                continue;
            }

            if (field.type === 'badges') {
                const badges = withCategoryTips(raw, normalizeToColoredBadges(raw, field.badgeKey ?? 'name'), field.key);
                if (!badges.length && field.hideEmpty !== false) {
                    continue;
                }
                if (!badges.length && field.emptyWarningKey) {
                    rows.push({
                        key: field.key,
                        label,
                        icon: field.icon,
                        warning: t(field.emptyWarningKey),
                    });
                    continue;
                }
                rows.push({ key: field.key, label, icon: field.icon, type: 'badges', badges });
                continue;
            }

            const text = formatCardFieldValue(raw, field, {
                t,
                convertToString: props.convertToString,
            });

            if ((text == null || text === '') && field.hideEmpty !== false) {
                continue;
            }

            rows.push({
                key: field.key,
                label,
                icon: field.icon,
                type: field.type,
                text: text ?? '—',
                href: text && text !== '—' ? fieldHref(field.key, text) : undefined,
            });
        }

        return rows;
    });

    const identityKeys = computed(() => new Set([props.cardConfig.profileRoleField, props.cardConfig.profileLocationField].filter(Boolean)));
    const infoRows = computed(() => visibleRows.value.filter((row) => row.type !== 'badges' && !row.warning && !identityKeys.value.has(row.key)));
    const badgeRows = computed(() => visibleRows.value.filter((row) => row.type === 'badges' || row.warning));
    const profileRole = computed(() => textFor(props.cardConfig.profileRoleField));
    const profileLocation = computed(() => textFor(props.cardConfig.profileLocationField));
    const avatarInitials = computed(() => {
        const stored = props.item.initials;
        if (typeof stored === 'string' && stored.trim()) {
            return stored.trim();
        }
        return title.value
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part.charAt(0).toUpperCase())
            .join('');
    });

    function textFor(key: string | undefined): string {
        if (!key) return '';
        const row = visibleRows.value.find((item) => item.key === key && item.text);
        return row?.text && row.text !== '—' ? row.text : '';
    }

    function fieldHref(key: string, text: string | null): string | undefined {
        if (!text) return undefined;
        if (key === 'email' || key === 'business_email') return `mailto:${text}`;
        if (key === 'phone' || key === 'business_phone') return `tel:${text}`;
        return undefined;
    }

    function visibleBadges(row: DisplayRow): CardListBadge[] {
        const badges = row.badges ?? [];
        if (expandedBadges[row.key]) return badges;
        return badges.slice(0, badgeLimit.value);
    }

    function hiddenBadgeCount(row: DisplayRow): number {
        if (expandedBadges[row.key]) return 0;
        return Math.max(0, (row.badges?.length ?? 0) - badgeLimit.value);
    }

    function onCardClick(): void {
        if (props.disableNavigation) {
            return;
        }
        emit('click');
    }
</script>
