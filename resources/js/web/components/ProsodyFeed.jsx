import React, { useState } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { Scale, Ruler, Music, Info, Scissors, Columns2, Wrench, Scroll, Footprints, Infinity, Anchor, Sunrise, Sunset } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import TaqtiTool from './TaqtiTool';

// Icon Map for dynamic icons from database
const IconMap = {
    Scale,
    Ruler,
    Music,
    Info,
    Scissors,
    Columns: Columns2,
    Wrench,
    Scroll,
    Footprints,
    Infinity,
    Anchor,
    Sunrise,
    Sunset
};

const ProsodyFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [showTool, setShowTool] = useState(false);

    const { data: items, isLoading } = useQuery({
        queryKey: ['prosody', lang],
        queryFn: async () => {
            const response = await axios.get(`/v1/prosody?lang=${lang}`);
            return response.data;
        }
    });

    const ConceptCard = ({ item }) => {
        const IconComponent = IconMap[item.icon] || Info;
        const isTaqti = item.title?.toLowerCase().includes('taqti') || item.title?.includes('تقطيع');

        return (
            <div
                onClick={() => { if (isTaqti) setShowTool(true); }}
                className={`group relative bg-white border border-gray-100 rounded-xl p-6 hover:border-black/20 hover:shadow-sm transition-all duration-300 cursor-pointer flex flex-col items-center text-center h-full ${isTaqti ? 'ring-2 ring-black ring-offset-2' : ''}`}
            >
                <div className={`h-12 w-12 rounded-full bg-gray-50 group-hover:bg-gray-100 flex items-center justify-center mb-4 transition-colors`}>
                    <IconComponent className="h-5 w-5 text-gray-400 group-hover:text-black transition-colors" />
                </div>

                <h3 className={`text-xl font-bold text-gray-900 mb-1 ${isRtl ? 'font-arabic' : ''}`}>
                    {item.title || '...'}
                </h3>

                <span className={`text-xs font-medium text-gray-400 uppercase tracking-wider mb-3 ${isRtl ? 'font-sans' : ''}`}>
                    {item.subtitle || '...'}
                </span>

                <p className="text-sm text-gray-500 line-clamp-2 leading-relaxed mb-4">
                    {item.description}
                </p>

                {item.technical_detail && (
                    <div className="text-[10px] text-gray-400 mt-1 mb-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        {item.technical_detail}
                    </div>
                )}

                <div className="mt-auto pt-4 w-full border-t border-gray-50 flex items-center justify-between text-xs text-gray-400">
                    <span className="flex items-center gap-1 font-medium text-black group-hover:underline underline-offset-4">
                        {isTaqti ? (isRtl ? 'ٽول کوليو' : 'Open Tool') : (isRtl ? 'تفصيل' : 'Learn Details')}
                    </span>
                    <span className="group-hover:translate-x-1 transition-transform duration-300 text-black">
                        {isRtl ? '←' : '→'}
                    </span>
                </div>
            </div>
        );
    };

    if (showTool) {
        return (
            <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-8 animate-in fade-in duration-500">
                <Button variant="ghost" className="mb-4" onClick={() => setShowTool(false)}>
                    {isRtl ? '← واپس' : '← Back to Concepts'}
                </Button>
                <TaqtiTool lang={lang} />
            </div>
        );
    }

    return (
        <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-8">
            <div className="mb-10">
                <h1 className={`text-3xl font-bold text-gray-900 mb-3 ${isRtl ? 'font-arabic' : ''}`}>
                    {isRtl ? 'علم عروض ۽ ڇند وديا' : 'Sindhi Prosody (Arooz & Chhand)'}
                </h1>
                <p className="text-gray-500 text-lg max-w-2xl">
                    {isRtl
                        ? 'شاعريءَ جي فني بنيادن، ماترائن، بحرن ۽ تال کي سمجهو.'
                        : 'Understand the technical foundations of Sindhi poetry, from indigenous Chhand Widya to classical Ilm-ul-Arooz.'}
                </p>

                {/* Promo Banner for Tool */}
                <div className="mt-8 bg-black text-white p-6 rounded-2xl flex items-center justify-between shadow-lg">
                    <div>
                        <h2 className="text-xl font-bold mb-1">{isRtl ? 'شاعري جي پرک' : "Poet's Workbench Available"}</h2>
                        <p className="text-gray-300 text-sm">{isRtl ? 'اسان جي تقطيع ٽول ذريعي پنهنجي شاعري جو وزن چيڪ ڪريو.' : 'Check the metrics of your poetry with our new Taqti tool.'}</p>
                    </div>
                    <Button onClick={() => setShowTool(true)} className="bg-white text-black hover:bg-gray-200 font-bold">
                        {isRtl ? 'تقطيع ڪريو' : 'Try Scansion'}
                    </Button>
                </div>
            </div>

            {isLoading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    {Array(8).fill(0).map((_, i) => (
                        <div key={i} className="border border-gray-100 rounded-xl p-6 flex flex-col items-center">
                            <Skeleton className="h-12 w-12 rounded-full mb-4" />
                            <Skeleton className="h-6 w-24 mb-2" />
                            <Skeleton className="h-4 w-16 mb-4" />
                            <Skeleton className="h-10 w-full" />
                        </div>
                    ))}
                </div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    {Array.isArray(items) && items.map((item) => (
                        <ConceptCard key={item.id} item={item} />
                    ))}
                </div>
            )}
        </div>
    );
};

export default ProsodyFeed;
