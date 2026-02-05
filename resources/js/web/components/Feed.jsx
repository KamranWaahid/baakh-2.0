import React from 'react';
import PostCard from './PostCard';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import PostCardSkeleton from './skeletons/PostCardSkeleton';

const Feed = ({ lang }) => {
    const isRtl = lang === 'sd';

    const [posts, setPosts] = React.useState([]);
    const [loading, setLoading] = React.useState(true);

    React.useEffect(() => {
        const fetchFeed = async () => {
            setLoading(true);
            try {
                const module = await import('../../admin/api/axios');
                const api = module.default;
                const response = await api.get('/api/v1/feed', {
                    params: { lang }
                });
                setPosts(response.data.data);
            } catch (error) {
                console.error("Failed to fetch feed", error);
            } finally {
                setLoading(false);
            }
        };

        fetchFeed();
    }, [lang]);

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
        <div className="flex-1 max-w-[720px] w-full mx-auto px-4 md:px-8 py-6" dir={isRtl ? 'rtl' : 'ltr'}>
            <Tabs defaultValue="for-you" className="w-full" dir={isRtl ? 'rtl' : 'ltr'}>
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
                    {loading ? <LoadingState /> : (posts && posts.length > 0) ? posts.map((post, i) => (
                        <React.Fragment key={post.id || i}>
                            <PostCard lang={lang} {...post} />
                            {i < posts.length - 1 && <Separator className="bg-gray-100" />}
                        </React.Fragment>
                    )) : (
                        <div className="py-20 text-center text-gray-500">
                            {isRtl ? 'ڪوبه مواد نه مليو.' : 'No content found.'}
                        </div>
                    )}
                </TabsContent>

                <TabsContent value="featured" className="space-y-8 mt-0">
                    {loading ? <LoadingState /> : (posts && posts.length > 0) ? posts.slice(0, 2).map((post, i) => (
                        <React.Fragment key={post.id || i}>
                            <PostCard lang={lang} {...post} />
                            {i < Math.min(posts.length, 2) - 1 && <Separator className="bg-gray-100" />}
                        </React.Fragment>
                    )) : (
                        <div className="py-20 text-center text-gray-500">
                            {isRtl ? 'ڪوبه مواد نه مليو.' : 'No content found.'}
                        </div>
                    )}
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default Feed;
