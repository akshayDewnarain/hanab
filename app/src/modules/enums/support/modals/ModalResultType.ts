/**
 * How a modal flow ended: user dismissed, accepted, or a custom semantic result.
 *
 * Used by modal actions, {@link import('@/modules/types/support/modals/ModalResult.ts').ModalResult ModalResult}, and wrapper components to route footer buttons.
 *
 * Enum members:
 * - DISMISS — user closed or cancelled without committing.
 * - ACCEPT — user confirmed the primary action.
 */
export enum ModalResultType {
    ACCEPT = 1,
    DISMISS = 0,
}
