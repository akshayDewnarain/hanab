import type { App } from 'vue';
import ToastService from '../services/ToastService';

export default {
    install(app: App) {
        app.provide('toastService', new ToastService());
    },
};
