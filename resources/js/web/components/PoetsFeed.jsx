import React, { useState, useEffect } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { UserPlus, Star } from 'lucide-react';

const PoetsFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [loading, setLoading] = useState(true);

    // Mock data for poets
    const poets = [
        { id: 1, name: 'Sheikh Ayaz', enName: 'Sheikh Ayaz', followers: '12k', bio: 'The most prominent poet of modern Sindhi poetry.', avatar: 'SA' },
        { id: 2, name: 'Shah Abdul Latif Bhittai', enName: 'Shah Latif', followers: '50k', bio: 'A Sufi scholar, mystic, saint, and poet.', avatar: 'SL' },
        { id: 3, name: 'Ustad Bukhari', enName: 'Ustad Bukhari', followers: '8k', bio: 'Known as the poet of the people.', avatar: 'UB' },
        { id: 4, name: 'Sachal Sarmast', enName: 'Sachal Sarmast', followers: '15k', bio: 'Famous for his bilingual poetry in Sindhi and Saraiki.', avatar: 'SS' },
        { id: 5, name: 'Amar Jaleel', enName: 'Amar Jaleel', followers: '9k', bio: 'A writer and journalist whose work challenges convention.', avatar: 'AJ' },
        { id: 6, name: 'Tajal Bewas', enName: 'Tajal Bewas', followers: '5k', bio: 'Renowned for his melodious and romantic poetry.', avatar: 'TB' },
        { id: 7, name: 'Imdad Hussaini', enName: 'Imdad Hussaini', followers: '6k', bio: 'A distinct voice in modern Sindhi literature.', avatar: 'IH' },
    ];

    useEffect(() => {
        const timer = setTimeout(() => {
            setLoading(false);
        }, 1500);
        return () => clearTimeout(timer);
    }, []);

    return (
        <div className="w-full max-w-3xl mx-auto py-8 px-4">
            <h1 className={`text-3xl font-bold mb-8 ${isRtl ? 'font-arabic' : ''}`}>
                {isRtl ? 'شاعر' : 'Poets'}
            </h1>

            <div className="space-y-4">
                {loading ? (
                    Array(5).fill(0).map((_, i) => (
                        <div key={i} className="flex items-center gap-4 p-4 border rounded-lg bg-white shadow-sm border-gray-100">
                            <Skeleton className="h-16 w-16 rounded-full" />
                            <div className="flex-1 space-y-2">
                                <Skeleton className="h-5 w-1/3" />
                                <Skeleton className="h-4 w-2/3" />
                            </div>
                            <Skeleton className="h-9 w-24 rounded-full" />
                        </div>
                    ))
                ) : (
                    poets.map((poet) => (
                        <div key={poet.id} className="flex items-center gap-6 p-6 border-b border-gray-100 bg-white hover:bg-gray-50 transition-colors group">
                            <div className="h-16 w-16 md:h-20 md:w-20 rounded-full bg-gray-100 flex items-center justify-center text-xl md:text-2xl font-bold text-gray-400 shrink-0 border border-gray-100">
                                {poet.avatar}
                            </div>

                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2 mb-1">
                                    <h3 className="text-lg md:text-xl font-bold text-gray-900 truncate">
                                        {isRtl && poet.name ? poet.name : poet.enName}
                                    </h3>
                                    {/* Verified Badge Placeholder if needed */}
                                    {/* <div className="h-4 w-4 bg-blue-500 rounded-full text-[10px] flex items-center justify-center text-white">✓</div> */}
                                </div>

                                <p className="text-gray-500 text-sm md:text-base line-clamp-2 mb-2">
                                    {poet.bio}
                                </p>

                                <div className="flex items-center gap-4 text-xs text-gray-400 font-medium">
                                    <span className="flex items-center gap-1">
                                        <Star className="h-3 w-3 fill-current" /> {poet.followers} {isRtl ? 'پيروي ڪندڙ' : 'Followers'}
                                    </span>
                                </div>
                            </div>

                            <Button
                                variant="outline"
                                className="rounded-full hidden sm:flex items-center gap-2 hover:bg-black hover:text-white transition-colors"
                            >
                                <UserPlus className="h-4 w-4" />
                                <span>{isRtl ? 'فالو ڪريو' : 'Follow'}</span>
                            </Button>
                        </div>
                    ))
                )}
            </div>

            {!loading && (
                <div className="mt-8 text-center">
                    <Button variant="ghost" className="text-gray-400 hover:text-gray-600">
                        {isRtl ? 'وڌيڪ ڏسو' : 'Load more'}
                    </Button>
                </div>
            )}
        </div>
    );
};

export default PoetsFeed;
