import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const MOBILE_MAX_WIDTH = 767;

/**
 * Responsive viewport helper using Tailwind's standard mobile breakpoint (< md).
 */
export function useViewport() {
    const width = ref<number>(window.innerWidth);

    function updateWidth(): void {
        width.value = window.innerWidth;
    }

    onMounted(() => {
        updateWidth();
        window.addEventListener('resize', updateWidth, { passive: true });
    });

    onBeforeUnmount(() => {
        window.removeEventListener('resize', updateWidth);
    });

    const isMobile = computed(() => width.value <= MOBILE_MAX_WIDTH);

    return {
        width,
        isMobile,
    };
}
