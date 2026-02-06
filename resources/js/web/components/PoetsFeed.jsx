import React, { useState, useEffect } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { User, BookOpen } from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
// import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"; // Removing Tabs for now as we are just listing

const PoetsFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [search, setSearch] = useState('');
    const [selectedTag, setSelectedTag] = useState('all');

    // Fetch tags
    const { data: tagsData } = useQuery({
        queryKey: ['poet-tags'],
        queryFn: async () => {
            const response = await axios.get('/api/v1/poet-tags');
            return response.data;
        }
    });

    const tags = tagsData || [];

    // Fetch poets from API
    const { data, isLoading } = useQuery({
        queryKey: ['poets', search, selectedTag],
        queryFn: async () => {
            // In real app, might want to debounce search here or in the UI
            const params = { search };
            if (selectedTag !== 'all') {
                params.tag = selectedTag;
            }
            const response = await axios.get('/api/v1/poets', {
                params
            });
            return response.data;
        }
    });

    const poets = data?.data || [];

    const PoetCard = ({ poet }) => (
        <div className="flex items-center gap-6 p-6 border-b border-gray-100 bg-white transition-colors group">
            <Avatar className="h-16 w-16 md:h-20 md:w-20 border border-gray-100">
                <AvatarImage src={poet.avatar} alt={poet.name_en} className="object-cover" />
                <AvatarFallback className="text-xl md:text-2xl font-bold text-gray-400 bg-gray-100">
                    {poet.name_en?.charAt(0) || poet.name_sd?.charAt(0) || 'P'}
                </AvatarFallback>
            </Avatar>

            <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                    <h3 className="text-lg md:text-xl font-bold text-gray-900 truncate">
                        {isRtl ? poet.name_sd : poet.name_en}
                    </h3>
                </div>

                <p className="text-gray-500 text-sm md:text-base line-clamp-2 mb-2 font-serif">
                    {isRtl ? poet.bio_sd : poet.bio_en}
                </p>

                <div className="flex items-center gap-4 text-xs text-gray-400 font-medium">
                    <span className="flex items-center gap-1">
                        <BookOpen className="h-3 w-3" /> {poet.entries_count || 0} {isRtl ? 'لکڻيون' : 'Entries'}
                    </span>
                </div>
            </div>

            <Button
                variant="outline"
                asChild
                className="rounded-full hidden sm:flex items-center gap-2 hover:bg-gray-50 transition-colors"
            >
                <a href={`/poets/${poet.slug}`}>
                    <User className="h-4 w-4" />
                    <span>{isRtl ? 'کاتو' : 'Profile'}</span>
                </a>
            </Button>
        </div>
    );

    return (
        <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-6">
            <div className="mb-8">
                <h1 className="text-3xl font-bold mb-6">{isRtl ? 'شاعر' : 'Poets'}</h1>

                {/* Tags Menu */}
                <div className="flex items-center gap-8 border-b border-gray-100 overflow-x-auto no-scrollbar pb-1">
                    <button
                        onClick={() => setSelectedTag('all')}
                        className={`pb-3 text-base font-medium whitespace-nowrap transition-colors relative
                            ${selectedTag === 'all'
                                ? 'text-black font-bold'
                                : 'text-gray-400 hover:text-gray-600'
                            }`}
                    >
                        {isRtl ? 'سڀ' : 'All'}
                        {selectedTag === 'all' && (
                            <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-black rounded-full" />
                        )}
                    </button>

                    {tags.map(tag => (
                        <button
                            key={tag.slug}
                            onClick={() => setSelectedTag(tag.slug)}
                            className={`pb-3 text-base font-medium whitespace-nowrap transition-colors relative
                                ${selectedTag === tag.slug
                                    ? 'text-black font-bold'
                                    : 'text-gray-400 hover:text-gray-600'
                                }`}
                        >
                            {tag.tag}
                            {selectedTag === tag.slug && (
                                <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-black rounded-full" />
                            )}
                        </button>
                    ))}
                </div>
            </div>

            <div className="space-y-4">
                {isLoading ? (
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
                ) : poets.length > 0 ? (
                    poets.map(poet => <PoetCard key={poet.id} poet={poet} />)
                ) : (
                    <div className="py-20 text-center text-gray-500">
                        {isRtl ? 'ڪو به شاعر نه مليو' : 'No poets found.'}
                    </div>
                )}

                {/* Pagination could be added here */}
            </div>
        </div>
    );
};

export default PoetsFeed;
