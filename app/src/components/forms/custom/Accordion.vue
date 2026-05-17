<template>
    <div class="rounded border border-gray-200 bg-gray-200 shadow-md">
        <button
            :aria-controls="panelId"
            :aria-expanded="openComputed"
            class="w-full flex items-center justify-between p-2 text-left select-none"
            type="button"
            @click="toggle"
            @keydown.enter.prevent="toggle"
            @keydown.space.prevent="toggle"
        >
            <Icon
                :class="openComputed ? 'rotate-90' : 'rotate-0'"
                aria-hidden="true"
                class="w-5 h-5 text-gray-500 transition-transform duration-200 mx-2"
                icon="material-symbols:chevron-right-rounded"
            />

            <div class="min-w-0 flex-1">
                <slot :open="openComputed" name="header" />
            </div>
        </button>

        <transition name="acc-collapse">
            <div v-show="openComputed" :id="panelId" class="bg-white" role="region">
                <div class="">
                    <slot :open="true" />
                </div>
            </div>
        </transition>
    </div>
</template>

<script lang="ts" setup>
    import { computed, ref, defineComponent } from 'vue';
    import { Icon } from '@iconify/vue';

    defineComponent({
        name: 'CustomAccordion',
    });

    const props = defineProps<{
        modelValue?: boolean;
        defaultOpen?: boolean;
    }>();

    const emit = defineEmits<{ (e: 'update:modelValue', v: boolean): void; (e: 'toggle', v: boolean): void }>();
    const panelId = `acc-${Math.random().toString(36).slice(2, 8)}`;

    const isControlled = computed(() => props.modelValue !== undefined);
    const state = ref<boolean>(props.defaultOpen ?? false);

    const openComputed = computed({
        get: () => (isControlled.value ? props.modelValue : state.value),
        set: (v: boolean) => {
            if (isControlled.value) emit('update:modelValue', v);
            else state.value = v;
            emit('toggle', v);
        },
    });

    function toggle() {
        openComputed.value = !openComputed.value;
    }
</script>

<style scoped>
    .acc-collapse-enter-from,
    .acc-collapse-leave-to {
        max-height: 0;
        opacity: 0;
    }
    .acc-collapse-enter-active,
    .acc-collapse-leave-active {
        transition:
            max-height 200ms ease,
            opacity 150ms ease;
    }
    .acc-collapse-enter-to,
    .acc-collapse-leave-from {
        max-height: 1000px;
        opacity: 1;
    }
</style>
