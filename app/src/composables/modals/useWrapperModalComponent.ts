import { onMounted, onUnmounted, type Ref, ref } from 'vue';
import { useModalService } from '../useModalService';
import ConfirmationModal from '../../components/modals/content-modals/ConfirmationModal.vue';
import type { ModalProps } from '@/modules/types/support/modals/ModalProps.ts';
import type { ModalComponent } from '@/modules/types/support/modals/ModalComponent.ts';
import type { ModalAction } from '@/modules/types/support/modals/ModalAction.ts';
import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
import { ModalVariant } from '@/modules/enums/support/modals/ModalVariant.ts';
import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType.ts';
import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget.ts';
import type { ModalResult } from '@/modules/types/support/modals/ModalResult.ts';

export function useWrapperModalComponent(modal: ModalProps, component: Ref<Partial<ModalComponent>>, delay?: number) {
    const visible = ref(false);
    const modalRef = ref<HTMLElement | null>(null);

    const _modal = modal;
    const _modalComponent = component;
    const _loading = ref(false);
    const _modalService = useModalService();

    async function handleClick(action: ModalAction, delay?: number): Promise<void> {
        if (_loading.value) {
            return;
        }

        let result: boolean = false;

        if (action.confirm && action.type === ModalResultType.ACCEPT) {
            const confirmResult = await _modalService.open({
                title: 'GENERAL_ARE_YOU_SURE',
                type: ModalType.SLIDE_IN_TOP_CENTER,
                component: ConfirmationModal,
                props: {
                    message: 'TABLE_CONFIGURATION_RESET_WARNING_MESSAGE',
                    variant: ModalVariant.WARNING,
                },
                actions: [
                    {
                        type: ModalResultType.DISMISS,
                        name: 'GENERAL_CANCEL',
                        variant: ModalActionType.DISMISS,
                        target: ModalActionTarget.CANCEL,
                    },
                    {
                        type: ModalResultType.ACCEPT,
                        name: 'GENERAL_SAVE',
                        variant: ModalActionType.PRIMARY,
                        target: ModalActionTarget.SAVE,
                    },
                ],
            });

            if (confirmResult.type === ModalResultType.DISMISS) {
                return;
            }
        }

        switch (action.type) {
            case ModalResultType.ACCEPT:
                result = await handleAccept(action);
                break;
            case ModalResultType.DISMISS:
                result = await handleDismiss(action);
                break;
        }

        if (result) {
            const modalResult: ModalResult = {
                type: action.type,
                target: action.target,
                data: {},
            };

            if (typeof _modalComponent.value.getData === 'function') {
                modalResult.data = _modalComponent.value.getData();
            }

            visible.value = false;

            setTimeout(async () => {
                try {
                    _modalService.dismiss(_modal, modalResult);
                } catch (e: unknown) {
                    console.log(e);
                }
            }, delay ?? 300);
        }
    }

    async function handleAccept(action: ModalAction): Promise<boolean> {
        _loading.value = true;

        if (typeof _modalComponent.value.isValidForAccept === 'function') {
            const isValid = await _modalComponent.value.isValidForAccept(action);
            if (!isValid) {
                _loading.value = false;
                return false;
            }
        }

        if (action.callback) {
            action.callback.call(action);
        }

        if (typeof _modalComponent.value.notifyAccepted === 'function') {
            await _modalComponent.value.notifyAccepted(action);
        }

        _loading.value = false;
        return true;
    }

    function handleClickOutside(event: MouseEvent) {
        const target = event.target as Node;
        const clickedInsideModal = modalRef.value?.contains(target);
        const clickedInsideDropdown = document.querySelector('[data-dropdown-open="true"]')?.contains(target);

        const isTopModal = _modalService.modals[_modalService.modals.length - 1] === _modal;

        if (!clickedInsideModal && !clickedInsideDropdown && isTopModal) {
            visible.value = false;
            setTimeout(() => {
                _modalService.dismiss(modal, { type: ModalResultType.DISMISS });
            }, delay ?? 300);
        }
    }

    async function handleDismiss(action: ModalAction): Promise<boolean> {
        if (action.callback) {
            await action.callback.call(action);
        }

        if (typeof _modalComponent.value.notifyDismissed === 'function') {
            await _modalComponent.value.notifyDismissed(action);
        }

        return true;
    }

    onMounted(() => {
        setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
        }, 200);
    });

    onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside);
    });

    return {
        visible,
        modalRef,
        handleClick,
    };
}
