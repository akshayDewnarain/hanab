import http from '@/utils/http';

/** Fetch Sanctum CSRF cookie before state-changing API calls from the SPA. */
export async function prepareApiCsrf(): Promise<void> {
    await http.get('/sanctum/csrf-cookie', {
        baseURL: import.meta.env.VITE_API_URL,
    });
}
