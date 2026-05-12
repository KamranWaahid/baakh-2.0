import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Progress } from '@/components/ui/progress';
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
    Copy, CheckCircle2, Search, ChevronLeft, ChevronRight
} from 'lucide-react';
import { toast } from 'sonner';

const initialNewSenseForm = {
    definition: '',
    definition_sd: '',
    definition_en: '',
    domain: '',
    language_direction: '',
    source_dictionary: '',
    review_status: 'unreviewed',
};

const trimValue = (value) => {
    if (typeof value !== 'string') return value;
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
};

const cleanSensePayload = (data) => {
    const payload = {};

    Object.entries(data || {}).forEach(([key, value]) => {
        const cleanValue = trimValue(value);
        if (cleanValue !== null && cleanValue !== undefined) {
            payload[key] = cleanValue;
        }
    });

    return payload;
};

const apiErrorMessage = (error, fallback) => {
    const errors = error?.response?.data?.errors;
    if (errors) {
        const validationMessages = Object.values(errors).flat().filter(Boolean);
        if (validationMessages.length > 0) {
            return validationMessages.join(' ');
        }
    }

    return error?.response?.data?.message || fallback;
};

const SenseEditor = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [listSearch, setListSearch] = useState('');
    const [listPage, setListPage] = useState(1);

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

    const { data: senseResponse, isLoading: isListLoading } = useQuery({
        queryKey: ['dictionary-senses', listSearch, listPage],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/senses', {
                params: { search: listSearch, page: listPage, limit: 20 }
            });
            return res.data;
        },
        enabled: !id,
        placeholderData: (previousData) => previousData
    });

    // ── Local state for editable fields ──
    const [lemmaForm, setLemmaForm] = useState({});
    const [morphForm, setMorphForm] = useState({});
    const [newRelation, setNewRelation] = useState({ relation_type: 'synonym', related_word: '' });
    const [newVariant, setNewVariant] = useState({ variant: '', type: 'dialectal', dialect: '' });
    const [isNewSenseFormOpen, setIsNewSenseFormOpen] = useState(false);
    const [newSenseForm, setNewSenseForm] = useState(initialNewSenseForm);

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
                normalized_lemma: lemma.normalized_lemma || '',
                pos: lemma.pos || '',
                transliteration: lemma.transliteration || '',
                ipa: lemma.ipa || '',
                phonetic: lemma.phonetic || '',
                audio_url: lemma.audio_url || '',
                syllabification: lemma.syllabification || '',
                status: lemma.status || 'pending',
                completion_notes: lemma.completion_notes || '',
                variants_reviewed: !!lemma.variants_reviewed,
                examples_reviewed: !!lemma.examples_reviewed,
                morphology_reviewed: !!lemma.morphology_reviewed,
                pronunciation_reviewed: !!lemma.pronunciation_reviewed,
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
        onError: (error) => toast.error(apiErrorMessage(error, 'Failed to save lemma changes.')),
    });

    const updateMorphology = useMutation({
        mutationFn: (data) => api.put(`/api/admin/dictionary/lemmas/${id}/morphology`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Morphology saved successfully.');
        },
        onError: (error) => toast.error(apiErrorMessage(error, 'Failed to save morphology.')),
    });

    const addSense = useMutation({
        mutationFn: (data) => api.post(`/api/admin/dictionary/senses`, { lemma_id: id, ...cleanSensePayload(data) }),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            setNewSenseForm(initialNewSenseForm);
            setIsNewSenseFormOpen(false);
            toast.success('New sense added.');
        },
        onError: (error) => toast.error(apiErrorMessage(error, 'Failed to add new sense.')),
    });

    const updateSense = useMutation({
        mutationFn: ({ senseId, data }) => api.put(`/api/admin/dictionary/senses/${senseId}`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Sense updated successfully.');
        },
        onError: (error) => toast.error(apiErrorMessage(error, 'Failed to update sense.')),
    });

    const deleteSense = useMutation({
        mutationFn: (senseId) => api.delete(`/api/admin/dictionary/senses/${senseId}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Sense deleted successfully.');
        },
        onError: (error) => toast.error(apiErrorMessage(error, 'Failed to delete sense.')),
    });

    const addRelation = useMutation({
        mutationFn: (data) => api.post(`/api/admin/dictionary/lemmas/${id}/relations`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            setNewRelation({ relation_type: 'synonym', related_word: '' });
            toast.success('Relation added.');
        },
        onError: (error) => toast.error(apiErrorMessage(error, 'Failed to add relation.')),
    });

    const deleteRelation = useMutation({
        mutationFn: (relId) => api.delete(`/api/admin/dictionary/relations/${relId}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Relation deleted.');
        },
        onError: (error) => toast.error(apiErrorMessage(error, 'Failed to delete relation.')),
    });

    const addVariant = useMutation({
        mutationFn: (data) => api.post(`/api/admin/dictionary/lemmas/${id}/variants`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            setNewVariant({ variant: '', type: 'dialectal', dialect: '' });
            toast.success('Variant added.');
        },
        onError: (error) => toast.error(apiErrorMessage(error, 'Failed to add variant.')),
    });

    const deleteVariant = useMutation({
        mutationFn: (vId) => api.delete(`/api/admin/dictionary/variants/${vId}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Variant deleted.');
        },
        onError: (error) => toast.error(apiErrorMessage(error, 'Failed to delete variant.')),
    });

    const approveLemma = useMutation({
        mutationFn: () => api.patch(`/api/admin/dictionary/lemmas/${id}/approve`),
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success('Lemma approved.');
        },
        onError: (error) => toast.error(apiErrorMessage(error, 'Failed to approve lemma.')),
    });

    const updateCompletion = useMutation({
        mutationFn: (completion_status) => api.patch(`/api/admin/dictionary/lemmas/${id}/completion`, {
            completion_status,
            completion_notes: lemmaForm.completion_notes || undefined,
        }),
        onSuccess: (res) => {
            queryClient.invalidateQueries(['lemma', id]);
            toast.success(res.data?.message || 'Completion status updated.');
        },
        onError: (error) => toast.error(apiErrorMessage(error, 'Failed to update completion status.')),
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

    if (!id) {
        return (
            <SenseListView
                response={senseResponse}
                isLoading={isListLoading}
                search={listSearch}
                setSearch={setListSearch}
                page={listPage}
                setPage={setListPage}
            />
        );
    }

    if (isLoading) return <div className="flex items-center justify-center h-64"><Loader2 className="animate-spin h-8 w-8 text-muted-foreground" /></div>;
    if (error) return <div className="p-8 text-center text-red-500">Error loading lemma.</div>;
    if (!lemma) return <div className="p-8 text-center text-muted-foreground">No lemma found.</div>;

    const synonyms = (lemma.lemma_relations || []).filter(r => r.relation_type === 'synonym');
    const antonyms = (lemma.lemma_relations || []).filter(r => r.relation_type === 'antonym');
    const hypernyms = (lemma.lemma_relations || []).filter(r => r.relation_type === 'hypernym');
    const senses = lemma.senses || [];
    const variants = lemma.variants || [];
    const sourceSummary = lemma.source_summary || {};
    const isSourceTerm = !!sourceSummary.is_source_term;
    const wordLabel = sourceSummary.word_label || 'Word (سنڌي)';
    const sourceWordDir = isSourceTerm ? 'auto' : 'rtl';
    const hasRealMorphology = !!lemma.has_real_morphology;
    const completion = lemma.completion || {};
    const completionChecks = completion.checks || {};
    const missingRequirements = completion.missing_requirements || [];
    const isComplete = lemma.completion_status === 'complete';

    const handleCreateSense = () => {
        if (!newSenseForm.definition.trim()) {
            toast.error('Enter a primary definition before adding a sense.');
            return;
        }

        addSense.mutate({
            ...newSenseForm,
            language_direction: newSenseForm.language_direction || sourceSummary.language_directions?.[0] || '',
        });
    };

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
                        <h2 className={`text-3xl font-bold ${isSourceTerm ? '' : 'font-arabic'}`} dir={sourceWordDir}>{lemma.lemma}</h2>
                        <Badge variant="outline">{lemma.pos || 'no POS'}</Badge>
                        {sourceSummary.primary_language && <Badge variant="secondary">{sourceSummary.primary_language}</Badge>}
                        {sourceSummary.source_dictionaries?.[0] && <Badge variant="outline">{sourceSummary.source_dictionaries[0]}</Badge>}
                        <Badge variant={lemma.status === 'approved' ? 'default' : lemma.status === 'rejected' ? 'destructive' : 'outline'}>
                            {lemma.status}
                        </Badge>
                        <Badge
                            variant={isComplete ? 'default' : 'outline'}
                            className={isComplete ? 'bg-green-600 hover:bg-green-600' : 'text-amber-700 border-amber-200 bg-amber-50'}
                        >
                            {isComplete ? 'Complete' : `Pending${completion.score ? ` · ${completion.score}%` : ''}`}
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
                    {isComplete ? (
                        <Button size="sm" variant="outline" onClick={() => updateCompletion.mutate('pending')} disabled={updateCompletion.isPending}>
                            Mark Pending
                        </Button>
                    ) : (
                        <Button size="sm" onClick={() => updateCompletion.mutate('complete')} disabled={updateCompletion.isPending}>
                            <CheckCircle2 className="mr-1 h-4 w-4" /> Mark Complete
                        </Button>
                    )}
                </div>
            </div>

            <Tabs defaultValue="general">
                <TabsList>
                    <TabsTrigger value="general">General</TabsTrigger>
                    <TabsTrigger value="completion">Completion</TabsTrigger>
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
                                    <Label>{wordLabel}</Label>
                                    <Input value={lemmaForm.lemma || ''} onChange={(e) => setLemmaForm({ ...lemmaForm, lemma: e.target.value })} className={`${isSourceTerm ? '' : 'font-arabic'} text-xl`} dir={sourceWordDir} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Normalized Form</Label>
                                    <Input value={lemmaForm.normalized_lemma || ''} onChange={(e) => setLemmaForm({ ...lemmaForm, normalized_lemma: e.target.value })} dir="auto" />
                                </div>
                                <div className="space-y-2">
                                    <Label>Transliteration (Roman)</Label>
                                    <Input value={lemmaForm.transliteration || ''} onChange={(e) => setLemmaForm({ ...lemmaForm, transliteration: e.target.value })} placeholder="e.g. dil" />
                                </div>
                                <div className="space-y-2">
                                    <Label>IPA</Label>
                                    <Input value={lemmaForm.ipa || ''} onChange={(e) => setLemmaForm({ ...lemmaForm, ipa: e.target.value })} placeholder="/dil/" />
                                </div>
                                <div className="space-y-2">
                                    <Label>Phonetic Form</Label>
                                    <Input value={lemmaForm.phonetic || ''} onChange={(e) => setLemmaForm({ ...lemmaForm, phonetic: e.target.value })} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Audio URL</Label>
                                    <Input value={lemmaForm.audio_url || ''} onChange={(e) => setLemmaForm({ ...lemmaForm, audio_url: e.target.value })} placeholder="https://..." />
                                </div>
                                <div className="space-y-2">
                                    <Label>Syllabification</Label>
                                    <Input value={lemmaForm.syllabification || ''} onChange={(e) => setLemmaForm({ ...lemmaForm, syllabification: e.target.value })} />
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
                            <div className="rounded-lg border p-4">
                                <Label className="mb-3 block">Review Flags</Label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                                    {[
                                        ['variants_reviewed', 'Variants reviewed'],
                                        ['examples_reviewed', 'Examples reviewed'],
                                        ['morphology_reviewed', 'Morphology reviewed'],
                                        ['pronunciation_reviewed', 'Pronunciation reviewed'],
                                    ].map(([field, label]) => (
                                        <label key={field} className="flex items-center gap-2 rounded-md border p-2">
                                            <input
                                                type="checkbox"
                                                checked={!!lemmaForm[field]}
                                                onChange={(e) => setLemmaForm({ ...lemmaForm, [field]: e.target.checked })}
                                            />
                                            <span>{label}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="border-t py-3 flex justify-end">
                            <Button onClick={() => updateLemma.mutate(lemmaForm)} disabled={updateLemma.isPending}>
                                <Save className="mr-2 h-4 w-4" /> {updateLemma.isPending ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </CardFooter>
                    </Card>

                    <OpenLexiconCard lemma={lemma} className="mt-4" />
                </TabsContent>

                <TabsContent value="completion" className="mt-4">
                    <CompletionChecklistPanel
                        completion={completion}
                        checks={completionChecks}
                        missingRequirements={missingRequirements}
                        isComplete={isComplete}
                        notes={lemmaForm.completion_notes ?? lemma.completion_notes ?? ''}
                        setNotes={(value) => setLemmaForm({ ...lemmaForm, completion_notes: value })}
                        onMarkComplete={() => updateCompletion.mutate('complete')}
                        onMarkPending={() => updateCompletion.mutate('pending')}
                        saving={updateCompletion.isPending}
                    />
                </TabsContent>

                {/* ═══ Morphology Tab ═══ */}
                <TabsContent value="morphology" className="mt-4">
                    <Card>
                        <CardHeader><CardTitle className="text-lg flex items-center gap-2"><Type className="h-4 w-4" /> Morphology</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            {hasRealMorphology ? (
                                <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    {['root', 'pattern', 'gender', 'number', 'case', 'tense'].map(field => (
                                        <div key={field} className="space-y-2">
                                            <Label className="capitalize">{field}</Label>
                                            <Input value={morphForm[field] || ''} onChange={(e) => setMorphForm({ ...morphForm, [field]: e.target.value })} placeholder={`Enter ${field}`} />
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="rounded-lg border border-dashed p-5 space-y-3">
                                    <div>
                                        <p className="font-medium">No morphology fields are available for this imported entry.</p>
                                        <p className="text-sm text-muted-foreground mt-1">
                                            Open Lexicon metadata for this row includes source, language direction, normalized word, domain, and definitions, but not root, pattern, gender, number, case, or tense.
                                        </p>
                                    </div>
                                    <MetadataGrid
                                        items={[
                                            ['Language direction', sourceSummary.language_labels?.join(', ')],
                                            ['Source dictionary', sourceSummary.source_dictionaries?.join(', ')],
                                            ['Domain', sourceSummary.domains?.join(', ')],
                                            ['Normalized word', sourceSummary.normalized_words?.join(', ')],
                                            ['Part of speech', lemma.pos || senses.find(s => s.part_of_speech)?.part_of_speech],
                                        ]}
                                    />
                                </div>
                            )}
                        </CardContent>
                        {hasRealMorphology && (
                            <CardFooter className="border-t py-3 flex justify-end">
                                <Button onClick={() => updateMorphology.mutate(morphForm)} disabled={updateMorphology.isPending}>
                                    <Save className="mr-2 h-4 w-4" /> {updateMorphology.isPending ? 'Saving...' : 'Save Morphology'}
                                </Button>
                            </CardFooter>
                        )}
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
                    {isNewSenseFormOpen ? (
                        <Card className="border-dashed">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <Plus className="h-4 w-4" /> Add New Sense
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="space-y-2">
                                    <Label>Definition (required)</Label>
                                    <Textarea
                                        value={newSenseForm.definition}
                                        onChange={(e) => setNewSenseForm({ ...newSenseForm, definition: e.target.value })}
                                        className="font-arabic text-lg"
                                        dir="auto"
                                        rows={3}
                                        placeholder="Enter the primary definition before creating the sense..."
                                    />
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label>Sindhi Meaning</Label>
                                        <Input
                                            value={newSenseForm.definition_sd}
                                            onChange={(e) => setNewSenseForm({ ...newSenseForm, definition_sd: e.target.value })}
                                            className="font-arabic"
                                            dir="rtl"
                                            placeholder="Optional Sindhi meaning..."
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>English Meaning</Label>
                                        <Input
                                            value={newSenseForm.definition_en}
                                            onChange={(e) => setNewSenseForm({ ...newSenseForm, definition_en: e.target.value })}
                                            placeholder="Optional English meaning..."
                                        />
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label>Domain</Label>
                                        <Input
                                            value={newSenseForm.domain}
                                            onChange={(e) => setNewSenseForm({ ...newSenseForm, domain: e.target.value })}
                                            placeholder="e.g. medicine, poetry, colloquial..."
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Language direction</Label>
                                        <Input
                                            value={newSenseForm.language_direction}
                                            onChange={(e) => setNewSenseForm({ ...newSenseForm, language_direction: e.target.value })}
                                            placeholder={sourceSummary.language_labels?.[0] || 'Optional'}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Source / Provenance</Label>
                                        <Input
                                            value={newSenseForm.source_dictionary}
                                            onChange={(e) => setNewSenseForm({ ...newSenseForm, source_dictionary: e.target.value })}
                                            placeholder="Dictionary, editor, import, or citation..."
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Review Status</Label>
                                        <Select value={newSenseForm.review_status} onValueChange={(v) => setNewSenseForm({ ...newSenseForm, review_status: v })}>
                                            <SelectTrigger><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="unreviewed">Unreviewed</SelectItem>
                                                <SelectItem value="reviewed">Reviewed</SelectItem>
                                                <SelectItem value="curated">Curated</SelectItem>
                                                <SelectItem value="needs_work">Needs work</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter className="border-t py-3 flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => {
                                        setNewSenseForm(initialNewSenseForm);
                                        setIsNewSenseFormOpen(false);
                                    }}
                                    disabled={addSense.isPending}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="button"
                                    onClick={handleCreateSense}
                                    disabled={addSense.isPending || !newSenseForm.definition.trim()}
                                >
                                    {addSense.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Plus className="mr-2 h-4 w-4" />}
                                    Add Sense
                                </Button>
                            </CardFooter>
                        </Card>
                    ) : (
                        <Button variant="outline" className="w-full border-dashed h-12" onClick={() => setIsNewSenseFormOpen(true)} disabled={addSense.isPending}>
                            <Plus className="mr-2 h-4 w-4" /> Add New Sense
                        </Button>
                    )}
                </TabsContent>

                {/* ═══ Relations Tab ═══ */}
                <TabsContent value="relations" className="mt-4 space-y-4">
                    <Card>
                        <CardHeader><CardTitle className="text-lg flex items-center gap-2"><ArrowRightLeft className="h-4 w-4" /> Linguistic Relations</CardTitle></CardHeader>
                        <CardContent className="space-y-6">
                            {synonyms.length + antonyms.length + hypernyms.length === 0 && (
                                <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                    No imported relations were present in Open Lexicon for this entry. Add manual relations here when editorial links are known.
                                </div>
                            )}

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
                                                <th className="py-2 px-3 text-left">Source</th>
                                                <th className="py-2 px-3 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {variants.map(v => (
                                                <tr key={v.id} className="border-t">
                                                    <td className="py-2 px-3 font-arabic text-lg" dir="auto">{v.variant}</td>
                                                    <td className="py-2 px-3">
                                                        <Badge variant={v.is_imported ? 'secondary' : 'outline'}>
                                                            {v.is_imported ? 'imported variant' : v.type}
                                                        </Badge>
                                                    </td>
                                                    <td className="py-2 px-3 text-muted-foreground">
                                                        {v.source_dictionary || v.dialect || v.source || '—'}
                                                        {v.lexical_id && <div className="text-xs">{v.lexical_id}</div>}
                                                    </td>
                                                    <td className="py-2 px-3 text-right">
                                                        {v.is_imported ? (
                                                            <span className="text-xs text-muted-foreground">Read-only</span>
                                                        ) : (
                                                            <Button variant="ghost" size="sm" className="text-destructive" onClick={() => { if (confirm('Delete this variant?')) deleteVariant.mutate(v.id); }}>
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </Button>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground text-center py-4">No manual or imported variants recorded for this entry.</p>
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
    const [shortGloss, setShortGloss] = useState(sense.short_gloss || '');
    const [languageDirection, setLanguageDirection] = useState(sense.language_direction || '');
    const [sourceDictionary, setSourceDictionary] = useState(sense.source_dictionary || sense.source || '');
    const [reviewStatus, setReviewStatus] = useState(sense.review_status || 'unreviewed');

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
                <div className="space-y-2">
                    <Label>Short Gloss</Label>
                    <Input value={shortGloss} onChange={(e) => setShortGloss(e.target.value)} placeholder="Human-friendly short gloss..." />
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
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="space-y-2">
                        <Label>Language Direction</Label>
                        <Input value={languageDirection} onChange={(e) => setLanguageDirection(e.target.value)} placeholder="sindhi, english, English → Sindhi..." />
                    </div>
                    <div className="space-y-2">
                        <Label>Source / Provenance</Label>
                        <Input value={sourceDictionary} onChange={(e) => setSourceDictionary(e.target.value)} placeholder="Source dictionary or editor note..." />
                    </div>
                    <div className="space-y-2">
                        <Label>Review Status</Label>
                        <Select value={reviewStatus} onValueChange={setReviewStatus}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="unreviewed">Unreviewed</SelectItem>
                                <SelectItem value="reviewed">Reviewed</SelectItem>
                                <SelectItem value="curated">Curated</SelectItem>
                                <SelectItem value="needs_work">Needs work</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <SourceMetadataPanel sense={sense} />
            </CardContent>
            <CardFooter className="bg-muted/10 border-t py-3 flex justify-end">
                <Button
                    size="sm"
                    onClick={() => onUpdate({
                        definition,
                        definition_en: definitionEn,
                        definition_sd: definitionSd,
                        short_gloss: shortGloss,
                        domain,
                        language_direction: languageDirection,
                        source_dictionary: sourceDictionary,
                        review_status: reviewStatus,
                    })}
                    disabled={saving}
                >
                    <Save className="mr-2 h-4 w-4" /> {saving ? 'Saving...' : 'Update Sense'}
                </Button>
            </CardFooter>
        </Card>
    );
};

const CompletionChecklistPanel = ({ completion, checks, missingRequirements, isComplete, notes, setNotes, onMarkComplete, onMarkPending, saving }) => (
    <Card>
        <CardHeader>
            <CardTitle className="text-lg flex items-center gap-2">
                <CheckCircle2 className="h-4 w-4" /> Completion Checklist
            </CardTitle>
        </CardHeader>
        <CardContent className="space-y-5">
            <div className="rounded-lg border p-4 space-y-3">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="font-medium">{isComplete ? 'Complete' : 'Pending completion'}</p>
                        <p className="text-sm text-muted-foreground">
                            {completion.passed || 0} of {completion.total || 0} checks passing.
                        </p>
                    </div>
                    <Badge
                        variant={isComplete ? 'default' : 'outline'}
                        className={isComplete ? 'bg-green-600 hover:bg-green-600' : 'text-amber-700 border-amber-200 bg-amber-50'}
                    >
                        {completion.score || 0}%
                    </Badge>
                </div>
                <Progress value={completion.score || 0} />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                {Object.entries(checks).map(([key, check]) => (
                    <div key={key} className={`rounded-md border p-3 ${check.passed ? 'bg-green-50 border-green-100' : 'bg-amber-50 border-amber-100'}`}>
                        <div className="flex items-start gap-2">
                            {check.passed ? (
                                <CheckCircle2 className="mt-0.5 h-4 w-4 text-green-600" />
                            ) : (
                                <X className="mt-0.5 h-4 w-4 text-amber-600" />
                            )}
                            <div>
                                <p className="text-sm font-medium">{check.label}</p>
                                {!check.passed && <p className="text-xs text-muted-foreground mt-1">{check.missing}</p>}
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {missingRequirements.length > 0 && (
                <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <p className="font-medium text-amber-900">Missing requirements</p>
                    <ul className="mt-2 space-y-1 text-sm text-amber-900">
                        {missingRequirements.map((item) => (
                            <li key={item.key}>- {item.message}</li>
                        ))}
                    </ul>
                </div>
            )}

            <div className="space-y-2">
                <Label>Completion Notes</Label>
                <Textarea value={notes} onChange={(e) => setNotes(e.target.value)} rows={3} placeholder="What was reviewed or what remains?" />
            </div>
        </CardContent>
        <CardFooter className="border-t py-3 flex justify-end gap-2">
            <Button variant="outline" onClick={onMarkPending} disabled={saving || !isComplete}>
                Mark Pending
            </Button>
            <Button onClick={onMarkComplete} disabled={saving || isComplete}>
                <CheckCircle2 className="mr-2 h-4 w-4" /> Mark Complete
            </Button>
        </CardFooter>
    </Card>
);

const OpenLexiconCard = ({ lemma, className = '' }) => {
    const summary = lemma.source_summary || {};
    if (!summary.is_open_lexicon) return null;

    return (
        <Card className={className}>
            <CardHeader>
                <CardTitle className="text-lg flex items-center gap-2">
                    <Globe className="h-4 w-4" /> Open Lexicon Source
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <MetadataGrid
                    items={[
                        ['Source word', joinValues(summary.source_words)],
                        ['Normalized word', joinValues(summary.normalized_words)],
                        ['Language direction', joinValues(summary.language_labels)],
                        ['Source dictionary', joinValues(summary.source_dictionaries)],
                        ['Domain', joinValues(summary.domains)],
                        ['Lexical ID', joinValues(summary.lexical_ids)],
                        ['Entry ID', joinValues(summary.entry_ids)],
                        ['Publisher', summary.publisher],
                        ['Prepared by', summary.prepared_by],
                    ]}
                />
                {summary.publisher_url && (
                    <p className="text-xs text-muted-foreground">
                        Publisher URL: <a href={summary.publisher_url} target="_blank" rel="noreferrer" className="underline">{summary.publisher_url}</a>
                    </p>
                )}
            </CardContent>
        </Card>
    );
};

const SourceMetadataPanel = ({ sense }) => {
    const metadata = sense.source_metadata || {};
    const hasMetadata = [
        metadata.lexical_id,
        metadata.entry_id,
        metadata.source_word,
        metadata.source_variant,
        metadata.normalized_word,
        metadata.normalized_definition,
        metadata.source_dictionary,
        metadata.language_label,
    ].some(Boolean);

    if (!hasMetadata) return null;

    return (
        <div className="rounded-lg border bg-muted/20 p-4 space-y-3">
            <div className="flex items-center gap-2 flex-wrap">
                <Badge variant="secondary">Open Lexicon</Badge>
                {metadata.source_dictionary && <Badge variant="outline">{metadata.source_dictionary}</Badge>}
                {metadata.language_label && <Badge variant="outline">{metadata.language_label}</Badge>}
            </div>
            <MetadataGrid
                items={[
                    ['Source word', metadata.source_word],
                    ['Variant / airab', metadata.source_variant],
                    ['Normalized word', metadata.normalized_word],
                    ['Normalized definition', metadata.normalized_definition],
                    ['Lexical ID', metadata.lexical_id],
                    ['Entry ID', metadata.entry_id],
                    ['Part of speech', metadata.part_of_speech],
                    ['Source extra', metadata.source_extra],
                ]}
            />
        </div>
    );
};

const MetadataGrid = ({ items }) => {
    const visibleItems = (items || []).filter(([, value]) => hasDisplayValue(value));

    if (visibleItems.length === 0) {
        return <p className="text-sm text-muted-foreground">No imported metadata available.</p>;
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            {visibleItems.map(([label, value]) => (
                <div key={label} className="rounded-md border bg-background/70 p-3">
                    <p className="text-xs uppercase tracking-wide text-muted-foreground">{label}</p>
                    <p className="mt-1 text-sm break-words" dir="auto">{displayValue(value)}</p>
                </div>
            ))}
        </div>
    );
};

const hasDisplayValue = (value) => {
    if (Array.isArray(value)) return value.some(hasDisplayValue);
    return value !== null && value !== undefined && String(value).trim() !== '';
};

const displayValue = (value) => {
    if (Array.isArray(value)) return joinValues(value);
    return String(value);
};

const joinValues = (value) => {
    if (!Array.isArray(value)) return value;
    return value.filter(hasDisplayValue).join(', ');
};

const SenseListView = ({ response, isLoading, search, setSearch, page, setPage }) => {
    const senses = response?.data || [];
    const meta = response || {};

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Sense Editor</h2>
                    <p className="text-muted-foreground mt-1">Search Open Lexicon senses, then open a lemma for editing.</p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-4">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search words, definitions, lexical IDs, sources..."
                                className="pl-8"
                                value={search}
                                onChange={(e) => {
                                    setSearch(e.target.value);
                                    setPage(1);
                                }}
                            />
                        </div>
                        {isLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border divide-y">
                        {senses.length > 0 ? senses.map((sense) => (
                            <div key={sense.id} className="p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div className="space-y-1 min-w-0">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <span className="font-arabic text-xl font-semibold" dir="rtl">{sense.lemma?.lemma || '—'}</span>
                                        {sense.part_of_speech && <Badge variant="secondary">{sense.part_of_speech}</Badge>}
                                        {sense.source_dictionary && <Badge variant="outline">{sense.source_dictionary}</Badge>}
                                    </div>
                                    <p className="text-sm line-clamp-2 font-arabic" dir="auto">{sense.definition}</p>
                                    {sense.lexical_id && <p className="text-xs text-muted-foreground">{sense.lexical_id}</p>}
                                </div>
                                <Button size="sm" variant="outline" asChild>
                                    <Link to={`/admin/dictionary/lemmas/${sense.lemma_id}`}>
                                        <BookOpen className="mr-2 h-4 w-4" /> Edit Lemma
                                    </Link>
                                </Button>
                            </div>
                        )) : !isLoading ? (
                            <div className="h-32 flex items-center justify-center text-muted-foreground">
                                No senses found.
                            </div>
                        ) : null}
                    </div>

                    <div className="flex items-center justify-between space-x-2 py-4">
                        <div className="text-sm text-muted-foreground">
                            Showing <strong>{meta.from || 0}</strong> to <strong>{meta.to || 0}</strong> of <strong>{meta.total || 0}</strong>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1}>
                                <ChevronLeft className="h-4 w-4" /> Previous
                            </Button>
                            <Button variant="outline" size="sm" onClick={() => setPage((p) => p + 1)} disabled={!meta.next_page_url}>
                                Next <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default SenseEditor;
