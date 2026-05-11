import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Plus, Trash2, MapPin, SpellCheck, TriangleAlert, Loader2, ArrowLeft, Layers, Search, ChevronLeft, ChevronRight } from 'lucide-react';

const Variants = () => {
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
        queryKey: ['dictionary-variants', search, page],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/variants', {
                params: { search, page, limit: 20 }
            });
            return res.data;
        },
        enabled: !id,
        placeholderData: (previousData) => previousData
    });

    const addVariantMutation = useMutation({
        mutationFn: async (data) => {
            return await api.post(`/api/admin/dictionary/lemmas/${id}/variants`, data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
        }
    });

    const deleteVariantMutation = useMutation({
        mutationFn: async (variantId) => {
            if (!confirm('Delete this variant?')) return;
            return await api.delete(`/api/admin/dictionary/variants/${variantId}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
        }
    });

    const handleAddVariant = (type) => {
        const variant = prompt(`Enter new ${type} variant:`);
        if (variant) {
            addVariantMutation.mutate({ variant, type: type === 'dialect' ? 'dialectal' : 'misspelling' });
        }
    };

    if (!id) {
        return (
            <VariantsListView
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
    const variants = lemma?.variants || [];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Variants & Misspellings</h2>
                    <div className="flex items-center gap-2 mt-1">
                        <p className="text-muted-foreground font-arabic text-2xl">{currentLemma.lemma}</p>
                        <Badge variant="outline">ID: {currentLemma.id}</Badge>
                    </div>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={() => navigate('/admin/dictionary/lemma-inbox')}><ArrowLeft className="mr-2 h-4 w-4" /> Back</Button>
                    <Button variant="outline" size="sm">Audit All</Button>
                </div>
            </div>

            {!id ? (
                <Card className="border-dashed border-2">
                    <CardContent className="py-20 text-center text-muted-foreground">
                        <Layers className="h-10 w-10 mx-auto mb-4 opacity-20" />
                        <p>Please select a lemma from the <strong>Lemma Inbox</strong> to manage its variants.</p>
                        <Button className="mt-4" onClick={() => navigate('/admin/dictionary/lemma-inbox')}>Go to Inbox</Button>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="h-4 w-4 text-primary" /> Dialect Variants
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {variants.filter(v => v.type === 'dialectal').length > 0 ? variants.filter(v => v.type === 'dialectal').map((v, i) => (
                                <div key={i} className="flex items-center justify-between p-3 border rounded-lg bg-muted/20">
                                    <div>
                                        <p className="font-arabic text-lg" dir="rtl">{v.variant}</p>
                                        <p className="text-xs text-muted-foreground uppercase">{v.dialect || 'General'}</p>
                                    </div>
                                    <div className="flex gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-destructive"
                                            onClick={() => deleteVariantMutation.mutate(v.id)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            )) : (
                                <p className="text-sm text-muted-foreground italic text-center py-4">No dialect variants found.</p>
                            )}
                            <Button
                                variant="outline"
                                className="w-full border-dashed"
                                onClick={() => handleAddVariant('dialect')}
                            >
                                <Plus className="mr-2 h-4 w-4" /> Add Dialect Variant
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <SpellCheck className="h-4 w-4 text-amber-500" /> Common Misspellings
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {variants.filter(v => v.type === 'misspelling').length > 0 ? variants.filter(v => v.type === 'misspelling').map((v, i) => (
                                <div key={v.id} className="flex items-center justify-between p-3 border rounded-lg bg-orange-50/20 border-orange-200">
                                    <div className="flex items-center gap-3">
                                        <TriangleAlert className="h-4 w-4 text-amber-500" />
                                        <p className="font-arabic text-lg" dir="rtl">{v.variant}</p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Badge variant="outline" className="bg-amber-100 text-amber-800">Auto-fix</Badge>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-destructive"
                                            onClick={() => deleteVariantMutation.mutate(v.id)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            )) : (
                                <p className="text-sm text-muted-foreground italic text-center py-4">No misspellings recorded.</p>
                            )}
                            <Button
                                variant="outline"
                                className="w-full border-dashed"
                                onClick={() => handleAddVariant('misspelling')}
                            >
                                <Plus className="mr-2 h-4 w-4" /> Add Misspelling
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            )}
        </div>
    );
};

const VariantsListView = ({ response, isLoading, search, setSearch, page, setPage, navigate }) => {
    const variants = response?.data || [];
    const meta = response || {};

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-3xl font-bold tracking-tight">Variants & Misspellings</h2>
                <p className="text-muted-foreground mt-1">Browse Open Lexicon variant spellings and open the owning lemma for edits.</p>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-4">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search variants, lemmas, definitions..."
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
                        {variants.length > 0 ? variants.map((variant) => (
                            <div key={variant.id} className="p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div className="space-y-1 min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-arabic text-xl font-semibold" dir="rtl">{variant.variant}</span>
                                        <span className="text-muted-foreground">→</span>
                                        <span className="font-arabic text-lg" dir="rtl">{variant.lemma?.lemma || '—'}</span>
                                        <Badge variant="outline">{variant.source_dictionary || variant.type}</Badge>
                                    </div>
                                    <p className="text-sm text-muted-foreground line-clamp-2 font-arabic" dir="auto">{variant.definition || 'No definition preview'}</p>
                                </div>
                                <Button size="sm" variant="outline" onClick={() => navigate(`/admin/dictionary/lemmas/${variant.lemma_id}/variants`)}>
                                    Manage Lemma Variants
                                </Button>
                            </div>
                        )) : !isLoading ? (
                            <div className="h-32 flex items-center justify-center text-muted-foreground">
                                No variants found in imported lexicon data.
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

export default Variants;
