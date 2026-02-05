import React, { useState, useEffect, useRef } from 'react';
import { Search, X, Loader2, User, BookOpen, Calendar, ArrowRight, Music, FileText } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import api from '../../admin/api/axios';
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { Link } from 'react-router-dom';

const SearchDialog = ({ open, onOpenChange, lang = 'en' }) => {
    const isRtl = lang === 'sd';
    const [query, setQuery] = useState('');
    const [results, setResults] = useState({ poets: [], poetry: [], periods: [] });
    const [loading, setLoading] = useState(false);
    const debounceRef = useRef(null);
    const navigate = useNavigate();

    useEffect(() => {
        if (open) {
            // Auto focus logic handled by Dialog typically, but reset query
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
                // Pass lang in header if needed, axois config usually handles generic headers
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
            <DialogContent className="sm:max-w-[550px] p-0 gap-0 overflow-hidden bg-white/95 backdrop-blur-xl border-gray-200 shadow-2xl">
                <div className="flex items-center border-b border-gray-100 px-4 py-3" dir={isRtl ? 'rtl' : 'ltr'}>
                    <Search className="h-5 w-5 text-gray-400 shrink-0" />
                    <input
                        className="flex-1 bg-transparent px-3 py-1 text-base outline-none placeholder:text-gray-400 font-normal"
                        placeholder={isRtl ? "ڳوليو..." : "Type to search..."}
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        autoFocus
                    />
                    {loading && <Loader2 className="h-4 w-4 animate-spin text-gray-400" />}
                    {!loading && query && (
                        <button onClick={() => setQuery('')}>
                            <X className="h-4 w-4 text-gray-400 hover:text-gray-600" />
                        </button>
                    )}
                </div>

                <div className="max-h-[60vh] overflow-y-auto p-2" dir={isRtl ? 'rtl' : 'ltr'}>
                    {!query && (
                        <div className="py-12 text-center text-sm text-gray-400">
                            {isRtl ? 'شاعر، شاعري، يا دور ڳوليو' : 'Search for poets, poetry, or historical periods...'}
                        </div>
                    )}

                    {query && !loading && !hasResults && (
                        <div className="py-12 text-center text-sm text-gray-500">
                            {isRtl ? 'ڪو به نتيجو نه مليو.' : 'No results found.'}
                        </div>
                    )}

                    {results.poets.length > 0 && (
                        <div className="mb-4">
                            <h3 className="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                {isRtl ? 'شاعر' : 'Poets'}
                            </h3>
                            {results.poets.map((poet) => (
                                <div
                                    key={`p-${poet.id}`}
                                    onClick={() => handleSelect(`/${lang}/poet/${poet.slug}`)}
                                    className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 cursor-pointer group transition-colors"
                                >
                                    <div className="h-8 w-8 rounded-full bg-gray-200 overflow-hidden shrink-0 border border-gray-100">
                                        {poet.image ? (
                                            <img src={poet.image} alt={poet.name} className="h-full w-full object-cover" />
                                        ) : (
                                            <User className="h-full w-full p-1.5 text-gray-400" />
                                        )}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className={`font-medium text-gray-900 group-hover:text-black ${isRtl ? 'font-arabic' : 'font-sans'}`}>
                                            {poet.name}
                                        </div>
                                    </div>
                                    <ArrowRight className={`h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity ${isRtl ? 'rotate-180' : ''}`} />
                                </div>
                            ))}
                        </div>
                    )}

                    {results.poetry.length > 0 && (
                        <div className="mb-4">
                            <h3 className="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                {isRtl ? 'شاعري' : 'Poetry'}
                            </h3>
                            {results.poetry.map((poem) => (
                                <div
                                    key={`pm-${poem.id}`}
                                    onClick={() => handleSelect(`/${lang}/poet/${poem.poet_slug}/${poem.cat_slug}/${poem.slug}`)} // Assuming generic link structure
                                    className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 cursor-pointer group transition-colors"
                                >
                                    <div className="h-8 w-8 rounded-md bg-gray-50 flex items-center justify-center shrink-0 border border-gray-100 text-gray-500">
                                        <BookOpen className="h-4 w-4" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className={`font-medium text-gray-900 group-hover:text-black truncate ${isRtl ? 'font-arabic' : 'font-sans'}`}>
                                            {poem.title}
                                        </div>
                                        <div className={`text-xs text-gray-500 ${isRtl ? 'font-arabic' : 'font-sans'}`}>
                                            {poem.poet_name}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {results.periods.length > 0 && (
                        <div className="mb-2">
                            <h3 className="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                {isRtl ? 'تاريخي دور' : 'Historical Periods'}
                            </h3>
                            {results.periods.map((period) => (
                                <div
                                    key={`prd-${period.id}`}
                                    onClick={() => handleSelect(`/${lang}/period`)} // Just links to main Period feed for now, ideally anchor?
                                    className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 cursor-pointer group transition-colors"
                                >
                                    <div className="h-8 w-8 rounded-md bg-gray-50 flex items-center justify-center shrink-0 border border-gray-100 text-gray-500">
                                        <Calendar className="h-4 w-4" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className={`font-medium text-gray-900 group-hover:text-black ${isRtl ? 'font-arabic' : 'font-sans'}`}>
                                            {period.title}
                                        </div>
                                        <div className="text-xs text-gray-500">
                                            {period.date_range}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="bg-gray-50 px-4 py-2 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                    <div className="flex gap-2">
                        <span className="bg-white border rounded px-1.5 py-0.5 shadow-sm">esc</span> to close
                        <span className="bg-white border rounded px-1.5 py-0.5 shadow-sm">↵</span> to select
                    </div>
                    <div>
                        Baakh Search
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default SearchDialog;
