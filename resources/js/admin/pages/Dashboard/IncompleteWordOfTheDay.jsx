import React, { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';
import api from '@/admin/api/axios';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import {
    BookOpenCheck,
    CheckCircle2,
    ExternalLink,
    Loader2,
    SkipForward,
    AlertTriangle,
} from 'lucide-react';

const emptyForm = {
    lemma_id: null,
    lemma: '',
    normalized_lemma: '',
    pos: '',
    transliteration: '',
    ipa: '',
    phonetic: '',
    pronunciation_simple: '',
    definition: '',
    definition_en: '',
    definition_sd: '',
    short_gloss: '',
    language_direction: 'sindhi',
    source: '',
    source_dictionary: '',
    example_sentence: '',
    example_translation: '',
    synonyms: '',
    antonyms: '',
    variants_reviewed: false,
    examples_reviewed: false,
    morphology_reviewed: false,
    pronunciation_reviewed: false,
    review_status: 'reviewed',
    sense_status: 'approved',
    sense_id: null,
};

function wordToForm(word) {
    if (!word) return { ...emptyForm };

    return {
        ...emptyForm,
        lemma_id: word.id,
        lemma: word.lemma || '',
        normalized_lemma: word.normalized_lemma || '',
        pos: word.pos || '',
        transliteration: word.transliteration || '',
        ipa: word.ipa || '',
        phonetic: word.phonetic || '',
        pronunciation_simple: word.pronunciation_simple || '',
        definition: word.sense?.definition || '',
        definition_en: word.sense?.definition_en || '',
        definition_sd: word.sense?.definition_sd || '',
        short_gloss: word.sense?.short_gloss || '',
        language_direction: word.sense?.language_direction || 'sindhi',
        source: word.sense?.source || word.sense?.source_dictionary || '',
        source_dictionary: word.sense?.source_dictionary || word.sense?.source || '',
        example_sentence: word.example?.sentence || '',
        example_translation: word.example?.translation || '',
        synonyms: (word.synonyms || []).join('، '),
        antonyms: (word.antonyms || []).join('، '),
        variants_reviewed: !!word.variants_reviewed,
        examples_reviewed: !!word.examples_reviewed,
        morphology_reviewed: !!word.morphology_reviewed,
        pronunciation_reviewed: !!word.pronunciation_reviewed,
        review_status: word.sense?.review_status || 'reviewed',
        sense_status: word.sense?.status || 'approved',
        sense_id: word.sense?.id || null,
    };
}

const IncompleteWordOfTheDay = () => {
    const queryClient = useQueryClient();
    const [form, setForm] = useState(emptyForm);

    const { data, isLoading, isError, refetch, isFetching } = useQuery({
        queryKey: ['incomplete-word-of-the-day'],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/word-of-the-day');
            return res.data;
        },
        staleTime: 30_000,
    });

    useEffect(() => {
        if (data?.word) {
            setForm(wordToForm(data.word));
        } else if (data?.all_complete) {
            setForm(emptyForm);
        }
    }, [data?.assignment_id, data?.word?.id, data?.all_complete]);

    const saveMutation = useMutation({
        mutationFn: async (payload) => {
            const res = await api.post('/api/admin/dictionary/word-of-the-day/save', payload);
            return res.data;
        },
        onSuccess: (payload) => {
            queryClient.setQueryData(['incomplete-word-of-the-day'], payload);
            if (payload.just_completed) {
                toast.success(payload.message || 'Word completed. Next word loaded.');
            } else if (payload.completion && !payload.completion.is_complete) {
                toast.message(payload.message || 'Saved — still incomplete.', {
                    description: `${payload.completion.missing_requirements?.length || 0} required items remaining`,
                });
            } else {
                toast.success(payload.message || 'Saved.');
            }
        },
        onError: (error) => {
            const message = error?.response?.data?.message
                || Object.values(error?.response?.data?.errors || {})?.[0]?.[0]
                || 'Failed to save word.';
            toast.error(message);
        },
    });

    const skipMutation = useMutation({
        mutationFn: async () => {
            const res = await api.post('/api/admin/dictionary/word-of-the-day/skip');
            return res.data;
        },
        onSuccess: (payload) => {
            queryClient.setQueryData(['incomplete-word-of-the-day'], payload);
            toast.message('Skipped. Another incomplete word loaded.');
        },
        onError: () => toast.error('Failed to skip word.'),
    });

    const missing = data?.completion?.missing_requirements || data?.word?.missing_fields || [];
    const progress = data?.progress || { total: 0, completed: 0, incomplete: 0, percent_complete: 0 };

    const setField = (key, value) => {
        setForm((prev) => ({ ...prev, [key]: value }));
    };

    const handleSave = (e) => {
        e.preventDefault();
        saveMutation.mutate({
            ...form,
            variants_reviewed: !!form.variants_reviewed,
            examples_reviewed: !!form.examples_reviewed || !!form.example_sentence,
            morphology_reviewed: !!form.morphology_reviewed,
            pronunciation_reviewed: !!form.pronunciation_reviewed || !!(form.ipa || form.phonetic || form.pronunciation_simple),
        });
    };

    const busy = saveMutation.isPending || skipMutation.isPending;

    const statusBadge = useMemo(() => {
        if (data?.all_complete) {
            return <Badge className="bg-green-100 text-green-800 hover:bg-green-100">Complete</Badge>;
        }
        const score = data?.completion?.score ?? data?.word?.completion_score ?? 0;
        return (
            <Badge variant="outline" className="border-amber-300 text-amber-800 bg-amber-50">
                Incomplete · {score}%
            </Badge>
        );
    }, [data]);

    if (isLoading) {
        return (
            <Card>
                <CardHeader>
                    <Skeleton className="h-7 w-64" />
                    <Skeleton className="h-4 w-96 mt-2" />
                </CardHeader>
                <CardContent className="space-y-3">
                    <Skeleton className="h-24 w-full" />
                    <Skeleton className="h-40 w-full" />
                </CardContent>
            </Card>
        );
    }

    if (isError) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Incomplete Word of the Day</CardTitle>
                    <CardDescription>Could not load today’s incomplete dictionary word.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button variant="outline" onClick={() => refetch()}>Retry</Button>
                </CardContent>
            </Card>
        );
    }

    if (data?.all_complete) {
        return (
            <Card className="border-green-200 bg-green-50/40">
                <CardHeader>
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <CheckCircle2 className="h-5 w-5 text-green-600" />
                                Incomplete Word of the Day
                            </CardTitle>
                            <CardDescription className="mt-1">
                                All dictionary words are complete.
                            </CardDescription>
                        </div>
                        {statusBadge}
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <Stat label="Total words" value={progress.total} />
                        <Stat label="Completed" value={progress.completed} />
                        <Stat label="Incomplete" value={progress.incomplete} />
                        <Stat label="Progress" value={`${progress.percent_complete}%`} />
                    </div>
                </CardContent>
            </Card>
        );
    }

    const word = data?.word;

    return (
        <Card className="border-amber-200/80 shadow-sm">
            <CardHeader className="pb-4">
                <div className="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                    <div className="min-w-0">
                        <CardTitle className="flex items-center gap-2 text-xl md:text-2xl">
                            <BookOpenCheck className="h-5 w-5 text-amber-700" />
                            Incomplete Word of the Day
                        </CardTitle>
                        <CardDescription className="mt-1">
                            Review and complete one incomplete dictionary entry. It stays for today until finished or skipped.
                        </CardDescription>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {statusBadge}
                        <Badge variant="secondary">{data?.selection_date}</Badge>
                        {isFetching && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
                    </div>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-3 pt-4">
                    <Stat label="Total words" value={progress.total} />
                    <Stat label="Completed" value={progress.completed} />
                    <Stat label="Incomplete left" value={progress.incomplete} />
                    <Stat label="Progress" value={`${progress.percent_complete}%`} />
                </div>
            </CardHeader>

            <CardContent>
                <form onSubmit={handleSave} className="space-y-6">
                    <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 rounded-xl border bg-white p-4">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-gray-400 mb-1">Headword</p>
                            <h3 className="text-3xl font-bold font-arabic text-gray-900" dir="rtl">
                                {word?.lemma || '—'}
                            </h3>
                            <p className="text-sm text-gray-500 mt-1">
                                {word?.transliteration ? `/${word.transliteration}/` : 'No romanization yet'}
                                {word?.pos ? ` · ${word.pos}` : ''}
                            </p>
                        </div>
                        <Button type="button" variant="outline" asChild>
                            <Link to={`/admin/dictionary/lemmas/${word?.id}`}>
                                <ExternalLink className="h-4 w-4 mr-2" />
                                Open full editor
                            </Link>
                        </Button>
                    </div>

                    {missing.length > 0 && (
                        <div className="rounded-xl border border-amber-200 bg-amber-50/70 p-4">
                            <div className="flex items-center gap-2 mb-3">
                                <AlertTriangle className="h-4 w-4 text-amber-700" />
                                <h4 className="font-semibold text-amber-900">Missing required fields</h4>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {missing.map((item) => (
                                    <Badge
                                        key={item.key}
                                        variant="outline"
                                        className="border-amber-300 bg-white text-amber-900"
                                        title={item.message}
                                    >
                                        {item.label}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Headword (lemma)">
                            <Input value={form.lemma} onChange={(e) => setField('lemma', e.target.value)} dir="rtl" className="font-arabic" />
                        </Field>
                        <Field label="Normalized form">
                            <Input value={form.normalized_lemma} onChange={(e) => setField('normalized_lemma', e.target.value)} dir="rtl" className="font-arabic" />
                        </Field>
                        <Field label="Part of speech">
                            <Input value={form.pos} onChange={(e) => setField('pos', e.target.value)} placeholder="noun, adjective, verb…" />
                        </Field>
                        <Field label="Romanization / transliteration">
                            <Input value={form.transliteration} onChange={(e) => setField('transliteration', e.target.value)} />
                        </Field>
                        <Field label="Pronunciation (IPA)">
                            <Input value={form.ipa} onChange={(e) => setField('ipa', e.target.value)} />
                        </Field>
                        <Field label="Pronunciation (simple / phonetic)">
                            <Input value={form.phonetic || form.pronunciation_simple} onChange={(e) => {
                                setField('phonetic', e.target.value);
                                setField('pronunciation_simple', e.target.value);
                            }} />
                        </Field>
                    </div>

                    <div className="grid gap-4">
                        <Field label="Primary definition / meaning">
                            <Textarea
                                value={form.definition}
                                onChange={(e) => setField('definition', e.target.value)}
                                rows={3}
                                dir="auto"
                                className="font-arabic"
                                placeholder="Main gloss or definition"
                            />
                        </Field>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="Sindhi meaning">
                                <Textarea
                                    value={form.definition_sd}
                                    onChange={(e) => setField('definition_sd', e.target.value)}
                                    rows={2}
                                    dir="rtl"
                                    className="font-arabic"
                                />
                            </Field>
                            <Field label="English meaning / translation">
                                <Textarea
                                    value={form.definition_en}
                                    onChange={(e) => setField('definition_en', e.target.value)}
                                    rows={2}
                                />
                            </Field>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="Short gloss">
                                <Input value={form.short_gloss} onChange={(e) => setField('short_gloss', e.target.value)} />
                            </Field>
                            <Field label="Language direction">
                                <Input value={form.language_direction} onChange={(e) => setField('language_direction', e.target.value)} placeholder="sindhi / english" />
                            </Field>
                            <Field label="Source">
                                <Input value={form.source} onChange={(e) => setField('source', e.target.value)} />
                            </Field>
                            <Field label="Source dictionary">
                                <Input value={form.source_dictionary} onChange={(e) => setField('source_dictionary', e.target.value)} />
                            </Field>
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Example sentence">
                            <Textarea
                                value={form.example_sentence}
                                onChange={(e) => setField('example_sentence', e.target.value)}
                                rows={2}
                                dir="rtl"
                                className="font-arabic"
                            />
                        </Field>
                        <Field label="Example translation">
                            <Textarea
                                value={form.example_translation}
                                onChange={(e) => setField('example_translation', e.target.value)}
                                rows={2}
                            />
                        </Field>
                        <Field label="Synonyms (comma-separated)">
                            <Input value={form.synonyms} onChange={(e) => setField('synonyms', e.target.value)} dir="auto" className="font-arabic" />
                        </Field>
                        <Field label="Antonyms (comma-separated)">
                            <Input value={form.antonyms} onChange={(e) => setField('antonyms', e.target.value)} dir="auto" className="font-arabic" />
                        </Field>
                    </div>

                    <div className="flex flex-wrap gap-4 text-sm">
                        {[
                            ['variants_reviewed', 'Variants reviewed'],
                            ['examples_reviewed', 'Examples reviewed'],
                            ['morphology_reviewed', 'Morphology reviewed'],
                            ['pronunciation_reviewed', 'Pronunciation reviewed'],
                        ].map(([key, label]) => (
                            <label key={key} className="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={!!form[key]}
                                    onChange={(e) => setField(key, e.target.checked)}
                                    className="rounded border-gray-300"
                                />
                                <span>{label}</span>
                            </label>
                        ))}
                    </div>

                    <div className="flex flex-col sm:flex-row gap-3 pt-2">
                        <Button type="submit" disabled={busy} className="bg-black hover:bg-gray-800 text-white">
                            {saveMutation.isPending ? (
                                <>
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                    Saving…
                                </>
                            ) : (
                                <>
                                    <CheckCircle2 className="h-4 w-4 mr-2" />
                                    Save & check completion
                                </>
                            )}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={busy}
                            onClick={() => skipMutation.mutate()}
                        >
                            {skipMutation.isPending ? (
                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                            ) : (
                                <SkipForward className="h-4 w-4 mr-2" />
                            )}
                            Skip for now
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
};

const Stat = ({ label, value }) => (
    <div className="rounded-lg border bg-white px-3 py-2">
        <div className="text-[11px] uppercase tracking-wide text-gray-400">{label}</div>
        <div className="text-lg font-semibold text-gray-900">{value ?? '—'}</div>
    </div>
);

const Field = ({ label, children }) => (
    <div className="space-y-1.5">
        <Label className="text-xs text-gray-500">{label}</Label>
        {children}
    </div>
);

export default IncompleteWordOfTheDay;
