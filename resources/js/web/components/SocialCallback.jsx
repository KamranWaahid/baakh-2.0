import React, { useEffect } from 'react';
import { useNavigate, useSearchParams, useParams } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '@/admin/api/axios';
import { Loader2 } from 'lucide-react';

const SocialCallback = () => {
    const [searchParams] = useSearchParams();
    const { lang } = useParams();
    const navigate = useNavigate();
    const { setUser, checkAuth } = useAuth();

    useEffect(() => {
        const handleCallback = async () => {
            const handshake = searchParams.get('k');
            const legacyToken = searchParams.get('token');
            let token = legacyToken;
            let isNewUser = searchParams.get('new_user') === '1';

            if (handshake) {
                try {
                    const exchanged = await api.post('/api/auth/google/handshake', { k: handshake });
                    token = exchanged.data?.token || null;
                    isNewUser = !!exchanged.data?.new_user;
                } catch (error) {
                    console.error('Error exchanging Google handshake:', error);
                    navigate(`/${lang}/?error=callback_error`, { replace: true });
                    return;
                }
            }

            if (token) {
                localStorage.setItem('auth_token', token);

                try {
                    const meResponse = await api.get('/api/auth/me', {
                        headers: {
                            Authorization: `Bearer ${token}`,
                        },
                    });
                    const user = meResponse?.data?.user || null;

                    if (user) {
                        setUser(user);
                        const canAccessAdmin = user.permissions?.includes('view_dashboard');

                        if (isNewUser) {
                            navigate(`/${lang}/auth/set-password`, { replace: true });
                        } else if (canAccessAdmin) {
                            window.location.href = '/admin';
                        } else {
                            window.location.replace(`/${lang}/`);
                        }
                    } else {
                        const fallbackUser = await checkAuth();
                        if (fallbackUser) {
                            setUser(fallbackUser);
                            const canAccessAdmin = fallbackUser.permissions?.includes('view_dashboard');
                            if (isNewUser) {
                                navigate(`/${lang}/auth/set-password`, { replace: true });
                            } else if (canAccessAdmin) {
                                window.location.href = '/admin';
                            } else {
                                window.location.replace(`/${lang}/`);
                            }
                            return;
                        }

                        console.error('Failed to verify user after social login.');
                        navigate(`/${lang}/?error=auth_failed_verification`, { replace: true });
                    }
                } catch (error) {
                    console.error('Error during social callback processing:', error);
                    navigate(`/${lang}/?error=callback_error`, { replace: true });
                }
            } else {
                console.warn('No token provided in social callback URL');
                navigate(`/${lang}/?error=no_token`, { replace: true });
            }
        };

        handleCallback();
    }, [searchParams, lang, navigate, checkAuth, setUser]);

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
