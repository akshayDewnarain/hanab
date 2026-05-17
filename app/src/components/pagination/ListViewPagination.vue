<template>
    <div
        v-if="pages && compact"
        class="space-y-2 px-4 py-3"
    >
        <div class="flex items-center gap-2">
            <span class="shrink-0 text-xs text-slate-500">{{ t('PAGINATION_PER_PAGE') }}</span>
            <dropdown
                compact
                class="min-w-0 flex-1"
                :hide-triangle="true"
                :model-value="selectedPerPage"
                :options="pageOptions"
                :placeholder="'DROPDOWN_PLACEHOLDER'"
                position="top"
                align="right"
                @update:modelValue="changePerPage"
            />
        </div>

        <div class="flex items-center justify-between gap-1">
            <button
                type="button"
                class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-[var(--color-primary)] disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-slate-500"
                :aria-label="t('PAGINATION_PREV')"
                :disabled="current <= 1"
                @click="previousPage"
            >
                <Icon class="size-5" icon="material-symbols:chevron-left-rounded" />
            </button>

            <div class="flex min-w-0 flex-1 flex-col items-center">
                <span class="text-sm tabular-nums text-slate-600">
                    <span class="font-semibold text-[var(--color-primary)]">{{ current }}</span>
                    <span class="text-slate-400"> / {{ pages }}</span>
                </span>
                <p class="mt-0.5 text-center text-[11px] leading-tight text-slate-500">
                    <span class="font-medium tabular-nums text-slate-600">{{ rangeStart }}–{{ rangeEnd }}</span>
                    {{ ' ' }}{{ t('PAGINATION_OF') }} {{ total }} {{ t('PAGINATION_RESULTS') }}
                </p>
            </div>

            <button
                type="button"
                class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-[var(--color-primary)] disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-slate-500"
                :aria-label="t('PAGINATION_NEXT')"
                :disabled="current >= pages"
                @click="nextPage"
            >
                <Icon class="size-5" icon="material-symbols:chevron-right-rounded" />
            </button>
        </div>
    </div>

    <div
        v-else-if="pages"
        class="flex items-center justify-between border-t border-gray-100 bg-gray-100 px-4 py-3 sm:px-6"
    >
        <div class="flex flex-1 justify-between sm:hidden">
            <a
                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-500 hover:bg-gray-100 transition-all duration-200 ease-in-out"
                href="javascript:void(0)"
                @click="previousPage"
            >
                {{ t('PAGINATION_PREV') }}
            </a>

            <a
                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-500 hover:bg-gray-100 transition-all duration-200 ease-in-out"
                href="javascript:void(0)"
                @click="nextPage"
            >
                {{ t('PAGINATION_NEXT') }}
            </a>
        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm">
                    {{ t('PAGINATION_SHOWING') }}
                    <span class="font-bold">{{ current * perPage - perPage + 1 }}</span>

                    {{ t('PAGINATION_TO') }}
                    <span class="font-bold">{{ current * perPage < total ? current * perPage : total }}</span>

                    {{ t('PAGINATION_OF') }}
                    <span class="font-bold">{{ total }}</span>

                    {{ t('PAGINATION_RESULTS') }}
                </p>
            </div>
            <div class="flex space-x-2">
                <div class="mx-4 flex items-center">
                    <span class="text-sm mr-2 whitespace-nowrap font-bold">{{ t('NAVIGATION_ITEMS_PER_PAGE') }}</span>
                    <dropdown
                        :hide-triangle="false"
                        :model-value="selectedPerPage"
                        :options="pageOptions"
                        :placeholder="'DROPDOWN_PLACEHOLDER'"
                        :position="'top'"
                        align="right"
                        @update:modelValue="changePerPage"
                    />
                </div>

                <nav aria-label="Pagination" class="relative z-0 inline-flex -space-x-px rounded-md shadow">
                    <a
                        class="relative text-white inline-flex items-center rounded-l-md border border-gray-300 px-2 py-2 text-sm font-medium bg-[var(--color-background)] transition-all duration-200 ease-in-out hover:bg-[var(--color-highlight-dark)]"
                        href="javascript:void(0)"
                        @click="previousPage"
                    >
                        <span class="sr-only">Previous</span>

                        <svg
                            aria-hidden="true"
                            class="h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                clip-rule="evenodd"
                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                fill-rule="evenodd"
                            />
                        </svg>
                    </a>

                    <a
                        v-if="current > 2"
                        class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:bg-gray-100 transition-all duration-200 ease-in-out"
                        href="javascript:void(0)"
                        @click="changePage(1)"
                    >
                        1
                    </a>

                    <a
                        v-if="current > 3 && pageNumbers.length !== 3"
                        class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:bg-gray-100 transition-all duration-200 ease-in-out"
                        href="javascript:void(0)"
                    >
                        ...
                    </a>

                    <template v-for="(page, index) in pageNumbers">
                        <a
                            v-if="page > current - 2 && page < current + 2"
                            :key="index"
                            class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:bg-gray-100 transition-all duration-200 ease-in-out"
                            href="javascript:void(0)"
                            @click="changePage(page)"
                        >
                            {{ page }}
                        </a>
                    </template>

                    <a
                        v-if="current <= pageNumbers.length - 3 && pageNumbers.length !== 3"
                        class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:bg-gray-100 transition-all duration-200 ease-in-out"
                        href="javascript:void(0)"
                    >
                        ...
                    </a>

                    <a
                        v-if="current <= pageNumbers.length - 2"
                        class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:bg-gray-100 transition-all duration-200 ease-in-out"
                        href="javascript:void(0)"
                        @click="changePage(pages)"
                    >
                        {{ pages }}
                    </a>

                    <a
                        class="relative text-white inline-flex items-center rounded-r-md border border-gray-300 px-2 py-2 text-sm font-medium bg-[var(--color-background)] transition-all duration-200 ease-in-out hover:bg-[var(--color-highlight-dark)]"
                        href="javascript:void(0)"
                        @click="nextPage"
                    >
                        <span class="sr-only">Next</span>

                        <svg
                            aria-hidden="true"
                            class="h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                clip-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                fill-rule="evenodd"
                            />
                        </svg>
                    </a>
                </nav>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, ref, watch } from 'vue';
    import { Icon } from '@iconify/vue';
    import Dropdown from '@/components/inputs/Dropdown.vue';
    import { useI18n } from 'vue-i18n';

    const props = withDefaults(
        defineProps<{
            current: number;
            perPage: number;
            total: number;
            compact?: boolean;
        }>(),
        {
            current: 1,
            perPage: 10,
            total: 1,
            compact: false,
        },
    );

    const pageOptions = ref([10, 25, 50, 100]);
    const selectedPerPage = ref(props.perPage);
    const { t } = useI18n();

    const emit = defineEmits<{
        (e: 'update:currentPage', value: number): void;
        (e: 'update:perPage', value: number): void;
    }>();

    const pages = computed(() => Math.ceil(props.total / props.perPage));
    const rangeStart = computed(() => props.current * props.perPage - props.perPage + 1);
    const rangeEnd = computed(() =>
        props.current * props.perPage < props.total ? props.current * props.perPage : props.total,
    );
    const pageNumbers = computed(() => {
        const numbers = [];
        for (let i = 1; i <= pages.value; i++) {
            numbers.push(i);
        }
        return numbers;
    });

    function changePage(page: number): void {
        emit('update:currentPage', page);
    }

    function previousPage(): void {
        if (props.current > 1) {
            emit('update:currentPage', props.current - 1);
        }
    }

    function nextPage(): void {
        if (props.current < pages.value) {
            emit('update:currentPage', props.current + 1);
        }
    }

    function changePerPage(val: unknown): void {
        emit('update:perPage', val as number);
    }

    watch(
        () => props.perPage,
        (newVal) => {
            selectedPerPage.value = newVal;
        },
    );
</script>
