import React, { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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
    Plus, BookCheck, BookX, SpellCheck
} from 'lucide-react';
import LemmaEditorJsonModal from './LemmaEditorJsonModal';
import { cn } from '@/lib/utils';

const DictionaryHome = () => {
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [completionStatus, setCompletionStatus] = useState('all');
    const [lughatStatus, setLughatStatus] = useState('all'); // all | added | remaining
    const [activeTab, setActiveTab] = useState('browse');
    const [viewingLemmaId, setViewingLemmaId] = useState(null);
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [isBatchScrapeModalOpen, setIsBatchScrapeModalOpen] = useState(false);
    const [hesudharRunning, setHesudharRunning] = useState(false);
    const [hesudharProgress, setHesudharProgress] = useState(null);
    const hesudharCancelRef = useRef(false);

    // ── Stats ──
    const { data: stats } = useQuery({
        queryKey: ['dictionary-stats'],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/stats');
            return res.data;
        }
    });

    const { data: lughatStats } = useQuery({
        queryKey: ['dictionary-lughat-stats'],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/lughat-stats');
            return res.data;
        }
    });

    // ── Lemma list ──
    const { data: response, isLoading } = useQuery({
        queryKey: ['dictionary-browse', search, page, completionStatus, lughatStatus],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/lemmas', {
                params: {
                    search,
                    page,
                    limit: 20,
                    completion_status: completionStatus,
                    lughat_status: lughatStatus,
                }
            });
            return res.data;
        },
        placeholderData: (prev) => prev
    });

    const setLughatFilter = (next) => {
        setLughatStatus(next);
        setPage(1);
    };

    const runDictionaryHesudhar = async () => {
        if (hesudharRunning) return;
        const resumeFrom = hesudharProgress?.afterId && !hesudharProgress?.done
            ? hesudharProgress.afterId
            : 0;
        const ok = window.confirm(
            'Run Hesudhar on general-dictionary HEADWORDS only?\n\n'
            + '• Fixes he / heh (ه ہ ھ) via WordNet + phonetic rules\n'
            + '• Does NOT change meanings / senses\n'
            + '• Runs in small batches so the server stays up\n'
            + (resumeFrom > 0 ? `\nResume from lemma id #${resumeFrom}?\n` : '\n')
            + 'Continue?'
        );
        if (!ok) return;

        hesudharCancelRef.current = false;
        setHesudharRunning(true);
        let afterId = resumeFrom;
        let totalScanned = resumeFrom > 0 ? (hesudharProgress?.scanned || 0) : 0;
        let totalUpdated = resumeFrom > 0 ? (hesudharProgress?.updated || 0) : 0;
        let totalConflicts = resumeFrom > 0 ? (hesudharProgress?.conflicts || 0) : 0;
        const samples = [];

        try {
            // eslint-disable-next-line no-constant-condition
            while (true) {
                if (hesudharCancelRef.current) break;
                const res = await api.post('/api/admin/dictionary/hesudhar-lemmas', {
                    after_id: afterId,
                    limit: 400,
                });
                const batch = res.data?.data || {};
                totalScanned += batch.scanned || 0;
                totalUpdated += batch.updated || 0;
                totalConflicts += batch.skipped_conflict || 0;
                if (Array.isArray(batch.samples)) {
                    samples.push(...batch.samples.slice(0, Math.max(0, 8 - samples.length)));
                }
                afterId = batch.next_after_id || afterId;
                setHesudharProgress({
                    scanned: totalScanned,
                    updated: totalUpdated,
                    conflicts: totalConflicts,
                    afterId,
                    done: !!batch.done,
                });
                if (batch.done) break;
                // Small pause so we don't stampede the API between batches.
                await new Promise((resolve) => setTimeout(resolve, 150));
            }

            queryClient.invalidateQueries({ queryKey: ['dictionary-browse'] });
            queryClient.invalidateQueries({ queryKey: ['dictionary-stats'] });
            queryClient.invalidateQueries({ queryKey: ['dictionary-lughat-stats'] });

            if (hesudharCancelRef.current) {
                toast.message(`Hesudhar stopped. Updated ${totalUpdated.toLocaleString()} headwords so far.`);
            } else {
                toast.success(
                    `Hesudhar done. Scanned ${totalScanned.toLocaleString()}, updated ${totalUpdated.toLocaleString()}`
                    + (totalConflicts ? `, skipped ${totalConflicts.toLocaleString()} conflicts` : '')
                    + '.'
                );
            }
        } catch (error) {
            if (error?.response?.status === 429) {
                toast.error('Rate limited. Wait ~30s, then click Hesudhar again — it resumes from the last cursor.');
            } else {
                toast.error(error?.response?.data?.message || 'Hesudhar batch failed.');
            }
        } finally {
            setHesudharRunning(false);
        }
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
                        Sindhi Open Lexicon — {stats?.open_lexicon_entries?.toLocaleString() || '—'} entries
                    </p>
                </div>
                <div className="flex flex-col sm:flex-row gap-2 shrink-0 w-full sm:w-auto">
                    <Button
                        variant="outline"
                        onClick={runDictionaryHesudhar}
                        disabled={hesudharRunning}
                        className="w-full sm:w-auto"
                        title="Fix he/heh on headwords only (batched)"
                    >
                        {hesudharRunning
                            ? <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            : <SpellCheck className="mr-2 h-4 w-4" />}
                        {hesudharRunning
                            ? `Hesudhar… ${hesudharProgress?.scanned?.toLocaleString?.() || 0}`
                            : 'Hesudhar'}
                    </Button>
                    {hesudharRunning && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="w-full sm:w-auto"
                            onClick={() => { hesudharCancelRef.current = true; }}
                        >
                            Stop
                        </Button>
                    )}
                    <Button onClick={() => setIsAddModalOpen(true)} className="w-full sm:w-auto">
                        <Plus className="mr-2 h-4 w-4" /> Add Word
                    </Button>
                </div>
            </div>

            {hesudharProgress && (
                <p className="text-xs text-muted-foreground">
                    Hesudhar progress: scanned {hesudharProgress.scanned?.toLocaleString()} ·
                    updated {hesudharProgress.updated?.toLocaleString()} ·
                    conflicts {hesudharProgress.conflicts?.toLocaleString()}
                    {hesudharProgress.done ? ' · done' : ` · cursor #${hesudharProgress.afterId}`}
                </p>
            )}

            {/* Stats Cards */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <StatCard icon={Book} label="Total Lemmas" value={stats?.total_lemmas?.toLocaleString() || '—'} />
                <StatCard icon={CheckCircle2} label="Complete" value={stats?.complete_lemmas?.toLocaleString() || '—'} />
                <StatCard icon={Layers} label="Pending Completion" value={stats?.pending_completion_lemmas?.toLocaleString() || '—'} />
                <StatCard icon={Type} label="Completion" value={`${stats?.completion_percentage ?? '—'}%`} sub={topSource?.source_dictionary || 'Top source'} />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <Card className={cn(lughatStatus === 'added' && 'ring-2 ring-emerald-500/40')}>
                    <CardHeader className="pb-2">
                        <CardDescription>Added in Baakh Lughat</CardDescription>
                        <CardTitle className="text-3xl tabular-nums text-emerald-700">
                            {lughatStats?.added?.toLocaleString() ?? '—'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-0">
                        <Button
                            size="sm"
                            variant={lughatStatus === 'added' ? 'default' : 'outline'}
                            className="w-full"
                            onClick={() => setLughatFilter(lughatStatus === 'added' ? 'all' : 'added')}
                        >
                            <BookCheck className="mr-2 h-4 w-4" />
                            {lughatStatus === 'added' ? 'Showing added' : 'See added'}
                        </Button>
                    </CardContent>
                </Card>
                <Card className={cn(lughatStatus === 'remaining' && 'ring-2 ring-amber-500/40')}>
                    <CardHeader className="pb-2">
                        <CardDescription>Remaining (not in Lughat)</CardDescription>
                        <CardTitle className="text-3xl tabular-nums text-amber-700">
                            {lughatStats?.remaining?.toLocaleString() ?? '—'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-0">
                        <Button
                            size="sm"
                            variant={lughatStatus === 'remaining' ? 'default' : 'outline'}
                            className="w-full"
                            onClick={() => setLughatFilter(lughatStatus === 'remaining' ? 'all' : 'remaining')}
                        >
                            <BookX className="mr-2 h-4 w-4" />
                            {lughatStatus === 'remaining' ? 'Showing remaining' : 'See remaining'}
                        </Button>
                    </CardContent>
                </Card>
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
                                <div className="flex items-center gap-1 rounded-md border p-1 overflow-x-auto max-w-full shrink-0">
                                    {[
                                        { key: 'all', label: 'All Lughat' },
                                        { key: 'added', label: 'Added' },
                                        { key: 'remaining', label: 'Remaining' },
                                    ].map((item) => (
                                        <Button
                                            key={item.key}
                                            type="button"
                                            size="sm"
                                            variant={lughatStatus === item.key ? 'default' : 'ghost'}
                                            onClick={() => setLughatFilter(item.key)}
                                            className="shrink-0"
                                        >
                                            {item.label}
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
                                            <TableHead>Lughat</TableHead>
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
                                                            {lemma.in_lughat ? (
                                                                <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-100">Added</Badge>
                                                            ) : (
                                                                <Badge variant="outline" className="text-amber-800 border-amber-300">Remaining</Badge>
                                                            )}
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
                                                                    <Link to={`/admin/dictionary/lemmas/${lemma.id}`}>
                                                                        <Edit2 className="h-3.5 w-3.5" />
                                                                    </Link>
                                                                </Button>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })
                                        ) : !isLoading ? (
                                            <TableRow>
                                                <TableCell colSpan={10} className="h-24 text-center text-muted-foreground">
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
                <QuickLink to="/admin/dictionary/lemma-inbox" icon={Layers} label="Lemma Inbox" />
                <QuickLink to="/admin/dictionary/sense-editor" icon={Book} label="Sense Editor" />
                <QuickLink to="/admin/dictionary/morphology-lab" icon={Type} label="Morphology Lab" />
                <QuickLink to="/admin/dictionary/variants" icon={Languages} label="Variants" />
                <QuickLink to="/admin/dictionary/qa-search" icon={Search} label="QA & Search" />
            </div>

            {/* Modal for Viewing JSON Data */}
            <LemmaEditorJsonModal lemmaId={viewingLemmaId} onClose={() => setViewingLemmaId(null)} />

            {/* Modal for Adding New Word */}
            <AddLemmaModal open={isAddModalOpen} onClose={() => setIsAddModalOpen(false)} />

            {/* Modal for Batch Scraping */}
            <BatchScrapeModal open={isBatchScrapeModalOpen} onClose={() => setIsBatchScrapeModalOpen(false)} />
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
                        onClick={() => { if (word.trim()) createLemma.mutate(word.trim()); }}
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

const BatchScrapeModal = ({ open, onClose }) => {
    const [count, setCount] = useState(10);
    const [results, setResults] = useState(null);

    const batchScrape = useMutation({
        mutationFn: (c) => api.post('/api/admin/dictionary/scrape-batch-missing', { count: c }),
        onSuccess: (res) => {
            toast.success(`Processed ${res.data.processed} words! Check Lemma Inbox.`);
            setResults(res.data.results);
        },
        onError: () => toast.error('Failed to run batch scrape.')
    });

    const handleClose = () => {
        setResults(null);
        onClose();
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="max-w-2xl max-h-[85vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Batch Scrape Missing Words</DialogTitle>
                    <DialogDescription>
                        Automatically find the most frequent missing words from our corpus and fetch their definitions from Sindhila. New words will go to the Inbox.
                    </DialogDescription>
                </DialogHeader>

                {!results ? (
                    <div className="py-6">
                        <Label>Number of words to process at once:</Label>
                        <div className="flex items-center gap-3 mt-2">
                            <Input
                                type="number"
                                min="1"
                                max="50"
                                value={count}
                                onChange={(e) => setCount(Number(e.target.value))}
                                className="w-24"
                            />
                            <span className="text-sm text-muted-foreground">Words will be drawn from top frequencies.</span>
                        </div>

                        <div className="mt-8 flex justify-end gap-2">
                            <Button variant="outline" onClick={handleClose}>Cancel</Button>
                            <Button onClick={() => batchScrape.mutate(count)} disabled={batchScrape.isPending}>
                                {batchScrape.isPending ? <Loader2 className="animate-spin h-4 w-4 mr-2" /> : <Layers className="h-4 w-4 mr-2" />}
                                Start Batch Scrape
                            </Button>
                        </div>
                    </div>
                ) : (
                    <ScrollArea className="flex-1 mt-4 border rounded-md bg-muted/30 p-4">
                        <h4 className="font-semibold mb-4 text-sm">Scraping Results:</h4>
                        <div className="space-y-2">
                            {results.map((r, i) => (
                                <div key={i} className="flex items-center justify-between text-sm py-2 px-3 border rounded bg-background">
                                    <span className="font-arabic font-semibold text-lg">{r.word}</span>
                                    {r.status === 'success' ? (
                                        <Badge variant="default" className="bg-green-600">Added ({r.senses_added} senses)</Badge>
                                    ) : r.status === 'not_found' ? (
                                        <Badge variant="secondary">Not found on Sindhila</Badge>
                                    ) : (
                                        <Badge variant="destructive">Error</Badge>
                                    )}
                                </div>
                            ))}
                        </div>
                        <div className="mt-6 flex justify-end">
                            <Button onClick={handleClose} asChild>
                                <Link to="/admin/dictionary/lemma-inbox">Go to Inbox</Link>
                            </Button>
                        </div>
                    </ScrollArea>
                )}
            </DialogContent>
        </Dialog>
    );
};

export default DictionaryHome;
