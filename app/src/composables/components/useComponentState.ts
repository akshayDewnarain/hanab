import { ref } from 'vue';

export function useComponentState() {
    const isLoading = ref(true);

    const setLoading = (loading: boolean) => {
        isLoading.value = loading;
    };

    const setContent = () => {
        isLoading.value = false;
    };

    return {
        isLoading,
        setLoading,
        setContent,
    };
}
