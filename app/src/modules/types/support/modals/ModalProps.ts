import type { Component } from 'vue';
import type { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
import type { ModalAction } from '@/modules/types/support/modals/ModalAction.ts';
import type { ModalResult } from '@/modules/types/support/modals/ModalResult.ts';
import type { ModalSize } from '@/modules/types/support/modals/ModalSize.ts';

/**
 * Configuration for opening one modal instance (shell + body component + callbacks).
 *
 * Used by {@link import('@/modules/types/support/modals/IModalService.ts').IModalService IModalService} and {@link import('@/components/modals/ModalContainer.vue').default ModalContainer}.
 *
 * @property title - Heading shown in the shell; often an i18n key.
 * @property type - Either a registered {@link ModalType} or a custom Vue component used as the shell.
 * @property component - Body component rendered inside the shell (receives `props` below).
 * @property props - Props object passed to the body component.
 * @property icon - Optional icons for the shell header.
 * @property onAccept - Called when the user accepts; receives the final result or null.
 * @property onDismiss - Called when the modal is dismissed without accept.
 * @property actions - Footer actions (dismiss vs accept groups).
 * @property _resolver - Internal: resolves the promise returned by `open`.
 * @property childModal - Optional follow-up modal configuration based on the prior result.
 * @property size - Panel width preset ({@link ModalSize}).
 * @property panelClass - Extra classes on the panel root.
 * @property heightClass - Optional height utility classes for the shell body.
 * @property widthClass - Optional width utility classes for the shell body.
 * @property disableDefaultPadding - When true, wrapper modals omit default padding around the scrollable body.
 */
export interface ModalProps {
    title: string;
    type: ModalType | Component;
    component?: Component;
    props?: Record<string, unknown>;
    icon?: string[];
    onAccept?: ((result: ModalResult | null) => void) | null;
    onDismiss?: (() => void) | null;
    actions?: ModalAction[];
    _resolver?: (result: ModalResult) => void;
    childModal?: (result: ModalResult) => ModalProps | Promise<ModalProps>;
    size?: ModalSize;
    panelClass?: string;
    heightClass?: string;
    widthClass?: string;
    disableDefaultPadding?: boolean;
}
