import React, { useState, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '../../admin/api/axios';
import { Camera, Save, ArrowLeft, ArrowRight, User as UserIcon, Mail, Phone, MessageCircle, Check, AlertCircle, Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import Logo from '../components/Logo';
import { getImageUrl } from '../utils/url';

// Deterministic color generator based on string
const getAvatarColor = (str) => {
    if (!str) return '#3b82f6'; // Default blue
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    const hue = Math.abs(hash % 360);
    return `hsl(${hue}, 70%, 50%)`;
};

const Profile = () => {
    const { lang } = useParams();
    const isRtl = lang === 'sd';
    const { user, setUser, checkAuth } = useAuth();

    const [formData, setFormData] = useState({
        name: user?.name || '',
        email: user?.email || '',
    });
    const [avatarFile, setAvatarFile] = useState(null);
    const [avatarPreview, setAvatarPreview] = useState(null);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState(null);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isPrivacyModalOpen, setIsPrivacyModalOpen] = useState(false);
    const fileInputRef = useRef(null);

    const handleDeleteAccount = async (password) => {
        try {
            await api.delete('/api/auth/profile', {
                data: { password } // Axios sends body in 'data' for DELETE requests
            });
            // Logout and redirect
            setUser(null);
            localStorage.removeItem('auth_token');
            window.location.href = `/${lang}`;
        } catch (error) {
            throw error; // Let the modal handle the error display
        }
    };

    if (!user) {
        return (
            <div className={`min-h-screen bg-white flex items-center justify-center px-4 ${isRtl ? 'font-arabic text-right' : 'font-sans text-left'}`}>
                <div className="text-center space-y-4">
                    <UserIcon className="h-12 w-12 sm:h-16 sm:w-16 text-gray-300 mx-auto" />
                    <p className="text-gray-500 text-base sm:text-lg">{isRtl ? 'مھرباني ڪري پھرين لاگ ان ٿيو' : 'Please log in first'}</p>
                    <Link to={`/${lang}`}>
                        <Button variant="outline" className="rounded-full">{isRtl ? 'گھر واپس' : 'Go Home'}</Button>
                    </Link>
                </div>
            </div>
        );
    }

    const handleChange = (e) => {
        setFormData(prev => ({ ...prev, [e.target.name]: e.target.value }));
    };

    const handleAvatarChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setAvatarFile(file);
            setAvatarPreview(URL.createObjectURL(file));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setMessage(null);

        try {
            const data = new FormData();
            data.append('name', formData.name);
            data.append('email', formData.email);
            // Removed name_sd, phone, whatsapp, avatar append

            const response = await api.post('/api/auth/profile', data, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            // Refresh user data in AuthContext
            await checkAuth();

            setMessage({
                type: 'success',
                text: isRtl ? 'پروفائل ڪاميابيءَ سان اپڊيٽ ٿيو' : 'Profile updated successfully',
            });
        } catch (error) {
            const errorMsg = error.response?.data?.message || (isRtl ? 'اپڊيٽ ناڪام ٿيو' : 'Update failed');
            setMessage({ type: 'error', text: errorMsg });
        } finally {
            setSaving(false);
        }
    };

    const avatarSrc = avatarPreview || getImageUrl(user.avatar, 'user');
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

                    <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-6 sm:mb-10">
                        {isRtl ? 'پروفائل' : 'Profile'}
                    </h1>

                    {/* Status message */}
                    {message && (
                        <div className={`mb-6 sm:mb-8 flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-3 rounded-xl text-xs sm:text-sm ${message.type === 'success' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'}`}>
                            {message.type === 'success' ? <Check className="h-4 w-4 shrink-0" /> : <AlertCircle className="h-4 w-4 shrink-0" />}
                            {message.text}
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6 sm:space-y-10">
                        {/* Avatar section - Unique Color Avatar */}
                        <div className="flex flex-col sm:flex-row items-center sm:items-center gap-4 sm:gap-6">
                            <div className="relative group shrink-0">
                                <Avatar className="h-20 w-20 sm:h-24 sm:w-24 border-2 border-gray-100 transition-opacity">
                                    <AvatarFallback
                                        className="text-2xl sm:text-3xl font-bold text-white relative overflow-hidden"
                                        style={{ backgroundColor: getAvatarColor(user.username || user.name) }}
                                    >
                                        {user.username?.charAt(0)?.toUpperCase() || 'U'}
                                    </AvatarFallback>
                                </Avatar>
                            </div>

                            <div className="text-center sm:text-left space-y-1">
                                <h3 className="font-semibold text-gray-900 text-lg sm:text-xl">
                                    {user.username}
                                </h3>
                                <p className="text-sm text-gray-500">
                                    {isRtl ? 'توهان جو عوامي نالو' : 'Your Public Anonymous ID'}
                                </p>
                            </div>
                        </div>

                        {/* Form Fields */}
                        <div className="space-y-6">
                            <div className="grid grid-cols-1 gap-6">
                                {/* Real Name (Encrypted) */}
                                <div className="space-y-1.5 sm:space-y-2">
                                    <label className="text-xs sm:text-sm font-medium text-gray-700 flex items-center gap-2">
                                        <UserIcon className="h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" />
                                        {isRtl ? 'اصل نالو (صرف توهان کي نظر ايندو)' : 'Real Name (Encrypted & Private)'}
                                    </label>
                                    <Input
                                        name="name"
                                        value={formData.name}
                                        onChange={handleChange}
                                        className="rounded-xl border-gray-200 focus:border-black focus:ring-black/5 h-11 sm:h-12 text-sm sm:text-base"
                                        placeholder={isRtl ? 'توهان جو نالو' : 'Your real name'}
                                    />
                                    <p className="text-xs text-gray-400 px-1">
                                        {isRtl ? 'هي نالو انڪرپٽ ٿيل آهي. اسان جي ٽيم به نٿي ڏسي سگهي.' : 'This is encrypted in our database. Not visible to the public.'}
                                    </p>
                                </div>

                                {/* Email */}
                                <div className="space-y-1.5 sm:space-y-2">
                                    <label className="text-xs sm:text-sm font-medium text-gray-700 flex items-center gap-2">
                                        <Mail className="h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" />
                                        {isRtl ? 'اي ميل' : 'Email Address'}
                                    </label>
                                    <Input
                                        name="email"
                                        type="email"
                                        value={formData.email}
                                        onChange={handleChange}
                                        className="rounded-xl border-gray-200 focus:border-black focus:ring-black/5 h-11 sm:h-12 text-sm sm:text-base bg-gray-50/50"
                                        readOnly
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Save — full width on mobile */}
                        <div className="pt-2 sm:pt-4">
                            <Button
                                type="submit"
                                disabled={saving}
                                className="w-full sm:w-auto bg-black text-white hover:bg-gray-800 rounded-full px-8 h-11 sm:h-12 text-sm font-medium transition-all active:scale-[0.98]"
                            >
                                {saving ? (
                                    <span className="flex items-center justify-center gap-2">
                                        <span className="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                        {isRtl ? 'محفوظ ٿي رهيو آهي...' : 'Saving...'}
                                    </span>
                                ) : (
                                    <span className="flex items-center justify-center gap-2">
                                        <Save className="h-4 w-4" />
                                        {isRtl ? 'محفوظ ڪريو' : 'Save Changes'}
                                    </span>
                                )}
                            </Button>
                        </div>
                    </form>

                    {/* Privacy & Security Section */}
                    <div className="mt-12 pt-8 border-t border-gray-100">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            {isRtl ? 'پرائيويسي ۽ سيڪيورٽي' : 'Privacy & Security'}
                        </h2>

                        <div className="bg-blue-50 border border-blue-100 rounded-2xl p-6">
                            <div className="flex items-start gap-4">
                                <div className="p-3 bg-blue-100 rounded-full shrink-0">
                                    <Lock className="h-6 w-6 text-blue-600" />
                                </div>
                                <div>
                                    <h3 className="font-medium text-blue-900 mb-1">
                                        {isRtl ? 'توهان جو ڊيٽا محفوظ آهي' : 'Your data is end-to-end encrypted'}
                                    </h3>
                                    <p className="text-sm text-blue-700 mb-4 leading-relaxed">
                                        {isRtl
                                            ? 'اسان توهان جي فون نمبر ۽ ٻي معلومات کي انڪرپٽ ڪري رکون ٿا. اسان جي ٽيم به اهو نٿي ڏسي سگهي.'
                                            : 'We use industry-standard encryption to protect your personal details. Even our administrators cannot see your actual phone number or WhatsApp.'}
                                    </p>
                                    <Button
                                        onClick={() => setIsPrivacyModalOpen(true)}
                                        variant="outline"
                                        className="bg-white hover:bg-blue-50 border-blue-200 text-blue-700 hover:text-blue-800"
                                    >
                                        {isRtl ? 'ڏسو ته ٽيم ڇا ٿي ڏسي' : 'View As Team'}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Danger Zone */}
                    <div className="mt-12 pt-8 border-t border-gray-100">
                        <h2 className="text-lg font-semibold text-red-600 mb-2">
                            {isRtl ? 'اڪائونٽ ختم ڪريو' : 'Danger Zone'}
                        </h2>
                        <p className="text-sm text-gray-500 mb-4">
                            {isRtl
                                ? 'هڪ دفعو توهان پنهنجو اڪائونٽ ختم ڪيو، واپس نٿو اچي سگهجي. مهرباني ڪري پڪ ڪريو.'
                                : 'Once you delete your account, there is no going back. Please be certain.'}
                        </p>
                        <Button
                            variant="destructive"
                            onClick={() => setIsDeleteModalOpen(true)}
                            className="bg-red-50 text-red-600 hover:bg-red-100 border-red-200"
                        >
                            {isRtl ? 'اڪائونٽ ختم ڪريو' : 'Delete Account'}
                        </Button>
                    </div>

                    <DeleteAccountDialog
                        isOpen={isDeleteModalOpen}
                        onClose={() => setIsDeleteModalOpen(false)}
                        isRtl={isRtl}
                        isSocialUser={!!user.google_id}
                        onConfirm={handleDeleteAccount}
                    />

                    <PrivacyModal
                        isOpen={isPrivacyModalOpen}
                        onClose={() => setIsPrivacyModalOpen(false)}
                        isRtl={isRtl}
                    />
                </div>
            </div>
        </div>
    );
};

