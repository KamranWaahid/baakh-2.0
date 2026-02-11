import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Trash2,
    Plus,
    Merge,
    Split,
    Check,
    Info,
    BookOpen,
    Quote,
    Loader2,
    Save
} from 'lucide-react';

const SenseEditor = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data: lemma, isLoading, error } = useQuery({
        queryKey: ['lemma', id],
        queryFn: async () => {
            if (!id) return null;
            const res = await api.get(`/api/admin/dictionary/lemmas/${id}`);
            return res.data;
        },
        enabled: !!id
    });

    const updateSenseMutation = useMutation({
        mutationFn: async ({ senseId, data }) => {
            return await api.put(`/api/admin/dictionary/senses/${senseId}`, data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
        }
    });

    const deleteSenseMutation = useMutation({
        mutationFn: async (senseId) => {
            if (!confirm('Are you sure you want to delete this sense?')) return;
            return await api.delete(`/api/admin/dictionary/senses/${senseId}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
        }
    });

    const addExampleMutation = useMutation({
        mutationFn: async ({ senseId, data }) => {
            return await api.post(`/api/admin/dictionary/senses/${senseId}/examples`, data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
        }
    });

    const deleteExampleMutation = useMutation({
        mutationFn: async (exampleId) => {
            if (!confirm('Delete this example?')) return;
            return await api.delete(`/api/admin/dictionary/examples/${exampleId}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
        }
    });

    const approveLemmaMutation = useMutation({
        mutationFn: async () => {
            return await api.patch(`/api/admin/dictionary/lemmas/${id}/approve`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['lemma', id]);
        }
    });

    if (isLoading) return <div className="flex items-center justify-center h-64"><Loader2 className="animate-spin h-8 w-8 text-muted-foreground" /></div>;
    if (error) return <div className="p-8 text-center text-red-500">Error loading lemma details.</div>;
    if (!lemma) return <div className="p-8 text-center">Please select a lemma from the inbox.</div>;

    const senses = lemma.senses || [];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Sense Editor</h2>
                    <div className="flex items-center gap-2 mt-1">
                        <p className="text-muted-foreground font-arabic text-2xl">{lemma.lemma}</p>
                        <Badge variant="outline" className="text-xs uppercase">{lemma.pos}</Badge>
                    </div>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={() => navigate('/admin/dictionary/lemma-inbox')}>Back to Inbox</Button>
                    <Button size="sm" onClick={() => addSenseMutation.mutate({ definition: 'New Sense Definition' })}>
                        <Plus className="mr-2 h-4 w-4" /> Add Sense
                    </Button>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div className="lg:col-span-2 space-y-4">
                    {senses.length > 0 ? senses.map((sense, idx) => (
                        <Card key={sense.id}>
                            <CardHeader className="pb-3 border-b bg-muted/20">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Badge>Sense #{idx + 1}</Badge>
                                        {sense.domain && <Badge variant="outline">{sense.domain}</Badge>}
                                        <Badge variant={sense.status === 'approved' ? 'success' : 'outline'}>{sense.status}</Badge>
                                    </div>
                                    <div className="flex gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-destructive"
                                            onClick={() => deleteSenseMutation.mutate(sense.id)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="pt-6 space-y-4">
                                <div className="space-y-2">
                                    <Label>Definition</Label>
                                    <Input
                                        value={sense.definition}
                                        className="text-lg font-medium"
                                        onChange={(e) => {
                                            const newSenses = [...senses];
                                            newSenses[idx].definition = e.target.value;
                                            queryClient.setQueryData(['lemma', id], { ...lemma, senses: newSenses });
                                        }}
                                    />
                                </div>
                                <div className="space-y-3">
                                    <Label className="flex items-center gap-2 text-muted-foreground"><Quote className="h-3 w-3" /> Example Sentences</Label>
                                    {sense.examples?.map((ex, i) => (
                                        <div key={ex.id} className="flex gap-2">
                                            <Input
                                                value={ex.sentence}
                                                className="flex-1 italic"
                                                onChange={(e) => {
                                                    const newSenses = [...senses];
                                                    newSenses[idx].examples[i].sentence = e.target.value;
                                                    queryClient.setQueryData(['lemma', id], { ...lemma, senses: newSenses });
                                                }}
                                            />
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-9 w-9 shrink-0 text-destructive"
                                                onClick={() => deleteExampleMutation.mutate(ex.id)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="w-full border-dashed border-2 h-10"
                                        onClick={() => addExampleMutation.mutate({ senseId: sense.id, data: { sentence: 'New example sentence' } })}
                                    >
                                        <Plus className="mr-2 h-4 w-4" /> Add Example
                                    </Button>
                                </div>
                            </CardContent>
                            <CardFooter className="bg-muted/10 border-t py-3 flex justify-between items-center">
                                <div className="text-xs text-muted-foreground">
                                    Last updated: {new Date(sense.updated_at).toLocaleDateString()}
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => {
                                            updateSenseMutation.mutate({
                                                senseId: sense.id,
                                                data: { definition: sense.definition }
                                            });
                                        }}
                                        disabled={updateSenseMutation.isPending}
                                    >
                                        <Save className="mr-2 h-4 w-4" /> {updateSenseMutation.isPending ? 'Saving...' : 'Update'}
                                    </Button>
                                    {sense.status !== 'approved' && (
                                        <Button
                                            size="sm"
                                            onClick={() => updateSenseMutation.mutate({ senseId: sense.id, data: { status: 'approved' } })}
                                        >
                                            <Check className="mr-2 h-4 w-4" /> Approve
                                        </Button>
                                    )}
                                </div>
                            </CardFooter>
                        </Card>
                    )) : (
                        <Card className="border-dashed">
                            <CardContent className="py-12 text-center text-muted-foreground">
                                No senses defined for this lemma yet.
                                <Button variant="link" onClick={() => addSenseMutation.mutate({ definition: 'New Sense Definition' })}>Add the first one</Button>
                            </CardContent>
                        </Card>
                    )}
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium flex items-center gap-2">
                                <BookOpen className="h-4 w-4" /> Corpus Evidence
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            <p className="text-xs text-muted-foreground">Quickly find examples from the 118M token mega corpus.</p>
                            <div className="p-3 rounded-md bg-muted/40 border italic">
                                "هي هڪ خاص مثال آهي."
                                <Button variant="link" size="sm" className="h-auto p-0 ml-2">Add to sense</Button>
                            </div>
                            <Button variant="outline" className="w-full text-xs">Search Corpus for "{lemma.lemma}"</Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium flex items-center gap-2">
                                <Info className="h-4 w-4" /> Lemma Metadata
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Frequency Rank:</span>
                                <span>#4,120</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Morphology:</span>
                                <span>{lemma.morphology ? 'Defined' : 'Not Set'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Variants:</span>
                                <span>{lemma.variants?.length || 0}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
};

export default SenseEditor;
