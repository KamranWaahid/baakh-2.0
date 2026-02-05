import React from 'react';
import { BookmarkPlus, MinusCircle, MoreHorizontal, Star } from 'lucide-react';
import { Button } from '@/components/ui/button';

const PostCard = ({ lang, title, excerpt, author, date, readTime, category }) => {
    const isRtl = lang === 'sd';

    return (
        <article className="py-8 first:pt-4 border-b border-gray-100 group">
            <div className="flex justify-between gap-8">
                <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                        <div className="h-5 w-5 rounded-full bg-gray-200 flex items-center justify-center text-[10px] font-bold">
                            {author[0]}
                        </div>
                        <span className="text-sm font-medium">{author}</span>
                        {category && (
                            <>
                                <span className="text-gray-400">in</span>
                                <span className="text-sm font-medium">{category}</span>
                            </>
                        )}
                    </div>

                    <h2 className={`text-xl md:text-2xl font-black mb-1 leading-tight group-hover:opacity-80 transition-opacity ${isRtl ? 'font-arabic' : ''}`}>
                        {title}
                    </h2>

                    <p className="text-gray-500 text-[15px] line-clamp-2 md:line-clamp-3 mb-4 leading-relaxed lg:max-w-[500px]">
                        {excerpt}
                    </p>

                    <div className="flex items-center justify-between text-gray-500 text-sm">
                        <div className="flex items-center gap-3">
                            <Star className="h-4 w-4 text-black fill-black" />
                            <span>{date}</span>
                            <span>·</span>
                            <span>{readTime}</span>
                        </div>

                        <div className="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <Button variant="ghost" size="icon" className="h-8 w-8 text-gray-500 hover:text-black">
                                <BookmarkPlus className="h-5 w-5" />
                            </Button>
                            <Button variant="ghost" size="icon" className="h-8 w-8 text-gray-500 hover:text-black">
                                <MinusCircle className="h-5 w-5" />
                            </Button>
                            <Button variant="ghost" size="icon" className="h-8 w-8 text-gray-500 hover:text-black">
                                <MoreHorizontal className="h-5 w-5" />
                            </Button>
                        </div>
                    </div>
                </div>

                <div className="w-20 h-20 md:w-36 md:h-24 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                    <div className="w-full h-full bg-gradient-to-br from-gray-50 to-gray-200" />
                </div>
            </div>
        </article>
    );
};

export default PostCard;
