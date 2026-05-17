import type { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';

/**
 * Payload emitted when a modal closes or an action is chosen.
 *
 * Used by the modal service and wrapper components to resolve {@link import('@/modules/types/support/modals/IModalService.ts').IModalService.open IModalService.open} promises and to chain `childModal` flows.
 *
 * @property type - Semantic outcome: dismiss vs accept (see {@link ModalResultType}).
 * @property target - Optional stable token from the action (e.g. {@link import('@/modules/enums/support/modals/ModalActionTarget.ts').ModalActionTarget ModalActionTarget}).
 * @property data - Optional payload from the inner component (`getData`) or custom handlers.
 */
export interface ModalResult {
    type: ModalResultType;
    target?: string;
    data?: unknown;
}
