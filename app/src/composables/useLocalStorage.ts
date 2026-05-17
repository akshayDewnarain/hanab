export function useLocalStorage() {
    function setLocalStorageItem(key: string, value: unknown) {
        localStorage.setItem(key, JSON.stringify(value));
    }

    function getLocalStorageItem(key: string) {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : null;
    }

    return {
        setLocalStorageItem,
        getLocalStorageItem,
    };
}
