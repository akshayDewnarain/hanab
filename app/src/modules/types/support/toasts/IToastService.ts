/**
 * Global non-blocking notifications (stacked toasts) with severity-specific presentation.
 *
 * Used by {@link import('@/composables/useToastService.ts').useToastService useToastService} and implemented by {@link import('@/services/ToastService.ts').default ToastService} via `toastService` injection.
 *
 * @property info - Neutral informational toast; `message` is typically an i18n key or short plain string.
 * @property success - Positive-outcome toast with the same `message` / `duration` / `options` contract as `info`.
 * @property error - Error-state toast; prefer for failures the user should notice.
 * @property warning - Caution toast; prefer for validation or recoverable issues.
 *
 * For each method: `duration` is visibility time in milliseconds (implementation default applies when omitted). `options` is merged into the underlying toast library options (e.g. vue-toastification).
 */
export interface IToastService {
    info(message: string, duration?: number, options?: Record<string, unknown>): void;

    success(message: string, duration?: number, options?: Record<string, unknown>): void;

    error(message: string, duration?: number, options?: Record<string, unknown>): void;

    warning(message: string, duration?: number, options?: Record<string, unknown>): void;
}
