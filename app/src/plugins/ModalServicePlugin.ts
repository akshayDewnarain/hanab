import type { App } from 'vue';
import ModalService from '../services/ModalService';

export default {
    install(app: App) {
        app.provide('modalService', new ModalService());
    },
};
