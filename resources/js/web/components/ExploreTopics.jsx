import React, { useState, useEffect } from 'react';
import { Search, Loader2, ArrowRight } from 'lucide-react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import { Badge } from "@/components/ui/badge";
import { Skeleton } from '@/components/ui/skeleton';

const ExploreTopics = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [categories, setCategories] = useState([]);
    const [recommended, setRecommended] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');

    useEffect(() => {
        const fetchTopics = async () => {
            setLoading(true);
            try {
                const response = await axios.get('/api/v1/explore-topics', {
                    headers: { 'Accept-Language': lang }
                });
                setCategories(response.data.categories);
                setRecommended(response.data.recommended);
            } catch (error) {
                console.error("Failed to fetch explore topics", error);
            } finally {
                setLoading(false);
            }
        };

        fetchTopics();
    }, [lang]);

    const filteredCategories = categories.map(cat => {
        const categoryMatches = cat.name.toLowerCase().includes(search.toLowerCase()) ||
            cat.slug.toLowerCase().includes(search.toLowerCase());

        const matchingTags = cat.tags.filter(tag =>
            tag.name.toLowerCase().includes(search.toLowerCase()) ||
            tag.slug.toLowerCase().includes(search.toLowerCase())
        );

        if (categoryMatches) {
            // If category matches, show all tags (or maybe still just matching ones? 
            // Usually if I search for a category I want to see its contents. 
            // But if I want to "filter", maybe I still want to see everything under that category.)
            // Let's assume if category matches, we return the category with ALL tags.
            return { ...cat, tags: cat.tags };
        }

        // Otherwise return only matching tags
        return {
            ...cat,
            tags: matchingTags
        };
    }).filter(cat => cat.tags.length > 0 || cat.name.toLowerCase().includes(search.toLowerCase()) || cat.slug.toLowerCase().includes(search.toLowerCase()));

    return (
        <div className="flex-1 max-w-[1000px] w-full mx-auto px-4 md:px-8 py-12 md:py-20 animate-in fade-in duration-500">
            {/* Header Section */}
            <div className={`text-center mb-12 md:mb-16 ${isRtl ? 'font-arabic' : ''}`}>
                <h1 className="text-4xl md:text-5xl font-bold text-gray-900 mb-8 tracking-tight">
                    {isRtl ? 'موضوعن جي ڳولا' : 'Explore topics'}
                </h1>

                {/* Search Bar - Medium Style */}
                <div className="relative max-w-2xl mx-auto group">
                    <div className={`absolute inset-y-0 ${isRtl ? 'right-0 pr-5' : 'left-0 pl-5'} flex items-center pointer-events-none`}>
                        <Search className="h-5 w-5 text-gray-400 group-focus-within:text-black transition-colors" />
                    </div>
                    <input
                        type="text"
                        className={`w-full bg-gray-50 border-none rounded-full py-4 ${isRtl ? 'pr-12 pl-6' : 'pl-12 pr-6'} text-lg focus:ring-1 focus:ring-gray-200 transition-all placeholder:text-gray-400`}
                        placeholder={isRtl ? "سڀئي موضوع ڳوليو" : "Search all topics"}
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>

                {/* Recommended Section */}
                {!search && (
                    <div className="mt-8 flex flex-wrap justify-center gap-3 items-center text-sm">
                        <span className="text-gray-500">{isRtl ? 'تجويز ڪيل:' : 'Recommended:'}</span>
                        {loading ? (
                            Array(3).fill(0).map((_, i) => <Skeleton key={i} className="h-6 w-20 rounded-full" />)
                        ) : (
                            recommended.slice(0, 5).map(tag => (
                                <Link key={tag.slug} to={`/${lang}/tag/${tag.slug}`} className="text-gray-900 hover:text-gray-600 font-medium transition-colors underline decoration-gray-200 underline-offset-4 decoration-2">
                                    {tag.name}
                                </Link>
                            ))
                        )}
                    </div>
                )}
            </div>

            <div className="border-t border-gray-100 mb-12" />

            {/* Categories & Tags Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-12">
                {loading ? (
                    Array(6).fill(0).map((_, i) => (
                        <div key={i} className="space-y-4">
                            <Skeleton className="h-8 w-32" />
                            <div className="space-y-3">
                                {Array(5).fill(0).map((_, j) => <Skeleton key={j} className="h-4 w-24" />)}
                            </div>
                        </div>
                    ))
                ) : filteredCategories.length > 0 ? (
                    filteredCategories.map(cat => (
                        <div key={cat.id} className="break-inside-avoid mb-0">
                            <h2 className={`text-xl md:text-2xl font-bold text-gray-900 mb-6 ${isRtl ? 'font-arabic' : ''}`}>
                                <Link to={`/${lang}/topic/${cat.slug}`} className="hover:underline">
                                    {cat.name}
                                </Link>
                            </h2>
                            <ul className="space-y-3">
                                {cat.tags.slice(0, 5).map(tag => (
                                    <li key={tag.slug}>
                                        <Link
                                            to={`/${lang}/tag/${tag.slug}`}
                                            className="text-gray-500 hover:text-gray-900 hover:underline transition-colors text-base block font-medium"
                                        >
                                            {tag.name}
                                        </Link>
                                    </li>
                                ))}
                                {cat.tags.length > 5 && (
                                    <li>
                                        <Link
                                            to={`/${lang}/topic/${cat.slug}`}
                                            className="text-gray-900 hover:text-gray-700 font-medium text-base inline-flex items-center gap-1 mt-1 underline decoration-gray-300 underline-offset-4"
                                        >
                                            {isRtl ? 'وڌيڪ' : 'More'}
                                        </Link>
                                    </li>
                                )}
                            </ul>
                        </div>
                    ))
                ) : (
                    <div className="col-span-full py-20 text-center text-gray-500">
                        {isRtl ? 'ڪو به نتيجو نه مليو' : 'No topics matched your search.'}
                    </div>
                )}
            </div>
        </div>
    );
};

export default ExploreTopics;
