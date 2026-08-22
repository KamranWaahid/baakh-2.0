const GSI_SRC = 'https://accounts.google.com/gsi/client';
const GSI_SCRIPT_ID = 'google-gsi-client';

export function googleClientId() {
    if (typeof window === 'undefined') {
        return '';
    }

    return String(window.__BAAKH_GOOGLE_CLIENT_ID__ || '').trim();
}

export function prefetchGoogleIdentity() {
    if (typeof document === 'undefined' || window.google?.accounts?.oauth2) {
        return;
    }

    if (document.getElementById(GSI_SCRIPT_ID)) {
        return;
    }

    const script = document.createElement('script');
    script.id = GSI_SCRIPT_ID;
    script.src = GSI_SRC;
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
}

export function requestGoogleAccessToken() {
    return new Promise((resolve, reject) => {
        const clientId = googleClientId();
        if (!clientId || !window.google?.accounts?.oauth2) {
            reject(new Error('gsi_unavailable'));
            return;
        }

        const client = window.google.accounts.oauth2.initTokenClient({
            client_id: clientId,
            scope: 'openid email profile',
            callback: (response) => {
                if (response?.error) {
                    reject(new Error(response.error));
                    return;
                }
                if (!response?.access_token) {
                    reject(new Error('missing_access_token'));
                    return;
                }
                resolve(response.access_token);
            },
            error_callback: (err) => {
                reject(new Error(err?.type || err?.message || 'google_popup_closed'));
            },
        });

        client.requestAccessToken();
    });
}
