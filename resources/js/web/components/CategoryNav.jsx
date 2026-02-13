import React, { useState, useEffect, useRef } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Compass, ChevronRight, ChevronLeft } from 'lucide-react';

const CategoryNav = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [topics, setTopics] = useState([]);
    const [loading, setLoading] = useState(true);
    const scrollContainerRef = useRef(null);
    const [showLeftArrow, setShowLeftArrow] = useState(false);
    const [showRightArrow, setShowRightArrow] = useState(true);
    const location = useLocation();

    // Check if current page is specific category to highlight
    // Path format: /:lang/topic/:category
    const pathParts = location.pathname.split('/').filter(Boolean);
    const currentCategorySlug = pathParts.length === 3 && pathParts[1] === 'topic'
        ? pathParts[2]
        : null;

    useEffect(() => {
        const fetchTopics = async () => {
            setLoading(true);
            try {
                // Determine API endpoint - use sidebar topics for now as it's a good list
                // Dynamic import to avoid circular dependencies if any
                const module = await import('../../admin/api/axios');
                const api = module.default;

                const response = await api.get('/api/v1/sidebar/topics', {
                    headers: { 'Accept-Language': lang }
                });
                setTopics(response.data);
            } catch (error) {
                console.error("Failed to fetch nav topics", error);
            } finally {
                setLoading(false);
            }
        };

        fetchTopics();
    }, [lang]);

    const handleScroll = () => {
        if (!scrollContainerRef.current) return;

        const { scrollLeft, scrollWidth, clientWidth } = scrollContainerRef.current;
        // In RTL, scrollLeft might be negative or different depending on browser impl
        // safely handling both
        const scrollX = Math.abs(scrollLeft);

        setShowLeftArrow(scrollX > 10);
        setShowRightArrow(scrollX < scrollWidth - clientWidth - 10);
    };

    const scroll = (direction) => {
        if (!scrollContainerRef.current) return;

        const scrollAmount = 200;
        const currentScroll = scrollContainerRef.current.scrollLeft;

        // For RTL, direction logic might need flipping depending on browser
        // But usually "left" decreases scrollLeft and "right" increases it visually
        // Standard scroll behavior:
        const targetScroll = direction === 'right'
            ? currentScroll + (isRtl ? -scrollAmount : scrollAmount)
            : currentScroll + (isRtl ? scrollAmount : -scrollAmount);

        scrollContainerRef.current.scrollTo({
            left: targetScroll,
            behavior: 'smooth'
        });
    };

    useEffect(() => {
        const el = scrollContainerRef.current;
        if (el) {
            el.addEventListener('scroll', handleScroll);
            // Check initial state
            handleScroll();
            return () => el.removeEventListener('scroll', handleScroll);
        }
    }, [topics, loading]);

    return (
        <div className="border-b border-gray-100 bg-white sticky top-[56px] lg:top-[65px] z-40">
            <div
                className="max-w-[1504px] mx-auto px-4 md:px-8 flex items-center gap-4 h-14 md:h-16"
                dir={isRtl ? 'rtl' : 'ltr'}
            >

                {/* Explore Topics Button */}
                <Link to={`/${lang}/explore`} className="shrink-0">
                    <Button
                        variant={location.pathname.includes('/explore') ? "default" : "outline"}
                        size="sm"
                        className={`rounded-full h-9 px-4 gap-2 transition-all font-medium
                            ${location.pathname.includes('/explore')
                                ? 'bg-black text-white hover:bg-black/90 border-transparent'
                                : 'bg-gray-50 border-gray-200 hover:bg-gray-100 text-gray-700'
                            }
                        `}
                    >
                        <Compass className="h-4 w-4" />
                        <span className={isRtl ? 'font-arabic pt-0.5' : ''}>
                            {isRtl ? 'سڀئي موضوع' : 'All topics'}
                        </span>
                    </Button>
                </Link>

                <div className="h-6 w-px bg-gray-200 shrink-0 hidden md:block" />

                {/* Scrollable Categories */}
                <div className="relative flex-1 min-w-0 group">
                    {/* Left Fade/Arrow */}
                    <div className={`absolute inset-y-0 ${isRtl ? 'right-0' : 'left-0'} w-12 bg-gradient-to-r from-white via-white/80 to-transparent z-10 flex items-center transition-opacity duration-300 ${showLeftArrow ? 'opacity-100' : 'opacity-0 pointer-events-none'}`}>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => scroll('left')}
                            className="h-8 w-8 rounded-full bg-white/90 shadow-sm border border-gray-100 hover:bg-gray-50 -ml-2"
                        >
                            {isRtl ? <ChevronRight className="h-4 w-4" /> : <ChevronLeft className="h-4 w-4" />}
                        </Button>
                    </div>

                    {/* Category List */}
                    <div
                        ref={scrollContainerRef}
                        className="flex items-center gap-2 overflow-x-auto no-scrollbar scroll-smooth py-2 px-1"
                        dir={isRtl ? 'rtl' : 'ltr'}
                    >
                        {loading ? (
                            Array(8).fill(0).map((_, i) => (
                                <Skeleton key={i} className="h-8 w-24 rounded-full shrink-0" />
                            ))
                        ) : (
                            topics.map(topic => {
                                const isActive = currentCategorySlug === topic.slug;
                                return (
                                    <Link key={topic.slug} to={`/${lang}/topic/${topic.slug}`} className="shrink-0">
                                        <Badge
                                            variant={isActive ? "default" : "secondary"}
                                            className={`
                                                rounded-full px-4 py-1.5 text-sm font-normal h-8 transition-all whitespace-nowrap
                                                ${isActive
                                                    ? 'bg-black text-white hover:bg-black/90'
                                                    : 'bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-900 border-transparent hover:border-gray-200'
                                                }
                                                ${isRtl ? 'font-arabic pt-1' : ''}
                                            `}
                                        >
                                            {topic.name}
                                        </Badge>
                                    </Link>
                                );
                            })
                        )}
                    </div>

                    {/* Right Fade/Arrow */}
                    <div className={`absolute inset-y-0 ${isRtl ? 'left-0' : 'right-0'} w-12 bg-gradient-to-l from-white via-white/80 to-transparent z-10 flex items-center justify-end transition-opacity duration-300 ${showRightArrow ? 'opacity-100' : 'opacity-0 pointer-events-none'}`}>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => scroll('right')}
                            className="h-8 w-8 rounded-full bg-white/90 shadow-sm border border-gray-100 hover:bg-gray-50 -mr-2"
                        >
                            {isRtl ? <ChevronLeft className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default CategoryNav;
