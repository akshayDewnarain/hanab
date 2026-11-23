export const SEEN_MESSAGE_STORAGE_KEY = 'hanab.messages.seen';
export const DISMISSED_MESSAGE_STORAGE_KEY = 'hanab.messages.dismissed';

export function readSeenMessageIds(): string[] | null {
    try {
        const stored = localStorage.getItem(SEEN_MESSAGE_STORAGE_KEY);
        if (stored === null) return null;
        const parsed = JSON.parse(stored) as unknown;
        return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch {
        return [];
    }
}

export function rememberSeenMessageIds(ids: Iterable<string>): void {
    writeIds(SEEN_MESSAGE_STORAGE_KEY, ids);
    window.dispatchEvent(new Event('hanab-messages-seen'));
}

export function readDismissedMessageIds(): string[] {
    return readIds(DISMISSED_MESSAGE_STORAGE_KEY);
}

export function rememberDismissedMessageIds(ids: Iterable<string>): void {
    writeIds(DISMISSED_MESSAGE_STORAGE_KEY, ids);
    window.dispatchEvent(new Event('hanab-messages-seen'));
}

function readIds(key: string): string[] {
    try {
        const stored = localStorage.getItem(key);
        if (stored === null) return [];
        const parsed = JSON.parse(stored) as unknown;
        return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch {
        return [];
    }
}

function writeIds(key: string, ids: Iterable<string>): void {
    try {
        localStorage.setItem(key, JSON.stringify([...ids]));
    } catch {
        // The bell still works for this visit when storage is unavailable.
    }
}
