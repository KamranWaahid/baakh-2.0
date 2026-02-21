import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
    Trash2, Plus, Check, Save, Loader2, ArrowLeft,
    BookOpen, Type, Languages, ArrowRightLeft, Layers, X, Globe,
    Copy, CheckCircle2
} from 'lucide-react';
import { toast } from 'sonner';

const SenseEditor = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    // ── Fetch lemma with all relations ──
    const { data: lemma, isLoading, error } = useQuery({
        queryKey: ['lemma', id],
        queryFn: async () => {
            if (!id) return null;
            const res = await api.get(`/api/admin/dictionary/lemmas/${id}`);
            return res.data;
        },
        enabled: !!id
    });

    // ── Local state for editable fields ──
    const [lemmaForm, setLemmaForm] = useState({});
    const [morphForm, setMorphForm] = useState({});
    const [newRelation, setNewRelation] = useState({ relation_type: 'synonym', related_word: '' });
    const [newVariant, setNewVariant] = useState({ variant: '', type: 'dialectal', dialect: '' });

    // ── Scraper State ──
    const [isScrapeModalOpen, setIsScrapeModalOpen] = useState(false);
    const [scrapeResults, setScrapeResults] = useState([]);
    const [isScraping, setIsScraping] = useState(false);
    const [scrapeError, setScrapeError] = useState(null);
    const [isScrapeCopied, setIsScrapeCopied] = useState(false);

    useEffect(() => {
        if (lemma) {
            setLemmaForm({
                lemma: lemma.lemma || '',
                pos: lemma.pos || '',
                transliteration: lemma.transliteration || '',
                status: lemma.status || 'pending',
            });
            setMorphForm({
                root: lemma.morphology?.root || '',
                pattern: lemma.morphology?.pattern || '',
                gender: lemma.morphology?.gender || '',
                number: lemma.morphology?.number || '',
                case: lemma.morphology?.case || '',
                tense: lemma.morphology?.tense || '',
            });
        }
    }, [lemma]);

    // ── Mutations ──
    const updateLemma = useMutation({
        mutationFn: (data) => api.put(`/api/admin/dictionary/lemmas/${id}`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Lemma changes saved successfully.');
        },
        onError: () => toast.error('Failed to save lemma changes.'),
    });

    const updateMorphology = useMutation({
        mutationFn: (data) => api.put(`/api/admin/dictionary/lemmas/${id}/morphology`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Morphology saved successfully.');
        },
        onError: () => toast.error('Failed to save morphology.'),
    });

    const addSense = useMutation({
        mutationFn: (data) => api.post(`/api/admin/dictionary/senses`, { lemma_id: id, ...data }),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('New sense added.');
        },
        onError: () => toast.error('Failed to add new sense.'),
    });

    const updateSense = useMutation({
        mutationFn: ({ senseId, data }) => api.put(`/api/admin/dictionary/senses/${senseId}`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Sense updated successfully.');
        },
        onError: () => toast.error('Failed to update sense.'),
    });

    const deleteSense = useMutation({
        mutationFn: (senseId) => api.delete(`/api/admin/dictionary/senses/${senseId}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Sense deleted successfully.');
        },
        onError: () => toast.error('Failed to delete sense.'),
    });

    const addRelation = useMutation({
        mutationFn: (data) => api.post(`/api/admin/dictionary/lemmas/${id}/relations`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            setNewRelation({ relation_type: 'synonym', related_word: '' });
            toast.success('Relation added.');
        },
        onError: () => toast.error('Failed to add relation.'),
    });

    const deleteRelation = useMutation({
        mutationFn: (relId) => api.delete(`/api/admin/dictionary/relations/${relId}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Relation deleted.');
        },
        onError: () => toast.error('Failed to delete relation.'),
    });

    const addVariant = useMutation({
        mutationFn: (data) => api.post(`/api/admin/dictionary/lemmas/${id}/variants`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            setNewVariant({ variant: '', type: 'dialectal', dialect: '' });
            toast.success('Variant added.');
        },
        onError: () => toast.error('Failed to add variant.'),
    });

    const deleteVariant = useMutation({
        mutationFn: (vId) => api.delete(`/api/admin/dictionary/variants/${vId}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Variant deleted.');
        },
        onError: () => toast.error('Failed to delete variant.'),
    });

    const approveLemma = useMutation({
        mutationFn: () => api.patch(`/api/admin/dictionary/lemmas/${id}/approve`),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Lemma approved.');
        },
        onError: () => toast.error('Failed to approve lemma.'),
    });

    const handleScrape = async () => {
        setIsScraping(true);
        setScrapeError(null);
        try {
            const res = await api.post(`/api/admin/dictionary/lemmas/${id}/scrape-sindhila`);
            setScrapeResults(res.data.results || []);
            setIsScrapeModalOpen(true);
        } catch (err) {
            setScrapeError('Failed to scrape data from Sindhila. The site might be unresponsive.');
        } finally {
            setIsScraping(false);
        }
    };

    const handleCopyScrapeJson = () => {
        if (!scrapeResults || scrapeResults.length === 0) return;
        navigator.clipboard.writeText(JSON.stringify({ scrapedSenses: scrapeResults }, null, 2));
        setIsScrapeCopied(true);
        toast.success('Scraped data JSON copied to clipboard!');
        setTimeout(() => setIsScrapeCopied(false), 2000);
    };

    if (isLoading) return <div className="flex items-center justify-center h-64"><Loader2 className="animate-spin h-8 w-8 text-muted-foreground" /></div>;
    if (error) return <div className="p-8 text-center text-red-500">Error loading lemma.</div>;
    if (!lemma) return <div className="p-8 text-center text-muted-foreground">No lemma found.</div>;

    const synonyms = (lemma.lemma_relations || []).filter(r => r.relation_type === 'synonym');
    const antonyms = (lemma.lemma_relations || []).filter(r => r.relation_type === 'antonym');
    const hypernyms = (lemma.lemma_relations || []).filter(r => r.relation_type === 'hypernym');
    const senses = lemma.senses || [];
    const variants = lemma.variants || [];

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <div className="flex items-center gap-2 mb-1">
                        <Button variant="ghost" size="sm" onClick={() => navigate('/admin/dictionary')}>
                            <ArrowLeft className="h-4 w-4 mr-1" /> Back
                        </Button>
                    </div>
                    <div className="flex items-center gap-3">
                        <h2 className="text-3xl font-bold font-arabic">{lemma.lemma}</h2>
                        <Badge variant="outline">{lemma.pos || 'no POS'}</Badge>
                        <Badge variant={lemma.status === 'approved' ? 'default' : lemma.status === 'rejected' ? 'destructive' : 'outline'}>
                            {lemma.status}
                        </Badge>
                    </div>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={handleScrape} disabled={isScraping}>
                        {isScraping ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Globe className="mr-2 h-4 w-4" />}
                        Scrape Sindhila
                    </Button>
                    {lemma.status !== 'approved' && (
                        <Button size="sm" onClick={() => approveLemma.mutate()} disabled={approveLemma.isPending}>
                            <Check className="mr-1 h-4 w-4" /> Approve
                        </Button>
                    )}
                </div>
            </div>

            <Tabs defaultValue="general">
                <TabsList>
                    <TabsTrigger value="general">General</TabsTrigger>
                    <TabsTrigger value="morphology">Morphology</TabsTrigger>
                    <TabsTrigger value="senses">Senses ({senses.length})</TabsTrigger>
                    <TabsTrigger value="relations">Relations ({synonyms.length + antonyms.length + hypernyms.length})</TabsTrigger>
                    <TabsTrigger value="variants">Variants ({variants.length})</TabsTrigger>
                </TabsList>

                {/* ═══ General Tab ═══ */}
                <TabsContent value="general" className="mt-4">
                    <Card>
                        <CardHeader><CardTitle className="text-lg flex items-center gap-2"><BookOpen className="h-4 w-4" /> Word Details</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Word (سنڌي)</Label>
                                    <Input value={lemmaForm.lemma || ''} onChange={(e) => setLemmaForm({ ...lemmaForm, lemma: e.target.value })} className="font-arabic text-xl" dir="rtl" />
                                </div>
                                <div className="space-y-2">
                                    <Label>Transliteration (Roman)</Label>
                                    <Input value={lemmaForm.transliteration || ''} onChange={(e) => setLemmaForm({ ...lemmaForm, transliteration: e.target.value })} placeholder="e.g. dil" />
                                </div>
                                <div className="space-y-2">
                                    <Label>Part of Speech</Label>
                                    <Select value={lemmaForm.pos || ''} onValueChange={(v) => setLemmaForm({ ...lemmaForm, pos: v })}>
                                        <SelectTrigger><SelectValue placeholder="Select POS" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="noun">Noun</SelectItem>
                                            <SelectItem value="verb">Verb</SelectItem>
                                            <SelectItem value="adjective">Adjective</SelectItem>
                                            <SelectItem value="adverb">Adverb</SelectItem>
                                            <SelectItem value="pronoun">Pronoun</SelectItem>
                                            <SelectItem value="preposition">Preposition</SelectItem>
                                            <SelectItem value="conjunction">Conjunction</SelectItem>
                                            <SelectItem value="interjection">Interjection</SelectItem>
                                            <SelectItem value="particle">Particle</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Status</Label>
                                    <Select value={lemmaForm.status || ''} onValueChange={(v) => setLemmaForm({ ...lemmaForm, status: v })}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pending">Pending</SelectItem>
                                            <SelectItem value="approved">Approved</SelectItem>
                                            <SelectItem value="rejected">Rejected</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="border-t py-3 flex justify-end">
                            <Button onClick={() => updateLemma.mutate(lemmaForm)} disabled={updateLemma.isPending}>
                                <Save className="mr-2 h-4 w-4" /> {updateLemma.isPending ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </CardFooter>
                    </Card>
                </TabsContent>

                {/* ═══ Morphology Tab ═══ */}
                <TabsContent value="morphology" className="mt-4">
                    <Card>
                        <CardHeader><CardTitle className="text-lg flex items-center gap-2"><Type className="h-4 w-4" /> Morphology</CardTitle></CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                                {['root', 'pattern', 'gender', 'number', 'case', 'tense'].map(field => (
                                    <div key={field} className="space-y-2">
                                        <Label className="capitalize">{field}</Label>
                                        <Input value={morphForm[field] || ''} onChange={(e) => setMorphForm({ ...morphForm, [field]: e.target.value })} placeholder={`Enter ${field}`} />
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                        <CardFooter className="border-t py-3 flex justify-end">
                            <Button onClick={() => updateMorphology.mutate(morphForm)} disabled={updateMorphology.isPending}>
                                <Save className="mr-2 h-4 w-4" /> {updateMorphology.isPending ? 'Saving...' : 'Save Morphology'}
                            </Button>
                        </CardFooter>
                    </Card>
                </TabsContent>

                {/* ═══ Senses Tab ═══ */}
                <TabsContent value="senses" className="mt-4 space-y-4">
                    {senses.map((sense, idx) => (
                        <SenseCard
                            key={sense.id}
                            sense={sense}
                            index={idx}
                            onUpdate={(data) => updateSense.mutate({ senseId: sense.id, data })}
                            onDelete={() => { if (confirm('Delete this sense?')) deleteSense.mutate(sense.id); }}
                            saving={updateSense.isPending}
                        />
                    ))}
                    <Button variant="outline" className="w-full border-dashed h-12" onClick={() => addSense.mutate({ definition: '', domain: '' })} disabled={addSense.isPending}>
                        <Plus className="mr-2 h-4 w-4" /> Add New Sense
                    </Button>
                </TabsContent>

                {/* ═══ Relations Tab ═══ */}
                <TabsContent value="relations" className="mt-4 space-y-4">
                    <Card>
                        <CardHeader><CardTitle className="text-lg flex items-center gap-2"><ArrowRightLeft className="h-4 w-4" /> Linguistic Relations</CardTitle></CardHeader>
                        <CardContent className="space-y-6">
                            {/* Synonyms */}
                            <div>
                                <Label className="text-muted-foreground mb-2 block">Synonyms ({synonyms.length})</Label>
                                <div className="flex flex-wrap gap-2">
                                    {synonyms.map(r => (
                                        <Badge key={r.id} variant="secondary" className="font-arabic text-sm gap-1 pr-1">
                                            {r.related_word}
                                            <button onClick={() => deleteRelation.mutate(r.id)} className="ml-1 hover:text-destructive"><X className="h-3 w-3" /></button>
                                        </Badge>
                                    ))}
                                    {synonyms.length === 0 && <span className="text-xs text-muted-foreground">No synonyms</span>}
                                </div>
                            </div>

                            {/* Antonyms */}
                            <div>
                                <Label className="text-muted-foreground mb-2 block">Antonyms ({antonyms.length})</Label>
                                <div className="flex flex-wrap gap-2">
                                    {antonyms.map(r => (
                                        <Badge key={r.id} variant="outline" className="font-arabic text-sm gap-1 pr-1 border-red-200 text-red-700">
                                            {r.related_word}
                                            <button onClick={() => deleteRelation.mutate(r.id)} className="ml-1 hover:text-destructive"><X className="h-3 w-3" /></button>
                                        </Badge>
                                    ))}
                                    {antonyms.length === 0 && <span className="text-xs text-muted-foreground">No antonyms</span>}
                                </div>
                            </div>

                            {/* Hypernyms */}
                            <div>
                                <Label className="text-muted-foreground mb-2 block">Hypernyms ({hypernyms.length})</Label>
                                <div className="flex flex-wrap gap-2">
                                    {hypernyms.map(r => (
                                        <Badge key={r.id} variant="secondary" className="font-arabic text-sm gap-1 pr-1 bg-blue-50 text-blue-700">
                                            {r.related_word}
                                            <button onClick={() => deleteRelation.mutate(r.id)} className="ml-1 hover:text-destructive"><X className="h-3 w-3" /></button>
                                        </Badge>
                                    ))}
                                    {hypernyms.length === 0 && <span className="text-xs text-muted-foreground">No hypernyms</span>}
                                </div>
                            </div>

                            {/* Add Relation */}
                            <div className="border-t pt-4">
                                <Label className="text-sm mb-2 block">Add Relation</Label>
                                <div className="flex gap-2">
                                    <Select value={newRelation.relation_type} onValueChange={(v) => setNewRelation({ ...newRelation, relation_type: v })}>
                                        <SelectTrigger className="w-[140px]"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="synonym">Synonym</SelectItem>
                                            <SelectItem value="antonym">Antonym</SelectItem>
                                            <SelectItem value="hypernym">Hypernym</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Input
                                        value={newRelation.related_word}
                                        onChange={(e) => setNewRelation({ ...newRelation, related_word: e.target.value })}
                                        placeholder="Enter word..."
                                        className="font-arabic"
                                        dir="rtl"
                                    />
                                    <Button onClick={() => { if (newRelation.related_word.trim()) addRelation.mutate(newRelation); }} disabled={addRelation.isPending}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                {/* ═══ Variants Tab ═══ */}
                <TabsContent value="variants" className="mt-4">
                    <Card>
                        <CardHeader><CardTitle className="text-lg flex items-center gap-2"><Languages className="h-4 w-4" /> Variants & Misspellings</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            {variants.length > 0 ? (
                                <div className="rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead className="bg-muted/40">
                                            <tr>
                                                <th className="py-2 px-3 text-left">Variant</th>
                                                <th className="py-2 px-3 text-left">Type</th>
                                                <th className="py-2 px-3 text-left">Dialect</th>
                                                <th className="py-2 px-3 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {variants.map(v => (
                                                <tr key={v.id} className="border-t">
                                                    <td className="py-2 px-3 font-arabic text-lg">{v.variant}</td>
                                                    <td className="py-2 px-3"><Badge variant="outline">{v.type}</Badge></td>
                                                    <td className="py-2 px-3 text-muted-foreground">{v.dialect || '—'}</td>
                                                    <td className="py-2 px-3 text-right">
                                                        <Button variant="ghost" size="sm" className="text-destructive" onClick={() => { if (confirm('Delete this variant?')) deleteVariant.mutate(v.id); }}>
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground text-center py-4">No variants recorded.</p>
                            )}

                            {/* Add Variant */}
                            <div className="border-t pt-4">
                                <Label className="text-sm mb-2 block">Add Variant</Label>
                                <div className="flex gap-2">
                                    <Input
                                        value={newVariant.variant}
                                        onChange={(e) => setNewVariant({ ...newVariant, variant: e.target.value })}
                                        placeholder="Variant word..."
                                        className="font-arabic"
                                        dir="rtl"
                                    />
                                    <Select value={newVariant.type} onValueChange={(v) => setNewVariant({ ...newVariant, type: v })}>
                                        <SelectTrigger className="w-[140px]"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="dialectal">Dialectal</SelectItem>
                                            <SelectItem value="misspelling">Misspelling</SelectItem>
                                            <SelectItem value="historical">Historical</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Input value={newVariant.dialect} onChange={(e) => setNewVariant({ ...newVariant, dialect: e.target.value })} placeholder="Dialect (opt)" className="w-32" />
                                    <Button onClick={() => { if (newVariant.variant.trim()) addVariant.mutate(newVariant); }} disabled={addVariant.isPending}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            {/* Scrape Results Modal */}
            <Dialog open={isScrapeModalOpen} onOpenChange={setIsScrapeModalOpen}>
                <DialogContent className="max-w-2xl max-h-[80vh] flex flex-col">
                    <DialogHeader>
                        <div className="flex items-center justify-between pr-6">
                            <div>
                                <DialogTitle>Sindhila Dictionary Results</DialogTitle>
                                <DialogDescription>
                                    Extracted definitions and translations for <span className="font-arabic text-lg px-2">{lemma.lemma}</span>
                                </DialogDescription>
                            </div>
                            <Button variant="outline" size="sm" onClick={handleCopyScrapeJson} disabled={scrapeResults.length === 0}>
                                {isScrapeCopied ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <Copy className="h-4 w-4 mr-2" />}
                                {isScrapeCopied ? 'Copied' : 'Copy Scrape JSON'}
                            </Button>
                        </div>
                    </DialogHeader>
                    {scrapeError ? (
                        <div className="text-red-500 text-sm p-4 text-center">{scrapeError}</div>
                    ) : (
                        <ScrollArea className="flex-1 px-1">
                            {scrapeResults.length > 0 ? (
                                <div className="space-y-4 pb-4">
                                    {scrapeResults.map((res, idx) => (
                                        <Card key={idx} className="overflow-hidden">
                                            <CardHeader className="py-2 px-3 bg-muted/30 border-b">
                                                <div className="flex items-center justify-between">
                                                    <span className="text-xs font-semibold text-muted-foreground uppercase">{res.source}</span>
                                                    <Button variant="secondary" size="sm" onClick={() => {
                                                        // Insert as new sense pre-filled
                                                        addSense.mutate({
                                                            definition: res.text,
                                                            domain: res.source === 'General' ? '' : res.source
                                                        });
                                                        setIsScrapeModalOpen(false);
                                                    }}>
                                                        + Add as Sense
                                                    </Button>
                                                </div>
                                            </CardHeader>
                                            <CardContent className="p-3">
                                                <p className="font-arabic text-lg leading-relaxed text-right" dir="rtl">{res.text}</p>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <div className="p-8 text-center text-muted-foreground flex flex-col items-center">
                                    <BookOpen className="h-10 w-10 text-muted-foreground/30 mb-3" />
                                    No direct word matches found on Sindhila for this query.
                                </div>
                            )}
                        </ScrollArea>
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
};

/** Sense Card — individual sense with inline editing */
const SenseCard = ({ sense, index, onUpdate, onDelete, saving }) => {
    const [definition, setDefinition] = useState(sense.definition || '');
    const [definitionEn, setDefinitionEn] = useState(sense.definition_en || '');
    const [definitionSd, setDefinitionSd] = useState(sense.definition_sd || '');
    const [domain, setDomain] = useState(sense.domain || '');

    return (
        <Card>
            <CardHeader className="pb-3 border-b bg-muted/20">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Badge>Sense #{index + 1}</Badge>
                        {sense.domain && <Badge variant="outline">{sense.domain}</Badge>}
                    </div>
                    <Button variant="ghost" size="icon" className="h-8 w-8 text-destructive" onClick={onDelete}>
                        <Trash2 className="h-4 w-4" />
                    </Button>
                </div>
            </CardHeader>
            <CardContent className="pt-4 space-y-3">
                <div className="space-y-2">
                    <Label>Definition (Primary)</Label>
                    <Input value={definition} onChange={(e) => setDefinition(e.target.value)} className="font-arabic text-lg" dir="rtl" placeholder="Enter primary definition..." />
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label>Sindhi Meaning</Label>
                        <Input value={definitionSd} onChange={(e) => setDefinitionSd(e.target.value)} className="font-arabic text-md" dir="rtl" placeholder="Enter Sindhi meaning..." />
                    </div>
                    <div className="space-y-2">
                        <Label>English Meaning</Label>
                        <Input value={definitionEn} onChange={(e) => setDefinitionEn(e.target.value)} placeholder="Enter English meaning..." />
                    </div>
                </div>
                <div className="space-y-2">
                    <Label>Domain</Label>
                    <Input value={domain} onChange={(e) => setDomain(e.target.value)} placeholder="e.g. medicine, poetry, colloquial..." />
                </div>
            </CardContent>
            <CardFooter className="bg-muted/10 border-t py-3 flex justify-end">
                <Button size="sm" onClick={() => onUpdate({ definition, definition_en: definitionEn, definition_sd: definitionSd, domain })} disabled={saving}>
                    <Save className="mr-2 h-4 w-4" /> {saving ? 'Saving...' : 'Update Sense'}
                </Button>
            </CardFooter>
        </Card>
    );
};

export default SenseEditor;
