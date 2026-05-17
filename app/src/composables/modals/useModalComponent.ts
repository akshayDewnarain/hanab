import { ref } from 'vue';

export function useModalComponent<T = unknown>(data: T | null = null) {
    const _data = ref<T | null>(data);
    const _valid = ref(true);

    function setData(val: T): void {
        _data.value = val;
    }

    function setValid(val: boolean): void {
        _valid.value = val;
    }

    async function isValidForAccept(): Promise<boolean> {
        return _valid.value;
    }

    async function notifyAccepted(): Promise<void> {
        // optionally add side effects
    }

    async function notifyDismissed(): Promise<void> {
        // optionally add side effects
    }

    function getData(): T | null {
        return _data.value;
    }

    return {
        isValidForAccept,
        notifyAccepted,
        notifyDismissed,
        getData,
        setData,
        setValid,
    };
}
