<template>
    <div class="flex items-center justify-center">
        <span :class="spanClasses">{{ displayValue }}</span>
    </div>
</template>

<script lang="ts" setup>
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';

    const { t } = useI18n();

    const props = withDefaults(
        defineProps<{
            value: string;
            badges?: Record<string, string>;
            customClasses?: string;
            useLocalizedString?: boolean;
        }>(),
        {
            useLocalizedString: true,
        },
    );

    const displayValue = computed(() => {
        if (props.useLocalizedString) {
            return t('GENERAL_' + props.value.toUpperCase());
        }
        return props.value;
    });

    const spanClasses = computed(() => {
        if (props.customClasses) {
            return props.customClasses;
        }

        const defaultClasses = 'inline-block px-2 py-1 text-xs font-semibold rounded-full';
        const badgeClass = props.badges?.[props.value] || 'bg-gray-200 text-gray-500 border border-gray-500';

        return `${defaultClasses} ${badgeClass}`;
    });
</script>
