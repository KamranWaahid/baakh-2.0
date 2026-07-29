import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
    withCredentials: true,
});

function looksLikeHtmlPayload(data) {
    if (typeof data !== 'string') {
        return false;
    }
    const s = data.trimStart().toLowerCase();
    return s.startsWith('<!doctype') || s.startsWith('<html');
}

api.interceptors.response.use(
    (response) => {
        const ct = response.headers['content-type'] || '';
        if (
            ct.includes('text/html') &&
            looksLikeHtmlPayload(response.data)
        ) {
            const err = new Error(
                'Server returned HTML instead of JSON. Fix API routing or sign in again.'
            );
            err.response = response;
            return Promise.reject(err);
        }
        return response;
    },
    (error) => {
        const data = error.response?.data;
        if (typeof data === 'string' && looksLikeHtmlPayload(data)) {
            error.message =
                'Server returned HTML instead of JSON. Fix API routing or sign in again.';
        }
        return Promise.reject(error);
    }
);

api.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    // Detect language from /en|/sd or /lyrics-site/en|/lyrics-site/sd
    const pathname = window.location.pathname;
    const langMatch = pathname.match(/(?:^|\/lyrics-site)\/(en|sd)(?:\/|$)/);
    const pathLang = langMatch ? langMatch[1] : 'sd';

    // Caller-provided lang (Bol SPA) wins over path detection.
    config.params = {
        lang: pathLang,
        ...config.params,
    };
    config.headers['Accept-Language'] = config.params.lang || pathLang;

    // Keep explicit API prefixes untouched to avoid hitting SPA/web routes.
    // All callers use /api/* paths and should resolve through Laravel API routes.

    return config;
});

export default api;

