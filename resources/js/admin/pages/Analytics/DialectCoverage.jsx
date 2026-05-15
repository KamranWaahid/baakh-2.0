import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Map, Flag, Info, Globe, Layers, Loader2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const DialectCoverage = () => {
    const { data, isLoading } = useQuery({
        queryKey: ['analytics-dialect'],
        queryFn: async () => {
            const res = await api.get('/api/admin/analytics/dialect');
            return res.data;
        }
    });

    const dialectRows = data?.variant_dialects?.length ? data.variant_dialects : data?.lexicon_directions || [];
    const topVariants = data?.top_variants || [];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Dialect Coverage</h2>
                <div className="flex items-center gap-2">
                    {isLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
                    <Button variant="outline" size="sm"><Map className="mr-2 h-4 w-4" /> View Aggregates</Button>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {dialectRows.slice(0, 4).map((dialect) => (
                    <Card key={dialect.name}>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium flex justify-between items-center">
                                {dialect.name}
                                <div className="h-2 w-2 rounded-full bg-primary" />
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{Number(dialect.total || 0).toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground mt-1">variant records</p>
                        </CardContent>
                    </Card>
                ))}
                {dialectRows.length === 0 && !isLoading && (
                    <Card className="lg:col-span-4">
                        <CardContent className="py-12 text-center text-muted-foreground">
                            No dialect or language-direction variant data is available yet.
                        </CardContent>
                    </Card>
                )}
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Globe className="h-4 w-4" /> Regional Lexical Distribution
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="h-[400px] flex items-center justify-center border rounded-lg border-dashed text-muted-foreground italic text-center p-8">
                        {Number(data?.totals?.lexicon_variants || 0).toLocaleString()} Open Lexicon variant spellings and{' '}
                        {Number(data?.totals?.curated_variants || 0).toLocaleString()} curated variants are indexed.
                    </div>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium flex items-center gap-2">
                            <Flag className="h-4 w-4" /> Top Dialectal Variations
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {topVariants.slice(0, 8).map((variant) => (
                            <div key={variant.id} className="flex items-center justify-between text-sm">
                                <span className="font-arabic text-lg" dir="rtl">
                                    {variant.lemma?.lemma || '—'} / {variant.word_variant}
                                </span>
                                <Badge variant="secondary">{variant.source_dictionary || variant.language_direction || 'Lexicon'}</Badge>
                            </div>
                        ))}
                        {topVariants.length === 0 && <p className="text-sm text-muted-foreground">No variant examples found.</p>}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium flex items-center gap-2">
                            <Layers className="h-4 w-4" /> Coverage Gaps
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            Coverage is calculated from explicit `lemma_variants` rows and Open Lexicon `word_variant` /
                            `language_direction` fields. Add curated dialect labels to improve regional reporting.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default DialectCoverage;
