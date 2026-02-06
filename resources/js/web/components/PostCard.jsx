import React from 'react';
import { Link } from 'react-router-dom';
import { Sparkles, User } from 'lucide-react';
import PoemActionBar from './PoemActionBar';
import { formatDate } from '@/lib/date-utils';

const PostCard = ({ lang, title, excerpt, author = 'Anonymous', author_avatar, cover, date = '', readTime = '', category, slug, poet_slug = '', cat_slug = '', showStar = true, likes = 0, id, is_couplet = false }) => {
    const isRtl = lang === 'sd';

    const safeDate = date || '';
    const safeReadTime = readTime || '';
    const safeAuthor = author || 'Anonymous';

    // Construct a pseudo-poem object for PoemActionBar
    const postPoem = {
        id,
        likes,
        slug,
        poet_slug,
        cat_slug
    };

    return (
        <article className={`py-8 first:pt-4 border-b border-gray-100 group ${isRtl ? 'text-right' : 'text-left'} ${is_couplet ? 'text-center' : ''}`}>
            <div className="flex justify-between gap-8">
                <div className="flex-1">
                    <div className={`flex flex-wrap items-center gap-2 mb-2 text-sm ${is_couplet ? 'justify-center' : ''}`}>
                        <Link
                            to={poet_slug ? `/${lang}/poet/${poet_slug}` : '#'}
                            className={`flex items-center gap-2 transition-opacity w-fit hover:opacity-80`}
                        >
                            <div className={`h-5 w-5 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 shrink-0 border border-gray-100 overflow-hidden`}>
                                {author_avatar ? (
                                    <img
                                        src={author_avatar.startsWith('http') ? author_avatar : `/${author_avatar}`}
                                        alt={safeAuthor}
                                        className="w-full h-full object-cover"
                                    />
                                ) : (
                                    <User className="h-3 w-3" />
                                )}
                            </div>
                            <span className="font-medium text-gray-900 hover:underline">{safeAuthor}</span>
                        </Link>

                        {!is_couplet && (
                            <>
                                <span className="text-gray-300">·</span>
                                <span className="text-gray-500">{formatDate(safeDate, lang)}</span>
                                <span className="text-gray-300">·</span>
                                <span className="text-gray-500">{isRtl ? safeReadTime.replace('min read', 'منٽ پڙهڻ') : safeReadTime}</span>
                                {showStar && <Sparkles className={`h-3.5 w-3.5 text-yellow-500 fill-yellow-500 ${isRtl ? 'mr-1' : 'ml-1'}`} />}
                            </>
                        )}
                    </div>

                    {is_couplet ? (
                        <div className="flex justify-center items-start gap-4">
                            <div className="flex-1">
                                {excerpt && (
                                    <p className={`text-gray-600 mb-4 leading-relaxed ${isRtl ? 'font-arabic' : ''} whitespace-pre-wrap text-center text-xl md:text-2xl font-medium py-6`}>
                                        {excerpt}
                                    </p>
                                )}
                            </div>
                        </div>
                    ) : (
                        <Link to={slug ? `/${lang}/poet/${poet_slug}/${cat_slug}/${slug}` : '#'}>
                            <div className="flex justify-between items-start gap-4">
                                <div className="flex-1">
                                    <h2 className={`text-xl md:text-2xl font-bold tracking-tight mb-2 leading-tight group-hover:underline group-hover:opacity-80 transition-all ${isRtl ? 'font-arabic' : ''}`}>
                                        {title || 'Untitled'}
                                    </h2>
                                </div>
                                {cover && (
                                    <div className="w-20 h-20 md:w-28 md:h-28 shrink-0 overflow-hidden rounded-sm bg-gray-50 border border-gray-100">
                                        <img
                                            src={cover.startsWith('http') ? cover : `/${cover}`}
                                            alt={title}
                                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        />
                                    </div>
                                )}
                            </div>
                        </Link>
                    )}

                    <PoemActionBar
                        poem={postPoem}
                        lang={lang}
                        className={is_couplet ? 'justify-center gap-10' : ''}
                        leftContent={is_couplet ? (
                            <div className="flex items-center gap-3 text-sm">
                                {showStar && <Sparkles className={`h-4 w-4 text-yellow-500 fill-yellow-500 ml-1`} />}
                                <span>{formatDate(safeDate, lang)}</span>
                                <span className="text-gray-300">·</span>
                            </div>
                        ) : null}
                    />
                </div>
            </div>
        </article>
    );
};

export default PostCard;
