<template>
    <Transition
        enter-active-class="transition-opacity duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-[1px]"
            @click.self="$emit('close')"
        >
            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="-translate-y-full"
                enter-to-class="translate-y-0"
                leave-active-class="transition-transform duration-200 ease-in"
                leave-from-class="translate-y-0"
                leave-to-class="-translate-y-full"
            >
                <section
                    v-if="open"
                    class="flex h-full min-h-0 w-full flex-col overflow-hidden bg-white"
                >
                    <header class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                        <h2 class="text-base font-semibold text-slate-900">
                            <slot name="title">Filters</slot>
                        </h2>
                        <button
                            type="button"
                            class="inline-flex size-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600"
                            @click="$emit('close')"
                        >
                            <Icon class="size-5" icon="material-symbols:close-rounded" />
                        </button>
                    </header>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <slot />
                    </div>

                    <footer class="border-t border-slate-200 bg-white px-4 py-3">
                        <slot name="footer" />
                    </footer>
                </section>
            </Transition>
        </div>
    </Transition>
</template>

<script setup lang="ts">
    import { Icon } from '@iconify/vue';

    defineProps<{
        open: boolean;
    }>();

    defineEmits<{
        (e: 'close'): void;
    }>();
</script>