// Extracted Dialog Component to keep main component clean
const DeleteAccountDialog = ({ isOpen, onClose, isRtl, isSocialUser, onConfirm }) => {
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);
        try {
            await onConfirm(password);
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to delete account');
            setLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div className={`bg-white rounded-2xl shadow-xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-200 ${isRtl ? 'text-right font-arabic' : 'text-left font-sans'}`}>
                <h3 className="text-lg font-bold text-gray-900 mb-2">
                    {isRtl ? 'ڇا توهان يڪينن اڪائونٽ ختم ڪرڻ چاهيو ٿا؟' : 'Delete Account?'}
                </h3>
                <p className="text-sm text-gray-500 mb-6">
                    {isRtl
                        ? 'هي عمل واپس نٿو ٿي سگهي. توهان جو سمورو ڊيٽا هميشه لاءِ ختم ٿي ويندو.'
                        : 'This action cannot be undone. This will permanently delete your account and remove your data from our servers.'}
                </p>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {!isSocialUser && (
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-gray-700">
                                {isRtl ? 'تصديق لاءِ پاسورڊ لکو' : 'Enter password to confirm'}
                            </label>
                            <Input
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                required
                                className="w-full"
                                placeholder="********"
                            />
                        </div>
                    )}

                    {error && (
                        <p className="text-sm text-red-600 bg-red-50 p-2 rounded-lg">{error}</p>
                    )}

                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                            disabled={loading}
                        >
                            {isRtl ? 'رپوس ڪريو' : 'Cancel'}
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={loading}
                            className="bg-red-600 hover:bg-red-700 text-white"
                        >
                            {loading ? (isRtl ? 'ختم ٿي رهيو آهي...' : 'Deleting...') : (isRtl ? 'اڪائونٽ ختم ڪريو' : 'Delete Account')}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
};

