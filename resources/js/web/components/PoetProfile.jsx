import React, { useState, useEffect } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { MoreHorizontal, Mail } from 'lucide-react';
import { useParams } from 'react-router-dom';
import PostCard from './PostCard';

const PoetProfile = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { slug } = useParams();
    const [loading, setLoading] = useState(true);

    // Mock Poet Data
    const poet = {
        name: slug.replace(/-/g, ' '), // Simple mock
        bio: "The most prominent poet of modern Sindhi poetry. Taking Sindhi literature to new heights with his revolutionary thoughts.",
        followers: "12.5K",
        avatar: "SA",
    };

    // Mock Feed Data
    const posts = [
        { id: 1, title: 'The Voice of Revolution', excerpt: 'Rise, O Sindhi! The dawn is waiting for your awakening...', author: poet.name, date: 'May 12', readTime: '5 min read', category: 'Poetry' },
        { id: 2, title: 'Songs of the Desert', excerpt: 'In the scorching heat of Thar, I found the cold solace of truth...', author: poet.name, date: 'Apr 28', readTime: '3 min read', category: 'Ghazal' },
        { id: 3, title: 'Letter to the Youth', excerpt: 'Do not despair, for the night is darkest before the dawn...', author: poet.name, date: 'Mar 15', readTime: '7 min read', category: 'Letters' },
    ];

    useEffect(() => {
        const timer = setTimeout(() => {
            setLoading(false);
        }, 1500);
        return () => clearTimeout(timer);
    }, []);

    if (loading) {
        return (
            <div className="w-full flex justify-center py-10 px-4 md:px-8">
                <div className="w-full max-w-[1000px] flex gap-12">
                    {/* Feed Skeleton */}
                    <div className="flex-1 space-y-8">
                        <div className="space-y-4 mb-10">
                            <Skeleton className="h-10 w-3/4" />
                            <div className="flex gap-6">
                                <Skeleton className="h-6 w-20" />
                                <Skeleton className="h-6 w-20" />
                            </div>
                        </div>
                        <div className="space-y-8">
                            {[1, 2, 3].map(i => (
                                <div key={i} className="space-y-3">
                                    <Skeleton className="h-6 w-full" />
                                    <Skeleton className="h-4 w-full" />
                                    <Skeleton className="h-4 w-2/3" />
                                </div>
                            ))}
                        </div>
                    </div>
                    {/* Sidebar Skeleton */}
                    <div className="hidden lg:block w-[320px] shrink-0 space-y-6">
                        <Skeleton className="h-32 w-32 rounded-full" />
                        <Skeleton className="h-6 w-48" />
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-10 w-full rounded-full" />
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="w-full flex justify-center py-10 px-4 md:px-8">
            <div className="w-full max-w-[1000px] flex gap-12">

                {/* Main Content (Left) */}
                <div className="flex-1 min-w-0">
                    <header className="mb-8">
                        <h1 className="text-4xl md:text-5xl font-black mb-6 text-gray-900 capitalize tracking-tight">
                            {poet.name}
                        </h1>

                        <div className="flex items-center gap-8 border-b border-gray-100 mb-8 overflow-x-auto no-scrollbar">
                            <button className="pb-4 text-sm font-medium border-b-2 border-black text-black whitespace-nowrap">
                                {isRtl ? 'گھر' : 'Home'}
                            </button>
                            <button className="pb-4 text-sm font-medium text-gray-500 hover:text-gray-800 transition-colors whitespace-nowrap">
                                {isRtl ? 'بابت' : 'About'}
                            </button>
                            <button className="pb-4 text-sm font-medium text-gray-500 hover:text-gray-800 transition-colors whitespace-nowrap">
                                {isRtl ? 'شاعري' : 'Poetry'}
                            </button>
                        </div>
                    </header>

                    <div className="space-y-0">
                        {posts.map(post => (
                            <PostCard key={post.id} lang={lang} {...post} />
                        ))}
                    </div>
                </div>

                {/* Profile Sidebar (Right) - Medium Style */}
                <aside className="hidden lg:block w-[320px] shrink-0 sticky top-24 h-fit border-l border-gray-100 pl-12 -ml-6">
                    <div className="flex flex-col items-start">
                        <div className="h-32 w-32 rounded-full bg-gray-200 mb-6 flex items-center justify-center text-3xl font-bold text-gray-400 overflow-hidden">
                            {/* Placeholder for Image */}
                            {poet.avatar}
                        </div>

                        <h3 className="font-bold text-lg mb-1 capitalize text-gray-900">{poet.name}</h3>
                        <p className="text-gray-500 text-sm mb-4">{poet.followers} {isRtl ? 'پيروي ڪندڙ' : 'Followers'}</p>

                        <p className="text-sm text-gray-600 leading-relaxed mb-6">
                            {poet.bio}
                        </p>

                        <div className="flex gap-2 w-full mb-6">
                            <Button className="flex-1 rounded-full bg-green-600 hover:bg-green-700 text-white font-medium">
                                {isRtl ? 'فالو ڪريو' : 'Follow'}
                            </Button>
                            <Button variant="outline" size="icon" className="rounded-full bg-green-600 border-green-600 text-white hover:bg-green-700 hover:text-white">
                                <Mail className="h-4 w-4" />
                            </Button>
                            <Button variant="outline" size="icon" className="rounded-full border-gray-300">
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </div>

                        <div className="text-xs text-gray-400">
                            <h4 className="font-bold text-gray-900 mb-2 uppercase tracking-wide">Following</h4>
                            {/* Small list if needed */}
                            <div className="flex gap-2">
                                <div className="h-6 w-6 rounded-full bg-gray-100"></div>
                                <div className="h-6 w-6 rounded-full bg-gray-100"></div>
                            </div>
                        </div>

                    </div>
                </aside>

            </div>
        </div>
    );
};

export default PoetProfile;
