import axios from 'axios';
import { getActivePinia } from 'pinia';
import { useAuthStore } from '@/stores/auth';

let redirectScheduled = false;

/**
 * Session / CSRF invalid for the SPA (419). Clears client state and sends the user to login.
 * Uses a full navigation so Pinia and layout state reset cleanly. Avoids calling POST /logout (may also 419).
 */
export function handleAuthSessionLost(): void {
    if (redirectScheduled) {
        return;
    }
    redirectScheduled = true;

    try {
        const pinia = getActivePinia();
        if (pinia) {
            useAuthStore(pinia).$patch({ user: null });
        }
    } catch {
        /* Pinia may not exist yet */
    }

    localStorage.removeItem('scope.location');
    delete axios.defaults.headers.common['X-Location-Id'];

    const path = window.location.pathname || '';
    if (path === '/login' || path.startsWith('/login/')) {
        redirectScheduled = false;
        return;
    }

    window.location.assign('/login');
}

export function isSessionExpiredResponse(status: number | undefined, data: unknown): boolean {
    if (status === 419) {
        return true;
    }
    if (data && typeof data === 'object' && 'code' in data && (data as { code?: string }).code === 'SESSION_EXPIRED') {
        return true;
    }
    const msg =
        data && typeof data === 'object' && 'message' in data ? String((data as { message?: string }).message) : '';
    if (msg && /csrf|session expired|token mismatch/i.test(msg)) {
        return true;
    }
    return false;
}

/** Treat unauthorized/forbidden API responses as auth loss in admin UI. */
export function isUnauthorizedResponse(status: number | undefined, data: unknown): boolean {
    if (status === 401 || status === 403) {
        return true;
    }
    const msg =
        data && typeof data === 'object' && 'message' in data ? String((data as { message?: string }).message) : '';
    if (msg && /unauthorized|unauthenticated|forbidden|action is unauthorized/i.test(msg)) {
        return true;
    }
    return false;
}
