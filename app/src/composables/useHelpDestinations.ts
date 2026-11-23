import { ref } from 'vue';
import http from '@/utils/http';

const destinations = ref<Record<string, string>>({});
const options = ref<Array<{ key: string; label: string }>>([]);
let pending: Promise<void> | null = null;

/** Registered help destinations. Paths stay in the application config, not in the shared editor. */
export function useHelpDestinations() {
    async function load(): Promise<void> {
        if (options.value.length > 0) return;
        if (!pending) {
            pending = http
                .get<{ data: Array<{ key: string; feature_path: string }> }>('help/contexts')
                .then((response) => {
                    const next: Record<string, string> = {};
                    const listed: Array<{ key: string; label: string }> = [];
                    for (const item of response.data.data ?? []) {
                        if (!item?.key || !item.feature_path.startsWith('/') || item.feature_path.includes('..')) continue;
                        next[item.key] = item.feature_path;
                        listed.push({ key: item.key, label: item.key });
                    }
                    destinations.value = next;
                    options.value = listed;
                })
                .finally(() => {
                    pending = null;
                });
        }
        await pending;
    }

    return { destinations, options, load };
}
