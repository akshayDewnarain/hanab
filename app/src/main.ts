import { createApp } from 'vue';
import { createPinia } from 'pinia';
import '@/css/global.css'

import App from './App.vue';
import router from './router';
import ModalServicePlugin from '@/plugins/ModalServicePlugin.ts';
import i18n from '@/i18n';
import Toast, { type PluginOptions } from 'vue-toastification';
import ToastServicePlugin from '@/plugins/ToastServicePlugin.ts';
import 'vue-toastification/dist/index.css';


const app = createApp(App);

const options: PluginOptions = {
    hideProgressBar: true,
    closeButton: false,
    icon: false,
    toastClassName: '',
    bodyClassName: '',
    timeout: false,
    shareAppContext: true,
    transition: 'Vue-Toastification__fade',
    onMounted: (_, toastApp) => {
        toastApp.use(i18n);
    },
};

app.use(createPinia());
app.use(router);
app.use(i18n);

app.use(ModalServicePlugin);
app.use(Toast, options);
app.use(ToastServicePlugin);

app.mount('#app');
