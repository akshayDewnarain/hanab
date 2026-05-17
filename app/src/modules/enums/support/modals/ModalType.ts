/**
 * Registered shell layout for a modal (slide-in from edge, centered panel, etc.).
 *
 * Used by {@link import('@/modules/types/support/modals/ModalProps.ts').ModalProps ModalProps}.type when not passing a raw Vue component.
 *
 * Enum members:
 * - SLIDE_IN_RIGHT — full-height panel from the right.
 * - SLIDE_IN_TOP_CENTER — centered overlay from the top.
 */
export enum ModalType {
    SLIDE_IN_TOP_CENTER = 'slide-in-top-center-modal',
    SLIDE_IN_RIGHT = 'slide-in-right-modal',
}
