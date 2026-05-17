export const useDebounce = (fn: (...args: unknown[]) => void, delay = 300) => {
    let timeoutId: number;

    return (...args: unknown[]) => {
        clearTimeout(timeoutId);
        timeoutId = window.setTimeout(() => {
            fn(...args);
        }, delay);
    };
};
