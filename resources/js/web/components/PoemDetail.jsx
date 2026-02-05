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

    return (
        <div className="w-full flex justify-center py-12 px-4 md:px-8 bg-white">
            <article className="w-full max-w-[680px]">
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
        </div>
    );
};

export default PoemDetail;
