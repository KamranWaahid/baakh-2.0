import React from 'react';
import { User } from 'lucide-react';

const ShareCard = ({
    title = "باک: سنڌي شاعريءَ جو آرڪائيو",
    poetryTitle,
    categoryName,
    poetName,
    poetAvatar,
    date,
    lang = 'sd'
}) => {
    const isRtl = lang === 'sd';

    return (
        <div
            className={`w-full aspect-[1.91/1] bg-[#FFFAEC] p-8 md:p-12 flex flex-col justify-between shadow-lg rounded-xl overflow-hidden ${isRtl ? 'font-arabic' : ''}`}
            dir={isRtl ? 'rtl' : 'ltr'}
        >
            {/* Header / Branding */}
            <div className={`text-xl md:text-2xl text-gray-800 ${isRtl ? 'text-right' : 'text-left'} font-medium`}>
                {title}
            </div>

            {/* Content / Poetry Title */}
            <div className={`flex-1 flex items-center ${isRtl ? 'justify-end' : 'justify-start'}`}>
                <h2 className={`text-4xl md:text-5xl lg:text-6xl font-black text-gray-900 leading-tight ${isRtl ? 'text-right' : 'text-left'} max-w-[90%]`}>
                    {poetryTitle}
                </h2>
            </div>

            {/* Footer / Author Info */}
            <div className={`flex items-center gap-4 ${isRtl ? 'flex-row' : 'flex-row-reverse'} self-end`}>
                <div className={`flex flex-col ${isRtl ? 'items-end' : 'items-start'}`}>
                    <div className="text-xl md:text-2xl font-bold text-gray-900 leading-tight">
                        {poetName} {categoryName && <span className="font-normal text-gray-600"> جو {categoryName}</span>}
                    </div>
                    <div className="text-lg text-gray-500 mt-1">
                        {date}
                    </div>
                </div>

                {/* Avatar */}
                <div className="relative">
                    {poetAvatar ? (
                        <img
                            src={poetAvatar.startsWith('http') ? poetAvatar : `/${poetAvatar}`}
                            alt={poetName}
                            className="w-16 h-16 md:w-20 md:h-20 rounded-full object-cover border-4 border-white shadow-sm"
                        />
                    ) : (
                        <div className="w-16 h-16 md:w-20 md:h-20 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 border-4 border-white shadow-sm">
                            <User className="w-8 h-8 md:w-10 md:h-10" />
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ShareCard;
