<template>
    <span class="group/hint relative inline-flex align-middle">
        <button
            type="button"
            class="inline-flex size-5 shrink-0 cursor-help items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-[var(--color-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[var(--color-primary)]"
            :aria-label="text"
        >
            <Icon class="size-4" icon="material-symbols:info-outline-rounded" />
        </button>
        <span
            role="tooltip"
            class="pointer-events-none absolute bottom-[calc(100%+6px)] left-1/2 z-50 w-max max-w-[16rem] -translate-x-1/2 rounded-sm border border-slate-200/90 bg-white px-2.5 py-2 text-left text-xs font-normal leading-snug text-slate-600 opacity-0 shadow-md transition-opacity duration-150 group-hover/hint:opacity-100 group-focus-within/hint:opacity-100"
        >
            {{ text }}
            <span
                class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-white"
                aria-hidden="true"
            />
        </span>
    </span>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';

    defineProps<{
        text: string;
    }>();
</script>
