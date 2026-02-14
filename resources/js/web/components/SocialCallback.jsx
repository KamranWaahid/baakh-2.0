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
            const isNewUser = searchParams.get('new_user') === '1';
            const isRtl = lang === 'sd';

            if (token) {
                // Save token to localStorage
                localStorage.setItem('auth_token', token);

                try {
                    // Force a small delay to ensure token is set
                    await new Promise(resolve => setTimeout(resolve, 100));

                    // Synchronize state with backend
                    const user = await checkAuth();

                    if (user) {
                        const canAccessAdmin = user.permissions?.includes('view_dashboard');

                        if (isNewUser) {
                            // New user - redirect to set password page
                            navigate(`/${lang}/auth/set-password`, { replace: true });
                        } else if (canAccessAdmin) {
                            // Admin user - redirect to admin panel
                            window.location.href = '/admin';
                        } else {
                            // Regular user - redirect to home
                            // Using window.location.replace to prevent back button looping
                            window.location.replace(`/${lang}/`);
                        }
                    } else {
                        // Failed to verify user even with token
                        console.error('Failed to verify user after social login. Token:', token.substring(0, 10) + '...');
                        navigate(`/${lang}/?error=auth_failed_verification`, { replace: true });
                    }
                } catch (error) {
                    console.error('Error during social callback processing:', error);
                    navigate(`/${lang}/?error=callback_error&details=${encodeURIComponent(error.message)}`, { replace: true });
                }
            } else {
                // No token found in URL
                console.warn('No token provided in social callback URL');
                navigate(`/${lang}/?error=no_token`, { replace: true });
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
