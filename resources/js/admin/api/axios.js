import axios from 'axios';

const api = axios.create({
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

    return config;
});

export default api;

