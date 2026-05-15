import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Layers, Trash2, Check, MapPin, SpellCheck, Loader2, ArrowLeft, Search, ChevronLeft, ChevronRight } from 'lucide-react';

const MorphologyLab = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const queryClient = useQueryClient();
    const { data: lemma, isLoading } = useQuery({
        queryKey: ['lemma', id],
        queryFn: async () => {
            if (!id) return null;
            const res = await api.get(`/api/admin/dictionary/lemmas/${id}`);
            return res.data;
        },
        enabled: !!id
    });

    const { data: listResponse, isLoading: isListLoading } = useQuery({
        queryKey: ['dictionary-morphology', search, page],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/morphology', {
                params: { search, page, limit: 20, status: 'all' }
            });
            return res.data;
        },
        enabled: !id,
        placeholderData: (previousData) => previousData
    });

    const saveMorphologyMutation = useMutation({
        mutationFn: async (data) => {
            return await api.put(`/api/admin/dictionary/lemmas/${id}/morphology`, data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
            alert('Morphology updated successfully');
        }
    });

    const [morphData, setMorphData] = useState({
        root: '',
        pattern: '',
        gender: '',
        number: '',
        case: '',
        aspect: '',
        tense: ''
    });

    useEffect(() => {
        if (lemma?.morphology) {
            setMorphData(lemma.morphology);
        }
    }, [lemma]);

    const handleSave = () => {
        saveMorphologyMutation.mutate(morphData);
    };

    if (!id) {
        return (
            <MorphologyListView
                response={listResponse}
                isLoading={isListLoading}
                search={search}
                setSearch={setSearch}
                page={page}
                setPage={setPage}
                navigate={navigate}
            />
        );
    }

    if (isLoading) return <div className="flex items-center justify-center h-64"><Loader2 className="animate-spin h-8 w-8 text-muted-foreground" /></div>;
    if (!lemma && id) return <div className="p-8 text-center text-red-500">Lemma not found.</div>;

    const currentLemma = lemma || { lemma: 'Development Mode', id: 0 };
    const sourceSummary = lemma?.source_summary || {};
    const hasRealMorphology = !!lemma?.has_real_morphology;

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Morphology Lab</h2>
                    <div className="flex items-center gap-2 mt-1">
                        <p className="text-muted-foreground font-arabic text-2xl">{currentLemma.lemma}</p>
                        <Badge variant="outline">ID: {currentLemma.id}</Badge>
                    </div>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={() => navigate('/admin/dictionary/lemma-inbox')}><ArrowLeft className="mr-2 h-4 w-4" /> Back</Button>
                    <Button variant="outline" size="sm"><Check className="mr-2 h-4 w-4" /> Approve All</Button>
                </div>
            </div>

            <Card>
                {!hasRealMorphology && (
                    <CardContent className="p-6">
                        <div className="rounded-lg border border-dashed p-5">
                            <p className="font-medium">No morphology fields have been curated for this entry yet.</p>
                            <p className="text-sm text-muted-foreground mt-1">
                                Source metadata is available, and you can add structured root, gender, number, case, aspect, or tense values below.
                            </p>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4 text-sm">
                                <MetaLine label="Language" value={(sourceSummary.language_labels || []).join(', ')} />
                                <MetaLine label="Source" value={(sourceSummary.source_dictionaries || []).join(', ')} />
                                <MetaLine label="Domain" value={(sourceSummary.domains || []).join(', ')} />
                                <MetaLine label="Normalized word" value={(sourceSummary.normalized_words || []).join(', ')} />
                            </div>
                        </div>
                    </CardContent>
                )}
                    <CardContent className="p-6">
                        <Tabs defaultValue="plurals" className="w-full">
                            <TabsList className="grid w-full grid-cols-3 lg:w-[400px]">
                                <TabsTrigger value="plurals">Plurals</TabsTrigger>
                                <TabsTrigger value="cases">Case Forms</TabsTrigger>
                                <TabsTrigger value="dialects">Dialects</TabsTrigger>
                            </TabsList>

                            <TabsContent value="plurals" className="mt-6 space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-4">
                                        <div className="space-y-2">
                                            <Label>Root (اصل)</Label>
                                            <Input
                                                value={morphData.root || ''}
                                                onChange={(e) => setMorphData({ ...morphData, root: e.target.value })}
                                                className="font-arabic text-xl text-right"
                                                dir="rtl"
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Pattern (وزن)</Label>
                                            <Input
                                                value={morphData.pattern || ''}
                                                onChange={(e) => setMorphData({ ...morphData, pattern: e.target.value })}
                                                className="font-arabic text-xl text-right"
                                                dir="rtl"
                                            />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label>Gender</Label>
                                            <Input
                                                value={morphData.gender || ''}
                                                onChange={(e) => setMorphData({ ...morphData, gender: e.target.value })}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Number</Label>
                                            <Input
                                                value={morphData.number || ''}
                                                onChange={(e) => setMorphData({ ...morphData, number: e.target.value })}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Case</Label>
                                            <Input
                                                value={morphData.case || ''}
                                                onChange={(e) => setMorphData({ ...morphData, case: e.target.value })}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Tense/Aspect</Label>
                                            <Input
                                                value={morphData.tense || ''}
                                                onChange={(e) => setMorphData({ ...morphData, tense: e.target.value })}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>

                            <TabsContent value="cases" className="mt-6 space-y-4">
                                <div className="space-y-4">
                                    <div className="p-4 rounded-lg border bg-muted/40 flex items-center justify-between">
                                        <div className="flex items-center gap-4">
                                            <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                                <SpellCheck className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <p className="font-medium">Oblique Case</p>
                                                <p className="text-sm text-muted-foreground font-arabic" dir="rtl">{currentLemma.lemma} کي</p>
                                            </div>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button variant="ghost" size="icon"><Trash2 className="h-4 w-4" /></Button>
                                            <Button size="sm">Edit</Button>
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>

                            <TabsContent value="dialects" className="mt-6 space-y-4">
                                <div className="flex flex-col gap-4">
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div className="p-4 rounded-lg border space-y-2">
                                            <div className="flex items-center gap-2 text-primary">
                                                <MapPin className="h-4 w-4" />
                                                <span className="text-sm font-semibold uppercase">Lari</span>
                                            </div>
                                            <p className="font-arabic text-xl" dir="rtl">{currentLemma.lemma}ي</p>
                                            <Button variant="link" size="sm" className="p-0 h-auto">Link to source</Button>
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>
                        </Tabs>
                    </CardContent>
            </Card>

            <div className="flex justify-end gap-2 pt-4">
                <Button variant="outline" onClick={() => navigate('/admin/dictionary/lemma-inbox')}>Cancel</Button>
                <Button onClick={handleSave} disabled={saveMorphologyMutation.isPending}>
                    {saveMorphologyMutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Check className="mr-2 h-4 w-4" />}
                    Save Morphology
                </Button>
            </div>
        </div>
    );
};

