import React, { useState, useEffect } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { History, Calendar, ScrollText, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import PeriodDetailsModal from './PeriodDetailsModal';

const PeriodFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [selectedPeriod, setSelectedPeriod] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);

    // Fetch Periods
    const { data: periods, isLoading } = useQuery({
        queryKey: ['periods', lang],
        queryFn: async () => {
            const response = await axios.get(`/api/v1/periods`);
            return response.data;
        }
    });

    const handlePeriodClick = (period) => {
        setSelectedPeriod(period);
        setIsModalOpen(true);
    };

    const PeriodCard = ({ period }) => (
        <div
            onClick={() => handlePeriodClick(period)}
            className="group relative bg-white border border-gray-100 rounded-xl p-8 hover:border-black/20 hover:shadow-sm transition-all duration-300 cursor-pointer flex flex-col h-full"
        >
            <div className="flex items-start justify-between mb-6">
                <div className={`h-12 w-12 rounded-full bg-gray-50 group-hover:bg-gray-100 flex items-center justify-center transition-colors`}>
                    <History className="h-5 w-5 text-gray-400 group-hover:text-black transition-colors" />
                </div>
                <div className="flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-50 text-xs font-medium text-gray-500">
                    <Calendar className="h-3 w-3" />
                    <span>{period.date_range}</span>
                </div>
            </div>

            <h3 className={`text-2xl font-bold text-gray-900 mb-2 ${isRtl ? 'font-arabic' : ''}`}>
                {isRtl ? period.title_sd : period.title_en}
            </h3>

            <span className={`text-xs font-medium text-gray-400 uppercase tracking-wider mb-4 ${isRtl ? 'font-sans' : ''}`}>
                {isRtl ? period.title_en : period.title_sd}
            </span>

            <p className="text-sm text-gray-500 leading-relaxed mb-6 flex-1">
                {isRtl ? period.description_sd : period.description_en}
            </p>

            <div className="pt-6 border-t border-gray-50 flex items-center justify-between">
                <span className="text-xs font-medium text-black group-hover:underline underline-offset-4">
                    {isRtl ? 'شاعر ۽ ڪم' : 'View Poets & Works'}
                </span>
                <span className="group-hover:translate-x-1 transition-transform duration-300 text-black">
                    {isRtl ? '←' : '→'}
                </span>
            </div>
        </div>
    );

    return (
        <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-8">
            <div className="mb-12">
                <h1 className={`text-3xl font-bold text-gray-900 mb-3 ${isRtl ? 'font-arabic' : ''}`}>
                    {isRtl ? 'ادبي دور' : 'Literary Periods'}
                </h1>
                <p className="text-gray-500 text-lg max-w-2xl">
                    {isRtl
                        ? 'سنڌي ادب جي تاريخي دورن جو سفر.'
                        : 'A journey through the timeline of Sindhi literature, from ancient folklore to modern resistance.'}
                </p>
            </div>

            {isLoading ? (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {Array(6).fill(0).map((_, i) => (
                        <div key={i} className="border border-gray-100 rounded-xl p-8 flex flex-col">
                            <div className="flex justify-between mb-6">
                                <Skeleton className="h-12 w-12 rounded-full" />
                                <Skeleton className="h-6 w-24 rounded-full" />
                            </div>
                            <Skeleton className="h-8 w-3/4 mb-2" />
                            <Skeleton className="h-4 w-1/2 mb-6" />
                            <Skeleton className="h-16 w-full" />
                        </div>
                    ))}
                </div>
            ) : periods?.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {periods.map((period) => (
                        <PeriodCard key={period.id} period={period} />
                    ))}
                </div>
            ) : (
                <div className="text-center py-20">
                    <Search className="h-12 w-12 text-gray-200 mx-auto mb-4" />
                    <p className="text-gray-500">
                        {isRtl ? 'ڪو به دور نه مليو.' : 'No periods found.'}
                    </p>
                </div>
            )}

            <PeriodDetailsModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                period={selectedPeriod}
                lang={lang}
            />
        </div>
    );
};

export default PeriodFeed;
