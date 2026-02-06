import React from 'react';
import { Link, useParams } from 'react-router-dom';
import Logo from '../components/Logo';

const Help = () => {
    const { lang } = useParams();
    const isRtl = lang === 'sd';

    return (
        <div className={`min-h-screen bg-white text-black ${isRtl ? 'text-right font-arabic' : 'text-left font-sans'}`}>
            {/* Minimal Header */}
            <header className="px-6 md:px-12 lg:px-24 py-8 flex items-center border-b border-gray-100">
                <Link to={`/${lang}`} className="hover:opacity-80 transition-opacity">
                    <Logo className="h-10 w-10 text-black" />
                </Link>
            </header>

            <div className="py-20 px-6 md:px-12 lg:px-24">
                <div className="max-w-3xl mx-auto space-y-12">
                    <h1 className="text-4xl md:text-5xl font-bold tracking-tight mb-8">
                        {isRtl ? 'مدد ۽ سهڪار' : 'Help & Support'}
                    </h1>
                    <p className="text-xl text-gray-600">
                        {isRtl ? 'مدد جو مرڪز جلد اچي رهيو آهي.' : 'Help center coming soon.'}
                    </p>
                </div>
            </div>
        </div>
    );
};

export default Help;
