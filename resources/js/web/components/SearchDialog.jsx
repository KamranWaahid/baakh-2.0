import React, { useState, useEffect, useRef } from 'react';
import { Search, X, Loader2, User, BookOpen, Calendar, ArrowRight, Tags, History } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import api from '../../admin/api/axios';
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { getImageUrl } from '../utils/url';

const SearchDialog = ({ open, onOpenChange, lang = 'en' }) => {
    const isRtl = lang === 'sd';
    const [query, setQuery] = useState('');
    const [results, setResults] = useState({ poets: [], poetry: [], periods: [], categories: [], tags: [], dictionary: [] });
    const [loading, setLoading] = useState(false);
    const debounceRef = useRef(null);
    const navigate = useNavigate();

    useEffect(() => {
        if (open) {
            setQuery('');
            setResults({ poets: [], poetry: [], periods: [], categories: [], tags: [], dictionary: [] });
        }
    }, [open]);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);

        if (query.length < 2) {
            setResults({ poets: [], poetry: [], periods: [], categories: [], tags: [], dictionary: [] });
            return;
        }

        setLoading(true);
        debounceRef.current = setTimeout(async () => {
            try {
                const response = await api.get(`/api/v1/search?query=${encodeURIComponent(query)}`, {
                    headers: { 'Accept-Language': lang }
                });
                setResults(response.data);
            } catch (error) {
                console.error("Search failed", error);
            } finally {
                setLoading(false);
            }
        }, 300);

        return () => clearTimeout(debounceRef.current);
    }, [query, lang]);

    const handleSelect = (path) => {
        onOpenChange(false);
        navigate(path);
    };

    const hasResults = results.poets.length > 0 || results.poetry.length > 0 || results.periods.length > 0 || results.categories.length > 0 || results.tags.length > 0 || results.dictionary.length > 0;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent hideClose className="sm:max-w-[520px] p-0 gap-0 overflow-hidden bg-white border-none shadow-2xl rounded-2xl">
                {/* Search Input - Clean & Borderless */}
                <div className="flex items-center gap-3 px-5 py-4" dir={isRtl ? 'rtl' : 'ltr'}>
                    <Search className="h-5 w-5 text-gray-400 shrink-0" />
                    <input
                        className="flex-1 bg-transparent text-lg outline-none border-none focus:border-none focus:ring-0 focus:outline-none placeholder:text-gray-400 font-normal"
                        placeholder={isRtl ? "ڳوليو..." : "Search..."}
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        autoFocus
                    />
                    {loading && <Loader2 className="h-4 w-4 animate-spin text-gray-400" />}
                    {!loading && query && (
                        <button
                            onClick={() => setQuery('')}
                            className="p-1 rounded-full hover:bg-gray-100 transition-colors"
                        >
                            <X className="h-4 w-4 text-gray-400" />
                        </button>
                    )}
                </div>

                {/* Results Area */}
                <div className="h-[350px] overflow-y-auto scroll-smooth" dir={isRtl ? 'rtl' : 'ltr'}>
                    {!query && (
                        <div className="py-16 text-center text-sm text-gray-400">
                            {isRtl ? 'شاعر، شاعري، يا دور ڳوليو...' : 'Search for poets, poetry, or periods...'}
                        </div>
                    )}

                    {query && !loading && !hasResults && (
                        <div className="py-16 text-center text-sm text-gray-400">
                            {isRtl ? 'ڪو به نتيجو نه مليو' : 'No results found'}
                        </div>
                    )}

                    {/* Poets */}
                    {results.poets.length > 0 && (
                        <div className="px-2 pb-2">
                            <div className="px-3 py-2 text-[11px] font-medium text-gray-400 uppercase tracking-wider">
                                {isRtl ? 'شاعر' : 'Poets'}
                            </div>
                            {results.poets.map((poet) => (
                                <button
                                    key={`p-${poet.id}`}
                                    onClick={() => handleSelect(`/${lang}/poet/${poet.slug}`)}
                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer group transition-all duration-150 w-full text-start outline-none focus:bg-gray-50 focus:ring-2 focus:ring-primary/20"
                                >
                                    <div className="h-9 w-9 rounded-full bg-gray-100 overflow-hidden shrink-0">
                                        <img
                                            src={getImageUrl(poet.image, 'poet')}
                                            alt={poet.name}
                                            className="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className={`font-medium text-gray-900 ${isRtl ? 'font-arabic' : ''}`}>
                                            {poet.name}
                                        </div>
                                    </div>
                                    <ArrowRight className={`h-4 w-4 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity ${isRtl ? 'rotate-180' : ''}`} />
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Poetry */}
                    {results.poetry.length > 0 && (
                        <div className="px-2 pb-2">
                            <div className="px-3 py-2 text-[11px] font-medium text-gray-400 uppercase tracking-wider">
                                {isRtl ? 'شاعري' : 'Poetry'}
                            </div>
                            {results.poetry.map((poem) => (
                                <button
                                    key={`pm-${poem.id}`}
                                    onClick={() => handleSelect(`/${lang}/poet/${poem.poet_slug}/${poem.cat_slug}/${poem.slug}`)}
                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer group transition-all duration-150 w-full text-start outline-none focus:bg-gray-50 focus:ring-2 focus:ring-primary/20"
                                    aria-label={`View poem: ${poem.title} by ${poem.poet_name}`}
                                >
                                    <div className="h-9 w-9 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
                                        <BookOpen className="h-4 w-4 text-gray-400" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className={`font-medium text-gray-900 truncate ${isRtl ? 'font-arabic' : ''}`}>
                                            {poem.title}
                                        </div>
                                        <div className={`text-xs text-gray-400 ${isRtl ? 'font-arabic' : ''}`}>
                                            {poem.poet_name}
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Categories */}
                    {results.categories.length > 0 && (
                        <div className="px-2 pb-2">
                            <div className="px-3 py-2 text-[11px] font-medium text-gray-400 uppercase tracking-wider">
                                {isRtl ? 'صنف' : 'Categories'}
                            </div>
                            {results.categories.map((cat) => (
                                <button
                                    key={`cat-${cat.id}`}
                                    onClick={() => handleSelect(`/${lang}/${cat.slug}`)}
                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer group transition-all duration-150 w-full text-start outline-none focus:bg-gray-50 focus:ring-2 focus:ring-primary/20"
                                >
                                    <div className="h-9 w-9 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
                                        <Tags className="h-4 w-4 text-gray-400" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className={`font-medium text-gray-900 ${isRtl ? 'font-arabic' : ''}`}>
                                            {cat.name}
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Tags */}
                    {results.tags.length > 0 && (
                        <div className="px-2 pb-2">
                            <div className="px-3 py-2 text-[11px] font-medium text-gray-400 uppercase tracking-wider">
                                {isRtl ? 'ٽيگ' : 'Tags'}
                            </div>
                            {results.tags.map((tag) => (
                                <button
                                    key={`tag-${tag.id}`}
                                    onClick={() => handleSelect(`/${lang}/poetry?tag=${tag.slug}`)}
                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer group transition-all duration-150 w-full text-start outline-none focus:bg-gray-50 focus:ring-2 focus:ring-primary/20"
                                >
                                    <div className="h-9 w-9 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
                                        <span className="text-gray-400 font-bold text-sm">#</span>
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className={`font-medium text-gray-900 ${isRtl ? 'font-arabic' : ''}`}>
                                            {tag.name}
                                        </div>
                                        <div className="text-xs text-gray-400">
                                            {tag.tag_type}
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Dictionary */}
                    {results.dictionary.length > 0 && (
                        <div className="px-2 pb-2">
                            <div className="px-3 py-2 text-[11px] font-medium text-gray-400 uppercase tracking-wider">
                                {isRtl ? 'لغت' : 'Dictionary'}
                            </div>
                            {results.dictionary.map((lemma) => (
                                <button
                                    key={`lem-${lemma.id}`}
                                    onClick={() => handleSelect(`/${lang}/dictionary`)}
                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer group transition-all duration-150 w-full text-start outline-none focus:bg-gray-50 focus:ring-2 focus:ring-primary/20"
                                >
                                    <div className="h-9 w-9 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
                                        <History className="h-4 w-4 text-gray-400" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className={`font-medium text-gray-900 ${isRtl ? 'font-arabic' : ''}`}>
                                            {lemma.lemma}
                                        </div>
                                        <div className="text-xs text-gray-400">
                                            {lemma.transliteration}
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Periods */}
                    {results.periods.length > 0 && (
                        <div className="px-2 pb-2">
                            <div className="px-3 py-2 text-[11px] font-medium text-gray-400 uppercase tracking-wider">
                                {isRtl ? 'دور' : 'Periods'}
                            </div>
                            {results.periods.map((period) => (
                                <button
                                    key={`prd-${period.id}`}
                                    onClick={() => handleSelect(`/${lang}/period`)}
                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer group transition-all duration-150 w-full text-start outline-none focus:bg-gray-50 focus:ring-2 focus:ring-primary/20"
                                    aria-label={`View period: ${period.title}`}
                                >
                                    <div className="h-9 w-9 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
                                        <Calendar className="h-4 w-4 text-gray-400" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className={`font-medium text-gray-900 ${isRtl ? 'font-arabic' : ''}`}>
                                            {period.title}
                                        </div>
                                        <div className="text-xs text-gray-400">
                                            {period.date_range}
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                {/* Footer - Ultra Minimal */}
                <div className="px-5 py-3 flex items-center justify-between text-[11px] text-gray-400">
                    <div className="flex items-center gap-3">
                        <span className="flex items-center gap-1">
                            <kbd className="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-medium">esc</kbd>
                            <span>close</span>
                        </span>
                        <span className="flex items-center gap-1">
                            <kbd className="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-medium">↵</kbd>
                            <span>select</span>
                        </span>
                    </div>
                    <span className="text-gray-400">Baakh</span>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default SearchDialog;
