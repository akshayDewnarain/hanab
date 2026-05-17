import type { ModalActionType } from '@/modules/enums/support/modals/ModalActionType.ts';
import type { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';

/**
 * Definition of a single footer (or header) control in a modal shell.
 *
 * Used by {@link import('@/modules/types/support/modals/ModalProps.ts').ModalProps ModalProps}.actions and by wrapper modals to filter dismiss vs accept buttons.
 *
 * @property type - Whether this button counts as dismiss or accept for default handling.
 * @property name - i18n key or literal label token for the button text.
 * @property target - Optional stable token echoed on the resulting {@link import('@/modules/types/support/modals/ModalResult.ts').ModalResult ModalResult}.
 * @property variant - Button styling token ({@link ModalActionType}).
 * @property icon - Optional icon identifier for the action.
 * @property callback - Optional handler invoked when the action is triggered.
 * @property confirm - When true, shells may require an extra confirmation step before firing.
 */
export interface ModalAction {
    type: ModalResultType;
    name: string;
    target?: string;
    variant?: ModalActionType;
    icon?: string;
    callback?: (...args: unknown[]) => unknown;
    confirm?: boolean;
}
