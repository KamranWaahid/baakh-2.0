import React, { useState, useEffect } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { BookOpen, Feather, Music, Scroll, Star, Hash, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Link } from 'react-router-dom';
import GenreDetailsModal from './GenreDetailsModal';

const GenreFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [selectedGenre, setSelectedGenre] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);

    // Fetch Genres
    const { data: genres, isLoading } = useQuery({
        queryKey: ['genres', lang],
        queryFn: async () => {
            const response = await axios.get(`/api/v1/categories?lang=${lang}`);
            return response.data;
        }
    });

    const handleGenreClick = (genre) => {
        setSelectedGenre(genre);
        setIsModalOpen(true);
    };

    const GenreCard = ({ genre }) => (
        <div
            onClick={() => handleGenreClick(genre)}
            className="group relative bg-white border border-gray-100 rounded-xl p-6 hover:border-black/20 hover:shadow-sm transition-all duration-300 cursor-pointer flex flex-col items-center text-center h-full"
        >
            <div className={`h-12 w-12 rounded-full bg-gray-50 group-hover:bg-gray-100 flex items-center justify-center mb-4 transition-colors ${isRtl ? 'ml-0' : 'mr-0'}`}>
                <Hash className="h-5 w-5 text-gray-400 group-hover:text-black transition-colors" />
            </div>

            <h3 className={`text-xl font-bold text-gray-900 mb-1 ${isRtl ? 'font-arabic' : ''}`}>
                {isRtl ? genre.sd_name : genre.en_name}
            </h3>

            <span className={`text-xs font-medium text-gray-400 uppercase tracking-wider mb-3 ${isRtl ? 'font-sans' : ''}`}>
                {isRtl ? genre.en_name : genre.sd_name}
            </span>

            <p className="text-sm text-gray-500 line-clamp-2 leading-relaxed mb-4">
                {genre.desc || (isRtl ? 'هن صنف جي شاعري جو مجموعو.' : 'A collection of poetry in this genre.')}
            </p>

            <div className="mt-auto pt-4 w-full border-t border-gray-50 flex items-center justify-between text-xs text-gray-400">
                <span className="flex items-center gap-1">
                    <BookOpen className="h-3 w-3" /> {genre.count} {isRtl ? 'ڪلام' : 'Works'}
                </span>
                <span className="group-hover:translate-x-1 group-hover:text-black transition-all duration-300">
                    {isRtl ? '←' : '→'}
                </span>
            </div>
        </div>
    );

    return (
        <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-8">
            <div className="mb-10">
                <h1 className={`text-3xl font-bold text-gray-900 mb-3 ${isRtl ? 'font-arabic' : ''}`}>
                    {isRtl ? 'ادبي صنفون' : 'Poetic Genres'}
                </h1>
                <p className="text-gray-500 text-lg max-w-2xl">
                    {isRtl
                        ? 'سنڌي شاعري جي مختلف صنفن جو مجموعو، قديم کان جديد تائين.'
                        : 'Explore the diverse forms of Sindhi poetry, ranging from classical traditions to modern expressions.'}
                </p>
            </div>

            {isLoading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    {Array(8).fill(0).map((_, i) => (
                        <div key={i} className="border border-gray-100 rounded-xl p-6 flex flex-col items-center">
                            <Skeleton className="h-12 w-12 rounded-full mb-4" />
                            <Skeleton className="h-6 w-24 mb-2" />
                            <Skeleton className="h-4 w-16 mb-4" />
                            <Skeleton className="h-20 w-full mb-4" />
                            <Skeleton className="h-10 w-full" />
                        </div>
                    ))}
                </div>
            ) : genres?.length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    {genres.map((genre) => (
                        <GenreCard key={genre.id} genre={genre} />
                    ))}
                </div>
            ) : (
                <div className="text-center py-20">
                    <Search className="h-12 w-12 text-gray-200 mx-auto mb-4" />
                    <p className="text-gray-500">
                        {isRtl ? 'ڪا صنف نه ملي.' : 'No genres found.'}
                    </p>
                </div>
            )}

            <GenreDetailsModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                genre={selectedGenre}
                lang={lang}
            />
        </div>
    );
};

export default GenreFeed;
