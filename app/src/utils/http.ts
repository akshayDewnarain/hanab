import type { AxiosInstance } from 'axios';
import axios from 'axios';
import { handleAuthSessionLost, isSessionExpiredResponse, isUnauthorizedResponse } from '@/utils/handleAuthSessionLost';

const http: AxiosInstance = axios.create({
    baseURL: import.meta.env.VITE_BASE_API_URL,
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
    },
});

http.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status as number | undefined;
        const data = error.response?.data;
        if (isSessionExpiredResponse(status, data)) {
            handleAuthSessionLost();
        } else if (isUnauthorizedResponse(status, data)) {
            handleAuthSessionLost();
        }

        return Promise.reject(error);
    },
);

export default http;
