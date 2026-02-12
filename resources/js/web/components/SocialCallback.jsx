import React, { useEffect } from 'react';
import { useNavigate, useSearchParams, useParams } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { Loader2 } from 'lucide-react';

const SocialCallback = () => {
    const [searchParams] = useSearchParams();
    const { lang } = useParams();
    const navigate = useNavigate();
    const { checkAuth } = useAuth();

    useEffect(() => {
        const handleCallback = async () => {
            const token = searchParams.get('token');
            const isRtl = lang === 'sd';

            if (token) {
                // Save token to localStorage
                localStorage.setItem('auth_token', token);

                try {
                    // Synchronize state with backend
                    const user = await checkAuth();

                    if (user) {
                        // Success - redirect to home
                        // Success - redirect to home with full reload to ensure auth state
                        window.location.href = `/${lang}/`;
                    } else {
                        // Failed to verify user even with token
                        console.error('Failed to verify user after social login');
                        navigate(`/${lang}/?error=auth_failed`, { replace: true });
                    }
                } catch (error) {
                    console.error('Error during social callback processing:', error);
                    navigate(`/${lang}/?error=callback_error`, { replace: true });
                }
            } else {
                // No token found in URL
                console.warn('No token provided in social callback URL');
                navigate(`/${lang}/`, { replace: true });
            }
        };

        handleCallback();
    }, [searchParams, lang, navigate, checkAuth]);

    return (
        <div className="min-h-screen w-full flex flex-col items-center justify-center bg-white">
            <div className="flex flex-col items-center gap-4">
                <Loader2 className="h-10 w-10 animate-spin text-black" />
                <p className="text-gray-500 font-medium font-serif animate-pulse">
                    {lang === 'sd' ? 'مهرباني ڪري انتظار ڪريو...' : 'Please wait, finishing sign in...'}
                </p>
            </div>
        </div>
    );
};

export default SocialCallback;
