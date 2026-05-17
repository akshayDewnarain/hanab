import type { ModalVariant } from '@/modules/enums/support/modals/ModalVariant.ts';

/**
 * Props for the simple confirmation body shown inside a modal shell.
 *
 * Used when {@link import('@/modules/types/support/modals/ModalProps.ts').ModalProps ModalProps}.component is the confirmation modal.
 *
 * @property message - i18n key or literal for the main body copy.
 * @property variant - Visual tone ({@link ModalVariant}).
 */
export interface ConfirmationModalProps {
    message: string;
    variant: ModalVariant;
}
