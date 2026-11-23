<template>
    <DetailView
        :busy="isSaving"
        :dirty="isDirty"
        :icon="icon"
        :labels="labels"
        :loading="isLoading"
        :title="title"
        @apply="onApply"
        @revert="onRevert"
    >
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <FormBuilder v-slot="scope" :instance="_instance">
            <FormScopeBridge :scope="scope" @sync="syncForm" />
        </FormBuilder>
    </DetailView>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, isRef, ref, watch, type ComputedRef, type PropType } from 'vue';
    import { DetailView } from '@freelance/vue-ui';
    import { useToastService } from '@/composables/useToastService';
    import { useComponentState } from '@/composables/components/useComponentState';
    import { useRoute } from 'vue-router';
    import FormBuilder from '@/components/forms/FormBuilder.vue';
    import type { ModelConstructor } from '@/modules/types/support/models/ModelConstructor.ts';
    import { useI18n } from 'vue-i18n';

    defineOptions({ name: 'BaseDetail' });

    type FormScope = {
        submit: () => Promise<unknown>;
        isDirty?: boolean | ComputedRef<boolean>;
        revertAll?: () => void;
        applyChanges?: () => void;
    };

    function readDirty(value: FormScope['isDirty']): boolean {
        if (value == null) return false;
        return Boolean(isRef(value) ? value.value : value);
    }

    const FormScopeBridge = defineComponent({
        name: 'FormScopeBridge',
        props: {
            scope: { type: Object as PropType<FormScope>, required: true },
        },
        emits: ['sync'],
        setup(props, { emit }) {
            watch(
                () =>
                    [
                        readDirty(props.scope.isDirty),
                        props.scope.submit,
                        props.scope.revertAll,
                        props.scope.applyChanges,
                    ] as const,
                () => emit('sync', props.scope),
                { immediate: true },
            );
            return () => null;
        },
    });

    const emit = defineEmits<{ saved: [] }>();

    const props = withDefaults(
        defineProps<{
            model: ModelConstructor<unknown>;
            /** Already translated title for the library header. */
            title?: string;
            /** Built-in header icon: info, person, note, badge, or warning. */
            icon?: string;
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

    const isDirty = ref(false);
    const isSaving = ref(false);
    const formActions: FormScope = {
        submit: async () => ({}),
        revertAll: () => {},
        applyChanges: () => {},
    };

    const labels = computed(() => ({
        loading: t('GENERAL_LOADING'),
        title: props.title,
        unsaved: t('GENERAL_UNSAVED_CHANGES'),
        unsavedHint: t('GENERAL_APPLY_OR_REVERT'),
        revert: t('GENERAL_REVERT'),
        apply: t('GENERAL_APPLY'),
    }));

    const id = computed(() => {
        if (props.id != null && Number.isFinite(props.id)) {
            return props.id as number;
        }
        const n = Number(route.params.id);
        return Number.isFinite(n) ? n : null;
    });

    function syncForm(scope: FormScope): void {
        formActions.submit = scope.submit;
        formActions.revertAll = scope.revertAll ?? (() => {});
        formActions.applyChanges = scope.applyChanges ?? (() => {});
        const dirty = readDirty(scope.isDirty);
        if (isDirty.value !== dirty) isDirty.value = dirty;
    }

    async function fetchRecord(): Promise<void> {
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

    function onRevert(): void {
        formActions.revertAll?.();
    }

    async function onApply(): Promise<void> {
        if (isSaving.value) return;

        isSaving.value = true;
        try {
            const res = await formActions.submit();
            formActions.applyChanges?.();
            _instance.update(_instance.id, res as Record<string, unknown>);
            _toastService.success('Changes saved');
            emit('saved');
        } catch {
            _toastService.error('Failed to save');
        } finally {
            isSaving.value = false;
        }
    }

    watch(isLoading, (loading) => {
        if (loading) isDirty.value = false;
    });

    watch(
        id,
        (newId) => {
            if (newId == null || !Number.isFinite(newId)) {
                setContent();
                return;
            }

            setLoading(true);
            fetchRecord().catch(() => console.error('Error in watch id'));
        },
        { immediate: true },
    );
</script>
