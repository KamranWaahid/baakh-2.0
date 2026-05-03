import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
    withCredentials: true,
});

api.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    // Global Language Interceptor
    // Automatically detect language from URL path (/en/ or /sd/) and attach as param
    const pathname = window.location.pathname;
    const langMatch = pathname.match(/^\/(en|sd)(\/|$)/);
    const lang = langMatch ? langMatch[1] : 'sd';

    if (lang) {
        config.params = {
            ...config.params,
            lang: lang
        };
        config.headers['Accept-Language'] = lang;
    }

    // Normalize API paths for serverless deployments where /api can be stripped.
    // Keep all app calls on stripped runtime paths that are known to resolve on Vercel.
    // Admin endpoints are requested as /admin/* with XHR/JSON headers so they are
    // forwarded to API handlers instead of the admin SPA HTML shell.
    if (typeof config.url === 'string') {
        if (config.url.startsWith('/api/v1/')) {
            config.url = config.url.replace('/api/v1/', '/v1/');
        } else if (config.url.startsWith('/api/auth/')) {
            config.url = config.url.replace('/api/auth/', '/auth/');
        } else if (config.url.startsWith('/api/admin/')) {
            config.url = config.url.replace('/api/admin/', '/admin/');
        }
    }

    return config;
});

export default api;

