<template>
    <div class="flex h-full flex-col gap-4 overflow-y-auto">
        <div class="rounded border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
            <div class="flex items-center justify-between">
                <span>{{ t('GENERAL_AMOUNT') }}</span>
                <span class="font-semibold">{{ currency }} {{ formatAmount(totalAmount) }}</span>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <span>{{ t('PAYMENTS_CHARGEBACK_RETAIN_PERCENTAGE') }}</span>
                <span class="font-semibold">{{ retainedPercentage }}%</span>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <span>{{ t('GENERAL_RETAINED_AMOUNT') }}</span>
                <span class="font-semibold">{{ currency }} {{ formatAmount(retainedAmount) }}</span>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <span>{{ t('GENERAL_REFUNDED_AMOUNT') }}</span>
                <span class="font-semibold">{{ currency }} {{ formatAmount(refundAmount) }}</span>
            </div>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">
                {{ t('PAYMENTS_CHARGEBACK_RETAIN_PERCENTAGE') }}
            </label>
            <input
                v-model.number="retainedPercentage"
                type="number"
                min="0"
                max="100"
                step="1"
                class="w-full rounded border border-gray-300 px-3 py-2 text-sm outline-none transition focus:border-[var(--color-background)]"
            />
            <p class="mt-2 text-xs text-gray-500">
                {{ t('PAYMENTS_CHARGEBACK_HELP_TEXT') }}
            </p>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, ref } from 'vue';
    import { useI18n } from 'vue-i18n';

    defineComponent({ name: 'ChargebackModal' });

    const props = defineProps<{
        props: {
            amount: number;
            currency?: string;
            initialRetainedAmount?: number;
        };
    }>();

    const { t } = useI18n();

    const totalAmount = Number(props.props.amount ?? 0);
    const currency = props.props.currency || 'EUR';
    const initialRetainedAmount = Math.min(
        Math.max(0, Number(props.props.initialRetainedAmount ?? 0)),
        Math.max(0, totalAmount),
    );
    const retainedPercentage = ref<number>(
        totalAmount > 0 ? Math.round((initialRetainedAmount / totalAmount) * 100) : 0,
    );

    const retainedAmount = computed(() => {
        const pct = Math.min(100, Math.max(0, Number(retainedPercentage.value || 0)));
        return Number(((totalAmount * pct) / 100).toFixed(2));
    });

    const refundAmount = computed(() => {
        return Number(Math.max(0, totalAmount - retainedAmount.value).toFixed(2));
    });

    function formatAmount(value: number): string {
        return Number.isFinite(value) ? value.toFixed(2) : '0.00';
    }

    function getData() {
        return {
            retained_percentage: Math.min(100, Math.max(0, Number(retainedPercentage.value || 0))),
            retained_amount: retainedAmount.value,
            refund_amount: refundAmount.value,
            total_amount: totalAmount,
            currency,
        };
    }

    async function isValidForAccept(): Promise<boolean> {
        const pct = Number(retainedPercentage.value);
        return Number.isFinite(pct) && pct >= 0 && pct <= 100;
    }

    defineExpose({ getData, isValidForAccept });
</script>
