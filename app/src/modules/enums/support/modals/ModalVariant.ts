/**
 * Tone for confirmation and alert-style modal bodies (icon, colors, copy emphasis).
 *
 * Used by {@link import('@/modules/types/support/modals/ConfirmationModalProps.ts').ConfirmationModalProps ConfirmationModalProps}.variant.
 *
 * Enum members:
 * - WARNING — non-destructive caution.
 * - ERROR — failure or destructive emphasis.
 * - SUCCESS — positive outcome.
 * - INFO — neutral informational tone.
 */
export enum ModalVariant {
    SUCCESS = 'success',
    WARNING = 'warning',
    ERROR = 'error',
    INFO = 'info',
}
