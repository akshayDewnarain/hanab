<template>
    <form class="flex flex-col gap-2 p-2" @submit.prevent>
        <label class="flex flex-col gap-1 text-sm" for="access-role-name">
            <span class="font-medium text-list-text">{{ t('ACCESS_ROLE_NAME') }}</span>
            <input
                id="access-role-name"
                v-model="name"
                class="h-10 rounded-lg border border-list-border bg-list-surface px-3 text-list-text"
                required
                type="text"
                maxlength="255"
                :placeholder="t('ACCESS_ROLE_NAME')"
            />
        </label>
        <p v-if="invalid" class="m-0 text-sm text-red-700">{{ t('ACCESS_ROLE_NAME_REQUIRED') }}</p>
    </form>
</template>

<script lang="ts" setup>
    import { ref } from 'vue';
    import { useI18n } from 'vue-i18n';

    defineOptions({ name: 'AccessRoleNameModal' });

    const props = defineProps<{
        props: { name?: string };
    }>();

    const { t } = useI18n();
    const name = ref(props.props.name ?? '');
    const invalid = ref(false);

    function getData(): { name: string } {
        return { name: name.value.trim() };
    }

    function isValidForAccept(): boolean {
        invalid.value = name.value.trim() === '';
        return !invalid.value;
    }

    defineExpose({ getData, isValidForAccept });
</script>