const MetaLine = ({ label, value }) => {
    if (!value) return null;

    return (
        <div className="rounded-md border bg-background/70 p-3">
            <p className="text-xs uppercase tracking-wide text-muted-foreground">{label}</p>
            <p className="mt-1 break-words" dir="auto">{value}</p>
        </div>
    );
};

const MorphologyListView = ({ response, isLoading, search, setSearch, page, setPage, navigate }) => {
    const lemmas = response?.data || [];
    const meta = response || {};

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-3xl font-bold tracking-tight">Morphology Lab</h2>
                <p className="text-muted-foreground mt-1">Browse lemmas and open one to edit morphology fields.</p>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-4">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search lemma, normalized form, root, pattern..."
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
                        {lemmas.length > 0 ? lemmas.map((lemma) => (
                            <div key={lemma.id} className="p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-arabic text-xl font-semibold" dir="rtl">{lemma.lemma}</span>
                                        {lemma.pos && <Badge variant="secondary">{lemma.pos}</Badge>}
                                        <Badge variant={lemma.morphology ? 'default' : 'outline'}>
                                            {lemma.morphology ? 'Morphology saved' : 'No morphology'}
                                        </Badge>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {lemma.morphology?.root || 'No root'} · {lemma.morphology?.pattern || 'No pattern'} · {lemma.senses_count || 0} senses
                                    </p>
                                </div>
                                <Button size="sm" variant="outline" onClick={() => navigate(`/admin/dictionary/lemmas/${lemma.id}/morphology`)}>
                                    Edit Morphology
                                </Button>
                            </div>
                        )) : !isLoading ? (
                            <div className="h-32 flex items-center justify-center text-muted-foreground">
                                No lemmas found.
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

export default MorphologyLab;
