import React from 'react';
import { useParams, Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Star, MessageCircle, Share2, MoreHorizontal, BookmarkPlus, PlayCircle } from 'lucide-react';
import { Separator } from '@/components/ui/separator';
import PaywallCTA from './PaywallCTA';

const PoemDetail = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { slug, category } = useParams();

    // Mock Data
    const poem = {
        title: "The Voice of Revolution",
        content: `
            <p>Rise, O Sindhi! The dawn is waiting for your awakening.</p>
            <p>The chains of slumber have held you long enough,</p>
            <p>See how the sun kisses the Indus, turning water into gold.</p>
            <br/>
            <p>Do you not hear the whispers of the wind?</p>
            <p>It carries the songs of our ancestors,</p>
            <p>Songs of courage, of love, of defiance.</p>
            <br/>
            <p>The desert of Thar is calling your name,</p>
            <p>The mountains of Kirthar stand as witnesses.</p>
            <p>It is time to break the silence,</p>
            <p>And let the world hear the roar of the Indus.</p>
        `,
        author: "Sheikh Ayaz",
        date: "Nov 11, 2025",
        readTime: "5 min read",
        claps: "1.3K",
        comments: 47
    };

    const footerPoems = [
        {
            title: "The Silent Echo",
            excerpt: "In the depth of the night, when stars align, a whisper traverses the dunes of time...",
            author: "Sheikh Ayaz",
            date: "Nov 12",
            claps: "840",
            comments: 12
        },
        {
            title: "Crimson Horizon",
            excerpt: "Red is not just a color, it is the blood of the weary sun setting upon the Indus...",
            author: "Sheikh Ayaz",
            date: "Nov 10",
            claps: "1.2K",
            comments: 45
        },
        {
            title: "Whispers of the Wind",
            excerpt: "Listen closely, for the breeze tells stories of old, of lovers lost and battles won...",
            author: "Sheikh Ayaz",
            date: "Oct 28",
            claps: "2.5K",
            comments: 89
        },
        {
            title: "The Poet's Lament",
            excerpt: "Why do words failing me now? When the world needs a song, my throat is dry...",
            author: "Sheikh Ayaz",
            date: "Oct 15",
            claps: "900",
            comments: 30
        }
    ];

    const recommendedPoems = [
        {
            title: "Shah Jo Risalo: Sur Kalyan",
            excerpt: "A deep dive into the melody of peace and the yearning of the soul...",
            author: "Shah Latif",
            date: "Dec 01",
            claps: "3.4K",
            comments: 120
        },
        {
            title: "Modern Sindhi Resistance",
            excerpt: "How poetry shaped the political landscape of the 20th century...",
            author: "Ustad Bukhari",
            date: "Nov 20",
            claps: "1.1K",
            comments: 56
        },
        {
            title: "The Sufi's Dance",
            excerpt: "Whirling in the trance of divine love, losing self to find the Truth...",
            author: "Sachal Sarmast",
            date: "Nov 18",
            claps: "2K",
            comments: 67
        },
        {
            title: "Echoes of Mohenjo-daro",
            excerpt: "The bricks speak of a civilization that knew peace before war was invented...",
            author: "Shaikh Ayaz",
            date: "Nov 05",
            claps: "1.5K",
            comments: 40
        }
    ];

    return (
        <div className="w-full flex flex-col items-center py-12 px-4 md:px-8 bg-white">
            <article className="w-full max-w-[680px] mb-20">
                {/* Header */}
                <header className="mb-8">
                    {/* Badge */}
                    <div className="flex items-center gap-2 mb-6 text-yellow-500">
                        <Star className="h-4 w-4 fill-yellow-500" />
                        <span className="text-sm font-medium text-gray-700">Member-only story</span>
                    </div>

                    <h1 className={`text-3xl md:text-[40px] font-bold tracking-tight text-gray-900 leading-tight mb-6 ${isRtl ? 'font-arabic' : ''}`}>
                        {poem.title}
                    </h1>

                    <div className="flex items-center gap-4 mb-8">
                        <div className="h-11 w-11 rounded-full bg-gray-200 flex items-center justify-center text-sm font-bold shrink-0">
                            SA
                        </div>
                        <div className="flex flex-col">
                            <div className="flex items-center gap-2">
                                <span className="font-medium text-gray-900">{poem.author}</span>
                                <Button variant="link" className="text-green-700 p-0 h-auto font-medium hidden">Follow</Button>
                                {/* Replaced Green follow with simple link style or black per branding */}
                                <button className="text-gray-500 hover:text-black hover:underline text-sm">Follow</button>
                            </div>
                            <div className="flex items-center gap-2 text-sm text-gray-500">
                                <span>{poem.readTime}</span>
                                <span>·</span>
                                <span>{poem.date}</span>
                            </div>
                        </div>
                    </div>

                    <Separator className="my-6" />

                    <div className="flex items-center justify-between text-gray-500 mb-8">
                        <div className="flex items-center gap-6">
                            <button className="flex items-center gap-2 hover:text-black transition-colors">
                                <span className="text-sm">{poem.claps}</span>
                            </button>
                            <button className="flex items-center gap-2 hover:text-black transition-colors">
                                <MessageCircle className="h-5 w-5" />
                                <span className="text-sm">{poem.comments}</span>
                            </button>
                        </div>
                        <div className="flex items-center gap-4">
                            <button className="hover:text-black"><Share2 className="h-5 w-5" /></button>
                            <button className="hover:text-black"><BookmarkPlus className="h-5 w-5" /></button>
                            <button className="hover:text-black"><MoreHorizontal className="h-5 w-5" /></button>
                        </div>
                    </div>
                </header>

                {/* Body */}
                <div className={`prose prose-lg max-w-none text-gray-00 font-serif leading-relaxed text-[20px] ${isRtl ? 'font-arabic' : ''}`}>
                    <div dangerouslySetInnerHTML={{ __html: poem.content }} />

                    <p className="mt-8 italic text-base text-gray-500">
                        Excerpt from "{category}" collection.
                    </p>
                </div>

                <Separator className="my-12 opacity-50" />

                {/* Author Footer */}
                <div className="flex items-start justify-between mb-12 font-sans">
                    <div className="flex gap-4">
                        <div className="h-[64px] w-[64px] rounded-full bg-gray-100 flex items-center justify-center text-xl font-bold text-gray-500 shrink-0 overflow-hidden">
                            SA
                        </div>
                        <div className="flex flex-col pt-1">
                            <h3 className="text-[20px] font-bold text-gray-900 mb-1 leading-tight">Written by {poem.author}</h3>
                            <div className="flex items-center gap-1 text-[14px] text-gray-500 mb-2">
                                <span>2.3K followers</span>
                                <span>·</span>
                                <span>9 following</span>
                            </div>
                            <p className="text-[15px] text-gray-600 leading-snug">Revolutionary poet of Sindh.</p>
                        </div>
                    </div>
                    <Button variant="outline" className="rounded-full border-gray-300 text-black hover:border-black hover:bg-transparent px-5 h-[38px] text-[14px]">
                        Follow
                    </Button>
                </div>

                <Separator className="my-12 opacity-50" />

                <PaywallCTA authorName={poem.author} isRtl={isRtl} />

            </article>

            {/* Footer Recommendations - More Background/Full Width feel but centered */}
            <div className="w-full max-w-[680px] bg-gray-50/50 p-8 rounded-xl border border-gray-100">

                {/* Section 1: More from Author */}
                <div className="mb-12">
                    <h3 className="font-bold text-base text-gray-900 mb-6">More from {poem.author}</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-8">
                        {footerPoems.map((p, i) => (
                            <div key={i} className="flex flex-col gap-2">
                                <div className="text-xs text-gray-500 mb-1">{p.date}</div>
                                <h4 className="font-bold text-lg text-gray-900 leading-tight">{p.title}</h4>
                                <p className="text-gray-600 text-[14px] leading-snug line-clamp-3 font-serif">{p.excerpt}</p>
                                <div className="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                    <div className="flex items-center gap-1">
                                        <span role="img" aria-label="claps">👏</span>
                                        <span>{p.claps}</span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <MessageCircle className="h-3 w-3" />
                                        <span>{p.comments}</span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Section 2: Recommended */}
                <div className="mb-12">
                    <h3 className="font-bold text-base text-gray-900 mb-6">Recommended from Baakh</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-8">
                        {recommendedPoems.map((p, i) => (
                            <div key={i} className="flex flex-col gap-2">
                                <div className="flex items-center gap-2 mb-1">
                                    <div className="h-5 w-5 rounded-full bg-gray-200 flex items-center justify-center text-[8px] font-bold">
                                        {p.author.split(' ').map(n => n[0]).join('')}
                                    </div>
                                    <span className="text-xs font-bold text-gray-900">{p.author}</span>
                                </div>
                                <h4 className="font-bold text-lg text-gray-900 leading-tight">{p.title}</h4>
                                <p className="text-gray-600 text-[14px] leading-snug line-clamp-3 font-serif">{p.excerpt}</p>
                                <div className="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                    <span>{p.date}</span>
                                    <div className="flex items-center gap-1">
                                        <span role="img" aria-label="claps">👏</span>
                                        <span>{p.claps}</span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* See All Button */}
                <div className="flex justify-center mt-8">
                    <Button variant="outline" className="rounded-full border-gray-300 text-gray-900 hover:border-black px-8 py-6 h-auto text-[15px]">
                        See all from {poem.author}
                    </Button>
                </div>

            </div>
        </div>
    );
};

export default PoemDetail;
