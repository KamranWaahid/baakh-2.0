import React, { useState, useEffect } from 'react';
import PostCard from './PostCard';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import PostCardSkeleton from './skeletons/PostCardSkeleton';

const CoupletsFeed = ({ lang }) => {
    const isRtl = lang === 'sd';

    const posts = [
        {
            title: isRtl ? 'عشق: لافاني سچائي' : 'Love: The Eternal Truth',
            excerpt: isRtl ? 'محبت جي راه ۾...' : 'Walking the path of divine love requires surrendering the self.',
            author: 'Shah Latif',
            date: '1d ago',
            readTime: '1 min read',
            category: 'Love'
        },
        {
            title: isRtl ? 'جدائي جو درد' : 'The Pain of Separation',
            excerpt: isRtl ? 'تنهنجي ياد ۾...' : 'Every moment without you feels like an eternity of silence.',
            author: 'Sachal Sarmast',
            date: '3d ago',
            readTime: '1 min read',
            category: 'Sad'
        },
        {
            title: isRtl ? 'سنڌ منهنجي امان' : 'Sindh, My Motherland',
            excerpt: isRtl ? 'مٽي جي خوشبو...' : 'The scent of the soil after rain reminds me of my eternal bond with this land.',
            author: 'Sheikh Ayaz',
            date: '5d ago',
            readTime: '1 min read',
            category: 'Homeland'
        },
        {
            title: isRtl ? 'بهار جي آمد' : 'Arrival of Spring',
            excerpt: isRtl ? 'گلن جي ورکا...' : 'When flowers bloom, the heart dances with the rhythm of nature.',
            author: 'Ustad Bukhari',
            date: '1w ago',
            readTime: '1 min read',
            category: 'Happy'
        }
    ];

    const topics = [
        { id: 'all', label: isRtl ? 'سڀ' : 'All' },
        { id: 'love', label: isRtl ? 'محبت' : 'Love' },
        { id: 'sad', label: isRtl ? 'غم' : 'Sad' },
        { id: 'happy', label: isRtl ? 'خوشي' : 'Happy' },
        { id: 'homeland', label: isRtl ? 'وطن' : 'Homeland' },
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
                        {topics.map(topic => (
                            <TabsTrigger
                                key={topic.id}
                                value={topic.id}
                                className="rounded-none border-b-2 border-transparent data-[state=active]:border-black data-[state=active]:shadow-none data-[state=active]:text-black text-gray-500 pb-3 px-4 min-w-fit"
                            >
                                {topic.label}
                            </TabsTrigger>
                        ))}
                    </TabsList>
                </div>

                {topics.map(topic => (
                    <TabsContent key={topic.id} value={topic.id} className="space-y-8 mt-0">
                        {loading ? <LoadingState /> : posts.map((post, i) => (
                            <React.Fragment key={i}>
                                <PostCard lang={lang} {...post} />
                                {i < posts.length - 1 && <Separator className="bg-gray-100" />}
                            </React.Fragment>
                        ))}
                        {!loading && topic.id !== 'all' && posts.filter(p => p.category.toLowerCase() === topic.id).length === 0 && (
                            <div className="text-center py-20 text-gray-500">
                                No couplets found in {topic.label}
                            </div>
                        )}
                    </TabsContent>
                ))}
            </Tabs>
        </div>
    );
};

export default CoupletsFeed;
