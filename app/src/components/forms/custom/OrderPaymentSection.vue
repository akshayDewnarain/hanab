<template>
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-4 flex items-center gap-2">
            <Icon class="h-4 w-4 text-[var(--color-primary)]" icon="material-symbols:payments-outline" />
            <span class="text-sm font-semibold text-gray-800">{{ t(options.label) }}</span>
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
            <span class="text-gray-600">{{ t('BOOKING_PAYMENT_PAYMENT_STATUS') }}</span>
            <span :class="paymentStatusClass" class="font-medium capitalize">
                {{ paymentStatusLabel }}
            </span>
        </div>

        <div class="overflow-hidden rounded border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-3 py-2">{{ t('GENERAL_NAME') }}</th>
                        <th class="px-3 py-2 text-right">{{ t('GENERAL_QUANTITY') }}</th>
                        <th class="px-3 py-2 text-right">{{ t('GENERAL_UNIT_PRICE') }}</th>
                        <th class="px-3 py-2 text-right">{{ t('GENERAL_TOTAL') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr v-for="line in orderLines" :key="line.key">
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900">{{ line.label }}</div>
                            <div v-if="line.description" class="text-xs text-gray-500">{{ line.description }}</div>
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-700">{{ line.quantity }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-700">EUR {{ formatPrice(line.unitPrice) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-900">
                            EUR {{ formatPrice(line.total) }}
                        </td>
                    </tr>
                    <tr v-if="orderLines.length === 0">
                        <td class="px-3 py-3 text-xs text-gray-500" colspan="4">No order lines available.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-3 space-y-1 rounded border border-gray-100 bg-gray-50 p-3 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-gray-600">{{ t('BOOKING_PAYMENT_SUBTOTAL') }}</span>
                <span class="font-medium tabular-nums">EUR {{ formatPrice(subtotal) }}</span>
            </div>
            <div v-if="discountAmount > 0" class="flex items-center justify-between text-red-600">
                <span>{{ t('BOOKING_PAYMENT_DISCOUNT') }}</span>
                <span class="font-medium tabular-nums">-EUR {{ formatPrice(discountAmount) }}</span>
            </div>
            <div v-if="couponDiscountAmount > 0" class="flex items-center justify-between text-green-700">
                <span>{{ t('BOOKING_ORDER_OVERVIEW_COUPON_USED') }}</span>
                <span class="font-medium tabular-nums">-EUR {{ formatPrice(couponDiscountAmount) }}</span>
            </div>
            <div v-if="depositAmount > 0" class="flex items-center justify-between text-gray-700">
                <span>{{ t('GENERAL_DEPOSIT') }}</span>
                <span class="font-medium tabular-nums">EUR {{ formatPrice(depositAmount) }}</span>
            </div>
            <div class="border-t border-gray-200 pt-2 flex items-center justify-between text-base font-semibold">
                <span>{{ t('BOOKING_PAYMENT_ORDER_TOTAL') }}</span>
                <span class="tabular-nums">EUR {{ formatPrice(orderTotal) }}</span>
            </div>
        </div>

        <div class="mt-4 border-t border-gray-100 pt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
            <button
                type="button"
                class="inline-flex flex-1 min-w-[200px] items-center justify-center rounded border border-[var(--color-background)] bg-[var(--color-background)] px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="inviteDisabled || sending || !reservationId || !hasCustomerEmail"
                @click="sendInvite"
            >
                <span v-if="sending" class="inline-flex items-center gap-2">
                    <span
                        class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                        aria-hidden="true"
                    />
                    {{ t('RESERVATION_PAYMENT_SENDING') }}
                </span>
                <span v-else class="inline-flex w-full items-center justify-between gap-2">
                    <span class="font-semibold">{{ t('RESERVATION_PAYMENT_SEND_ORDER_OVERVIEW') }}</span>
                    <Icon class="h-4 w-4 shrink-0" icon="material-symbols:outgoing-mail-outline-rounded" />
                </span>
            </button>
            <button
                type="button"
                class="inline-flex flex-1 min-w-[200px] items-center justify-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 shadow-sm transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="copyingPaymentLink || !reservationId"
                @click="copyPaymentLink"
            >
                <span v-if="copyingPaymentLink" class="inline-flex items-center gap-2">
                    <span
                        class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-[var(--color-primary)]"
                        aria-hidden="true"
                    />
                    {{ t('RESERVATION_PAYMENT_COPYING_LINK') }}
                </span>
                <span v-else class="inline-flex w-full items-center justify-between gap-2">
                    <span class="font-semibold">{{ t('RESERVATION_PAYMENT_COPY_PAYMENT_LINK') }}</span>
                    <Icon class="h-4 w-4 shrink-0" icon="material-symbols:content-copy-outline-rounded" />
                </span>
            </button>
            <p v-if="!hasCustomerEmail" class="mt-1 w-full text-xs text-amber-700 sm:order-first">
                {{ t('RESERVATION_PAYMENT_NO_CUSTOMER_EMAIL') }}
            </p>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, ref, watch } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import http from '@/utils/http';
    import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
    import { useToastService } from '@/composables/useToastService';
    import Customer from '@/models/general/Customer';
    import TimeSlot from '@/models/general/TimeSlot';
    import PricingTier from '@/models/general/PricingTier';
    import { useBookingSettings } from '@/composables/useBookingSettings';
    import type { BookingSettings } from '@/modules/types/BookingTypes';

    defineComponent({ name: 'OrderPaymentSection' });

    const props = defineProps<{
        modelValue?: unknown;
        options: BaseInputFieldOptions;
        error?: string;
        formData?: Record<string, unknown>;
        setFieldValue?: (name: string, value: unknown) => void;
        getFieldValue?: (name: string) => unknown;
        /** Reservation model from the form (nested relations); formData only has customer_id. */
        reservation?: Record<string, unknown>;
    }>();

    const { t } = useI18n();
    const toast = useToastService();

    const sending = ref(false);
    const inviteDisabled = ref(false);
    const copyingPaymentLink = ref(false);

    const customerApi = new Customer();
    const timeSlotApi = new TimeSlot();
    const pricingTierApi = new PricingTier();
    const { fetchForLocation } = useBookingSettings();

    /** Location booking rules (deposit, children price, …) — same public endpoint as BookingPage / PaymentStep. */
    const locationBookingSettings = ref<BookingSettings | null>(null);

    /** Merged view: live model (relations, id) + form field overrides (e.g. customer_id). */
    const detailSource = computed((): Record<string, unknown> => ({
        ...(props.reservation ?? {}),
        ...(props.formData ?? {}),
    }));

    const locationIdForSettings = computed((): number | null => {
        const loc = detailSource.value.location as Record<string, unknown> | undefined;
        const fromRelation = loc != null ? Number(loc.id) : NaN;
        if (Number.isFinite(fromRelation) && fromRelation > 0) return fromRelation;
        const raw = detailSource.value.location_id;
        const n = Number(raw);
        return Number.isFinite(n) && n > 0 ? n : null;
    });

    watch(
        locationIdForSettings,
        async (lid) => {
            if (lid == null) {
                locationBookingSettings.value = null;
                return;
            }
            locationBookingSettings.value = await fetchForLocation(lid);
        },
        { immediate: true },
    );

    const reservationId = computed(() => {
        const id = detailSource.value.id;
        return typeof id === 'number' && Number.isFinite(id) ? id : null;
    });

    const customerIdForEmail = computed((): number | null => {
        const raw = detailSource.value.customer_id;
        const n = Number(raw);
        return Number.isFinite(n) && n > 0 ? n : null;
    });

    const resolvedCustomerEmail = ref<string | null>(null);
    let emailResolveToken = 0;

    function emailFromNestedCustomer(customerId: number): string | null {
        const c = detailSource.value.customer as Record<string, unknown> | undefined;
        if (!c || typeof c !== 'object') return null;
        if (Number(c.id) !== customerId) return null;
        const email = c.email;
        return typeof email === 'string' && email.trim().length > 0 ? email.trim() : null;
    }

    async function resolveCustomerEmail(): Promise<void> {
        const id = customerIdForEmail.value;
        const token = ++emailResolveToken;
        resolvedCustomerEmail.value = null;
        if (id == null) return;

        const nested = emailFromNestedCustomer(id);
        if (nested) {
            resolvedCustomerEmail.value = nested;
            return;
        }

        try {
            const res = await customerApi.show(id);
            const payload = (res.data as unknown as { data?: unknown })?.data ?? res.data;
            const row = payload as Record<string, unknown>;
            const email = row?.email;
            if (token !== emailResolveToken) return;
            resolvedCustomerEmail.value =
                typeof email === 'string' && email.trim().length > 0 ? email.trim() : null;
        } catch {
            if (token !== emailResolveToken) return;
            resolvedCustomerEmail.value = null;
        }
    }

    watch(
        () => [customerIdForEmail.value, detailSource.value.customer] as const,
        () => {
            void resolveCustomerEmail();
        },
        { immediate: true },
    );

    const hasCustomerEmail = computed(() => Boolean(resolvedCustomerEmail.value));

    const selectedTimeSlotId = computed((): number | null => {
        const raw = detailSource.value.time_slot_id;
        const n = Number(raw);
        return Number.isFinite(n) && n > 0 ? n : null;
    });

    const guestsCount = computed(() => {
        const n = Number(detailSource.value.guests);
        return Number.isFinite(n) && n > 0 ? n : 1;
    });


    const resolvedTimeSlot = ref<Record<string, unknown> | null>(null);
    const resolvedPricingTier = ref<Record<string, unknown> | null>(null);
    let pricingResolveToken = 0;

    function existingTimeSlotFromData(timeSlotId: number): Record<string, unknown> | null {
        const fromData = detailSource.value.time_slot as Record<string, unknown> | undefined;
        if (!fromData || Number(fromData.id) !== timeSlotId) {
            return null;
        }
        return fromData;
    }

    async function resolvePricingContext(): Promise<void> {
        const token = ++pricingResolveToken;
        resolvedTimeSlot.value = null;
        resolvedPricingTier.value = null;

        const timeSlotId = selectedTimeSlotId.value;
        if (timeSlotId == null) return;

        let slot: Record<string, unknown> | null = existingTimeSlotFromData(timeSlotId);
        if (!slot) {
            try {
                const res = await timeSlotApi.show(timeSlotId);
                const payload = (res.data as unknown as { data?: unknown })?.data ?? res.data;
                if (token !== pricingResolveToken) return;
                slot = payload as Record<string, unknown>;
            } catch {
                if (token !== pricingResolveToken) return;
                return;
            }
        }

        if (token !== pricingResolveToken) return;
        resolvedTimeSlot.value = slot;

        const pricingTableId = Number(slot.pricing_table_id);
        if (!Number.isFinite(pricingTableId) || pricingTableId <= 0) return;

        try {
            const res = await pricingTierApi.index({
                page: 1,
                per_page: 1,
                filter: {
                    pricing_table_id: pricingTableId,
                    guests: guestsCount.value,
                },
            });
            if (token !== pricingResolveToken) return;
            const payload = (res.data as { data?: unknown[] })?.data;
            if (Array.isArray(payload) && payload.length > 0) {
                resolvedPricingTier.value = payload[0] as Record<string, unknown>;
            }
        } catch {
            if (token !== pricingResolveToken) return;
            resolvedPricingTier.value = null;
        }
    }

    watch(
        () => [selectedTimeSlotId.value, guestsCount.value] as const,
        () => {
            void resolvePricingContext();
        },
        { immediate: true },
    );

    function pivotLines(items: unknown): Array<{ id: number; name: string; quantity: number; unit_price: number }> {
        if (!Array.isArray(items)) return [];
        return items.map((raw) => {
            const item = raw as Record<string, unknown>;
            const pivot = (item.pivot as Record<string, unknown>) || {};
            return {
                id: Number(item.id),
                name: String(item.name ?? ''),
                quantity: Number(pivot.quantity ?? item.quantity ?? 0),
                unit_price: Number(pivot.unit_price ?? item.unit_price ?? 0),
            };
        });
    }

    const orderData = computed(() => {
        const r = detailSource.value;
        const rid = Number((r as Record<string, unknown> | undefined)?.id);
        if (!r || !Number.isFinite(rid) || rid <= 0) return null;

        const paymentRaw = (r.payment ?? r.booking_payment) as Record<string, unknown> | null | undefined;
        const payment = mapPaymentRow(paymentRaw);

        const bookingPaymentRaw = (r.booking_payment ?? r.payment) as Record<string, unknown> | null | undefined;
        const booking_payment = mapPaymentRow(bookingPaymentRaw) ?? payment;

        const depositPaymentRaw = (r.deposit_payment ?? r.depositPayment) as Record<string, unknown> | null | undefined;
        const dpr =
            depositPaymentRaw && typeof depositPaymentRaw === 'object' ? depositPaymentRaw : null;
        const depositPayment =
            dpr && typeof dpr === 'object'
                ? {
                      amount: Number(dpr.amount ?? 0),
                  }
                : undefined;

        const couponRaw = r.coupon as Record<string, unknown> | null | undefined;
        const coupon =
            couponRaw && typeof couponRaw === 'object'
                ? {
                      id: Number(couponRaw.id),
                      code: String(couponRaw.code ?? ''),
                      name: couponRaw.name != null ? String(couponRaw.name) : undefined,
                  }
                : undefined;

        const ts = r.time_slot as Record<string, unknown> | undefined;
        const time_slot = ts
            ? {
                  id: Number(ts.id),
                  time: String(ts.time ?? ''),
                  start_time: String(ts.start_time ?? ''),
                  end_time: String(ts.end_time ?? ''),
              }
            : undefined;

        const loc = r.location as Record<string, unknown> | undefined;
        const location = loc ? { id: Number(loc.id), name: String(loc.name ?? '') } : undefined;

        const act = r.activity as Record<string, unknown> | undefined;
        const activity = act ? { id: Number(act.id), name: String(act.name ?? '') } : undefined;

        const totalDepositRaw = (r as Record<string, unknown>).total_deposit_amount ?? (r as Record<string, unknown>).totalDepositAmount;

        return {
            id: rid,
            location_id: Number(r.location_id),
            service_date: String(r.service_date ?? ''),
            guests: Number(r.guests ?? 0),
            status: String(r.status ?? ''),
            pricing_snapshot: (r.pricing_snapshot as Record<string, unknown>) || undefined,
            location,
            activity,
            time_slot,
            arrangements: pivotLines(r.arrangements),
            drinks: pivotLines(r.drinks),
            extra_drinks: pivotLines(r.extra_drinks),
            dishes: pivotLines(r.dishes),
            payment,
            booking_payment,
            deposit_payment: depositPayment,
            total_deposit_amount: parseNumeric(totalDepositRaw),
            meta: asMetaRecord(r.meta),
            coupon,
        };
    });

    type OrderLine = {
        key: string;
        label: string;
        description?: string;
        quantity: number;
        unitPrice: number;
        total: number;
    };

    function parseNumeric(value: unknown): number {
        const n = typeof value === 'number' ? value : parseFloat(String(value ?? 0));
        return Number.isFinite(n) ? n : 0;
    }

    /** API may return meta as object or JSON string (legacy / edge cases). */
    function asMetaRecord(raw: unknown): Record<string, unknown> | undefined {
        if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
            return raw as Record<string, unknown>;
        }
        if (typeof raw === 'string' && raw.trim().length > 0) {
            try {
                const parsed: unknown = JSON.parse(raw);
                if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
                    return parsed as Record<string, unknown>;
                }
            } catch {
                /* ignore */
            }
        }
        return undefined;
    }

    /**
     * True when booking checkout persisted tier/pricing data on the payment meta (see CreateReservationPaymentAction).
     * If absent, deposit may come only from reservation snapshot rows above — otherwise use configured booking_settings deposit.
     */
    function paymentMetaHasPricingSnapshot(meta: Record<string, unknown> | undefined): boolean {
        if (!meta) return false;
        const nested = meta.pricing_snapshot;
        if (nested != null && typeof nested === 'object' && !Array.isArray(nested)) {
            return true;
        }
        return (
            meta.booking_amount != null ||
            meta.price != null ||
            meta.tier_base_price != null ||
            meta.includes_deposit === true
        );
    }

    function mapPaymentRow(
        paymentRaw: Record<string, unknown> | null | undefined,
    ):
        | {
              id: number;
              provider: string;
              status: string;
              amount: number;
              currency: string;
              coupon_discount: number;
              meta: Record<string, unknown> | undefined;
          }
        | undefined {
        if (!paymentRaw || typeof paymentRaw !== 'object') return undefined;
        return {
            id: Number(paymentRaw.id),
            provider: String(paymentRaw.provider ?? ''),
            status: String(paymentRaw.status ?? ''),
            amount: Number(paymentRaw.amount ?? 0),
            currency: String(paymentRaw.currency ?? 'EUR'),
            coupon_discount: Number(paymentRaw.coupon_discount ?? 0),
            meta: asMetaRecord(paymentRaw.meta),
        };
    }

    const timeslotPrice = computed(() => {
        const snap = orderData.value?.pricing_snapshot;
        const fromSnapshot = parseNumeric(snap?.price);
        if (fromSnapshot > 0) return fromSnapshot;
        const tierBase = parseNumeric(snap?.tier_base_price);
        const childSur = parseNumeric(snap?.children_surcharge_total);
        if (tierBase > 0) {
            return roundMoney(tierBase + Math.max(0, childSur));
        }
        const fromTier = parseNumeric(resolvedPricingTier.value?.price);
        if (fromTier > 0) return fromTier;
        return 0;
    });

    function roundMoney(n: number): number {
        return Math.round((Number.isFinite(n) ? n : 0) * 100) / 100;
    }

    const childrenCount = computed(() => {
        const snap = orderData.value?.pricing_snapshot;
        if (snap && snap.children_count_snapshot != null && snap.children_count_snapshot !== '') {
            return Math.max(0, Math.floor(parseNumeric(snap.children_count_snapshot)));
        }
        return Math.max(0, Math.floor(parseNumeric(detailSource.value.children)));
    });

    const childrenPricePerChild = computed(() => {
        const fromSnap = parseNumeric(orderData.value?.pricing_snapshot?.children_price_per_child);
        if (fromSnap > 0) return fromSnap;
        return 0;
    });

    const adultsForPricing = computed(() => {
        const fromSnapshot = parseNumeric(orderData.value?.pricing_snapshot?.adults_for_pricing);
        if (fromSnapshot > 0) return fromSnapshot;
        const guests = parseNumeric(orderData.value?.guests);
        const ch = Math.max(0, Math.floor(parseNumeric(detailSource.value.children)));
        return Math.max(1, guests - ch);
    });

    const arrangementsSubtotal = computed(() =>
        (orderData.value?.arrangements ?? []).reduce((sum, i) => sum + parseNumeric(i.quantity) * parseNumeric(i.unit_price), 0),
    );
    /** Regular `drinks` are included in arrangements (no line charge). Only `extra_drinks` are payable. */
    const drinksSubtotal = computed(() => 0);
    const extraDrinksSubtotal = computed(() =>
        (orderData.value?.extra_drinks ?? []).reduce((sum, i) => sum + parseNumeric(i.quantity) * parseNumeric(i.unit_price), 0),
    );
    const dishesSubtotal = computed(() =>
        (orderData.value?.dishes ?? []).reduce((sum, i) => sum + parseNumeric(i.quantity) * parseNumeric(i.unit_price), 0),
    );

    const subtotal = computed(
        () =>
            timeslotPrice.value +
            arrangementsSubtotal.value +
            drinksSubtotal.value +
            extraDrinksSubtotal.value +
            dishesSubtotal.value,
    );
    const discountAmount = computed(() => {
        const snap = orderData.value?.pricing_snapshot;
        if (snap && snap.discount != null && snap.discount !== '') {
            return parseNumeric(snap.discount);
        }
        return 0;
    });
    const couponDiscountAmount = computed(() => parseNumeric(orderData.value?.payment?.coupon_discount));
    const depositAmount = computed(() => {
        const d = orderData.value;
        if (!d) return 0;

        const fromDepositPayment = parseNumeric(d.deposit_payment?.amount);
        if (fromDepositPayment > 0) return fromDepositPayment;

        /** Sum of DEPOSIT-type payment rows (API); covers cases where HasOne deposit relation is empty. */
        const fromTotalDeposit = parseNumeric(d.total_deposit_amount);
        if (fromTotalDeposit > 0) return fromTotalDeposit;

        const fromPaymentMeta = parseNumeric(d.payment?.meta?.deposit_amount);
        if (fromPaymentMeta > 0) return fromPaymentMeta;

        const fromBookingMeta = parseNumeric(d.booking_payment?.meta?.deposit_amount);
        if (fromBookingMeta > 0) return fromBookingMeta;

        const fromReservationMeta = parseNumeric(d.meta?.deposit_amount);
        if (fromReservationMeta > 0) return fromReservationMeta;

        const fromSnapshot = parseNumeric(d.pricing_snapshot?.deposit_amount);
        if (fromSnapshot > 0) return fromSnapshot;

        /** Scheduled booking (timeslot): show configured deposit when no checkout snapshot exists on payment meta yet. */
        if (selectedTimeSlotId.value == null) {
            return 0;
        }

        const payMeta = d.booking_payment?.meta ?? d.payment?.meta;
        if (paymentMetaHasPricingSnapshot(payMeta)) {
            return 0;
        }

        const cfg = locationBookingSettings.value;
        if (!cfg?.deposit_enabled) {
            return 0;
        }

        const configured = parseNumeric(cfg.deposit_amount);
        return configured > 0 ? configured : 0;
    });
    const orderTotal = computed(
        () =>
            subtotal.value - discountAmount.value - couponDiscountAmount.value + depositAmount.value,
    );

    const orderLines = computed<OrderLine[]>(() => {
        const rows: OrderLine[] = [];
        const d = orderData.value;
        if (!d) return rows;

        const timeSlot = (resolvedTimeSlot.value as Record<string, unknown> | null) ?? (d.time_slot as Record<string, unknown> | undefined);
        const timeLabel =
            (typeof timeSlot?.time === 'string' ? timeSlot.time : '') ||
            [timeSlot?.start_time, timeSlot?.end_time].filter(Boolean).join(' - ');
        const activityName = d.activity?.name || 'Reservation';
        const baseGuestsLine = `${adultsForPricing.value} adult(s)`;
        const childExtra =
            childrenCount.value > 0
                ? ` + ${childrenCount.value} child(ren) x EUR ${formatPrice(childrenPricePerChild.value)}`
                : '';
        if (selectedTimeSlotId.value != null) {
            rows.push({
                key: 'timeslot',
                label: `${activityName}${timeLabel ? ` (${timeLabel})` : ''}`,
                description: `${baseGuestsLine}${childExtra}`,
                quantity: 1,
                unitPrice: timeslotPrice.value,
                total: timeslotPrice.value,
            });
        }

        for (const item of d.arrangements ?? []) {
            const q = parseNumeric(item.quantity);
            const p = parseNumeric(item.unit_price);
            rows.push({
                key: `arr-${item.id}`,
                label: item.name,
                quantity: q,
                unitPrice: p,
                total: q * p,
            });
        }
        for (const item of d.drinks ?? []) {
            const q = parseNumeric(item.quantity);
            const p = 0;
            rows.push({
                key: `drink-${item.id}`,
                label: item.name,
                description: t('BOOKING_DRINKS_INCLUDED_IN_ARRANGEMENT'),
                quantity: q,
                unitPrice: p,
                total: q * p,
            });
        }
        for (const item of d.extra_drinks ?? []) {
            const q = parseNumeric(item.quantity);
            const p = parseNumeric(item.unit_price);
            rows.push({
                key: `xdrink-${item.id}`,
                label: item.name,
                description: 'Extra drink',
                quantity: q,
                unitPrice: p,
                total: q * p,
            });
        }
        for (const item of d.dishes ?? []) {
            const q = parseNumeric(item.quantity);
            const p = parseNumeric(item.unit_price);
            rows.push({
                key: `dish-${item.id}`,
                label: item.name,
                description: 'Extra dish',
                quantity: q,
                unitPrice: p,
                total: q * p,
            });
        }

        return rows.filter((r) => r.quantity > 0);
    });

    function formatPrice(price: number | string | undefined | null): string {
        const value = parseNumeric(price);
        return new Intl.NumberFormat('nl-NL', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
    }

    const paymentStatusKey = computed(() => {
        const p = orderData.value?.payment;
        if (!p) return 'concept';
        return (p.status || 'pending').toLowerCase();
    });

    const paymentStatusLabel = computed(() => {
        if (paymentStatusKey.value === 'concept') {
            return t('RESERVATION_PAYMENT_CONCEPT');
        }
        const key = 'GENERAL_' + paymentStatusKey.value.toUpperCase();
        const translated = t(key);
        return translated !== key ? translated : paymentStatusKey.value;
    });

    const paymentStatusClass = computed(() => {
        const s = paymentStatusKey.value;
        if (s === 'concept') return 'text-gray-600';
        if (s === 'paid') return 'text-green-600';
        if (s === 'pending') return 'text-yellow-600';
        if (s === 'failed' || s === 'expired' || s === 'canceled') return 'text-red-600';
        return 'text-gray-600';
    });

    async function sendInvite(): Promise<void> {
        const id = reservationId.value;
        if (id == null || inviteDisabled.value || sending.value || !hasCustomerEmail.value) return;

        sending.value = true;
        try {
            await http.post(`reservations/${id}/send-order-overview-invite`);
            inviteDisabled.value = true;
            toast.success(t('RESERVATION_ORDER_OVERVIEW_INVITE_SENT'));
        } catch (e: unknown) {
            const msg =
                (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
                t('RESERVATION_ORDER_OVERVIEW_INVITE_FAILED');
            toast.error(typeof msg === 'string' ? msg : String(msg));
        } finally {
            sending.value = false;
        }
    }

    async function writeTextToClipboard(text: string): Promise<void> {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
            return;
        }
        const el = document.createElement('textarea');
        el.value = text;
        el.setAttribute('readonly', '');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }

    async function copyPaymentLink(): Promise<void> {
        const id = reservationId.value;
        if (id == null || copyingPaymentLink.value) return;

        copyingPaymentLink.value = true;
        try {
            const res = await http.post(`reservations/${id}/payment-link`);
            const body = res.data as { data?: { url?: string } } | undefined;
            const url = typeof body?.data?.url === 'string' ? body.data.url : '';
            if (!url) {
                throw new Error('missing_url');
            }
            await writeTextToClipboard(url);
            toast.success(t('RESERVATION_PAYMENT_PAYMENT_LINK_COPIED'));
        } catch {
            toast.error(t('RESERVATION_PAYMENT_PAYMENT_LINK_FAILED'));
        } finally {
            copyingPaymentLink.value = false;
        }
    }
</script>
