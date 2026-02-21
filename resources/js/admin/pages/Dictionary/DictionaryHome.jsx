import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { ScrollArea } from "@/components/ui/scroll-area";
import { toast } from 'sonner';
import {
    Search, Book, Layers, Type, Languages, ArrowRightLeft,
    ChevronLeft, ChevronRight, Loader2, Edit2, Eye, Copy, CheckCircle2,
    Plus
} from 'lucide-react';

const DictionaryHome = () => {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [activeTab, setActiveTab] = useState('browse');
    const [viewingLemmaId, setViewingLemmaId] = useState(null);
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);

    // ── Stats ──
    const { data: stats } = useQuery({
        queryKey: ['dictionary-stats'],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/lemmas', { params: { limit: 1 } });
            return { total: res.data.total };
        }
    });

    // ── Lemma list ──
    const { data: response, isLoading } = useQuery({
        queryKey: ['dictionary-browse', search, page],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/lemmas', {
                params: { search, page, limit: 20 }
            });
            return res.data;
        },
        placeholderData: (prev) => prev
    });

    // ── Word lookup ──
    const [lookupWord, setLookupWord] = useState('');
    const [lookupResult, setLookupResult] = useState(null);
    const [lookupLoading, setLookupLoading] = useState(false);

    const handleLookup = async (e) => {
        e.preventDefault();
        if (!lookupWord.trim()) return;
        setLookupLoading(true);
        try {
            const res = await api.get(`/api/v1/word/${encodeURIComponent(lookupWord.trim())}`);
            setLookupResult(res.data);
        } catch {
            setLookupResult({ found: false });
        }
        setLookupLoading(false);
    };

    const lemmas = response?.data || [];
    const meta = response || {};

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Dictionary</h2>
                    <p className="text-muted-foreground mt-1">
                        Sindhi WordNet — {stats?.total?.toLocaleString() || '—'} words
                    </p>
                </div>
                <Button onClick={() => setIsAddModalOpen(true)}>
                    <Plus className="mr-2 h-4 w-4" /> Add Word
                </Button>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <StatCard icon={Book} label="Total Words" value={stats?.total?.toLocaleString() || '—'} />
                <StatCard icon={Layers} label="With Meanings" value="—" sub="Senses" />
                <StatCard icon={ArrowRightLeft} label="Relations" value="48,914" sub="Syn/Ant/Hyp" />
                <StatCard icon={Type} label="Morphologies" value={stats?.total?.toLocaleString() || '—'} />
            </div>

            {/* Tabs */}
            <Tabs value={activeTab} onValueChange={setActiveTab}>
                <TabsList>
                    <TabsTrigger value="browse">Browse Words</TabsTrigger>
                    <TabsTrigger value="lookup">Word Lookup</TabsTrigger>
                </TabsList>

                {/* ── Browse Tab ── */}
                <TabsContent value="browse" className="mt-4">
                    <Card>
                        <CardHeader className="pb-3">
                            <div className="flex items-center gap-4">
                                <div className="relative flex-1 max-w-md">
                                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search words..."
                                        className="pl-8"
                                        value={search}
                                        onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                                    />
                                </div>
                                {isLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-[60px]">ID</TableHead>
                                            <TableHead>Word</TableHead>
                                            <TableHead>POS</TableHead>
                                            <TableHead>Gender</TableHead>
                                            <TableHead>Number</TableHead>
                                            <TableHead>Relations</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {lemmas.length > 0 ? (
                                            lemmas.map((lemma) => (
                                                <TableRow key={lemma.id}>
                                                    <TableCell className="text-muted-foreground text-xs">{lemma.id}</TableCell>
                                                    <TableCell className="font-arabic text-lg font-semibold">{lemma.lemma}</TableCell>
                                                    <TableCell>
                                                        {lemma.pos ? <Badge variant="secondary">{lemma.pos}</Badge> : <span className="text-muted-foreground">—</span>}
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">
                                                        {lemma.morphology?.gender || '—'}
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">
                                                        {lemma.morphology?.number || '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex gap-1">
                                                            {lemma.lemma_relations_count > 0 && (
                                                                <Badge variant="outline" className="text-xs">
                                                                    {lemma.lemma_relations_count} rel
                                                                </Badge>
                                                            )}
                                                            {lemma.senses_count > 0 && (
                                                                <Badge variant="outline" className="text-xs">
                                                                    {lemma.senses_count} def
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={
                                                            lemma.status === 'approved' ? 'default' :
                                                                lemma.status === 'rejected' ? 'destructive' : 'outline'
                                                        }>
                                                            {lemma.status}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex justify-end gap-1">
                                                            <Button size="sm" variant="ghost" onClick={() => setViewingLemmaId(lemma.id)}>
                                                                <Eye className="h-3.5 w-3.5" />
                                                            </Button>
                                                            <Button size="sm" variant="ghost" asChild>
                                                                <Link to={`/admin/dictionary/lemmas/${lemma.id}`}>
                                                                    <Edit2 className="h-3.5 w-3.5" />
                                                                </Link>
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : !isLoading ? (
                                            <TableRow>
                                                <TableCell colSpan={8} className="h-24 text-center text-muted-foreground">
                                                    No words found.
                                                </TableCell>
                                            </TableRow>
                                        ) : null}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Pagination */}
                            <div className="flex items-center justify-between space-x-2 py-4">
                                <div className="text-sm text-muted-foreground">
                                    Showing <strong>{meta.from || 0}</strong> to <strong>{meta.to || 0}</strong> of <strong>{meta.total || 0}</strong>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Button variant="outline" size="sm" onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}>
                                        <ChevronLeft className="h-4 w-4" /> Previous
                                    </Button>
                                    <Button variant="outline" size="sm" onClick={() => setPage(p => p + 1)} disabled={!meta.next_page_url}>
                                        Next <ChevronRight className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                {/* ── Lookup Tab ── */}
                <TabsContent value="lookup" className="mt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Word Lookup</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleLookup} className="flex items-center gap-2 mb-6 max-w-md">
                                <div className="relative flex-1">
                                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        value={lookupWord}
                                        onChange={(e) => setLookupWord(e.target.value)}
                                        placeholder="Type a Sindhi word..."
                                        className="pl-8 font-arabic text-lg"
                                        dir="rtl"
                                    />
                                </div>
                                <Button type="submit" disabled={lookupLoading}>
                                    {lookupLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Lookup'}
                                </Button>
                            </form>

                            {lookupResult && !lookupResult.found && (
                                <div className="text-center py-8 text-muted-foreground">
                                    Word not found in dictionary.
                                </div>
                            )}

                            {lookupResult?.found && (
                                <div className="border rounded-lg p-6 max-w-lg space-y-4">
                                    {/* Word header */}
                                    <div className="flex items-center gap-3" dir="rtl">
                                        <span className="text-3xl font-bold font-arabic">{lookupResult.word}</span>
                                        {lookupResult.pos && (
                                            <Badge variant="secondary">{lookupResult.pos}</Badge>
                                        )}
                                    </div>

                                    {lookupResult.romanized && (
                                        <p className="text-sm text-muted-foreground">/{lookupResult.romanized}/</p>
                                    )}

                                    {(lookupResult.gender || lookupResult.number) && (
                                        <p className="text-sm text-muted-foreground">
                                            {[lookupResult.gender, lookupResult.number, lookupResult.tense].filter(Boolean).join(' · ')}
                                        </p>
                                    )}

                                    {/* Meanings */}
                                    {lookupResult.meanings?.length > 0 && (
                                        <div>
                                            <h4 className="text-xs text-muted-foreground uppercase tracking-wider mb-1">Meanings</h4>
                                            <ul className="space-y-1">
                                                {lookupResult.meanings.map((m, i) => (
                                                    <li key={i} className="text-sm font-arabic" dir="rtl">{i + 1}. {m}</li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}

                                    {/* Synonyms */}
                                    {lookupResult.synonyms?.length > 0 && (
                                        <div>
                                            <h4 className="text-xs text-muted-foreground uppercase tracking-wider mb-1">Synonyms</h4>
                                            <div className="flex flex-wrap gap-1.5" dir="rtl">
                                                {lookupResult.synonyms.map((s, i) => (
                                                    <Badge key={i} variant="outline" className="font-arabic text-sm">{s}</Badge>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Antonyms */}
                                    {lookupResult.antonyms?.length > 0 && (
                                        <div>
                                            <h4 className="text-xs text-muted-foreground uppercase tracking-wider mb-1">Antonyms</h4>
                                            <div className="flex flex-wrap gap-1.5" dir="rtl">
                                                {lookupResult.antonyms.map((a, i) => (
                                                    <Badge key={i} variant="destructive" className="font-arabic text-sm">{a}</Badge>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Hypernyms */}
                                    {lookupResult.hypernyms?.length > 0 && (
                                        <div>
                                            <h4 className="text-xs text-muted-foreground uppercase tracking-wider mb-1">Hypernyms</h4>
                                            <div className="flex flex-wrap gap-1.5" dir="rtl">
                                                {lookupResult.hypernyms.map((h, i) => (
                                                    <Badge key={i} variant="secondary" className="font-arabic text-sm">{h}</Badge>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            {/* Quick Links */}
            <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                <QuickLink to="/admin/dictionary/lemma-inbox" icon={Layers} label="Lemma Inbox" />
                <QuickLink to="/admin/dictionary/sense-editor" icon={Book} label="Sense Editor" />
                <QuickLink to="/admin/dictionary/morphology-lab" icon={Type} label="Morphology Lab" />
                <QuickLink to="/admin/dictionary/variants" icon={Languages} label="Variants" />
                <QuickLink to="/admin/dictionary/qa-search" icon={Search} label="QA & Search" />
            </div>

            {/* Modal for Viewing JSON Data */}
            <LemmaViewModal lemmaId={viewingLemmaId} onClose={() => setViewingLemmaId(null)} />

            {/* Modal for Adding New Word */}
            <AddLemmaModal open={isAddModalOpen} onClose={() => setIsAddModalOpen(false)} />
        </div>
    );
};

const StatCard = ({ icon: Icon, label, value, sub }) => (
    <Card>
        <CardContent className="pt-4 pb-3 px-4">
            <div className="flex items-center gap-3">
                <div className="h-9 w-9 rounded-lg bg-muted flex items-center justify-center">
                    <Icon className="h-4 w-4 text-muted-foreground" />
                </div>
                <div>
                    <p className="text-xl font-bold leading-none">{value}</p>
                    <p className="text-xs text-muted-foreground mt-0.5">{label}{sub ? ` · ${sub}` : ''}</p>
                </div>
            </div>
        </CardContent>
    </Card>
);

const QuickLink = ({ to, icon: Icon, label }) => (
    <Link to={to}>
        <Card className="hover:bg-muted/50 transition-colors cursor-pointer">
            <CardContent className="py-3 px-4 flex items-center gap-2">
                <Icon className="h-4 w-4 text-muted-foreground" />
                <span className="text-sm font-medium">{label}</span>
            </CardContent>
        </Card>
    </Link>
);

const LemmaViewModal = ({ lemmaId, onClose }) => {
    const { data: lemma, isLoading } = useQuery({
        queryKey: ['lemma', lemmaId],
        queryFn: async () => {
            const res = await api.get(`/api/admin/dictionary/lemmas/${lemmaId}`);
            return res.data;
        },
        enabled: !!lemmaId,
    });

    const [copied, setCopied] = useState(false);

    const handleCopyJson = () => {
        if (!lemma) return;
        navigator.clipboard.writeText(JSON.stringify(lemma, null, 2));
        setCopied(true);
        toast.success('JSON copied to clipboard!');
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <Dialog open={!!lemmaId} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-3xl max-h-[85vh] flex flex-col">
                <DialogHeader>
                    <div className="flex items-center justify-between pr-6">
                        <div>
                            <DialogTitle>Word Data JSON</DialogTitle>
                            <DialogDescription>
                                Copy this data to use as context for AI assistants like Perplexity or ChatGPT.
                            </DialogDescription>
                        </div>
                        <Button variant="outline" size="sm" onClick={handleCopyJson} disabled={!lemma}>
                            {copied ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <Copy className="h-4 w-4 mr-2" />}
                            {copied ? 'Copied' : 'Copy JSON'}
                        </Button>
                    </div>
                </DialogHeader>
                {isLoading ? (
                    <div className="flex h-40 items-center justify-center">
                        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                    </div>
                ) : (
                    <ScrollArea className="flex-1 bg-muted/50 rounded-md border p-4">
                        <pre className="text-xs font-mono whitespace-pre-wrap break-all" dir="ltr">
                            {JSON.stringify(lemma, null, 2)}
                        </pre>
                    </ScrollArea>
                )}
            </DialogContent>
        </Dialog>
    );
};

const AddLemmaModal = ({ open, onClose }) => {
    const [word, setWord] = useState('');
    const navigate = useNavigate();

    const createLemma = useMutation({
        mutationFn: (lemmaString) => api.post('/api/admin/dictionary/lemmas', { lemma: lemmaString, status: 'pending' }),
        onSuccess: (res) => {
            toast.success('Word created! Redirecting to editor...');
            navigate(`/admin/dictionary/lemmas/${res.data.id}`);
            onClose();
        },
        onError: () => toast.error('Failed to create word')
    });

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add New Word</DialogTitle>
                    <DialogDescription>
                        Create a draft word. You can immediately scrape data from Sindhila on the next screen.
                    </DialogDescription>
                </DialogHeader>
                <div className="py-4">
                    <Label className="text-lg">Word (Sindhi)</Label>
                    <Input
                        value={word}
                        onChange={(e) => setWord(e.target.value)}
                        className="font-arabic text-2xl mt-3 text-right"
                        dir="rtl"
                        autoFocus
                        placeholder="سنڌي لفظ..."
                    />
                </div>
                <div className="flex justify-between items-center mt-2">
                    <Button variant="outline" onClick={onClose}>Cancel</Button>
                    <Button
                        onClick={() => createLemma.mutate(word.trim())}
                        disabled={!word.trim() || createLemma.isPending}
                    >
                        {createLemma.isPending ? <Loader2 className="animate-spin h-4 w-4 mr-2" /> : <Plus className="h-4 w-4 mr-2" />}
                        Add & Scrape Data
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default DictionaryHome;
