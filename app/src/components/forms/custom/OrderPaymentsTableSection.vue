<template>
    <div class="flex h-full min-h-[520px] flex-col rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2">
            <Icon class="h-4 w-4 text-[var(--color-primary)]" icon="material-symbols:table-outline" />
            <span class="text-sm font-semibold text-gray-800">{{ t(options.label) }}</span>
        </div>

        <ListView
            :key="listKey"
            :disable-row-navigation="true"
            :display-header="false"
            :display-table-configuration="false"
            :enable-default-actions="false"
            :entity="'payments'"
            :model="Payment"
            :selectable="true"
            :show-default-bulk-actions="false"
            :static-filters="staticFilters"
            :use-u-r-l-queries="false"
            class="min-h-0 flex-1 overflow-hidden"
        >
            <template #bulk-actions="{ selectedIds, clear }">
                <button
                    v-if="Array.isArray(selectedIds) && selectedIds.length === 1"
                    type="button"
                    class="flex items-center justify-center rounded border border-amber-600 bg-amber-500 px-2 py-1 text-white shadow-md transition-colors duration-200 ease-in hover:bg-amber-600"
                    :disabled="chargebackLoading"
                    @click="applyChargebackFromSelection(selectedIds, clear)"
                >
                    {{ chargebackLoading ? t('GENERAL_PROCESSING') : t('PAYMENTS_CHARGEBACK_SELECTED') }}
                </button>
            </template>
        </ListView>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import ChargebackModal from '@/components/modals/content-modals/ChargebackModal.vue';
    import { useModalService } from '@/composables/useModalService';
    import { useToastService } from '@/composables/useToastService';
    import { ModalActionTarget, ModalActionType, ModalResultType, ModalType } from '@/modules/enums/modals/ModalEnums';
    import type { ModalResult } from '@/modules/types/ModalTypes';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
    import http from '@/utils/http';
    import ListView from '@/components/list-views/ListView.vue';
    import Payment from '@/models/bookings/Payment';

    defineComponent({ name: 'OrderPaymentsTableSection' });

    const props = defineProps<{
        modelValue?: unknown;
        options: BaseInputFieldOptions;
        error?: string;
        formData?: Record<string, unknown>;
        setFieldValue?: (name: string, value: unknown) => void;
        getFieldValue?: (name: string) => unknown;
        reservation?: Record<string, unknown>;
    }>();

    const { t } = useI18n();
    const toast = useToastService();
    const modalService = useModalService();
    const listKey = ref(0);
    const chargebackLoading = ref(false);
    type PaymentRow = {
        type?: string;
        amount?: number | string;
        refunded_amount?: number | string;
        retained_amount?: number | string;
        currency?: string;
    };

    const reservationId = computed<number | null>(() => {
        const fromForm = Number(props.formData?.id);
        if (Number.isFinite(fromForm) && fromForm > 0) return fromForm;
        const fromReservation = Number(props.reservation?.id);
        if (Number.isFinite(fromReservation) && fromReservation > 0) return fromReservation;
        return null;
    });

    const staticFilters = computed<Record<string, unknown>>(() => {
        if (reservationId.value == null) return {};
        return { reservation_id: reservationId.value };
    });

    async function applyChargeback(selectedIds: number[], clear: () => void) {
        if (!reservationId.value) {
            toast.error('GENERAL_FETCH_ERROR');
            return;
        }

        if (!selectedIds.length) {
            return;
        }

        chargebackLoading.value = true;
        try {
            const paymentApi = new Payment();
            const selectedPayments = await Promise.all<PaymentRow>(
                selectedIds.map(async (id) => {
                    const response = await paymentApi.show(id);
                    const payload = response.data as unknown as { data?: unknown };
                    return (payload?.data ?? response.data) as PaymentRow;
                }),
            );

            const hasDepositPayment = selectedPayments.some((payment: PaymentRow) => payment?.type === 'deposit');
            if (!hasDepositPayment) {
                toast.error('PAYMENTS_CHARGEBACK_DEPOSIT_ONLY');
                return;
            }

            const selectedDepositPayment = selectedPayments.find((payment: PaymentRow) => payment?.type === 'deposit');
            const depositAmount = Number(selectedDepositPayment?.amount ?? 0);
            const alreadyRefunded = Number(selectedDepositPayment?.refunded_amount ?? 0);
            const alreadyRetained = Number(selectedDepositPayment?.retained_amount ?? 0);
            const remainingAmount = Number(Math.max(0, depositAmount - alreadyRefunded).toFixed(2));

            if (remainingAmount <= 0) {
                toast.error('PAYMENTS_CHARGEBACK_NOTHING_LEFT');
                return;
            }

            const modalResult: ModalResult = await modalService.open({
                title: 'PAYMENTS_CHARGEBACK_MODAL_TITLE',
                type: ModalType.SLIDE_IN_TOP_CENTER,
                component: ChargebackModal,
                props: {
                    amount: remainingAmount,
                    currency: selectedDepositPayment?.currency || 'EUR',
                    initialRetainedAmount: Math.min(remainingAmount, alreadyRetained),
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
                size: 'md',
            });

            if (modalResult.type !== ModalResultType.ACCEPT) {
                return;
            }

            const retainedAmount = Number(
                (modalResult.data as Record<string, unknown> | undefined)?.retained_amount ?? 0,
            );

            await http.post(`reservations/${reservationId.value}/refund-deposit`, {
                retained_amount: retainedAmount,
            });

            toast.success('PAYMENTS_CHARGEBACK_SUCCESS');
            clear();
            listKey.value += 1;
        } catch (error: any) {
            const message = error?.response?.data?.message || error?.response?.data?.error;
            if (message) {
                toast.error(message);
                return;
            }
            toast.error('PAYMENTS_CHARGEBACK_FAILED');
        } finally {
            chargebackLoading.value = false;
        }
    }

    function applyChargebackFromSelection(selectedIds: unknown, clear: () => void) {
        const parsed = Array.isArray(selectedIds)
            ? selectedIds.filter((id): id is number => typeof id === 'number')
            : [];
        void applyChargeback(parsed, clear);
    }
</script>
