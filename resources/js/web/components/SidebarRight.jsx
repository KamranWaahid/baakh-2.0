import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { User } from 'lucide-react';
import { formatSindhiDate } from '../utils/dateUtils';
import AvatarImgOrIcon from './AvatarImgOrIcon';

const normalizeArrayPayload = (payload) => {
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.data)) return payload.data;
    if (Array.isArray(payload?.items)) return payload.items;
    return [];
};

const SidebarRight = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [staffPicks, setStaffPicks] = useState([]);
    const [topics, setTopics] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            setLoading(true);
            try {
                // Dynamically import axios
                await import('../../admin/api/axios').then(async (module) => {
                    const api = module.default;

                    const [picksResponse, topicsResponse] = await Promise.all([
                        api.get('/api/v1/sidebar/staff-picks', {
                            headers: { 'Accept-Language': lang }
                        }),
                        api.get('/api/v1/sidebar/topics', {
                            headers: { 'Accept-Language': lang }
                        })
                    ]);

                    setStaffPicks(normalizeArrayPayload(picksResponse.data).filter(Boolean));
                    setTopics(normalizeArrayPayload(topicsResponse.data).filter(Boolean));
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
            className="w-[368px] hidden xl:block shrink-0 border-s border-gray-100 p-8 sticky flex flex-col"
            style={{ top: 85 }}
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
                            <div key={i} className="mb-6 last:mb-0">
                                <Link to={`/${lang}/poet/${pick.poet_slug}`} className="flex items-center gap-2 mb-1 hover:opacity-80 transition-opacity w-fit">
                                    <div className="h-5 w-5 shrink-0 overflow-hidden rounded-full border border-gray-100">
                                        <AvatarImgOrIcon src={pick.author_avatar} imageType="poet" alt={pick.author} />
                                    </div>
                                    <span className={`text-xs font-medium hover:underline ${isRtl ? 'font-arabic' : ''}`}>{pick.author}</span>
                                </Link>
                                <Link to={`/${lang}/poet/${pick.poet_slug}/${pick.cat_slug}/${pick.slug}`} className="group block">
                                    <h4 className={`text-[14px] font-bold leading-snug group-hover:underline ${isRtl ? 'font-arabic' : ''}`}>{pick.title}</h4>
                                    <span className="text-xs text-gray-500 mt-1 block">{isRtl ? formatSindhiDate(pick.date) : pick.date}</span>
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
                        topics.map((topic, index) => (
                            <Link key={topic?.slug || `topic-${index}`} to={`/${lang}/topic/${topic?.slug || ''}`}>
                                <Badge variant="secondary" className="rounded-full px-4 py-2 text-sm font-normal hover:bg-gray-200 cursor-pointer text-gray-700 bg-gray-100 border-none transition-colors">
                                    {topic?.name || ''}
                                </Badge>
                            </Link>
                        ))
                    )}
                </div>
                <Button variant="link" asChild className="text-black hover:text-gray-600 p-0 h-auto mt-6 font-medium">
                    <Link to={`/${lang}/explore`}>
                        {isRtl ? 'وڌيڪ موضوع ڏسو' : 'See more topics'}
                    </Link>
                </Button>
            </section>

            <section className="pt-4 mt-auto">
                <div className="flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-500">
                    <Link to={`/${lang}/help`} className="hover:text-black transition-colors">{isRtl ? 'مدد' : 'Help'}</Link>
                    <Link to={`/${lang}/status`} className="hover:text-black transition-colors">{isRtl ? 'حالت' : 'Status'}</Link>
                    <Link to={`/${lang}/about`} className="hover:text-black transition-colors">{isRtl ? 'بابت' : 'About'}</Link>
                    <Link to={`/${lang}/privacy`} className="hover:text-black transition-colors">{isRtl ? 'رازداري' : 'Privacy'}</Link>
                    <Link to={`/${lang}/terms`} className="hover:text-black transition-colors">{isRtl ? 'شرطون' : 'Terms'}</Link>
                </div>
            </section>
        </aside>
    );
};

export default SidebarRight;
