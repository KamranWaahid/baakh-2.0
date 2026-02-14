import React, { useState, useEffect } from 'react';
import { useNavigate, useParams, useSearchParams, Link } from 'react-router-dom';
import api from '../../admin/api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Lock, Eye, EyeOff, AlertCircle, CheckCircle } from 'lucide-react';

const ResetPassword = () => {
    const { lang, token } = useParams(); // Start with URL params (React Router)
    const [searchParams] = useSearchParams();
    const emailFromQuery = searchParams.get('email');

    // Sometimes frameworks/routers might put token in query or path. Laravel default is path for token, query for email.
    // Our Notification builds: /password-reset/{token}?email={email}
    // So 'token' comes from route param, 'email' from query.

    const isRtl = lang === 'sd';
    const navigate = useNavigate();

    const [formData, setFormData] = useState({
        email: emailFromQuery || '',
        password: '',
        password_confirmation: ''
    });
    const [showPassword, setShowPassword] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        if (formData.password !== formData.password_confirmation) {
            setError(isRtl ? 'پاسورڊ هڪجهڙا ناهين' : 'Passwords do not match');
            setLoading(false);
            return;
        }

        if (formData.password.length < 8) {
            setError(isRtl ? 'پاسورڊ گهٽ ۾ گهٽ 8 اکر هجڻ گهرجي' : 'Password must be at least 8 characters');
            setLoading(false);
            return;
        }

        try {
            await api.post('/api/auth/reset-password', {
                token: token,
                email: formData.email,
                password: formData.password,
                password_confirmation: formData.password_confirmation
            });
            setSuccess(true);
            setTimeout(() => {
                navigate(`/${lang}/`); // Redirect to login (assuming login modal or page is at root)
            }, 3000);
        } catch (err) {
            setError(err.response?.data?.message || (isRtl ? 'پاسورڊ ري سيٽ ڪرڻ ۾ ناڪامي' : 'Failed to reset password. Token may be invalid or expired.'));
        } finally {
            setLoading(false);
        }
    };

    if (success) {
        return (
            <div className={`min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 ${isRtl ? 'font-arabic' : 'font-sans'}`} dir={isRtl ? 'rtl' : 'ltr'}>
                <div className="sm:mx-auto sm:w-full sm:max-w-md">
                    <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 text-center">
                        <CheckCircle className="h-12 w-12 text-green-500 mx-auto mb-4" />
                        <h2 className="text-2xl font-bold text-gray-900 mb-2">
                            {isRtl ? 'پاسورڊ ڪاميابي سان تبديل ٿي ويو' : 'Password Reset Successful'}
                        </h2>
                        <p className="text-gray-600 mb-6">
                            {isRtl ? 'توهان جو پاسورڊ تبديل ڪيو ويو آهي. توهان کي لاگ ان صفحي ڏانهن منتقل ڪيو پيو وڃي...' : 'Your password has been reset. Redirecting you to login...'}
                        </p>
                        <Button onClick={() => navigate(`/${lang}/`)} className="w-full">
                            {isRtl ? 'هاڻي لاگ ان ٿيو' : 'Login Now'}
                        </Button>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className={`min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 ${isRtl ? 'font-arabic' : 'font-sans'}`} dir={isRtl ? 'rtl' : 'ltr'}>
            <div className="sm:mx-auto sm:w-full sm:max-w-md">
                <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    {isRtl ? 'نوون پاسورڊ سيٽ ڪريو' : 'Set New Password'}
                </h2>
            </div>

            <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    <form className="space-y-6" onSubmit={handleSubmit}>
                        {/* Hidden Email Field (or ReadOnly) */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                {isRtl ? 'اي ميل پتو' : 'Email Address'}
                            </label>
                            <div className="mt-1">
                                <Input
                                    type="email"
                                    required
                                    readOnly
                                    className="bg-gray-100 cursor-not-allowed"
                                    value={formData.email}
                                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                {isRtl ? 'نوون پاسورڊ' : 'New Password'}
                            </label>
                            <div className="mt-1 relative rounded-md shadow-sm">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Lock className="h-5 w-5 text-gray-400" />
                                </div>
                                <Input
                                    type={showPassword ? "text" : "password"}
                                    required
                                    className="pl-10"
                                    value={formData.password}
                                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                    placeholder={isRtl ? 'گهٽ ۾ گهٽ 8 لفظ' : 'Minimum 8 characters'}
                                />
                                <div className="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onClick={() => setShowPassword(!showPassword)}>
                                    {showPassword ? <EyeOff className="h-5 w-5 text-gray-400" /> : <Eye className="h-5 w-5 text-gray-400" />}
                                </div>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                {isRtl ? 'پاسورڊ جي تصديق ڪريو' : 'Confirm Password'}
                            </label>
                            <div className="mt-1 relative rounded-md shadow-sm">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Lock className="h-5 w-5 text-gray-400" />
                                </div>
                                <Input
                                    type={showPassword ? "text" : "password"}
                                    required
                                    className="pl-10"
                                    value={formData.password_confirmation}
                                    onChange={(e) => setFormData({ ...formData, password_confirmation: e.target.value })}
                                />
                            </div>
                        </div>

                        {error && (
                            <div className="rounded-md bg-red-50 p-4">
                                <div className="flex">
                                    <div className="flex-shrink-0">
                                        <AlertCircle className="h-5 w-5 text-red-400" />
                                    </div>
                                    <div className="ml-3">
                                        <h3 className="text-sm font-medium text-red-800">{error}</h3>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div>
                            <Button
                                type="submit"
                                className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black"
                                disabled={loading}
                            >
                                {loading ? (
                                    <span className="flex items-center gap-2">
                                        <span className="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                        {isRtl ? 'ري سيٽ ٿي رهيو آهي...' : 'Resetting...'}
                                    </span>
                                ) : (
                                    isRtl ? 'پاسورڊ ري سيٽ ڪريو' : 'Reset Password'
                                )}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default ResetPassword;
