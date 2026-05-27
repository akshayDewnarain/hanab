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
            class="fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-md"
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
                    class="flex max-h-[min(88dvh,680px)] w-full flex-col overflow-hidden rounded-b-2xl bg-slate-50 shadow-2xl"
                >
                    <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span
                                class="relative inline-flex size-8 items-center justify-center rounded-lg bg-sky-100 text-sky-600"
                            >
                                <Icon class="size-5" icon="material-symbols:notifications-outline-rounded" />
                                <span class="absolute -right-0.5 -top-0.5 inline-flex size-2.5 rounded-full bg-sky-500" />
                            </span>
                            <h2 class="text-base font-semibold text-slate-900">
                                {{ t('MOBILE_NOTIFICATIONS_TITLE') }}
                            </h2>
                        </div>
                        <button
                            type="button"
                            class="inline-flex size-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600"
                            @click="$emit('close')"
                        >
                            <Icon class="size-5" icon="material-symbols:close-rounded" />
                        </button>
                    </header>

                    <div class="min-h-0 flex-1 overflow-y-auto p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            {{ t('MOBILE_NOTIFICATIONS_TODAY') }}
                        </p>

                        <article class="flex gap-3 rounded-xl border border-sky-200 bg-white p-4 shadow-sm">
                            <span
                                class="flex size-6 shrink-0 items-center justify-center rounded-md bg-sky-600 text-white"
                            >
                                <Icon class="size-6" icon="material-symbols:exclamation-rounded" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-semibold leading-snug text-slate-900">
                                        {{ t('MOBILE_NOTIFICATION_BIRTHDAY_TITLE') }}
                                    </p>
                                    <span
                                        class="inline-flex shrink-0 items-center gap-1 text-xs text-slate-400"
                                    >
                                        <Icon class="size-3.5" icon="material-symbols:person-outline-rounded" />
                                        {{ t('MOBILE_NOTIFICATION_BIRTHDAY_TIME') }}
                                    </span>
                                </div>

                                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                                    {{ t('MOBILE_NOTIFICATION_BIRTHDAY_BODY', { name: demoEmployeeName }) }}
                                </p>

                                <div class="mt-3 flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex size-8 cursor-pointer items-center justify-center rounded-md bg-slate-100 text-sky-500 transition hover:bg-slate-200"
                                        :aria-label="t('MOBILE_NOTIFICATION_MARK_READ')"
                                        @click.prevent
                                    >
                                        <span class="grid size-5 place-items-center">
                                            <Icon
                                                class="col-start-1 row-start-1 size-5 -translate-x-1 text-sky-500"
                                                icon="material-symbols:check-small"
                                            />
                                            <Icon
                                                class="col-start-1 row-start-1 size-5 translate-x-1 text-sky-500"
                                                icon="material-symbols:check-small"
                                            />
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex size-8 cursor-pointer items-center justify-center rounded-md bg-slate-100 text-red-500 transition hover:bg-slate-200"
                                        :aria-label="t('MOBILE_NOTIFICATION_REMOVE')"
                                        @click.prevent
                                    >
                                        <Icon class="size-4" icon="material-symbols:delete-outline-rounded" />
                                    </button>
                                </div>
                            </div>
                        </article>

                        <article class="mt-3 flex gap-3 rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
                            <span
                                class="flex size-6 shrink-0 items-center justify-center rounded-md bg-amber-500 text-white"
                            >
                                <Icon class="size-6" icon="material-symbols:exclamation-rounded" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-semibold leading-snug text-slate-900">
                                        {{ t('MOBILE_NOTIFICATION_DOCUMENTS_TITLE') }}
                                    </p>
                                    <span
                                        class="inline-flex shrink-0 items-center gap-1 text-xs text-slate-400"
                                    >
                                        <Icon class="size-3.5" icon="material-symbols:person-outline-rounded" />
                                        {{ t('MOBILE_NOTIFICATION_DOCUMENTS_TIME') }}
                                    </span>
                                </div>

                                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                                    {{ t('MOBILE_NOTIFICATION_DOCUMENTS_BODY') }}
                                    <a
                                        href="#"
                                        class="font-medium italic text-amber-700 underline"
                                        @click.prevent
                                    >{{ demoProfileUrl }}</a>
                                </p>

                                <div class="mt-3 flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex size-8 cursor-pointer items-center justify-center rounded-md bg-slate-100 text-sky-500 transition hover:bg-slate-200"
                                        :aria-label="t('MOBILE_NOTIFICATION_MARK_READ')"
                                        @click.prevent
                                    >
                                        <span class="grid size-5 place-items-center">
                                            <Icon
                                                class="col-start-1 row-start-1 size-5 -translate-x-1 text-sky-500"
                                                icon="material-symbols:check-small"
                                            />
                                            <Icon
                                                class="col-start-1 row-start-1 size-5 translate-x-1 text-sky-500"
                                                icon="material-symbols:check-small"
                                            />
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex size-8 cursor-pointer items-center justify-center rounded-md bg-slate-100 text-red-500 transition hover:bg-slate-200"
                                        :aria-label="t('MOBILE_NOTIFICATION_REMOVE')"
                                        @click.prevent
                                    >
                                        <Icon class="size-4" icon="material-symbols:delete-outline-rounded" />
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </Transition>
        </div>
    </Transition>
</template>

<script setup lang="ts">
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';

    defineProps<{
        open: boolean;
    }>();

    defineEmits<{
        (e: 'close'): void;
    }>();

    const { t } = useI18n();

    /** Demo content for screenshots only. */
    const demoEmployeeName = 'Kay van de Bilt';
    const demoProfileUrl = 'profile';
</script>
