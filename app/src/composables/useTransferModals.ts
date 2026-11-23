import ImportDataModal from '@/components/import-export/ImportDataModal.vue';
import ExportDataModal from '@/components/import-export/ExportDataModal.vue';
import { useModalService } from '@/composables/useModalService';
import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType.ts';
import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';

export function useTransferModals() {
    const modalService = useModalService();

    function openImport(modelKey: string, refresh: () => void): void {
        void modalService.open({
            title: 'IMPORT_DATA_TITLE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            panelClass: 'import-modal-panel w-full max-h-[90vh] rounded-xl border-slate-200 shadow-2xl',
            disableDefaultPadding: true,
            bare: true,
            component: ImportDataModal,
            props: {
                modelKey,
                refresh,
            },
            actions: [],
        });
    }

    function openTransfer(
        modelKey: string,
        purpose: 'export' | 'template',
        filters: Record<string, unknown> = {},
        search = '',
    ): void {
        void modalService.open({
            title: purpose === 'template' ? 'TEMPLATE_TITLE' : 'EXPORT_DATA_TITLE',
            type: ModalType.SLIDE_IN_TOP_CENTER,
            size: 'lg',
            component: ExportDataModal,
            props: {
                modelKey,
                purpose,
                filters: { ...filters },
                search,
            },
            actions: [
                {
                    type: ModalResultType.DISMISS,
                    name: 'GENERAL_DISMISS',
                    variant: ModalActionType.DISMISS,
                },
            ],
        });
    }

    return { openImport, openTransfer };
}
