import React, { useState, useEffect } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { UserPlus, Star } from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

const PoetsFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [loading, setLoading] = useState(true);

    // Mock data for poets with categories
    const poets = [
        { id: 1, name: 'Sheikh Ayaz', enName: 'Sheikh Ayaz', followers: '12k', bio: 'The most prominent poet of modern Sindhi poetry.', avatar: 'SA', category: 'revolutionary' },
        { id: 2, name: 'Shah Abdul Latif Bhittai', enName: 'Shah Latif', followers: '50k', bio: 'A Sufi scholar, mystic, saint, and poet.', avatar: 'SL', category: 'classical' },
        { id: 3, name: 'Ustad Bukhari', enName: 'Ustad Bukhari', followers: '8k', bio: 'Known as the poet of the people.', avatar: 'UB', category: 'revolutionary' },
        { id: 4, name: 'Sachal Sarmast', enName: 'Sachal Sarmast', followers: '15k', bio: 'Famous for his bilingual poetry in Sindhi and Saraiki.', avatar: 'SS', category: 'classical' },
        { id: 5, name: 'Amar Jaleel', enName: 'Amar Jaleel', followers: '9k', bio: 'A writer and journalist whose work challenges convention.', avatar: 'AJ', category: 'revolutionary' },
        { id: 6, name: 'Tajal Bewas', enName: 'Tajal Bewas', followers: '5k', bio: 'Renowned for his melodious and romantic poetry.', avatar: 'TB', category: 'young' }, // Placeholder category
        { id: 7, name: 'Imdad Hussaini', enName: 'Imdad Hussaini', followers: '6k', bio: 'A distinct voice in modern Sindhi literature.', avatar: 'IH', category: 'young' }, // Placeholder category
    ];

    const categories = [
        { id: 'all', label: isRtl ? 'سڀ' : 'All Poets' },
        { id: 'young', label: isRtl ? 'نوجوان' : 'Young' },
        { id: 'classical', label: isRtl ? 'ڪلاسيڪل' : 'Classical' },
        { id: 'revolutionary', label: isRtl ? 'انقلابي' : 'Revolutionary' },
    ];

    useEffect(() => {
        const timer = setTimeout(() => {
            setLoading(false);
        }, 1500);
        return () => clearTimeout(timer);
    }, []);

    const PoetCard = ({ poet }) => (
        <div className="flex items-center gap-6 p-6 border-b border-gray-100 bg-white transition-colors group">
            <div className="h-16 w-16 md:h-20 md:w-20 rounded-full bg-gray-100 flex items-center justify-center text-xl md:text-2xl font-bold text-gray-400 shrink-0 border border-gray-100">
                {poet.avatar}
            </div>

            <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                    <h3 className="text-lg md:text-xl font-bold text-gray-900 truncate">
                        {isRtl && poet.name ? poet.name : poet.enName}
                    </h3>
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
    );

    return (
        <div className="w-full max-w-[1080px] mx-auto py-8 px-4">
            <Tabs defaultValue="all" className="w-full">
                <div className="sticky top-[65px] bg-white/95 backdrop-blur-sm pt-2 pb-0 z-40 border-b border-gray-100 mb-8">
                    <TabsList className="bg-transparent p-0 h-auto justify-start border-b-0 w-full rounded-none overflow-x-auto flex-nowrap scrollbar-hide">
                        {categories.map(cat => (
                            <TabsTrigger
                                key={cat.id}
                                value={cat.id}
                                className="rounded-none border-b-2 border-transparent data-[state=active]:border-black data-[state=active]:shadow-none data-[state=active]:text-black text-gray-500 pb-3 px-4 min-w-fit"
                            >
                                {cat.label}
                            </TabsTrigger>
                        ))}
                    </TabsList>
                </div>

                {categories.map(cat => (
                    <TabsContent key={cat.id} value={cat.id} className="space-y-4 mt-0">
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
                            cat.id === 'all'
                                ? poets.map(poet => <PoetCard key={poet.id} poet={poet} />)
                                : poets.filter(p => p.category === cat.id).length > 0
                                    ? poets.filter(p => p.category === cat.id).map(poet => <PoetCard key={poet.id} poet={poet} />)
                                    : <div className="py-20 text-center text-gray-500">No poets found in this category</div>
                        )}

                        {!loading && (
                            <div className="mt-8 text-center">
                                <Button variant="ghost" className="text-gray-400 hover:text-gray-600">
                                    {isRtl ? 'وڌيڪ ڏسو' : 'Load more'}
                                </Button>
                            </div>
                        )}
                    </TabsContent>
                ))}
            </Tabs>
        </div>
    );
};

export default PoetsFeed;
