/**
 * Optional stable token on an action for analytics or branching without relying on translated labels.
 *
 * Used by {@link import('@/modules/types/support/modals/ModalAction.ts').ModalAction ModalAction}.target alongside {@link import('@/modules/types/support/modals/ModalResult.ts').ModalResult ModalResult}.target.
 *
 * Enum members:
 * - CANCEL — dismiss path (e.g. “Cancel”).
 * - CONFIRM — accept path (e.g. “OK” / “Log out”).
 */
export enum ModalActionTarget {
    SAVE = 'save',
    RESET = 'reset',
    DELETE = 'delete',
    CANCEL = 'cancel',
    CONFIRM = 'confirm',
}
