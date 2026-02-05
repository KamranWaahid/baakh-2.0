import React, { useState, useEffect } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { BookOpen, Feather, Music, Scroll, Star, Hash } from 'lucide-react';
import { Button } from '@/components/ui/button';

const GenreFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [loading, setLoading] = useState(true);

    // Mock Data: ~23 Genres
    const genres = [
        { id: 'ghazal', label: 'غزل', enLabel: 'Ghazal', desc: 'The poetic form of love and longing.', count: '1.2k' },
        { id: 'waai', label: 'وائي', enLabel: 'Waai', desc: 'A lyrical form specific to Sindhi poetry.', count: '850' },
        { id: 'nazam', label: 'نظم', enLabel: 'Nazam', desc: 'Narrative poetry with a central theme.', count: '2.1k' },
        { id: 'bait', label: 'بيت', enLabel: 'Bait', desc: 'Traditional couplets rich in wisdom.', count: '3.4k' },
        { id: 'kafi', label: 'ڪافي', enLabel: 'Kafi', desc: 'Sufi poetry set to music.', count: '900' },
        { id: 'rubai', label: 'رباعي', enLabel: 'Rubai', desc: 'A four-line poem with specific rhyme.', count: '300' },
        { id: 'hiku', label: 'هائيڪو', enLabel: 'Haiku', desc: 'Japanese form adapted into Sindhi.', count: '150' },
        { id: 'azad-nazam', label: 'آزاد نظم', enLabel: 'Azad Nazam', desc: 'Free verse poetry without strict meter.', count: '1.8k' },
        { id: 'nasar-nazam', label: 'نثري نظم', enLabel: 'Nasar Nazam', desc: 'Prose poetry focusing on imagery.', count: '500' },
        { id: 'geet', label: 'گيت', enLabel: 'Geet', desc: 'Songs expressed in poetic verses.', count: '1.2k' },
        { id: 'doha', label: 'دوها', enLabel: 'Doha', desc: 'Self-contained rhyming couplets.', count: '600' },
        { id: 'soratha', label: 'سورٺا', enLabel: 'Soratha', desc: 'Inverse of Doha, popular in folklore.', count: '400' },
        { id: 'panjkara', label: 'پنجڪڙا', enLabel: 'Panjkara', desc: 'A five-line poetic stanza.', count: '200' },
        { id: 'sannet', label: 'سانيٽ', enLabel: 'Sonnet', desc: '14-line poem with fixed rhyme scheme.', count: '120' },
        { id: 'triolet', label: 'ٽرائيل', enLabel: 'Triolet', desc: 'Eight-line poem with repeating lines.', count: '80' },
        { id: 'mahiya', label: 'ماھيا', enLabel: 'Mahiya', desc: 'Folk poetry of Punjab and Sindh.', count: '350' },
        { id: 'qita', label: 'قطعو', enLabel: 'Qita', desc: 'A fragment or short poem.', count: '450' },
        { id: 'masnavi', label: 'مثنوي', enLabel: 'Masnavi', desc: 'Extensive poems written in rhyming couplets.', count: '100' },
        { id: 'marsiyo', label: 'مرثيو', enLabel: 'Marsiya', desc: 'Elegy, typically mourning a martyr.', count: '250' },
        { id: 'qasido', label: 'قصيدو', enLabel: 'Qasida', desc: 'Ode, often characterized by praise.', count: '180' },
        { id: 'munqabat', label: 'منقبت', enLabel: 'Munqabat', desc: 'Praise for religious figures.', count: '300' },
        { id: 'hamd', label: 'حمد', enLabel: 'Hamd', desc: 'Praise of God.', count: '600' },
        { id: 'naat', label: 'نعت', enLabel: 'Naat', desc: 'Praise of the Prophet (PBUH).', count: '800' },
    ];

    useEffect(() => {
        const timer = setTimeout(() => {
            setLoading(false);
        }, 1500);
        return () => clearTimeout(timer);
    }, []);

    const GenreCard = ({ genre }) => (
        <div className="group relative bg-white border border-gray-100 rounded-xl p-6 hover:border-black/20 hover:shadow-sm transition-all duration-300 cursor-pointer flex flex-col items-center text-center h-full">
            <div className={`h-12 w-12 rounded-full bg-gray-50 group-hover:bg-gray-100 flex items-center justify-center mb-4 transition-colors ${isRtl ? 'ml-0' : 'mr-0'}`}>
                <Hash className="h-5 w-5 text-gray-400 group-hover:text-black transition-colors" />
            </div>

            <h3 className={`text-xl font-bold text-gray-900 mb-1 ${isRtl ? 'font-arabic' : ''}`}>
                {isRtl ? genre.label : genre.enLabel}
            </h3>

            <span className={`text-xs font-medium text-gray-400 uppercase tracking-wider mb-3 ${isRtl ? 'font-sans' : ''}`}>
                {isRtl ? genre.enLabel : genre.label}
            </span>

            <p className="text-sm text-gray-500 line-clamp-2 leading-relaxed mb-4">
                {genre.desc}
            </p>

            <div className="mt-auto pt-4 w-full border-t border-gray-50 flex items-center justify-between text-xs text-gray-400">
                <span className="flex items-center gap-1">
                    <BookOpen className="h-3 w-3" /> {genre.count} {isRtl ? 'ڪلام' : 'Works'}
                </span>
                <span className="group-hover:translate-x-1 group-hover:text-black transition-all duration-300">
                    {isRtl ? '←' : '→'}
                </span>
            </div>
        </div>
    );

    return (
        <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-8">
            <div className="mb-10">
                <h1 className={`text-3xl font-bold text-gray-900 mb-3 ${isRtl ? 'font-arabic' : ''}`}>
                    {isRtl ? 'ادبي صنفون' : 'Poetic Genres'}
                </h1>
                <p className="text-gray-500 text-lg max-w-2xl">
                    {isRtl
                        ? 'سنڌي شاعري جي مختلف صنفن جو مجموعو، قديم کان جديد تائين.'
                        : 'Explore the diverse forms of Sindhi poetry, ranging from classical traditions to modern expressions.'}
                </p>
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
                    {genres.map((genre) => (
                        <GenreCard key={genre.id} genre={genre} />
                    ))}
                </div>
            )}

            {!loading && (
                <div className="mt-12 text-center">
                    <Button variant="outline" className="rounded-full px-8 border-gray-200">
                        {isRtl ? 'وڌيڪ ڏيکاريو' : 'Load More'}
                    </Button>
                </div>
            )}
        </div>
    );
};

export default GenreFeed;
