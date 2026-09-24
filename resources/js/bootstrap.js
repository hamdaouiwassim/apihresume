import axios from 'axios';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

/**
 * Same-origin client for the existing JSON API (/api/*).
 * Session cookie auth (Sanctum stateful) + CSRF token from the page meta tag.
 * Interceptors mirror the former React axiosInstance: 422 -> toast, 401 -> /login, 419 -> refresh token once.
 */
const api = axios.create({
    baseURL: '/api/',
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
    },
});

const formatValidationErrors = (errors) => {
    if (!errors || typeof errors !== 'object') return [];
    const messages = [];
    Object.keys(errors).forEach((field) => {
        const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
        fieldErrors.forEach((msg) => {
            if (msg) {
                const label = field.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
                messages.push(`${label}: ${msg}`);
            }
        });
    });
    return messages;
};

api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const { response, config } = error;
        if (!response) return Promise.reject(error);
        const { status, data } = response;

        if (status === 419 && !config?._csrfRetry) {
            try {
                const { data: tokenData } = await api.get('csrf-token');
                if (tokenData?.csrf_token) {
                    api.defaults.headers.common['X-CSRF-TOKEN'] = tokenData.csrf_token;
                    return api.request({
                        ...config,
                        _csrfRetry: true,
                        headers: { ...config.headers, 'X-CSRF-TOKEN': tokenData.csrf_token },
                    });
                }
            } catch {
                return Promise.reject(error);
            }
        }

        if (status === 401 && !config?.skipAuthRedirect) {
            window.location.href = '/login';
            return Promise.reject(error);
        }

        if (status === 422 && !config?.skipValidationToast && window.toast) {
            const formatted = formatValidationErrors(data?.errors);
            const message = data?.message || 'Validation failed';
            if (formatted.length === 1) {
                window.toast.error(formatted[0], { duration: 5000 });
            } else if (formatted.length > 1) {
                window.toast.error(message, {
                    description: formatted.map((e, i) => `${i + 1}. ${e}`).join('\n'),
                    duration: 6000,
                });
            } else {
                window.toast.error(message || 'Validation failed. Please check your input.', { duration: 5000 });
            }
        }

        return Promise.reject(error);
    },
);

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

window.api = api;

/** Pull a readable message out of an axios error (mirrors the React error handling). */
window.apiErrorMessage = (error, fallback = 'Something went wrong') => error?.response?.data?.message || fallback;

export default api;