const PrivacyModal = ({ isOpen, onClose, isRtl }) => {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);

    React.useEffect(() => {
        if (isOpen) {
            setLoading(true);
            api.get('/api/auth/privacy/view-as-team')
                .then(res => setData(res.data))
                .catch(err => console.error(err))
                .finally(() => setLoading(false));
        }
    }, [isOpen]);

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div className={`bg-white rounded-2xl shadow-xl max-w-lg w-full p-0 overflow-hidden animate-in fade-in zoom-in-95 duration-200 ${isRtl ? 'text-right font-arabic' : 'text-left font-sans'}`}>
                <div className="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 className="font-bold text-gray-900 flex items-center gap-2">
                        <Lock className="h-4 w-4 text-green-600" />
                        {isRtl ? 'ٽيم ڇا ٿي ڏسيس' : 'What Our Team Sees'}
                    </h3>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600">×</button>
                </div>

                <div className="p-6">
                    {loading ? (
                        <div className="flex justify-center py-8">
                            <span className="h-6 w-6 border-2 border-gray-200 border-t-black rounded-full animate-spin"></span>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <div className="bg-green-50 text-green-800 text-sm p-4 rounded-xl mb-6">
                                {data?.message}
                            </div>

                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div className="text-gray-500">{isRtl ? 'نالو' : 'Name'}</div>
                                <div className="font-medium">{data?.as_seen_by_team?.name}</div>

                                <div className="text-gray-500">{isRtl ? 'اي ميل' : 'Email'}</div>
                                <div className="font-medium font-mono">{data?.as_seen_by_team?.email}</div>

                                <div className="text-gray-500">{isRtl ? 'فون نمبر' : 'Phone'}</div>
                                <div className="font-medium font-mono bg-gray-100 inline-block px-2 rounded text-gray-600">
                                    {data?.as_seen_by_team?.phone || 'Not Set'}
                                </div>

                                <div className="text-gray-500">{isRtl ? 'واٽس ايپ' : 'WhatsApp'}</div>
                                <div className="font-medium font-mono bg-gray-100 inline-block px-2 rounded text-gray-600">
                                    {data?.as_seen_by_team?.whatsapp || 'Not Set'}
                                </div>
                            </div>

                            <div className="mt-6 pt-4 border-t border-gray-100 text-xs text-gray-400 text-center">
                                Encryption Status: <span className="text-green-600 font-medium">{data?.encryption_status}</span>
                            </div>
                        </div>
                    )}
                </div>

                <div className="bg-gray-50 px-6 py-4 flex justify-end">
                    <Button onClick={onClose} variant="outline" className="bg-white">
                        {isRtl ? 'بند ڪريو' : 'Close'}
                    </Button>
                </div>
            </div>
        </div>
    );
};

export default Profile;
