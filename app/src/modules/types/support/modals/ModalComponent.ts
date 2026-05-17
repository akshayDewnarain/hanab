import type { ModalAction } from '@/modules/types/support/modals/ModalAction.ts';

/**
 * Optional imperative surface implemented by modal body components via `defineExpose`.
 *
 * Used by wrapper modal composables to validate before accept, collect `getData`, and run lifecycle hooks.
 *
 * @property isValidForAccept - Return false (or a promise resolving false) to block the accept action.
 * @property notifyAccepted - Invoked after a successful accept for this action.
 * @property notifyDismissed - Invoked when the modal is dismissed from an action.
 * @property getData - Returns the payload merged into {@link import('@/modules/types/support/modals/ModalResult.ts').ModalResult ModalResult}.data.
 * @property setData - Hydrates the body from external data.
 * @property setValid - Allows the shell to reflect external validation state when supported.
 */
export interface ModalComponent {
    isValidForAccept?: (action: ModalAction) => boolean | Promise<boolean>;
    notifyAccepted?: (action: ModalAction) => Promise<void>;
    notifyDismissed?: (action: ModalAction) => Promise<void>;
    getData?: () => unknown;
    setData?: (data: unknown) => void;
    setValid?: (valid: boolean) => void;
}
