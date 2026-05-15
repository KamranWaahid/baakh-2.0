import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { BarChart, Download, Loader2, Search, ChevronLeft, ChevronRight } from 'lucide-react';

const FrequencyStats = () => {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const { data: stats, isLoading } = useQuery({
        queryKey: ['corpus-stats'],
        queryFn: async () => {
            const res = await api.get('/api/admin/corpus/stats');
            return res.data;
        }
    });

    const { data: frequencyResponse, isLoading: isFrequencyLoading } = useQuery({
        queryKey: ['analytics-frequency', search, page],
        queryFn: async () => {
            const res = await api.get('/api/admin/analytics/frequency', {
                params: { search, page, limit: 25 }
            });
            return res.data;
        },
        placeholderData: (previousData) => previousData
    });

    const formatNumber = (num) => {
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
        return num;
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Frequency Stats</h2>
                <div className="flex items-center gap-2">
                    <Button size="sm"><Download className="mr-2 h-4 w-4" /> Export Report</Button>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground uppercase">Total Sentences</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : formatNumber(stats?.total_sentences || 0)}
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            Analyzed in primary corpus
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground uppercase">Total Tokens</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : formatNumber(stats?.total_tokens || 0)}
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            Individual subword units
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground uppercase">Unique Sources</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : stats?.sources?.length || 0}
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            Data providers contributing
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-4">
                        <CardTitle className="flex items-center gap-2">
                            <BarChart className="h-4 w-4" /> Word Frequency
                        </CardTitle>
                        <div className="relative flex-1 max-w-md ml-auto">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search words..."
                                className="pl-8"
                                value={search}
                                onChange={(e) => {
                                    setSearch(e.target.value);
                                    setPage(1);
                                }}
                            />
                        </div>
                        {isFrequencyLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border divide-y">
                        {(frequencyResponse?.data || []).map((row) => (
                            <div key={`${row.word}-${row.id || row.frequency}`} className="p-3 flex items-center justify-between gap-4">
                                <div>
                                    <p className="font-arabic text-lg" dir="rtl">{row.word}</p>
                                    <p className="text-xs text-muted-foreground">{row.source_dictionary || row.part_of_speech || row.sindhila_status || 'Dictionary aggregate'}</p>
                                </div>
                                <Badge variant="secondary">{formatNumber(Number(row.frequency || 0))}</Badge>
                            </div>
                        ))}
                        {(!frequencyResponse?.data || frequencyResponse.data.length === 0) && !isFrequencyLoading && (
                            <div className="h-32 flex items-center justify-center text-muted-foreground">
                                No frequency data available.
                            </div>
                        )}
                    </div>

                    <div className="flex items-center justify-between space-x-2 py-4">
                        <div className="text-sm text-muted-foreground">
                            Showing <strong>{frequencyResponse?.from || 0}</strong> to <strong>{frequencyResponse?.to || 0}</strong> of <strong>{frequencyResponse?.total || 0}</strong>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1}>
                                <ChevronLeft className="h-4 w-4" /> Previous
                            </Button>
                            <Button variant="outline" size="sm" onClick={() => setPage((p) => p + 1)} disabled={!frequencyResponse?.next_page_url}>
                                Next <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default FrequencyStats;
