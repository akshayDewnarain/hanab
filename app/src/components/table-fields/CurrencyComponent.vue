<template>
    <span>{{ formattedCurrency }}</span>
</template>

<script lang="ts" setup>
    import { computed, defineProps, withDefaults } from 'vue';

    const props = withDefaults(
        defineProps<{
            value: number | string | null | undefined; // accept both
            currency?: string;
            locale?: string;
            minor?: boolean;
        }>(),
        {
            currency: 'EUR',
            locale: 'nl-NL',
            minor: false,
        },
    );

    const numeric = computed<number>(() => {
        const v = props.value;
        if (v === null || v === undefined || v === '') return 0;
        const n = typeof v === 'number' ? v : Number(v);
        if (Number.isNaN(n)) return 0;
        return props.minor ? n / 100 : n;
    });

    const formattedCurrency = computed(() =>
        new Intl.NumberFormat(props.locale, { style: 'currency', currency: props.currency }).format(numeric.value),
    );
</script>
