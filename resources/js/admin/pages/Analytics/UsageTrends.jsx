import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Activity, Clock, TrendingUp, TrendingDown, ArrowUpRight, Loader2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const UsageTrends = () => {
    const { data: trends, isLoading } = useQuery({
        queryKey: ['analytics-trends'],
        queryFn: async () => {
            const res = await api.get('/api/admin/analytics/trends');
            return res.data;
        }
    });

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Usage Trends</h2>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm"><Activity className="mr-2 h-4 w-4" /> Real-time</Button>
                    <Button size="sm">Generate Forecast</Button>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <TrendingUp className="h-4 w-4" /> Lexicon Composition
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[350px] flex flex-col items-center justify-center border rounded-lg border-dashed text-muted-foreground italic gap-2">
                            {isLoading ? <Loader2 className="animate-spin h-8 w-8" /> : (
                                <>
                                    <div>{Number(trends?.totals?.lemmas || 0).toLocaleString()} lemmas</div>
                                    <div>{Number(trends?.totals?.senses || 0).toLocaleString()} senses</div>
                                    <div>{Number(trends?.totals?.approved_lemmas || 0).toLocaleString()} approved lemmas</div>
                                </>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Source Dictionaries</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {trends?.sources?.map((item) => (
                                <div key={item.source_dictionary} className="flex items-center justify-between">
                                    <span className="text-sm">{item.source_dictionary || 'Unknown source'}</span>
                                    <div className="flex items-center text-green-500 text-sm font-semibold">
                                        <ArrowUpRight className="h-4 w-4 mr-1" /> {Number(item.total || 0).toLocaleString()}
                                    </div>
                                </div>
                            )) || (isLoading && <Loader2 className="animate-spin h-4 w-4" />)}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Parts of Speech</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {trends?.parts_of_speech?.map((item) => (
                                <div key={item.name} className="flex items-center justify-between opacity-70">
                                    <span className="text-sm">{item.name}</span>
                                    <div className="flex items-center text-muted-foreground text-sm font-semibold">
                                        <TrendingDown className="h-4 w-4 mr-1" /> {Number(item.total || 0).toLocaleString()}
                                    </div>
                                </div>
                            )) || (isLoading && <Loader2 className="animate-spin h-4 w-4" />)}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-lg flex items-center gap-2"><Clock className="h-5 w-5" /> Recently Updated Lemmas</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    {(trends?.recent_lemmas || []).map((lemma) => (
                        <div key={lemma.id} className="flex items-center justify-between rounded-md border p-3">
                            <span className="font-arabic text-lg" dir="rtl">{lemma.lemma}</span>
                            <Badge variant="outline">{lemma.senses_count} senses · {lemma.status}</Badge>
                        </div>
                    ))}
                    {(!trends?.recent_lemmas || trends.recent_lemmas.length === 0) && !isLoading && (
                        <p className="text-sm text-muted-foreground">No recently updated lemma data available.</p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
};

export default UsageTrends;
