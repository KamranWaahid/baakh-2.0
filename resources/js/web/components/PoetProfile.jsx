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
                        {/* Mobile Profile Header */}
                        <div className="lg:hidden mb-8">
                            <div className="flex items-center gap-4 mb-4">
                                <div className="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center text-xl font-bold text-gray-400 shrink-0 border border-gray-100">
                                    {poet.avatar}
                                </div>
                                <div>
                                    <h1 className="text-3xl font-bold tracking-tight text-gray-900 capitalize leading-none mb-1">{poet.name}</h1>
                                    <p className="text-gray-500 text-sm">{poet.followers} {isRtl ? 'پيروي ڪندڙ' : 'Followers'}</p>
                                </div>
                            </div>
                            <p className="text-gray-600 font-serif text-[16px] leading-relaxed mb-6">
                                {poet.bio}
                            </p>
                            <div className="flex gap-3 w-full">
                                <Button className="flex-1 rounded-full bg-black hover:bg-gray-800 text-white font-medium h-10">
                                    {isRtl ? 'فالو ڪريو' : 'Follow'}
                                </Button>
                                <Button variant="outline" size="icon" className="rounded-full bg-black border-black text-white hover:bg-gray-800 hover:text-white h-10 w-10">
                                    <Mail className="h-4 w-4" />
                                </Button>
                                <Button variant="outline" size="icon" className="rounded-full border-gray-300 h-10 w-10">
                                    <MoreHorizontal className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        {/* Desktop Title */}
                        <h1 className="hidden lg:block text-4xl md:text-5xl font-bold tracking-tight mb-6 text-gray-900 capitalize">
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

                        <h3 className="font-bold tracking-tight text-lg mb-1 capitalize text-gray-900">{poet.name}</h3>
                        <p className="text-gray-500 text-sm mb-4">{poet.followers} {isRtl ? 'پيروي ڪندڙ' : 'Followers'}</p>

                        <p className="font-serif text-sm text-gray-600 leading-relaxed mb-6">
                            {poet.bio}
                        </p>

                        <div className="flex gap-2 w-full mb-6">
                            <Button className="flex-1 rounded-full bg-black hover:bg-gray-800 text-white font-medium">
                                {isRtl ? 'فالو ڪريو' : 'Follow'}
                            </Button>
                            <Button variant="outline" size="icon" className="rounded-full bg-black border-black text-white hover:bg-gray-800 hover:text-white">
                                <Mail className="h-4 w-4" />
                            </Button>
                            <Button variant="outline" size="icon" className="rounded-full border-gray-300">
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </div>

                        <div className="w-full pt-6 border-t border-gray-100">
                            <h4 className="font-medium text-gray-900 mb-4 text-sm uppercase tracking-wide">
                                {isRtl ? 'تجويز ڪيل شاعر' : 'Recommended Poets'}
                            </h4>
                            <div className="space-y-4">
                                {[
                                    { name: 'Shah Latif', avatar: 'SL' },
                                    { name: 'Ustad Bukhari', avatar: 'UB' },
                                    { name: 'Sachal Sarmast', avatar: 'SS' }
                                ].map((p, i) => (
                                    <div key={i} className="flex items-center justify-between group cursor-pointer">
                                        <div className="flex items-center gap-3">
                                            <div className="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-400 border border-gray-200">
                                                {p.avatar}
                                            </div>
                                            <span className="text-sm font-medium text-gray-700 group-hover:text-black transition-colors">{p.name}</span>
                                        </div>
                                        <Button variant="outline" size="sm" className="h-7 w-auto px-3 text-xs rounded-full border-gray-300 hover:border-black hover:bg-black hover:text-white transition-all">
                                            Follow
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        </div>

                    </div>
                </aside>

            </div>
        </div>
    );
};

export default PoetProfile;
