import React, { useState, useEffect } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { Scale, Ruler, Music, Info } from 'lucide-react';
import { Button } from '@/components/ui/button';
import TaqtiTool from './TaqtiTool';

const ProsodyFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [loading, setLoading] = useState(true);
    const [showTool, setShowTool] = useState(false);

    // Mock Data: Prosody Concepts (Ilm-ul-Arooz)
    const items = [
        { id: 'beher', label: 'بحر', enLabel: 'Beher (Meter)', desc: 'The rhythmic structure of a poetic line.', icon: Ruler },
        { id: 'rukan', label: 'رڪن', enLabel: 'Rukan (Foot)', desc: 'The basic unit of rhythm in a poem.', icon: Scale },
        { id: 'wazn', label: 'وزن', enLabel: 'Wazn (Weight)', desc: 'The pattern of long and short syllables.', icon: Music },
        { id: 'taqti', label: 'تقطيع', enLabel: 'Taqti (Scansion)', desc: 'Breaking verse into feet to determine meter.', icon: Info },
        { id: 'kafiyo', label: 'قافيو', enLabel: 'Kafiyo (Rhyme)', desc: 'Repeating sound at the end of verses.', icon: Music },
        { id: 'radif', label: 'رديف', enLabel: 'Radif (Refrain)', desc: 'Word or phrase repeated after the rhyme.', icon: Music },
        { id: 'matla', label: 'مطلع', enLabel: 'Matla', desc: 'The opening couplet of a Ghazal.', icon: Info },
        { id: 'maqta', label: 'مقطع', enLabel: 'Maqta', desc: 'The closing couplet, often with pen name.', icon: Info },
        { id: 'hajaz', label: 'بحر هزج', enLabel: 'Beher-e-Hajaz', desc: 'A popular meter: Ma-fa-ee-lun Ma-fa-ee-lun...', icon: Ruler },
        { id: 'ramal', label: 'بحر رمل', enLabel: 'Beher-e-Ramal', desc: 'Meter pattern: Faa-i-la-tun Faa-i-la-tun...', icon: Ruler },
        { id: 'rajaz', label: 'بحر رجز', enLabel: 'Beher-e-Rajaz', desc: 'Meter pattern: Mus-taf-i-lun Mus-taf-i-lun...', icon: Ruler },
        { id: 'mutaqarib', label: 'بحر متقارب', enLabel: 'Beher-e-Mutaqarib', desc: 'Meter pattern: Fa-oo-lun Fa-oo-lun...', icon: Ruler },
    ];

    useEffect(() => {
        const timer = setTimeout(() => {
            setLoading(false);
        }, 1500);
        return () => clearTimeout(timer);
    }, []);

    const ConceptCard = ({ item }) => (
        <div
            onClick={() => { if (item.id === 'taqti') setShowTool(true); }}
            className={`group relative bg-white border border-gray-100 rounded-xl p-6 hover:border-black/20 hover:shadow-sm transition-all duration-300 cursor-pointer flex flex-col items-center text-center h-full ${item.id === 'taqti' ? 'ring-2 ring-black ring-offset-2' : ''}`}
        >
            <div className={`h-12 w-12 rounded-full bg-gray-50 group-hover:bg-gray-100 flex items-center justify-center mb-4 transition-colors`}>
                <item.icon className="h-5 w-5 text-gray-400 group-hover:text-black transition-colors" />
            </div>

            <h3 className={`text-xl font-bold text-gray-900 mb-1 ${isRtl ? 'font-arabic' : ''}`}>
                {isRtl ? item.label : item.enLabel}
            </h3>

            <span className={`text-xs font-medium text-gray-400 uppercase tracking-wider mb-3 ${isRtl ? 'font-sans' : ''}`}>
                {isRtl ? item.enLabel : item.label}
            </span>

            <p className="text-sm text-gray-500 line-clamp-2 leading-relaxed mb-4">
                {item.desc}
            </p>

            <div className="mt-auto pt-4 w-full border-t border-gray-50 flex items-center justify-between text-xs text-gray-400">
                <span className="flex items-center gap-1 font-medium text-black group-hover:underline underline-offset-4">
                    {item.id === 'taqti' ? (isRtl ? 'ٽول کوليو' : 'Open Tool') : (isRtl ? 'تفصيل' : 'Learn Details')}
                </span>
                <span className="group-hover:translate-x-1 transition-transform duration-300 text-black">
                    {isRtl ? '←' : '→'}
                </span>
            </div>
        </div>
    );

    if (showTool) {
        return (
            <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-8 animate-in fade-in duration-500">
                <Button variant="ghost" className="mb-4" onClick={() => setShowTool(false)}>
                    ← Back to Concepts
                </Button>
                <TaqtiTool lang={lang} />
            </div>
        );
    }

    return (
        <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-8">
            <div className="mb-10">
                <h1 className={`text-3xl font-bold text-gray-900 mb-3 ${isRtl ? 'font-arabic' : ''}`}>
                    {isRtl ? 'علم عروض' : 'Prosody (Ilm-ul-Arooz)'}
                </h1>
                <p className="text-gray-500 text-lg max-w-2xl">
                    {isRtl
                        ? 'شاعري جي وزن ۽ بحرن جو علم.'
                        : 'Understand the technical foundations of poetry, from meters (Beher) to rhymes (Kafiyo) and feet (Rukan).'}
                </p>

                {/* Promo Banner for Tool */}
                <div className="mt-8 bg-black text-white p-6 rounded-2xl flex items-center justify-between shadow-lg">
                    <div>
                        <h2 className="text-xl font-bold mb-1">Poet's Workbench Available</h2>
                        <p className="text-gray-300 text-sm">Check the metrics of your poetry with our new Taqti tool.</p>
                    </div>
                    <Button onClick={() => setShowTool(true)} className="bg-white text-black hover:bg-gray-200 font-bold">
                        Try Scansion
                    </Button>
                </div>
            </div>

            {loading ? (
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
                    {items.map((item) => (
                        <ConceptCard key={item.id} item={item} />
                    ))}
                </div>
            )}
        </div>
    );
};

export default ProsodyFeed;
