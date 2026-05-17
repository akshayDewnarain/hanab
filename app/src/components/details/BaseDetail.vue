<template>
    <Transition mode="out-in" name="detail-fade">
        <div v-if="isLoading" key="loading" class="w-full h-full px-4 my-2 overflow-y-auto">
            <div class="flex h-full min-h-[260px] items-center justify-center">
                <div
                    class="h-10 w-10 rounded-full border-2 border-gray-300 border-t-[var(--color-primary)] animate-spin"
                ></div>
            </div>
        </div>
        <div v-else key="content" class="w-full h-full px-4 my-2 overflow-y-auto pb-20">
            <div class="detail-stagger">
                <div class="detail-stagger-item">
                    <slot name="header">
                        <h1 class="text-2xl font-bold mb-4">Detail View</h1>
                    </slot>
                </div>
                <div class="detail-stagger-item">
                    <FormBuilder v-slot="{ submit, isDirty, revertAll, applyChanges }" :instance="_instance">
                        <!-- Viewport-fixed bar (teleported: avoids transform ancestors + no translate-x/y clash on one element) -->
                        <Teleport to="body">
                            <Transition
                                enter-active-class="transform transition ease-out duration-200"
                                enter-from-class="translate-y-[calc(100%+1rem)] opacity-0"
                                enter-to-class="translate-y-0 opacity-100"
                                leave-active-class="transform transition ease-in duration-150"
                                leave-from-class="translate-y-0 opacity-100"
                                leave-to-class="translate-y-[calc(100%+1rem)] opacity-0"
                            >
                                <div
                                    v-if="isDirty"
                                    class="fixed inset-x-0 bottom-0 z-[100] flex justify-center px-4 pb-4 pointer-events-none"
                                >
                                    <div
                                        class="pointer-events-auto flex h-[60px] w-full max-w-[900px] items-center justify-between rounded-lg border border-gray-200 bg-white px-4 shadow-lg"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Icon
                                                class="h-5 w-5 text-[var(--color-warning)]"
                                                icon="material-symbols:warning-outline-rounded"
                                            />
                                            <span class="font-semibold"> {{ t('GENERAL_UNSAVED_CHANGES') }} </span>
                                            <span class="hidden text-sm text-gray-500 sm:inline">
                                                {{ t('GENERAL_APPLY_OR_REVERT') }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button
                                                class="flex h-full cursor-pointer items-center justify-center rounded border border-[var(--color-background)] px-3 py-1 text-[var(--color-primary)] shadow-md transition-colors duration-200 ease-in hover:bg-gray-200"
                                                type="button"
                                                @click="revertAll?.()"
                                            >
                                                Revert
                                            </button>
                                            <button
                                                class="flex h-full cursor-pointer items-center justify-center rounded border border-gray-200 bg-[var(--color-background)] px-3 py-1 text-white shadow-md transition-colors duration-200 ease-in hover:bg-[var(--color-highlight-dark)]"
                                                type="button"
                                                @click="onApply(submit, applyChanges || (() => {}))"
                                            >
                                                Apply
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </Teleport>
                    </FormBuilder>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, onMounted, watch } from 'vue';
    import { useToastService } from '@/composables/useToastService';
    import { useComponentState } from '@/composables/components/useComponentState';
    import { useRoute } from 'vue-router';
    import FormBuilder from '@/components/forms/FormBuilder.vue';
    import { ModelConstructor } from '@/modules/types/ModelTypes';
    import { useI18n } from 'vue-i18n';
    import { Icon } from '@iconify/vue';

    defineComponent({
        name: 'BaseDetail',
    });

    const props = withDefaults(
        defineProps<{
            model: ModelConstructor<unknown>;
            /** When provided (e.g. in a modal), use this instead of route params */
            id?: number | null;
        }>(),
        { id: undefined },
    );

    const route = useRoute();
    const _instance = new props.model();
    const _toastService = useToastService();

    const { isLoading, setLoading, setContent } = useComponentState();
    const { t } = useI18n();
    const id = computed(() => {
        if (props.id != null && Number.isFinite(props.id)) {
            return props.id as number;
        }
        const n = Number(route.params.id);
        return Number.isFinite(n) ? n : null;
    });

    async function fetch(): Promise<void> {
        const n = id.value;
        if (n == null || !Number.isFinite(n)) return;

        try {
            const res = await _instance.show(n);
            const payload = (res.data as Record<string, unknown>)?.data ?? res.data;
            Object.assign(_instance, payload);
        } catch {
            _toastService.error('ERROR_FAILED_TO_FETCH');
        } finally {
            setContent();
        }
    }

    async function onApply(submit: () => Promise<unknown>, applyChanges: () => void) {
        try {
            const res = await submit();
            applyChanges();
            _instance.update(_instance.id, res as Record<string, unknown>);
            _toastService.success('Changes saved');
        } catch {
            _toastService.error('Failed to save');
        }
    }

    onMounted(async () => {
        setLoading(true);
        fetch().catch(() => console.error('Error in onMounted'));
    });

    watch(id, (newId) => {
        if (newId != null && Number.isFinite(newId)) {
            setLoading(true);
            fetch().catch(() => console.error('Error in watch id'));
        }
    });
</script>

<style scoped>
    .detail-fade-enter-active,
    .detail-fade-leave-active {
        transition: opacity 0.2s ease;
    }

    .detail-fade-enter-from,
    .detail-fade-leave-to {
        opacity: 0;
    }

    .detail-stagger-item {
        opacity: 0;
        transform: translateY(4px);
        animation: detail-stagger-in 0.25s ease-out forwards;
    }

    .detail-stagger-item:nth-child(1) {
        animation-delay: 40ms;
    }

    .detail-stagger-item:nth-child(2) {
        animation-delay: 120ms;
    }

    @keyframes detail-stagger-in {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
