import React, { useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import api from '../../admin/api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Mail, Check, AlertCircle, ArrowLeft, ArrowRight } from 'lucide-react';

const ForgotPassword = () => {
    const { lang } = useParams();
    const isRtl = lang === 'sd';
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState(null); // success message
    const [error, setError] = useState(null);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);
        setStatus(null);

        try {
            const response = await api.post('/api/auth/forgot-password', { email });
            setStatus(response.data.message);
        } catch (err) {
            setError(err.response?.data?.message || (isRtl ? 'اي ميل موڪلڻ ۾ ناڪامي' : 'Failed to send reset link'));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className={`min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 ${isRtl ? 'font-arabic' : 'font-sans'}`} dir={isRtl ? 'rtl' : 'ltr'}>
            <div className="sm:mx-auto sm:w-full sm:max-w-md">
                <Link to={`/${lang}/`} className="flex justify-center mb-6">
                    {/* Logo could go here */}
                    <span className="text-2xl font-bold text-gray-900">Baakh</span>
                </Link>
                <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    {isRtl ? 'پاسورڊ ري سيٽ ڪريو' : 'Reset your password'}
                </h2>
                <p className="mt-2 text-center text-sm text-gray-600">
                    {isRtl
                        ? 'پنهنجو اي ميل پتو داخل ڪريو ۽ اسان توهان کي پاسورڊ ري سيٽ ڪرڻ جو لنڪ موڪلينداسين.'
                        : 'Enter your email address and we will send you a link to reset your password.'}
                </p>
            </div>

            <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    {status ? (
                        <div className="rounded-md bg-green-50 p-4">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <Check className="h-5 w-5 text-green-400" />
                                </div>
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-green-800">{status}</h3>
                                    <div className="mt-4">
                                        <p className="text-sm text-green-700">
                                            {isRtl ? 'مهرباني ڪري پنهنجو اي ميل انباڪس (۽ اسپيم فولڊر) چيڪ ڪريو.' : 'Please check your email inbox (and spam folder).'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <form className="space-y-6" onSubmit={handleSubmit}>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    {isRtl ? 'اي ميل پتو' : 'Email Address'}
                                </label>
                                <div className="mt-1 relative rounded-md shadow-sm">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <Mail className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <Input
                                        type="email"
                                        required
                                        className="pl-10"
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        placeholder={isRtl ? 'پنهنجو اي ميل لکو' : 'Enter your email'}
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
                                            {isRtl ? 'موڪلي پيو...' : 'Sending...'}
                                        </span>
                                    ) : (
                                        isRtl ? 'ري سيٽ لنڪ موڪليو' : 'Send Reset Link'
                                    )}
                                </Button>
                            </div>
                        </form>
                    )}

                    <div className="mt-6">
                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-gray-300" />
                            </div>
                            <div className="relative flex justify-center text-sm">
                                <span className="px-2 bg-white text-gray-500">
                                    {isRtl ? 'يا' : 'Or'}
                                </span>
                            </div>
                        </div>

                        <div className="mt-6 flex justify-center">
                            <Link to={`/${lang}/`} className="font-medium text-black hover:text-gray-500 flex items-center gap-2">
                                {isRtl ? <ArrowRight className="h-4 w-4" /> : <ArrowLeft className="h-4 w-4" />}
                                {isRtl ? 'لاگ ان صفحي تي واپس وڃو' : 'Back to Login'}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ForgotPassword;
