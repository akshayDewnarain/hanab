import type { IToastService } from '@/modules/types/support/toasts/IToastService.ts';
import { inject } from 'vue';

export function useToastService(): IToastService {
    const service = inject<IToastService>('toastService');
    if (!service) {
        throw new Error('ToastService not provided!');
    }

    return service;
}
