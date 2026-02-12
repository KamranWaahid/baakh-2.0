import React, { useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '../../admin/api/axios';
import { Lock, ArrowLeft, ArrowRight, Check, AlertCircle, LogOut, Shield, Clock, Eye, EyeOff } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Logo from '../components/Logo';

const Settings = () => {
    const { lang } = useParams();
    const isRtl = lang === 'sd';
    const { user, logout } = useAuth();
    const navigate = useNavigate();

    const [passwordData, setPasswordData] = useState({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const [showPasswords, setShowPasswords] = useState({
        current: false,
        new: false,
        confirm: false,
    });
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState(null);

    if (!user) {
        return (
            <div className={`min-h-screen bg-white flex items-center justify-center px-4 ${isRtl ? 'font-arabic text-right' : 'font-sans text-left'}`}>
                <div className="text-center space-y-4">
                    <Shield className="h-12 w-12 sm:h-16 sm:w-16 text-gray-300 mx-auto" />
                    <p className="text-gray-500 text-base sm:text-lg">{isRtl ? 'مھرباني ڪري پھرين لاگ ان ٿيو' : 'Please log in first'}</p>
                    <Link to={`/${lang}`}>
                        <Button variant="outline" className="rounded-full">{isRtl ? 'گھر واپس' : 'Go Home'}</Button>
                    </Link>
                </div>
            </div>
        );
    }

    const handlePasswordChange = (e) => {
        setPasswordData(prev => ({ ...prev, [e.target.name]: e.target.value }));
    };

    const togglePasswordVisibility = (field) => {
        setShowPasswords(prev => ({ ...prev, [field]: !prev[field] }));
    };

    const handlePasswordSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setMessage(null);

        try {
            await api.put('/api/auth/password', passwordData);
            setMessage({
                type: 'success',
                text: isRtl ? 'پاسورڊ ڪاميابيءَ سان تبديل ٿيو' : 'Password changed successfully',
            });
            setPasswordData({ current_password: '', password: '', password_confirmation: '' });
        } catch (error) {
            const errorMsg = error.response?.data?.message || (isRtl ? 'پاسورڊ تبديل ناڪام ٿيو' : 'Password change failed');
            setMessage({ type: 'error', text: errorMsg });
        } finally {
            setSaving(false);
        }
    };

    const handleLogout = async () => {
        await logout();
        navigate(`/${lang}`);
    };

    const BackArrow = isRtl ? ArrowRight : ArrowLeft;

    return (
        <div className={`min-h-screen bg-white text-black ${isRtl ? 'text-right font-arabic' : 'text-left font-sans'}`}>
            {/* Header */}
            <header className="px-4 sm:px-5 md:px-12 lg:px-24 py-4 sm:py-6 md:py-8 flex items-center border-b border-gray-100">
                <Link to={`/${lang}`} className="hover:opacity-80 transition-opacity">
                    <Logo className="h-7 w-7 sm:h-8 sm:w-8 md:h-10 md:w-10 text-black" />
                </Link>
            </header>

            <div className="py-6 sm:py-10 md:py-20 px-4 sm:px-5 md:px-12 lg:px-24">
                <div className="max-w-2xl mx-auto">
                    {/* Back link */}
                    <Link
                        to={`/${lang}`}
                        className="inline-flex items-center gap-2 text-gray-500 hover:text-black transition-colors mb-6 sm:mb-10 text-sm active:scale-95"
                    >
                        <BackArrow className="h-4 w-4" />
                        {isRtl ? 'واپس' : 'Back'}
                    </Link>

                    <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-8 sm:mb-12">
                        {isRtl ? 'سيٽنگون' : 'Settings'}
                    </h1>

                    {/* Status message */}
                    {message && (
                        <div className={`mb-6 sm:mb-8 flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-3 rounded-xl text-xs sm:text-sm ${message.type === 'success' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'}`}>
                            {message.type === 'success' ? <Check className="h-4 w-4 shrink-0" /> : <AlertCircle className="h-4 w-4 shrink-0" />}
                            {message.text}
                        </div>
                    )}

                    {/* ── Change Password ── */}
                    <section className="mb-10 sm:mb-14">
                        <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6 flex items-center gap-2">
                            <Lock className="h-4 w-4 sm:h-5 sm:w-5 text-gray-400" />
                            {isRtl ? 'پاسورڊ تبديل ڪريو' : 'Change Password'}
                        </h2>

                        <form onSubmit={handlePasswordSubmit} className="space-y-4 sm:space-y-5">
                            {/* Current Password */}
                            <div className="space-y-1.5 sm:space-y-2">
                                <label className="text-xs sm:text-sm font-medium text-gray-700">
                                    {isRtl ? 'موجوده پاسورڊ' : 'Current Password'}
                                </label>
                                <div className="relative">
                                    <Input
                                        name="current_password"
                                        type={showPasswords.current ? 'text' : 'password'}
                                        value={passwordData.current_password}
                                        onChange={handlePasswordChange}
                                        required
                                        className="rounded-xl border-gray-200 focus:border-black focus:ring-black/5 h-11 sm:h-12 pr-12 text-sm sm:text-base"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => togglePasswordVisibility('current')}
                                        className="absolute top-1/2 -translate-y-1/2 right-3 text-gray-400 hover:text-gray-600 p-1"
                                    >
                                        {showPasswords.current ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                            </div>

                            {/* New Password */}
                            <div className="space-y-1.5 sm:space-y-2">
                                <label className="text-xs sm:text-sm font-medium text-gray-700">
                                    {isRtl ? 'نئون پاسورڊ' : 'New Password'}
                                </label>
                                <div className="relative">
                                    <Input
                                        name="password"
                                        type={showPasswords.new ? 'text' : 'password'}
                                        value={passwordData.password}
                                        onChange={handlePasswordChange}
                                        required
                                        className="rounded-xl border-gray-200 focus:border-black focus:ring-black/5 h-11 sm:h-12 pr-12 text-sm sm:text-base"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => togglePasswordVisibility('new')}
                                        className="absolute top-1/2 -translate-y-1/2 right-3 text-gray-400 hover:text-gray-600 p-1"
                                    >
                                        {showPasswords.new ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                            </div>

                            {/* Confirm Password */}
                            <div className="space-y-1.5 sm:space-y-2">
                                <label className="text-xs sm:text-sm font-medium text-gray-700">
                                    {isRtl ? 'پاسورڊ جي تصديق' : 'Confirm New Password'}
                                </label>
                                <div className="relative">
                                    <Input
                                        name="password_confirmation"
                                        type={showPasswords.confirm ? 'text' : 'password'}
                                        value={passwordData.password_confirmation}
                                        onChange={handlePasswordChange}
                                        required
                                        className="rounded-xl border-gray-200 focus:border-black focus:ring-black/5 h-11 sm:h-12 pr-12 text-sm sm:text-base"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => togglePasswordVisibility('confirm')}
                                        className="absolute top-1/2 -translate-y-1/2 right-3 text-gray-400 hover:text-gray-600 p-1"
                                    >
                                        {showPasswords.confirm ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                            </div>

                            {/* Submit — full width on mobile */}
                            <div className="pt-2">
                                <Button
                                    type="submit"
                                    disabled={saving}
                                    className="w-full sm:w-auto bg-black text-white hover:bg-gray-800 rounded-full px-8 h-11 text-sm font-medium active:scale-[0.98]"
                                >
                                    {saving ? (
                                        <span className="flex items-center justify-center gap-2">
                                            <span className="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                            {isRtl ? 'محفوظ ٿي رهيو آهي...' : 'Saving...'}
                                        </span>
                                    ) : (
                                        isRtl ? 'پاسورڊ تبديل ڪريو' : 'Update Password'
                                    )}
                                </Button>
                            </div>
                        </form>
                    </section>

                    {/* ── Account Info ── */}
                    <section className="mb-10 sm:mb-14">
                        <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6 flex items-center gap-2">
                            <Shield className="h-4 w-4 sm:h-5 sm:w-5 text-gray-400" />
                            {isRtl ? 'اڪائونٽ' : 'Account'}
                        </h2>

                        <div className="bg-gray-50 rounded-2xl p-4 sm:p-6 space-y-3 sm:space-y-4">
                            <div className="flex justify-between items-center">
                                <span className="text-xs sm:text-sm text-gray-500">{isRtl ? 'حالت' : 'Status'}</span>
                                <span className={`text-xs sm:text-sm font-medium px-2.5 sm:px-3 py-0.5 sm:py-1 rounded-full ${user.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}`}>
                                    {user.status === 'active' ? (isRtl ? 'فعال' : 'Active') : (isRtl ? 'غير فعال' : 'Inactive')}
                                </span>
                            </div>
                            <div className="h-px bg-gray-200" />
                            <div className="flex justify-between items-center">
                                <span className="text-xs sm:text-sm text-gray-500">{isRtl ? 'ڪردار' : 'Role'}</span>
                                <span className="text-xs sm:text-sm font-medium text-gray-900">
                                    {user.roles && user.roles.length > 0 ? user.roles.join(', ') : (isRtl ? 'صارف' : 'User')}
                                </span>
                            </div>
                            <div className="h-px bg-gray-200" />
                            <div className="flex justify-between items-center">
                                <span className="text-xs sm:text-sm text-gray-500 flex items-center gap-1">
                                    <Clock className="h-3 w-3 sm:h-3.5 sm:w-3.5" />
                                    {isRtl ? 'يوزر نيم' : 'Username'}
                                </span>
                                <span className="text-xs sm:text-sm text-gray-900">{user.username || '—'}</span>
                            </div>
                        </div>
                    </section>

                    {/* ── Sign Out ── */}
                    <section className="pb-8 sm:pb-0">
                        <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6">
                            {isRtl ? 'لاگ آئوٽ' : 'Sign Out'}
                        </h2>
                        <div className="border border-gray-200 rounded-2xl p-4 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4">
                            <div>
                                <p className="text-xs sm:text-sm text-gray-700 font-medium">
                                    {isRtl ? 'پنھنجي اڪائونٽ مان لاگ آئوٽ ٿيو' : 'Sign out of your account'}
                                </p>
                                <p className="text-[11px] sm:text-xs text-gray-500 mt-1">
                                    {isRtl ? 'توھان کي ٻيهر لاگ ان ڪرڻو پوندو' : 'You will need to log in again'}
                                </p>
                            </div>
                            <Button
                                variant="outline"
                                onClick={handleLogout}
                                className="w-full sm:w-auto rounded-full border-gray-300 hover:bg-gray-100 hover:border-gray-400 text-sm gap-2 active:scale-[0.98]"
                            >
                                <LogOut className="h-4 w-4" />
                                {isRtl ? 'لاگ آئوٽ' : 'Log out'}
                            </Button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    );
};

export default Settings;
