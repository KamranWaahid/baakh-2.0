import React from 'react';
import PostCard from './PostCard';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import PostCardSkeleton from './skeletons/PostCardSkeleton';

const Feed = ({ lang }) => {
    const isRtl = lang === 'sd';

    const posts = [
        // ... (keep posts array same, assumed to be part of the component logic not shown in replacement chunk if I target the return)
        {
            title: isRtl ? 'جنوري ۾ مون پڙهيل 3 شاندار ڪتاب' : '3 Brilliant Books I Read In January',
            excerpt: isRtl ? 'جنوري ۾ پڙهڻ لاءِ بهترين ڪتابن جي فهرست...' : 'I discovered something so valuable for all of us that it deserved a description longer than an article.',
            author: 'Tom Addison',
            date: '5d ago',
            readTime: '8 min read',
            category: 'Reading List'
        },
        {
            title: isRtl ? 'هڪ ننڍڙي عادت جيڪي منهنجي دماغ کي نئون ڪيو' : 'The Tiny Habit that Rewired My Brain',
            excerpt: isRtl ? 'ڪيئن هڪ ننڍي تبديلي وڏي نتيجي جو سبب بڻجي سگهي ٿي...' : 'This is how a small daily ritual can transform your productivity and clarity in ways you never expected.',
            author: 'Learning Strategist',
            date: '3d ago',
            readTime: '12 min read',
            category: 'Psychology'
        },
        {
            title: isRtl ? 'شاھ لطيف جي شاعري ۾ فطرت جا نظارا' : 'Nature in the Poetry of Shah Latif',
            excerpt: isRtl ? 'لطيف سائين پنهنجي شاعري ۾ ڪيئن فطرت کي بيان ڪيو آهي...' : 'An exploration of naturalistic elements and symbolism in the immortal verses of Shah Abdul Latif Bhittai.',
            author: 'Baakh Editorial',
            date: 'Feb 1',
            readTime: '15 min read',
            category: 'Sindhi Literature'
        }
    ];

    const [loading, setLoading] = React.useState(true);

    React.useEffect(() => {
        const timer = setTimeout(() => {
            setLoading(false);
        }, 2000);
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
        <div className="flex-1 max-w-[720px] w-full mx-auto px-4 md:px-8 py-6">
            <Tabs defaultValue="for-you" className="w-full">
                <div className="sticky top-[65px] bg-white/95 backdrop-blur-sm pt-2 pb-0 z-40 border-b border-gray-100 mb-8">
                    <TabsList className="bg-transparent p-0 h-auto justify-start border-b-0 w-full rounded-none">
                        <TabsTrigger
                            value="for-you"
                            className="rounded-none border-b-2 border-transparent data-[state=active]:border-black data-[state=active]:shadow-none data-[state=active]:text-black text-gray-500 pb-3"
                        >
                            {isRtl ? 'توهان لاءِ' : 'For you'}
                        </TabsTrigger>
                        <TabsTrigger
                            value="featured"
                            className="rounded-none border-b-2 border-transparent data-[state=active]:border-black data-[state=active]:shadow-none data-[state=active]:text-black text-gray-500 pb-3"
                        >
                            {isRtl ? 'چونڊيل' : 'Featured'}
                        </TabsTrigger>
                    </TabsList>
                </div>

                <TabsContent value="for-you" className="space-y-8 mt-0">
                    {loading ? <LoadingState /> : posts.map((post, i) => (
                        <React.Fragment key={i}>
                            <PostCard lang={lang} {...post} />
                            {i < posts.length - 1 && <Separator className="bg-gray-100" />}
                        </React.Fragment>
                    ))}
                </TabsContent>

                <TabsContent value="featured" className="space-y-8 mt-0">
                    {loading ? <LoadingState /> : posts.slice(0, 2).map((post, i) => (
                        <React.Fragment key={i}>
                            <PostCard lang={lang} {...post} />
                            {i < 1 && <Separator className="bg-gray-100" />}
                        </React.Fragment>
                    ))}
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default Feed;
