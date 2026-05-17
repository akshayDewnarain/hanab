<template>
    <article
        class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-lg"
        :class="{ 'cursor-pointer': !disableNavigation }"
        role="button"
        tabindex="0"
        @click="onCardClick"
        @keydown.enter.prevent="onCardClick"
    >
        <!-- Header -->
        <div class="px-4 py-3" :style="headerGradientStyle">
            <h3 class="text-center text-base font-semibold leading-snug text-white drop-shadow-sm">
                <span class="line-clamp-2">{{ title || '—' }}</span>
            </h3>
        </div>

        <div class="flex flex-1 flex-col px-5 pb-5 pt-5">
            <!-- Avatar -->
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

            <!-- Email -->
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

            <!-- Detail rows -->
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
                            <span
                                v-if="row.warning"
                                class="text-sm font-medium leading-snug text-amber-800"
                            >
                                {{ row.warning }}
                            </span>
                            <div v-else-if="row.type === 'badges' && row.badges?.length" class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="(badge, index) in row.badges"
                                    :key="`${row.key}-${index}`"
                                    class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                    :class="
                                        badge.chipStyle
                                            ? ''
                                            : 'border-[var(--color-primary)]/20 bg-[var(--color-primary)]/8 text-[var(--color-primary)]'
                                    "
                                    :style="badge.chipStyle"
                                >
                                    {{ badge.label }}
                                </span>
                            </div>
                            <span v-else class="break-words">{{ row.text }}</span>
                        </dd>
                    </div>
                </div>
            </dl>

            <!-- Footer CTA -->
            <div
                v-if="showFooter"
                class="mt-5 flex items-center justify-center border-t border-dashed border-slate-200 pt-4"
            >
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
    import { computed } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
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

    const { t } = useI18n();

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
        badges?: CardListBadge[];
        warning?: string;
    };

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
                const badges = normalizeToColoredBadges(raw, field.badgeKey ?? 'name');
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
            });
        }

        return rows;
    });

    function onCardClick(): void {
        if (props.disableNavigation) {
            return;
        }
        emit('click');
    }
</script>
