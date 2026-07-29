import React, { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
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
    Plus, Trash2, ScrollText
} from 'lucide-react';
import LughatLemmaEditorJsonModal from './LughatLemmaEditorJsonModal';

const LughatHome = () => {
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [completionStatus, setCompletionStatus] = useState('all');
    const [activeTab, setActiveTab] = useState('browse');
    const [viewingLemmaId, setViewingLemmaId] = useState(null);
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [poetryImportResult, setPoetryImportResult] = useState(null);

    // ── Stats ──
    const { data: stats } = useQuery({
        queryKey: ['lughat-stats'],
        queryFn: async () => {
            const res = await api.get('/api/admin/lughat/stats');
            return res.data;
        }
    });

    // ── Lemma list ──
    const { data: response, isLoading } = useQuery({
        queryKey: ['lughat-browse', search, page, completionStatus],
        queryFn: async () => {
            const res = await api.get('/api/admin/lughat/lemmas', {
                params: { search, page, limit: 20, completion_status: completionStatus }
            });
            return res.data;
        },
        placeholderData: (prev) => prev
    });

    const deleteLemma = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/lughat/lemmas/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['lughat-browse'] });
            queryClient.invalidateQueries({ queryKey: ['lughat-stats'] });
            toast.success('Word deleted from Baakh Lughat.');
        },
        onError: () => toast.error('Failed to delete word.'),
    });

    const { data: poetryImportPeek, refetch: refetchPoetryPeek } = useQuery({
        queryKey: ['lughat-poetry-import-peek'],
        queryFn: async () => {
            const res = await api.get('/api/admin/lughat/import-from-poetry');
            return res.data;
        },
    });

    const importFromPoetry = useMutation({
        mutationFn: (payload = {}) => api.post('/api/admin/lughat/import-from-poetry', payload),
        onSuccess: (res) => {
            const data = res.data;
            setPoetryImportResult(data);
            queryClient.invalidateQueries({ queryKey: ['lughat-browse'] });
            queryClient.invalidateQueries({ queryKey: ['lughat-stats'] });
            queryClient.invalidateQueries({ queryKey: ['lughat-lemma-inbox'] });
            refetchPoetryPeek();

            if (data.done) {
                toast.message('Poetry import finished', {
                    description: 'No more poetry left after the current cursor.',
                });
                return;
            }

            toast.success(
                `Poetry #${data.poetry?.id}: +${data.lemmas_created ?? data.created} lemmas` +
                (data.occurrences_created != null ? ` · ${data.occurrences_created} occurrences` : '') +
                (data.word_forms_created != null ? ` · ${data.word_forms_created} forms` : '') +
                (data.skipped_duplicate ? ` · ${data.skipped_duplicate} already linked` : '')
            );
        },
        onError: () => toast.error('Failed to import words from poetry.'),
    });

    const handleDeleteLemma = (lemma) => {
        if (!confirm(`Delete “${lemma.lemma}” from Baakh Lughat? This cannot be undone.`)) return;
        deleteLemma.mutate(lemma.id);
    };

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
    const topSource = stats?.sources?.[0];

    return (
        <div className="space-y-6 min-w-0 w-full max-w-full">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 min-w-0">
                <div className="min-w-0">
                    <h2 className="text-2xl sm:text-3xl font-bold tracking-tight">Dictionary</h2>
                    <p className="text-muted-foreground mt-1 break-words">
                        Baakh Lughat — poetic dictionary · {stats?.total_lemmas?.toLocaleString() || '—'} words
                    </p>
                </div>
                <div className="flex flex-wrap gap-2 shrink-0">
                    <Button
                        variant="outline"
                        onClick={() => importFromPoetry.mutate({})}
                        disabled={importFromPoetry.isPending || poetryImportPeek?.done}
                        className="w-full sm:w-auto"
                        title={
                            poetryImportPeek?.poetry
                                ? `Next: #${poetryImportPeek.poetry.id}${poetryImportPeek.poetry.title ? ` — ${poetryImportPeek.poetry.title}` : ''}`
                                : 'No more poetry'
                        }
                    >
                        {importFromPoetry.isPending
                            ? <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            : <ScrollText className="mr-2 h-4 w-4" />}
                        Get words from poetry
                    </Button>
                    <Button onClick={() => setIsAddModalOpen(true)} className="w-full sm:w-auto">
                        <Plus className="mr-2 h-4 w-4" /> Add Word
                    </Button>
                </div>
            </div>

            {poetryImportPeek && !poetryImportPeek.done && (
                <p className="text-sm text-muted-foreground -mt-3">
                    Next poetry: <span className="font-medium text-foreground">#{poetryImportPeek.poetry?.id}</span>
                    {poetryImportPeek.poetry?.title ? (
                        <span className="font-arabic" dir="rtl"> — {poetryImportPeek.poetry.title}</span>
                    ) : null}
                    {' · '}
                    ~{poetryImportPeek.new_word_count?.toLocaleString() ?? '—'} new / {poetryImportPeek.word_count?.toLocaleString() ?? '—'} unique
                    {poetryImportPeek.cursor > 0 && (
                        <>
                            {' · '}
                            <button
                                type="button"
                                className="underline underline-offset-2 hover:text-foreground"
                                onClick={() => {
                                    if (!confirm('Reset poetry import cursor to the oldest poetry?')) return;
                                    importFromPoetry.mutate({ reset: true });
                                }}
                            >
                                Reset to oldest
                            </button>
                        </>
                    )}
                </p>
            )}
            {poetryImportPeek?.done && (
                <p className="text-sm text-muted-foreground -mt-3">
                    All poetry has been walked for word import.
                    {' '}
                    <button
                        type="button"
                        className="underline underline-offset-2 hover:text-foreground"
                        onClick={() => {
                            if (!confirm('Reset poetry import cursor to the oldest poetry?')) return;
                            importFromPoetry.mutate({ reset: true });
                        }}
                    >
                        Reset to oldest
                    </button>
                </p>
            )}

            {/* Stats Cards */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <StatCard icon={Book} label="Total Lemmas" value={stats?.total_lemmas?.toLocaleString() || '—'} />
                <StatCard icon={CheckCircle2} label="Complete" value={stats?.complete_lemmas?.toLocaleString() || '—'} />
                <StatCard icon={Layers} label="Pending Completion" value={stats?.pending_completion_lemmas?.toLocaleString() || '—'} />
                <StatCard icon={Type} label="Completion" value={`${stats?.completion_percentage ?? '—'}%`} sub={topSource?.source_dictionary || 'Top source'} />
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
                            <div className="flex flex-col sm:flex-row sm:items-center gap-3 min-w-0">
                                <div className="relative flex-1 w-full sm:max-w-md min-w-0">
                                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search words, definitions, sources, lexical IDs..."
                                        className="pl-8"
                                        value={search}
                                        onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                                    />
                                </div>
                                <div className="flex items-center gap-1 rounded-md border p-1 overflow-x-auto max-w-full shrink-0">
                                    {['all', 'pending', 'complete'].map((status) => (
                                        <Button
                                            key={status}
                                            type="button"
                                            size="sm"
                                            variant={completionStatus === status ? 'default' : 'ghost'}
                                            onClick={() => {
                                                setCompletionStatus(status);
                                                setPage(1);
                                            }}
                                            className="capitalize shrink-0"
                                        >
                                            {status === 'all' ? 'All' : status}
                                        </Button>
                                    ))}
                                </div>
                                {isLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground shrink-0" />}
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
                                            <TableHead>Definition</TableHead>
                                            <TableHead>Source</TableHead>
                                            <TableHead>Senses</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Completion</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {lemmas.length > 0 ? (
                                            lemmas.map((lemma) => {
                                                const firstSense = lemma.senses?.[0];
                                                const pos = lemma.pos || firstSense?.part_of_speech;

                                                return (
                                                    <TableRow key={lemma.id}>
                                                        <TableCell className="text-muted-foreground text-xs">{lemma.id}</TableCell>
                                                        <TableCell>
                                                            <div className="font-arabic text-lg font-semibold" dir="rtl">{lemma.lemma}</div>
                                                            {firstSense?.word_variant && (
                                                                <div className="text-xs text-muted-foreground font-arabic" dir="rtl">{firstSense.word_variant}</div>
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            {pos ? <Badge variant="secondary">{pos}</Badge> : <span className="text-muted-foreground">—</span>}
                                                        </TableCell>
                                                        <TableCell className="max-w-lg">
                                                            <p className="line-clamp-2 text-sm font-arabic" dir="auto">
                                                                {firstSense?.definition || '—'}
                                                            </p>
                                                            {firstSense?.definition_en && (
                                                                <p className="mt-1 line-clamp-1 text-xs text-muted-foreground" dir="ltr">
                                                                    EN: {firstSense.definition_en}
                                                                </p>
                                                            )}
                                                            {firstSense?.lexical_id && (
                                                                <p className="mt-1 text-xs text-muted-foreground">{firstSense.lexical_id}</p>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-sm text-muted-foreground">
                                                            {firstSense?.source_dictionary || firstSense?.domain || '—'}
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex gap-1">
                                                                {lemma.senses_count > 0 && (
                                                                    <Badge variant="outline" className="text-xs">
                                                                        {lemma.senses_count} def
                                                                    </Badge>
                                                                )}
                                                                {lemma.lemma_relations_count > 0 && (
                                                                    <Badge variant="outline" className="text-xs">
                                                                        {lemma.lemma_relations_count} rel
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
                                                        <TableCell>
                                                            <CompletionBadge lemma={lemma} />
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <div className="flex justify-end gap-1">
                                                                <Button size="sm" variant="ghost" onClick={() => setViewingLemmaId(lemma.id)}>
                                                                    <Eye className="h-3.5 w-3.5" />
                                                                </Button>
                                                                <Button size="sm" variant="ghost" asChild>
                                                                    <Link to={`/admin/baakh-lughat/lemmas/${lemma.id}`}>
                                                                        <Edit2 className="h-3.5 w-3.5" />
                                                                    </Link>
                                                                </Button>
                                                                <Button
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    className="text-destructive hover:text-destructive"
                                                                    disabled={deleteLemma.isPending}
                                                                    onClick={() => handleDeleteLemma(lemma)}
                                                                    title="Delete word"
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                </Button>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })
                                        ) : !isLoading ? (
                                            <TableRow>
                                                <TableCell colSpan={9} className="h-24 text-center text-muted-foreground">
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
                                        <CompletionBadge lemma={lookupResult} />
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
                                    {lookupResult.senses?.length > 0 && (
                                        <div>
                                            <h4 className="text-xs text-muted-foreground uppercase tracking-wider mb-1">Structured Senses</h4>
                                            <div className="space-y-2">
                                                {lookupResult.senses.map((sense, i) => (
                                                    <div key={sense.public_id || sense.id || i} className="rounded-md border p-2">
                                                        <div className="flex items-center gap-2 mb-1">
                                                            <Badge variant="outline">#{i + 1}</Badge>
                                                            {sense.short_gloss && <span className="text-sm font-medium">{sense.short_gloss}</span>}
                                                            {sense.source && <Badge variant="secondary">{sense.source}</Badge>}
                                                        </div>
                                                        <p className="text-sm font-arabic" dir="auto">{sense.definition || sense.full_definition}</p>
                                                        {sense.definition_en && (
                                                            <p className="mt-1 text-sm text-muted-foreground" dir="ltr">EN: {sense.definition_en}</p>
                                                        )}
                                                        {sense.definition_sd && (
                                                            <p className="mt-1 text-sm font-arabic text-muted-foreground" dir="rtl">SD: {sense.definition_sd}</p>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}

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
                <QuickLink to="/admin/baakh-lughat/lemma-inbox" icon={Layers} label="Lughat Inbox" />
                <QuickLink to="/admin/baakh-lughat/sense-editor" icon={Book} label="Lughat Sense Editor" />
                <QuickLink to="/admin/baakh-lughat/morphology-lab" icon={Type} label="Lughat Morphology Lab" />
                <QuickLink to="/admin/baakh-lughat/variants" icon={Languages} label="Variants" />
                <QuickLink to="/admin/baakh-lughat/qa-search" icon={Search} label="QA & Search" />
            </div>

            {/* Modal for Viewing JSON Data */}
            <LughatLemmaEditorJsonModal lemmaId={viewingLemmaId} onClose={() => setViewingLemmaId(null)} />

            {/* Modal for Adding New Word */}
            <AddLemmaModal open={isAddModalOpen} onClose={() => setIsAddModalOpen(false)} />

            {/* Poetry import result */}
            <Dialog open={!!poetryImportResult && !poetryImportResult.done} onOpenChange={(open) => { if (!open) setPoetryImportResult(null); }}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Words from poetry</DialogTitle>
                        <DialogDescription>
                            Poetry #{poetryImportResult?.poetry?.id}
                            {poetryImportResult?.poetry?.title ? ` — ${poetryImportResult.poetry.title}` : ''}
                            . Diacritics (zabar/pesh/zer) stripped; duplicates skipped.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid grid-cols-3 gap-3 text-center py-2">
                        <div>
                            <p className="text-2xl font-bold">{poetryImportResult?.created ?? 0}</p>
                            <p className="text-xs text-muted-foreground">Created</p>
                        </div>
                        <div>
                            <p className="text-2xl font-bold">{poetryImportResult?.skipped_duplicate ?? 0}</p>
                            <p className="text-xs text-muted-foreground">Duplicates</p>
                        </div>
                        <div>
                            <p className="text-2xl font-bold">{poetryImportResult?.total_tokens ?? 0}</p>
                            <p className="text-xs text-muted-foreground">Unique in poem</p>
                        </div>
                    </div>
                    {poetryImportResult?.words?.length > 0 && (
                        <ScrollArea className="h-48 rounded-md border p-3">
                            <div className="flex flex-wrap gap-1.5 justify-end" dir="rtl">
                                {poetryImportResult.words.map((w) => (
                                    <Badge key={w} variant="secondary" className="font-arabic text-sm">{w}</Badge>
                                ))}
                            </div>
                        </ScrollArea>
                    )}
                    <div className="flex justify-between gap-2 mt-2">
                        <Button variant="outline" onClick={() => setPoetryImportResult(null)}>Close</Button>
                        <Button
                            onClick={() => importFromPoetry.mutate({})}
                            disabled={importFromPoetry.isPending || !poetryImportResult?.next_poetry_id}
                        >
                            {importFromPoetry.isPending
                                ? <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                : <ScrollText className="mr-2 h-4 w-4" />}
                            Next poetry
                            {poetryImportResult?.next_poetry_id ? ` (#${poetryImportResult.next_poetry_id})` : ''}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

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

const CompletionBadge = ({ lemma }) => {
    const status = lemma?.completion_status || 'pending';
    const isComplete = status === 'complete';

    return (
        <Badge
            variant={isComplete ? 'default' : 'outline'}
            className={isComplete ? 'bg-green-600 hover:bg-green-600' : 'text-amber-700 border-amber-200 bg-amber-50'}
        >
            {isComplete ? 'Complete' : `Pending${lemma?.completion_score ? ` · ${lemma.completion_score}%` : ''}`}
        </Badge>
    );
};

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

const AddLemmaModal = ({ open, onClose }) => {
    const [word, setWord] = useState('');
    const navigate = useNavigate();

    const createLemma = useMutation({
        mutationFn: (lemmaString) => api.post('/api/admin/lughat/lemmas', { lemma: lemmaString, status: 'pending' }),
        onSuccess: (res) => {
            toast.success('Word created! Redirecting to editor...');
            navigate(`/admin/baakh-lughat/lemmas/${res.data.id}`);
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
                        Create a word in Baakh Lughat, then add meanings and poetic senses in the editor.
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
                        onClick={() => { if (word.trim()) createLemma.mutate(word.trim()); }}
                        disabled={!word.trim() || createLemma.isPending}
                    >
                        {createLemma.isPending ? <Loader2 className="animate-spin h-4 w-4 mr-2" /> : <Plus className="h-4 w-4 mr-2" />}
                        Add & Edit
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default LughatHome;
