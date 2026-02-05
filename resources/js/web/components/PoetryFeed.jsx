import React, { useState, useEffect } from 'react';
import PostCard from './PostCard';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import PostCardSkeleton from './skeletons/PostCardSkeleton';

const PoetryFeed = ({ lang }) => {
    const isRtl = lang === 'sd';

    const posts = [
        {
            title: isRtl ? 'وائي: درد جو داستان' : 'Waai: A Tale of Pain',
            excerpt: isRtl ? 'وائي سنڌي شاعري جي قديم صنف آهي...' : 'The waai is one of the oldest forms of Sindhi poetry, echoing the cries of the soul.',
            author: 'Sheikh Ayaz',
            date: '2d ago',
            readTime: '3 min read',
            category: 'Waai'
        },
        {
            title: isRtl ? 'غزل: محبت جو پيغام' : 'Ghazal: The Message of Love',
            excerpt: isRtl ? 'غزل جي دنيا ۾ هڪ نئون تجربو...' : 'Exploring the nuances of modern Sindhi Ghazal through the lens of romance and revolution.',
            author: 'Ustad Bukhari',
            date: '4d ago',
            readTime: '5 min read',
            category: 'Ghazal'
        },
        {
            title: isRtl ? 'نظم: آزادي جو سڏ' : 'Nazam: The Call for Freedom',
            excerpt: isRtl ? 'نظم ذريعي قومي شعور بيدار ڪرڻ...' : 'How Nazam became the voice of resistance during the chaotic times.',
            author: 'Hari Dilgir',
            date: '1w ago',
            readTime: '6 min read',
            category: 'Nazam'
        }
    ];

    const categories = [
        { id: 'all', label: isRtl ? 'سڀ' : 'All' },
        { id: 'ghazal', label: isRtl ? 'غزل' : 'Ghazal' },
        { id: 'waai', label: isRtl ? 'وائي' : 'Waai' },
        { id: 'nazam', label: isRtl ? 'نظم' : 'Nazam' },
        { id: 'bait', label: isRtl ? 'بيت' : 'Bait' },
        { id: 'kafi', label: isRtl ? 'ڪافي' : 'Kafi' },
    ];

    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const timer = setTimeout(() => {
            setLoading(false);
        }, 1500);
        return () => clearTimeout(timer);
    }, []);

    const LoadingState = () => (
        <div className="space-y-8 mt-0">
            {[1, 2, 3].map((i) => (
                <div key={i}>
                    <PostCardSkeleton />
                    {i < 3 && <Separator className="bg-gray-100" />}
                </div>
            ))}
        </div>
    );

    return (
        <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-6">
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
                    <TabsContent key={cat.id} value={cat.id} className="space-y-8 mt-0">
                        {loading ? <LoadingState /> : posts.map((post, i) => (
                            <React.Fragment key={i}>
                                <PostCard lang={lang} {...post} />
                                {i < posts.length - 1 && <Separator className="bg-gray-100" />}
                            </React.Fragment>
                        ))}
                        {!loading && cat.id !== 'all' && posts.filter(p => p.category.toLowerCase() === cat.id).length === 0 && (
                            <div className="text-center py-20 text-gray-500">
                                No poetry found in {cat.label}
                            </div>
                        )}
                    </TabsContent>
                ))}
            </Tabs>
        </div>
    );
};

export default PoetryFeed;
