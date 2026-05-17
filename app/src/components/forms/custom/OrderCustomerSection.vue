<template>
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <Icon class="h-4 w-4 text-[var(--color-primary)]" icon="material-symbols:person" />
                    <span class="text-sm font-semibold text-gray-800">{{ t(options.label) }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Select an existing customer or create a new one directly in this order.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded border border-[var(--color-primary)] px-3 py-1.5 text-xs font-medium text-[var(--color-primary)] hover:bg-gray-50"
                    @click="openSelectCustomer"
                >
                    {{ t('GENERAL_SELECT') }}
                </button>
                <button
                    type="button"
                    class="rounded border border-gray-200 bg-[var(--color-background)] px-3 py-1.5 text-xs font-medium text-white hover:bg-[var(--color-highlight-dark)]"
                    @click="openCreateCustomer"
                >
                    {{ t('MODAL_CREATE') }}
                </button>
                <button
                    v-if="customerId != null"
                    type="button"
                    class="rounded border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50"
                    @click="clearSelection"
                >
                    {{ t('GENERAL_REMOVE') }}
                </button>
            </div>
        </div>

        <div class="mt-3 rounded border border-gray-200 bg-gray-50 p-3">
            <div v-if="isLoading" class="text-xs text-gray-500">{{ t('FORM_LOADING') }}...</div>
            <div v-else-if="selectedCustomer" class="grid grid-cols-1 gap-3 text-gray-700 md:grid-cols-2">
                <div class="flex items-start gap-2.5 rounded-md border border-gray-200/80 bg-white px-3 py-2 shadow-sm">
                    <Icon
                        class="mt-0.5 h-[18px] w-[18px] shrink-0 text-[var(--color-primary)]"
                        icon="material-symbols:person"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                            {{ t('GENERAL_NAME') }}
                        </div>
                        <div class="text-sm font-medium text-gray-900">{{ selectedCustomer.name || '—' }}</div>
                    </div>
                </div>
                <div class="flex items-start gap-2.5 rounded-md border border-gray-200/80 bg-white px-3 py-2 shadow-sm">
                    <Icon
                        class="mt-0.5 h-[18px] w-[18px] shrink-0 text-[var(--color-primary)]"
                        icon="material-symbols:mail-outline"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                            {{ t('GENERAL_EMAIL') }}
                        </div>
                        <div class="break-all text-sm font-medium text-gray-900">{{ selectedCustomer.email || '—' }}</div>
                    </div>
                </div>
                <div class="flex items-start gap-2.5 rounded-md border border-gray-200/80 bg-white px-3 py-2 shadow-sm">
                    <Icon
                        class="mt-0.5 h-[18px] w-[18px] shrink-0 text-[var(--color-primary)]"
                        icon="material-symbols:call"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                            {{ t('GENERAL_PHONE') }}
                        </div>
                        <div class="text-sm font-medium text-gray-900">{{ selectedCustomer.phone || '—' }}</div>
                    </div>
                </div>
                <div class="flex items-start gap-2.5 rounded-md border border-gray-200/80 bg-white px-3 py-2 shadow-sm">
                    <Icon
                        class="mt-0.5 h-[18px] w-[18px] shrink-0 text-[var(--color-primary)]"
                        icon="material-symbols:location-city"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                            {{ t('GENERAL_CITY') }}
                        </div>
                        <div class="text-sm font-medium text-gray-900">{{ selectedCustomer.city || '—' }}</div>
                    </div>
                </div>
            </div>
            <div v-else class="text-xs text-gray-500">No customer selected yet.</div>
        </div>

        <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, ref, watch } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import Customer from '@/models/general/Customer';
    import { useModalService } from '@/composables/useModalService';
    import { useToastService } from '@/composables/useToastService';
    import EntityPicker from '@/components/modals/content-modals/EntityPicker.vue';
    import EntityModal from '@/components/modals/content-modals/EntityModal.vue';
    import { ModalActionTarget, ModalActionType, ModalResultType, ModalType } from '@/modules/enums/modals/ModalEnums';
    import type { ModalResult } from '@/modules/types/ModalTypes';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';

    defineComponent({ name: 'OrderCustomerSection' });

    type CustomerPreview = {
        id: number;
        name?: string;
        email?: string;
        phone?: string;
        city?: string;
    };

    const props = defineProps<{
        modelValue: number | null;
        options: BaseInputFieldOptions;
        error?: string;
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', value: number | null): void;
        (e: 'blur'): void;
    }>();

    const { t } = useI18n();
    const modalService = useModalService();
    const toastService = useToastService();

    const customerModel = new Customer();
    const selectedCustomer = ref<CustomerPreview | null>(null);
    const isLoading = ref(false);

    /** Resolved id for API fetch; null when unset or placeholder 0 from API/forms. */
    const customerId = computed((): number | null => {
        const raw = props.modelValue;
        if (raw == null) return null;
        const n = Number(raw);
        if (!Number.isFinite(n) || n <= 0) return null;
        return n;
    });

    async function loadCustomer(id: number | null): Promise<void> {
        if (id == null || !Number.isFinite(id) || id <= 0) {
            selectedCustomer.value = null;
            return;
        }

        isLoading.value = true;
        try {
            const res = await customerModel.show(id);
            const payload = ((res.data as unknown as { data?: unknown })?.data ?? res.data) as Record<string, unknown>;
            selectedCustomer.value = {
                id: Number(payload.id),
                name: payload.name as string | undefined,
                email: payload.email as string | undefined,
                phone: payload.phone as string | undefined,
                city: payload.city as string | undefined,
            };
        } catch {
            selectedCustomer.value = null;
            toastService.error('GENERAL_FETCH_ERROR');
        } finally {
            isLoading.value = false;
        }
    }

    function openSelectCustomer(): void {
        modalService.open({
            title: 'GENERAL_SELECT_ENTITY',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            component: EntityPicker,
            panelClass: 'w-[80vw] h-[90vh] max-w-[80vw] max-h-[90vh]',
            disableDefaultPadding: true,
            props: {
                entity: 'customers',
                model: Customer,
                includes: [],
                ids: customerId.value != null ? [customerId.value] : [],
            },
            actions: [
                {
                    type: ModalResultType.DISMISS,
                    name: 'GENERAL_CANCEL',
                    variant: ModalActionType.DISMISS,
                    target: ModalActionTarget.CANCEL,
                },
                {
                    type: ModalResultType.ACCEPT,
                    name: 'GENERAL_SAVE',
                    variant: ModalActionType.PRIMARY,
                    target: ModalActionTarget.SAVE,
                },
            ],
            onAccept: (result: ModalResult | null) => {
                if (!result?.data) return;
                const data = result.data as { selectedIds?: number[] };
                const pickedId = data.selectedIds?.[0] ?? null;
                emit('update:modelValue', pickedId);
                emit('blur');
            },
        });
    }

    function openCreateCustomer(): void {
        modalService.open({
            title: 'CUSTOMER_MODAL_CREATE',
            type: ModalType.SLIDE_IN_RIGHT,
            component: EntityModal,
            props: {
                entity: 'customers',
                model: Customer,
            },
            actions: [
                {
                    type: ModalResultType.DISMISS,
                    name: 'GENERAL_CANCEL',
                    variant: ModalActionType.DISMISS,
                    target: ModalActionTarget.CANCEL,
                },
                {
                    type: ModalResultType.ACCEPT,
                    name: 'GENERAL_SAVE',
                    variant: ModalActionType.PRIMARY,
                    target: ModalActionTarget.SAVE,
                },
            ],
            onAccept: async (result: ModalResult | null) => {
                if (!result?.data || result.target !== ModalActionTarget.SAVE) return;
                const data = result.data as { record?: Record<string, unknown> };
                try {
                    const res = await customerModel.create(data.record ?? {});
                    const payload = ((res.data as Record<string, unknown>)?.data ?? res.data) as Record<string, unknown>;
                    const newId = Number(payload.id);
                    if (!Number.isNaN(newId)) {
                        emit('update:modelValue', newId);
                        emit('blur');
                    }
                } catch {
                    toastService.error('GENERAL_SAVE_ERROR');
                }
            },
        });
    }

    function clearSelection(): void {
        emit('update:modelValue', null);
        emit('blur');
    }

    watch(
        customerId,
        (id) => {
            loadCustomer(id).catch(() => {
                /* handled in loadCustomer */
            });
        },
        { immediate: true },
    );
</script>
