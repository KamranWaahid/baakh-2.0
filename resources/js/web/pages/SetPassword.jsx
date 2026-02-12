import React, { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../admin/api/axios';
import { useAuth } from '../contexts/AuthContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Lock, Eye, EyeOff, Check, AlertCircle } from 'lucide-react';

const SetPassword = () => {
    const { lang } = useParams();
    const isRtl = lang === 'sd';
    const navigate = useNavigate();
    const { user } = useAuth();

    const [formData, setFormData] = useState({
        password: '',
        password_confirmation: ''
    });
    const [showPassword, setShowPassword] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

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
            await api.put('/api/auth/password/set', {
                password: formData.password,
                password_confirmation: formData.password_confirmation
            });

            // Redirect to home on success
            window.location.replace(`/${lang}/`);
        } catch (err) {
            setError(err.response?.data?.message || (isRtl ? 'پاسورڊ سيٽ ڪرڻ ۾ ناڪامي' : 'Failed to set password'));
            setLoading(false);
        }
    };

    return (
        <div className={`min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 ${isRtl ? 'font-arabic' : 'font-sans'}`} dir={isRtl ? 'rtl' : 'ltr'}>
            <div className="sm:mx-auto sm:w-full sm:max-w-md">
                <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    {isRtl ? 'پنهنجو پاسورڊ ٺاهيو' : 'Create your password'}
                </h2>
                <p className="mt-2 text-center text-sm text-gray-600">
                    {isRtl
                        ? 'توهان جي اڪائونٽ کي محفوظ ڪرڻ لاء، مهرباني ڪري پاسورڊ سيٽ ڪريو.'
                        : 'To secure your account, please set a password.'}
                </p>
            </div>

            <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    <form className="space-y-6" onSubmit={handleSubmit}>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                {isRtl ? 'پاسورڊ' : 'Password'}
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
                                        {isRtl ? 'محفوظ ٿي رهيو آهي...' : 'Saving...'}
                                    </span>
                                ) : (
                                    isRtl ? 'پاسورڊ سيٽ ڪريو' : 'Set Password'
                                )}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default SetPassword;
