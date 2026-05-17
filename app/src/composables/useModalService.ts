import type { IModalService } from '@/modules/types/support/modals/IModalService.ts';
import { inject } from 'vue';

export function useModalService(): IModalService {
    const service = inject<IModalService>('modalService');
    if (!service) {
        throw new Error('ModalService not provided!');
    }

    return service;
}
