import React from 'react';
import { Link } from 'react-router-dom';
import { BookmarkPlus, MinusCircle, MoreHorizontal, Star } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAuth } from '../contexts/AuthContext';
import LoginModal from './LoginModal';

const PostCard = ({ lang, title, excerpt, author = 'Anonymous', author_avatar, cover, date = '', readTime = '', category, slug, poet_slug = '', cat_slug = '' }) => {
    const isRtl = lang === 'sd';
    const { user } = useAuth();

    const BookmarkButton = (
        <Button variant="ghost" size="icon" className="h-8 w-8 text-gray-500 hover:text-black">
            <BookmarkPlus className="h-5 w-5" />
        </Button>
    );

    const safeDate = date || '';
    const safeReadTime = readTime || '';
    const safeAuthor = author || 'Anonymous';

    return (
        <article className={`py-8 first:pt-4 border-b border-gray-100 group ${isRtl ? 'text-right' : 'text-left'}`}>
            <div className="flex justify-between gap-8">
                <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                        <div className="h-5 w-5 rounded-full bg-gray-200 flex items-center justify-center text-[10px] shrink-0 overflow-hidden">
                            {author_avatar ? (
                                <img src={author_avatar.startsWith('http') ? author_avatar : `/${author_avatar}`} alt={safeAuthor} className="w-full h-full object-cover" />
                            ) : (
                                safeAuthor[0]
                            )}
                        </div>
                        <span className="text-sm">{safeAuthor}</span>
                    </div>

                    <Link to={slug ? `/${lang}/poet/${poet_slug}/${cat_slug}/${slug}` : '#'}>
                        <div className="flex justify-between items-start gap-4">
                            <div className="flex-1">
                                <h2 className={`text-xl md:text-2xl font-bold tracking-tight mb-2 leading-tight group-hover:opacity-80 transition-opacity ${isRtl ? 'font-arabic' : ''}`}>
                                    {title || 'Untitled'}
                                </h2>
                                {excerpt && (
                                    <p className={`text-gray-600 line-clamp-2 mb-4 text-sm md:text-base leading-relaxed ${isRtl ? 'font-arabic' : ''}`}>
                                        {excerpt}
                                    </p>
                                )}
                            </div>
                            {cover && (
                                <div className="w-20 h-20 md:w-28 md:h-28 shrink-0 overflow-hidden rounded-sm bg-gray-50 border border-gray-100">
                                    <img
                                        src={cover.startsWith('http') ? cover : `/${cover}`}
                                        alt={title}
                                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                </div>
                            )}
                        </div>
                    </Link>

                    <div className="flex items-center justify-between text-gray-500 text-sm">
                        <div className="flex items-center gap-3">
                            <Star className={`h-4 w-4 text-yellow-500 fill-yellow-500 ${isRtl ? 'ml-0' : ''}`} />
                            <span>{isRtl ? safeDate.replace(/(\d+)d ago/, '$1 ڏينھن اڳ ۾') : safeDate}</span>
                            <span>·</span>
                            <span>{isRtl ? safeReadTime.replace('min read', 'منٽ پڙهڻ') : safeReadTime}</span>
                        </div>

                        <div className="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            {user ? (
                                BookmarkButton
                            ) : (
                                <LoginModal trigger={BookmarkButton} isRtl={isRtl} />
                            )}
                            <Button variant="ghost" size="icon" className="h-8 w-8 text-gray-500 hover:text-black">
                                <MinusCircle className="h-5 w-5" />
                            </Button>
                            <Button variant="ghost" size="icon" className="h-8 w-8 text-gray-500 hover:text-black">
                                <MoreHorizontal className="h-5 w-5" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    );
};

export default PostCard;
