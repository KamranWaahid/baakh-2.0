import React, { useState, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '../../admin/api/axios';
import { Camera, Save, ArrowLeft, ArrowRight, User as UserIcon, Mail, Phone, MessageCircle, Check, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import Logo from '../components/Logo';
import { getImageUrl } from '../utils/url';

const Profile = () => {
    const { lang } = useParams();
    const isRtl = lang === 'sd';
    const { user, setUser, checkAuth } = useAuth();

    const [formData, setFormData] = useState({
        name: user?.name || '',
        name_sd: user?.name_sd || '',
        email: user?.email || '',
        phone: user?.phone || '',
        whatsapp: user?.whatsapp || '',
    });
    const [avatarFile, setAvatarFile] = useState(null);
    const [avatarPreview, setAvatarPreview] = useState(null);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState(null);
    const fileInputRef = useRef(null);

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
            if (formData.name_sd) data.append('name_sd', formData.name_sd);
            if (formData.phone) data.append('phone', formData.phone);
            if (formData.whatsapp) data.append('whatsapp', formData.whatsapp);
            if (avatarFile) data.append('avatar', avatarFile);

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
                        {/* Avatar section — stacks vertically on small mobile */}
                        <div className="flex flex-col sm:flex-row items-center sm:items-center gap-4 sm:gap-6">
                            <div className="relative group cursor-pointer shrink-0" onClick={() => fileInputRef.current?.click()}>
                                <Avatar className="h-20 w-20 sm:h-24 sm:w-24 border-2 border-gray-100 transition-opacity group-hover:opacity-75">
                                    <AvatarImage src={avatarSrc} alt={user.name} />
                                    <AvatarFallback className="text-xl sm:text-2xl bg-gray-100 text-gray-500">
                                        {user.name?.charAt(0)?.toUpperCase()}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <div className="bg-black/50 rounded-full p-2">
                                        <Camera className="h-4 w-4 sm:h-5 sm:w-5 text-white" />
                                    </div>
                                </div>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/*"
                                    className="hidden"
                                    onChange={handleAvatarChange}
                                />
                            </div>
                            <div className="text-center sm:text-left">
                                <p className="font-semibold text-base sm:text-lg text-gray-900">{user.name}</p>
                                <p className="text-xs sm:text-sm text-gray-500">{user.email}</p>
                                {user.roles && user.roles.length > 0 && (
                                    <span className="inline-block mt-2 text-xs font-medium bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
                                        {user.roles[0]}
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Fields */}
                        <div className="space-y-4 sm:space-y-6">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                {/* Name */}
                                <div className="space-y-1.5 sm:space-y-2">
                                    <label className="text-xs sm:text-sm font-medium text-gray-700 flex items-center gap-2">
                                        <UserIcon className="h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" />
                                        {isRtl ? 'نالو (انگريزي)' : 'Name'}
                                    </label>
                                    <Input
                                        name="name"
                                        value={formData.name}
                                        onChange={handleChange}
                                        required
                                        className="rounded-xl border-gray-200 focus:border-black focus:ring-black/5 h-11 sm:h-12 text-sm sm:text-base"
                                    />
                                </div>

                                {/* Name Sindhi */}
                                <div className="space-y-1.5 sm:space-y-2">
                                    <label className="text-xs sm:text-sm font-medium text-gray-700 flex items-center gap-2">
                                        <UserIcon className="h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" />
                                        {isRtl ? 'نالو (سنڌي)' : 'Name (Sindhi)'}
                                    </label>
                                    <Input
                                        name="name_sd"
                                        value={formData.name_sd}
                                        onChange={handleChange}
                                        dir="rtl"
                                        className="rounded-xl border-gray-200 focus:border-black focus:ring-black/5 h-11 sm:h-12 text-sm sm:text-base font-arabic"
                                    />
                                </div>
                            </div>

                            {/* Email */}
                            <div className="space-y-1.5 sm:space-y-2">
                                <label className="text-xs sm:text-sm font-medium text-gray-700 flex items-center gap-2">
                                    <Mail className="h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" />
                                    {isRtl ? 'اي ميل' : 'Email'}
                                </label>
                                <Input
                                    name="email"
                                    type="email"
                                    value={formData.email}
                                    onChange={handleChange}
                                    required
                                    className="rounded-xl border-gray-200 focus:border-black focus:ring-black/5 h-11 sm:h-12 text-sm sm:text-base"
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                {/* Phone */}
                                <div className="space-y-1.5 sm:space-y-2">
                                    <label className="text-xs sm:text-sm font-medium text-gray-700 flex items-center gap-2">
                                        <Phone className="h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" />
                                        {isRtl ? 'فون نمبر' : 'Phone'}
                                    </label>
                                    <Input
                                        name="phone"
                                        value={formData.phone}
                                        onChange={handleChange}
                                        className="rounded-xl border-gray-200 focus:border-black focus:ring-black/5 h-11 sm:h-12 text-sm sm:text-base"
                                    />
                                </div>

                                {/* WhatsApp */}
                                <div className="space-y-1.5 sm:space-y-2">
                                    <label className="text-xs sm:text-sm font-medium text-gray-700 flex items-center gap-2">
                                        <MessageCircle className="h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" />
                                        {isRtl ? 'واٽس ايپ' : 'WhatsApp'}
                                    </label>
                                    <Input
                                        name="whatsapp"
                                        value={formData.whatsapp}
                                        onChange={handleChange}
                                        className="rounded-xl border-gray-200 focus:border-black focus:ring-black/5 h-11 sm:h-12 text-sm sm:text-base"
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
                </div>
            </div>
        </div>
    );
};

export default Profile;
