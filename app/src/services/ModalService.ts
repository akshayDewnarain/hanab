import type { ModalProps } from '@/modules/types/support/modals/ModalProps.ts';
import type { ModalResult } from '@/modules/types/support/modals/ModalResult.ts';
import { markRaw, reactive } from 'vue';
import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
export default class ModalService {
    /**
     * The modals currently open
     */
    private _modals = reactive<ModalProps[]>([]);

    /**
     * Returns the modals currently open
     * @returns {ModalProps[]}
     */
    get modals(): ModalProps[] {
        return this._modals;
    }

    /**
     * Opens a new modal
     * @param modal
     */
    public open(modal: ModalProps): Promise<ModalResult> {
        return new Promise<ModalResult>((resolve) => {
            modal._resolver = resolve; // store resolver internally
            if (modal.component) {
                modal.component = markRaw(modal.component);
            }
            this._modals.push(modal);
        });
    }

    /**
     * Dismisses a modal
     * @param modal
     * @param result
     * @returns {Promise<boolean>}
     */
    public async dismiss(modal: ModalProps, result: ModalResult): Promise<boolean> {
        if (!this._modals.includes(modal)) {
            return false;
        }

        switch (result.type) {
            case ModalResultType.ACCEPT:
                modal.onAccept?.call(null, result);
                break;
            case ModalResultType.DISMISS:
                modal.onDismiss?.call(null);
                break;
        }

        const index: number = this._modals.indexOf(modal);
        if (index !== -1) {
            this._modals.splice(index, 1);
        }

        modal._resolver?.(result);

        return Promise.resolve(true);
    }

    /**
     * Dismisses all current active modals
     * @returns {Promise<boolean>}
     */
    public async dismissAll(): Promise<boolean> {
        const modalsToDismiss: ModalProps[] = [...this._modals];
        for (const modal of modalsToDismiss) {
            await this.dismiss(modal, { type: ModalResultType.DISMISS });
        }
        return true;
    }
}
