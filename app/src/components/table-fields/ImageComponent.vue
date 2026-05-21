<template>
    <div class="flex justify-center">
        <div v-if="hasImage" class="rounded-full border border-gray-300 bg-gray-200 shadow-md">
            <img :src="value.thumb_url" alt="image" class="rounded-full aspect-square max-h-12" loading="lazy" />
        </div>
        <div v-else class="rounded-full p-3 border border-gray-300 bg-gray-200 shadow-md">
            <icon class="" height="24" icon="material-symbols:image-not-supported-outline-rounded" />
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent } from 'vue';
    import type MediaURL from '@/modules/types/support/media/MediaURL.ts';
    import { Icon } from '@iconify/vue';

    defineComponent({
        name: 'ImageComponent',
    });

    const props = defineProps<{ value: MediaURL }>();

    const hasImage = computed(() => {
        return props.value && props.value.thumb_url;
    });
</script>
