import React from 'react';
import { Link, useParams } from 'react-router-dom';
import { CheckCircle } from 'lucide-react';
import Logo from '../components/Logo';

const Status = () => {
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
                        {isRtl ? 'سسٽم جي حالت' : 'System Status'}
                    </h1>

                    <div className="bg-gray-50 border border-gray-100 rounded-2xl p-8 flex items-center gap-4">
                        <CheckCircle className="h-8 w-8 text-green-500 flex-shrink-0" />
                        <div>
                            <h3 className="text-xl font-bold text-gray-900">
                                {isRtl ? 'سڀ سسٽم فعال آهن' : 'All systems operational'}
                            </h3>
                            <p className="text-gray-500 mt-1">
                                {isRtl ? 'پليٽ فارم صحيح ڪم ڪري رهيو آهي.' : 'The platform is running smoothly.'}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Status;
