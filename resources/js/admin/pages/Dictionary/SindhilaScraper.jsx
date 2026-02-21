import React, { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { toast } from 'sonner';
import {
    Search, Globe, Loader2, Copy, CheckCircle2, Plus, FileJson, ArrowRightLeft, Square, Database
} from 'lucide-react';

const SindhilaScraper = () => {
    const [word, setWord] = useState('');
    const [results, setResults] = useState(null);
    const [copied, setCopied] = useState(false);

    // Auto Scraper States
    const [isAutoScraping, setIsAutoScraping] = useState(false);
    const [autoScrapeLogs, setAutoScrapeLogs] = useState([]);
    const [remainingStats, setRemainingStats] = useState(null);
    const autoScrapeRef = useRef(isAutoScraping);

    const navigate = useNavigate();

    // Keep ref in sync for the recursive loop
    useEffect(() => {
        autoScrapeRef.current = isAutoScraping;
    }, [isAutoScraping]);

    const scrapeWord = useMutation({
        mutationFn: (searchWord) => api.post('/api/admin/dictionary/scrape-word', { word: searchWord }),
        onSuccess: (res) => {
            setResults(res.data);
            if (res.data.results.length === 0) {
                toast.info('No definitions found on Sindhila for this word.');
            } else {
                toast.success(`Found ${res.data.results.length} definitions!`);
            }
        },
        onError: (err) => {
            toast.error(err.response?.data?.error || 'Failed to reach Sindhila website.');
            setResults(null);
        }
    });

    const createLemma = useMutation({
        mutationFn: async (lemmaString) => {
            // First create the lemma
            const res = await api.post('/api/admin/dictionary/lemmas', { lemma: lemmaString, status: 'approved' });

            // Then add all scraped senses to it automatically
            if (results && results.results.length > 0) {
                const addPromises = results.results
                    .filter(sense => sense.text && sense.text.trim().length > 0)
                    .map(sense =>
                        api.post(`/api/admin/dictionary/senses`, {
                            lemma_id: res.data.id,
                            definition: sense.text,
                            domain: sense.source,
                        })
                    );
                await Promise.allSettled(addPromises);
            }
            return res.data;
        },
        onSuccess: (data) => {
            toast.success('Word and all scraped senses imported! Redirecting...');
            navigate(`/admin/dictionary/lemmas/${data.id}`);
        },
        onError: () => toast.error('Failed to create word in dictionary.')
    });

    // Recursive Auto-Scraper core loop
    const runAutoScrapeStep = async () => {
        if (!autoScrapeRef.current) return; // Exit if paused

        try {
            const res = await api.post('/api/admin/dictionary/auto-scrape-step');

            if (res.data.message === 'No more missing words to check!') {
                setIsAutoScraping(false);
                toast.success('Auto-scraper finished! All missing words checked.');
                return;
            }

            // Put latest log on top
            setAutoScrapeLogs(prev => [res.data, ...prev].slice(0, 50));
            if (res.data.remaining !== undefined) {
                setRemainingStats(res.data.remaining);
            }

            // If we are still actively scraping, queue the next run after 2000ms pause
            if (autoScrapeRef.current) {
                setTimeout(runAutoScrapeStep, 2000);
            }

        } catch (err) {
            console.error(err);
            toast.error("Auto-Scrape step failed. Pausing to prevent server overload.");
            setIsAutoScraping(false);
        }
    };

    const toggleAutoScrape = () => {
        if (!isAutoScraping) {
            setIsAutoScraping(true);
            setResults(null); // Clear manual results
            toast.info("Started Background Scraper. Please leave this page open.");

            // Kick off the loop
            setTimeout(runAutoScrapeStep, 500);
        } else {
            setIsAutoScraping(false);
            toast.info("Auto-Scraper paused.");
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        if (word.trim()) {
            setIsAutoScraping(false);
            scrapeWord.mutate(word.trim());
        }
    };

    const handleCopyJson = () => {
        if (!results) return;
        navigator.clipboard.writeText(JSON.stringify(results, null, 2));
        setCopied(true);
        toast.success('Scraped data JSON copied to clipboard!');
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Sindhila Scraper</h2>
                    <p className="text-muted-foreground mt-1">
                        Query the external Sindhila Dictionary explicitly or run the continuous background bulk-importer.
                    </p>
                </div>
                {remainingStats !== null && (
                    <Badge variant="outline" className="text-lg py-2 px-4 shadow-sm border-blue-500/20 bg-blue-50/50">
                        {remainingStats.toLocaleString()} words remaining in Corpus queue
                    </Badge>
                )}
            </div>

            <Card className="border-primary/20 bg-primary/5">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Globe className="h-5 w-5 text-primary" /> Parse Sindhila Online
                    </CardTitle>
                    <CardDescription>
                        Enter any Sindhi word to fetch its formatted definitions from dic.sindhila.edu.pk without having to create it in our database first.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSearch} className="flex gap-4 items-center">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-3.5 h-6 w-6 text-muted-foreground" />
                            <Input
                                value={word}
                                onChange={(e) => setWord(e.target.value)}
                                className="pl-12 font-arabic text-3xl py-8 text-right bg-background"
                                dir="rtl"
                                placeholder="سنڌي لفظ ڳوليو..."
                                autoFocus
                            />
                        </div>

                        {word.trim() ? (
                            <Button
                                type="submit"
                                size="lg"
                                className="h-16 px-8 text-lg min-w-[200px]"
                                disabled={scrapeWord.isPending}
                            >
                                {scrapeWord.isPending ? <Loader2 className="animate-spin mr-2 h-5 w-5" /> : <Globe className="mr-2 h-5 w-5" />}
                                Scrape Now
                            </Button>
                        ) : (
                            <Button
                                type="button"
                                size="lg"
                                onClick={toggleAutoScrape}
                                variant={isAutoScraping ? "destructive" : "default"}
                                className={`h-16 px-8 text-lg min-w-[200px] ${!isAutoScraping && 'bg-blue-600 hover:bg-blue-700'}`}
                            >
                                {isAutoScraping ? (
                                    <><Square className="mr-2 h-5 w-5 fill-current" /> Stop Scraper</>
                                ) : (
                                    <><Database className="mr-2 h-5 w-5" /> Auto-Scrape All Words</>
                                )}
                            </Button>
                        )}
                    </form>
                </CardContent>
            </Card>

            {/* Manual Scrape Results Table */}
            {!isAutoScraping && results && (
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between bg-muted/30">
                        <div>
                            <CardTitle className="text-2xl font-arabic">{results.word}</CardTitle>
                            <CardDescription className="mt-1">
                                {results.results.length} result(s) scraped from Sindhila
                            </CardDescription>
                        </div>

                        <div className="flex gap-2">
                            <Button variant="outline" onClick={handleCopyJson} disabled={results.results.length === 0}>
                                {copied ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <FileJson className="h-4 w-4 mr-2" />}
                                {copied ? 'Copied' : 'Copy JSON'}
                            </Button>

                            <Button
                                variant="default"
                                onClick={() => createLemma.mutate(results.word)}
                                disabled={createLemma.isPending || results.results.length === 0}
                            >
                                {createLemma.isPending ? <Loader2 className="animate-spin h-4 w-4 mr-2" /> : <Plus className="h-4 w-4 mr-2" />}
                                Import into Baakh WordNet
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        {results.results.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/10">
                                        <TableHead className="w-[150px]">Source Dictionary</TableHead>
                                        <TableHead className="text-right">Scraped Definition</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {results.results.map((item, idx) => (
                                        <TableRow key={idx}>
                                            <TableCell>
                                                <Badge variant="secondary" className="bg-primary/10 text-primary">
                                                    {item.source}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right font-arabic text-xl leading-relaxed" dir="rtl">
                                                {item.text}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <div className="p-12 text-center text-muted-foreground flex flex-col items-center">
                                <ArrowRightLeft className="h-12 w-12 mb-4 opacity-20" />
                                <p className="text-lg">No definitions found for <span className="font-arabic font-bold text-foreground">"{results.word}"</span> on Sindhila.</p>
                                <p className="text-sm mt-2">Try checking the spelling or variations.</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

            {/* Auto Scraper Live Rolling Log */}
            {autoScrapeLogs.length > 0 && (
                <Card className="border-primary/20 shadow-md">
                    <CardHeader className="bg-muted/30 border-b relative overflow-hidden">
                        {isAutoScraping && (
                            <div className="absolute top-0 left-0 right-0 h-1 bg-blue-100 overflow-hidden">
                                <div className="h-full bg-blue-500 animate-pulse w-full rounded" />
                            </div>
                        )}
                        <CardTitle className="flex items-center justify-between">
                            <span className="flex items-center gap-2">
                                <Database className="h-5 w-5 text-blue-600" />
                                Live Corpus Auto-Scraper Event Log
                            </span>
                            {isAutoScraping && (
                                <Badge variant="secondary" className="bg-blue-100 text-blue-800 border-none animate-pulse">Running - Pausing 2s between requests</Badge>
                            )}
                        </CardTitle>
                        <CardDescription>
                            Showing the last 50 words checked against the Sindhila database automatically.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[180px]">Word</TableHead>
                                    <TableHead>Execution Status</TableHead>
                                    <TableHead className="text-right">Imported Senses</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {autoScrapeLogs.map((log, index) => (
                                    <TableRow key={index} className={index === 0 ? "bg-muted/30" : ""}>
                                        <TableCell className="font-arabic text-xl font-bold">{log.word}</TableCell>
                                        <TableCell>
                                            {log.status === 'success' && <Badge className="bg-green-100 text-green-800 hover:bg-green-200 border-green-200 shadow-sm">Success - Imported</Badge>}
                                            {log.status === 'not_found' && <Badge variant="outline" className="text-gray-500 border-gray-200">Not Found</Badge>}
                                            {log.status === 'error_parsing' && <Badge variant="destructive">HTML Parse Error</Badge>}
                                            {log.status === 'error_http' && <Badge variant="destructive">HTTP Request Failed</Badge>}
                                        </TableCell>
                                        <TableCell className="text-right text-lg">
                                            {log.senses_added !== undefined ? log.senses_added : '-'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            )}
        </div>
    );
};

export default SindhilaScraper;
