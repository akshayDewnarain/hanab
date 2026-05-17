import type { ModalProps } from '@/modules/types/support/modals/ModalProps.ts';
import type { ModalResult } from '@/modules/types/support/modals/ModalResult.ts';

/**
 * Application-wide modal stack: open modals, read the stack, dismiss one or all.
 *
 * Used by {@link import('@/composables/useModalService.ts').useModalService useModalService}, {@link import('@/components/modals/ModalContainer.vue').default ModalContainer}, and features that call `open()`.
 *
 * @property modals - Currently opened modal configurations, in stack order.
 * @property open - Pushes a modal and returns a promise that resolves with the final {@link import('@/modules/types/support/modals/ModalResult.ts').ModalResult ModalResult}.
 * @property getModals - Returns the list of currently opened {@link import('@/modules/types/support/modals/ModalProps.ts').ModalProps ModalProps}.
 * @property dismissAll - Closes every open modal; behavior for pending `open` promises depends on the concrete implementation.
 * @property dismiss - Closes the given modal and delivers the provided {@link import('@/modules/types/support/modals/ModalResult.ts').ModalResult ModalResult}.
 */
export interface IModalService {
    readonly modals: ModalProps[];

    open(modalProps: ModalProps): Promise<ModalResult>;

    getModals(): ModalProps[];

    dismissAll(): void;

    dismiss(modal: ModalProps, result: ModalResult): void;
}
