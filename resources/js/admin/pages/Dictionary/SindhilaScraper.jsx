import React, { useState } from 'react';
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
    Search, Globe, Loader2, Copy, CheckCircle2, Plus, FileJson, ArrowRightLeft
} from 'lucide-react';

const SindhilaScraper = () => {
    const [word, setWord] = useState('');
    const [results, setResults] = useState(null);
    const [copied, setCopied] = useState(false);
    const navigate = useNavigate();

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
            const res = await api.post('/api/admin/dictionary/lemmas', { lemma: lemmaString, status: 'pending' });

            // Then add all scraped senses to it automatically
            if (results && results.results.length > 0) {
                const addPromises = results.results.map(sense =>
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

    const handleSearch = (e) => {
        e.preventDefault();
        if (word.trim()) {
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
                        Query the external Sindhila Dictionary directly and import results.
                    </p>
                </div>
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
                        <Button
                            type="submit"
                            size="lg"
                            className="h-16 px-8 text-lg"
                            disabled={!word.trim() || scrapeWord.isPending}
                        >
                            {scrapeWord.isPending ? <Loader2 className="animate-spin mr-2 h-5 w-5" /> : <Globe className="mr-2 h-5 w-5" />}
                            Scrape Now
                        </Button>
                    </form>
                </CardContent>
            </Card>

            {/* Results Table */}
            {results && (
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
        </div>
    );
};

export default SindhilaScraper;
