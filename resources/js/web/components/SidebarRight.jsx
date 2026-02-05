import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';

const SidebarRight = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [staffPicks, setStaffPicks] = useState([]);
    const [topics, setTopics] = useState([]);
    const [loading, setLoading] = useState(true);
    const [stickyTop, setStickyTop] = useState(85);
    const sidebarRef = useRef(null);

    const updateSticky = useCallback(() => {
        if (!sidebarRef.current) return;
        const sidebarHeight = sidebarRef.current.offsetHeight;
        const windowHeight = window.innerHeight;
        const headerHeight = 65;
        const padding = 20;

        if (sidebarHeight + headerHeight + padding < windowHeight) {
            setStickyTop(headerHeight + padding);
        } else {
            setStickyTop(windowHeight - sidebarHeight - padding);
        }
    }, []);

    useEffect(() => {
        updateSticky();

        const observer = new ResizeObserver(() => updateSticky());
        if (sidebarRef.current) observer.observe(sidebarRef.current);

        window.addEventListener('resize', updateSticky);
        return () => {
            observer.disconnect();
            window.removeEventListener('resize', updateSticky);
        };
    }, [updateSticky, staffPicks, topics, loading]);

    useEffect(() => {
        const fetchData = async () => {
            setLoading(true);
            try {
                // Dynamically import axios
                await import('../../admin/api/axios').then(async (module) => {
                    const api = module.default;

                    // Fetch Staff Picks
                    const picksResponse = await api.get('/api/v1/sidebar/staff-picks', {
                        headers: { 'Accept-Language': lang }
                    });
                    setStaffPicks(picksResponse.data);

                    // Fetch Topics
                    const topicsResponse = await api.get('/api/v1/sidebar/topics', {
                        headers: { 'Accept-Language': lang }
                    });
                    setTopics(topicsResponse.data);
                });
            } catch (error) {
                console.error("Failed to fetch sidebar data", error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, [lang]);



    return (
        <aside
            ref={sidebarRef}
            className="w-[368px] hidden xl:block shrink-0 border-s border-gray-100 p-8 sticky flex flex-col"
            style={{ top: stickyTop }}
        >
            <section className="mb-10">
                <h3 className="font-bold text-black mb-4">{isRtl ? 'اسٽاف جا چونڊيل' : 'Staff Picks'}</h3>
                <div className="space-y-6">
                    {loading ? (
                        Array(3).fill(0).map((_, i) => (
                            <div key={i} className="group">
                                <div className="flex items-center gap-2 mb-2">
                                    <Skeleton className="h-5 w-5 rounded-full" />
                                    <Skeleton className="h-3 w-24" />
                                </div>
                                <Skeleton className="h-4 w-full mb-2" />
                                <Skeleton className="h-3 w-16" />
                            </div>
                        ))
                    ) : (
                        staffPicks.map((pick, i) => (
                            <div key={i} className="group cursor-pointer">
                                <Link to={`/${lang}/poet/${pick.poet_slug}/${pick.cat_slug}/${pick.slug}`}>
                                    <div className="flex items-center gap-2 mb-1">
                                        <div className="h-5 w-5 rounded-full bg-gray-100 flex items-center justify-center text-[10px] text-gray-500 overflow-hidden font-bold">
                                            {pick.author ? pick.author.charAt(0) : 'A'}
                                        </div>
                                        <span className={`text-xs font-medium ${isRtl ? 'font-arabic' : ''}`}>{pick.author}</span>
                                    </div>
                                    <h4 className={`text-[14px] font-bold leading-snug group-hover:underline ${isRtl ? 'font-arabic' : ''}`}>{pick.title}</h4>
                                    <span className="text-xs text-gray-500 mt-1 block">{pick.date}</span>
                                </Link>
                            </div>
                        ))
                    )}
                </div>
                <Button variant="link" className="text-black hover:text-gray-600 p-0 h-auto mt-6 font-medium">
                    {isRtl ? 'مڪمل لسٽ ڏسو' : 'See the full list'}
                </Button>
            </section>

            <section className="mb-10">
                <h3 className="font-bold text-black mb-4">{isRtl ? 'تجويز ڪيل موضوع' : 'Recommended topics'}</h3>
                <div className="flex flex-wrap gap-2">
                    {loading ? (
                        Array(6).fill(0).map((_, i) => (
                            <Skeleton key={i} className="h-8 w-20 rounded-full" />
                        ))
                    ) : (
                        topics.map(topic => (
                            <Badge key={topic} variant="secondary" className="rounded-full px-4 py-2 text-sm font-normal hover:bg-gray-200 cursor-pointer">
                                {topic}
                            </Badge>
                        ))
                    )}
                </div>
                <Button variant="link" className="text-black hover:text-gray-600 p-0 h-auto mt-6 font-medium">
                    {isRtl ? 'وڌيڪ موضوع ڏسو' : 'See more topics'}
                </Button>
            </section>

            <section className="pt-4 mt-auto">
                <div className="flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-500">
                    <a href="#" className="hover:text-black transition-colors">Help</a>
                    <a href="#" className="hover:text-black transition-colors">Status</a>
                    <a href="#" className="hover:text-black transition-colors">About</a>
                    <a href="#" className="hover:text-black transition-colors">Careers</a>
                    <a href="#" className="hover:text-black transition-colors">Privacy</a>
                    <a href="#" className="hover:text-black transition-colors">Terms</a>
                </div>
            </section>
        </aside>
    );
};

export default SidebarRight;
