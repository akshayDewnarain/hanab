<template>
    <section
        class="overflow-hidden rounded-sm border border-slate-200/90 bg-gradient-to-br from-slate-50 to-white shadow-sm"
    >
        <button
            type="button"
            class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition-colors hover:bg-slate-100/60"
            :aria-expanded="expanded"
            :aria-controls="bodyId"
            @click="expanded = !expanded"
        >
            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                <div
                    class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-[var(--color-primary)]/12 text-[var(--color-primary)]"
                >
                    <Icon :icon="icon" class="size-[18px]" />
                </div>
                <h3 class="min-w-0 text-sm font-semibold tracking-tight text-slate-800">
                    <slot name="title">{{ title }}</slot>
                </h3>
            </div>
            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <span class="max-w-[11rem] text-right text-xs leading-snug text-slate-500 sm:max-w-none">
                    {{ expanded ? collapseLabel : expandLabel }}
                </span>
                <Icon
                    icon="material-symbols:keyboard-arrow-down-rounded"
                    class="size-5 shrink-0 text-slate-500 motion-safe:transition-transform motion-safe:duration-300 motion-safe:ease-out"
                    :class="{ '-rotate-90': !expanded }"
                />
            </div>
        </button>

        <!-- Grid row height interpolates smoothly so sibling layout reflow is progressive -->
        <div class="smooth-expand-grid grid" :class="{ 'smooth-expand-grid--open': expanded }">
            <div class="smooth-expand-inner">
                <!-- `inert` keeps collapsed content off the tab ring while height → 0 -->
                <div :inert="!expanded">
                    <div :id="bodyId" class="border-t border-slate-200/80 px-3 py-3 pt-3">
                        <div
                            class="smooth-expand-body pl-0.5 text-sm leading-relaxed text-slate-600 sm:pl-10"
                            :class="{ 'smooth-expand-body--visible': expanded }"
                        >
                            <slot />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script lang="ts" setup>
    import { computed, useId } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';

    defineOptions({
        name: 'InformationPanel',
    });

    const props = withDefaults(
        defineProps<{
            /** Header text; override with `#title` slot if needed. */
            title: string;
            /** Iconify icon id (e.g. `material-symbols:info-outline-rounded`). */
            icon?: string;
            /** Optional i18n key for the collapsed-state hint (defaults to `INFORMATION_PANEL_CLICK_TO_EXPAND`). */
            expandLabelKey?: string;
            /** Optional i18n key for the expanded-state hint (defaults to `INFORMATION_PANEL_CLICK_TO_COLLAPSE`). */
            collapseLabelKey?: string;
        }>(),
        {
            icon: 'material-symbols:info-outline-rounded',
        },
    );

    const expanded = defineModel<boolean>('expanded', { default: true });
    const bodyId = useId();
    const { t } = useI18n();

    const expandLabel = computed(() =>
        t(props.expandLabelKey ?? 'INFORMATION_PANEL_CLICK_TO_EXPAND'),
    );
    const collapseLabel = computed(() =>
        t(props.collapseLabelKey ?? 'INFORMATION_PANEL_CLICK_TO_COLLAPSE'),
    );
</script>

