import React, { useState, useEffect, useRef } from 'react';
import { Search, X, Loader2, User, BookOpen, Calendar, ArrowRight } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import api from '../../admin/api/axios';
import { Dialog, DialogContent } from "@/components/ui/dialog";

const SearchDialog = ({ open, onOpenChange, lang = 'en' }) => {
    const isRtl = lang === 'sd';
    const [query, setQuery] = useState('');
    const [results, setResults] = useState({ poets: [], poetry: [], periods: [] });
    const [loading, setLoading] = useState(false);
    const debounceRef = useRef(null);
    const navigate = useNavigate();

    useEffect(() => {
        if (open) {
            setQuery('');
            setResults({ poets: [], poetry: [], periods: [] });
        }
    }, [open]);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);

        if (query.length < 2) {
            setResults({ poets: [], poetry: [], periods: [] });
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

    const hasResults = results.poets.length > 0 || results.poetry.length > 0 || results.periods.length > 0;

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
                                <div
                                    key={`p-${poet.id}`}
                                    onClick={() => handleSelect(`/${lang}/poet/${poet.slug}`)}
                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 cursor-pointer group transition-all duration-150"
                                >
                                    <div className="h-9 w-9 rounded-full bg-gray-100 overflow-hidden shrink-0">
                                        {poet.image ? (
                                            <img src={poet.image} alt={poet.name} className="h-full w-full object-cover" />
                                        ) : (
                                            <div className="h-full w-full flex items-center justify-center">
                                                <User className="h-4 w-4 text-gray-400" />
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className={`font-medium text-gray-900 ${isRtl ? 'font-arabic' : ''}`}>
                                            {poet.name}
                                        </div>
                                    </div>
                                    <ArrowRight className={`h-4 w-4 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity ${isRtl ? 'rotate-180' : ''}`} />
                                </div>
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
                                <div
                                    key={`pm-${poem.id}`}
                                    onClick={() => handleSelect(`/${lang}/poet/${poem.poet_slug}/${poem.cat_slug}/${poem.slug}`)}
                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 cursor-pointer group transition-all duration-150"
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
                                </div>
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
                                <div
                                    key={`prd-${period.id}`}
                                    onClick={() => handleSelect(`/${lang}/period`)}
                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 cursor-pointer group transition-all duration-150"
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
                                </div>
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
