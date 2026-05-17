import { useToast } from 'vue-toastification';
import SuccessToast from '../components/toasts/SuccessToast.vue';
import WarningToast from '../components/toasts/WarningToast.vue';
import InfoToast from '../components/toasts/InfoToast.vue';
import ErrorToast from '../components/toasts/ErrorToast.vue';
import MobileBubbleToast from '../components/toasts/MobileBubbleToast.vue';
import type { IToastService } from '@/modules/types/support/toasts/IToastService.ts';

export default class ToastService implements IToastService {
    private toast = useToast();

    public info(message: string, duration: number = 3000, options?: Record<string, unknown>): void {
        const mobile = this.checkIsMobile();
        const toastOptions = {
            ...(mobile ? this.getMobileOptions(duration) : this.getDesktopOptions(duration)),
            ...options,
        };

        this.toast(
            mobile
                ? {
                      component: MobileBubbleToast,
                      props: {
                          message: message,
                          variant: 'info' as const,
                      },
                  }
                : {
                      component: InfoToast,
                      props: {
                          message: message,
                      },
                  },
            toastOptions,
        );
    }

    public success(message: string, duration: number = 3000, options?: Record<string, unknown>): void {
        const mobile = this.checkIsMobile();
        const toastOptions = {
            ...(mobile ? this.getMobileOptions(duration) : this.getDesktopOptions(duration)),
            ...options,
        };

        this.toast(
            mobile
                ? {
                      component: MobileBubbleToast,
                      props: {
                          message: message,
                          variant: 'success' as const,
                      },
                  }
                : {
                      component: SuccessToast,
                      props: {
                          message: message,
                      },
                  },
            toastOptions,
        );
    }

    public error(message: string, duration: number = 3000, options?: Record<string, unknown>): void {
        const mobile = this.checkIsMobile();
        const toastOptions = {
            ...(mobile ? this.getMobileOptions(duration) : this.getDesktopOptions(duration)),
            ...options,
        };

        this.toast(
            mobile
                ? {
                      component: MobileBubbleToast,
                      props: {
                          message: message,
                          variant: 'error' as const,
                      },
                  }
                : {
                      component: ErrorToast,
                      props: {
                          message: message,
                      },
                  },
            toastOptions,
        );
    }

    public warning(message: string, duration: number = 3000, options?: Record<string, unknown>): void {
        const mobile = this.checkIsMobile();
        const toastOptions = {
            ...(mobile ? this.getMobileOptions(duration) : this.getDesktopOptions(duration)),
            ...options,
        };

        this.toast(
            mobile
                ? {
                      component: MobileBubbleToast,
                      props: {
                          message: message,
                          variant: 'warning' as const,
                      },
                  }
                : {
                      component: WarningToast,
                      props: {
                          message: message,
                      },
                  },
            toastOptions,
        );
    }

    private checkIsMobile(): boolean {
        if (typeof window === 'undefined') {
            return false;
        }
        return window.matchMedia('(max-width: 768px)').matches;
    }

    private getMobileOptions(duration: number) {
        return {
            position: 'top-center' as const,
            transition: 'fade',
            timeout: duration || 3000,
            hideProgressBar: true,
            closeButton: false,
            icon: false,
            maxToasts: 1,
        };
    }

    private getDesktopOptions(duration: number) {
        return {
            timeout: duration,
        };
    }
}
