import { ref } from 'vue';

const open = ref(false);
const contextKey = ref<string | null>(null);

export function useHelpCenter() {
    function show(key: string): void {
        contextKey.value = key;
        open.value = true;
    }

    function close(): void {
        open.value = false;
    }

    return { open, contextKey, show, close };
}
