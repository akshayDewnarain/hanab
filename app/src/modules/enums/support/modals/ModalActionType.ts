/**
 * Visual / semantic style for a modal footer button (not the same as {@link import('@/modules/enums/support/modals/ModalResultType.ts').ModalResultType ModalResultType}).
 *
 * Used by {@link import('@/modules/types/support/modals/ModalAction.ts').ModalAction ModalAction}.variant to pick button classes.
 *
 * Enum members:
 * - DISMISS — secondary / cancel styling.
 * - PRIMARY — primary call-to-action styling.
 */
export enum ModalActionType {
    PRIMARY = 'primary',
    PRIMARY_OUTLINE = 'primary-outline',
    DISMISS = 'dismiss',
    DEFAULT = 'default',
    SUCCESS = 'success',
    DANGER = 'danger',
}
