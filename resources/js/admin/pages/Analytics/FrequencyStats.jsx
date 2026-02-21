import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { BarChart, TrendingUp, PieChart, Download, Calendar, Loader2 } from 'lucide-react';

const FrequencyStats = () => {
    const { data: stats, isLoading } = useQuery({
        queryKey: ['corpus-stats'],
        queryFn: async () => {
            console.log('Fetching corpus stats');
            const res = await api.get('/api/admin/corpus/stats');
            console.log('Corpus stats response:', res.data);
            return res.data;
        }
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
                    <Button variant="outline" size="sm"><Calendar className="mr-2 h-4 w-4" /> Period</Button>
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

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card className="lg:col-span-1">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <BarChart className="h-4 w-4" /> Source Distribution
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {stats?.sources?.map((source) => (
                                <div key={source.source} className="flex items-center justify-between">
                                    <span className="text-sm font-medium">{source.source || 'General'}</span>
                                    <Badge variant="secondary">{formatNumber(source.count)} sentences</Badge>
                                </div>
                            ))}
                            {(!stats?.sources || stats.sources.length === 0) && !isLoading && (
                                <div className="h-[200px] flex items-center justify-center border rounded-lg border-dashed text-muted-foreground italic">
                                    No source data available
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
                <Card className="lg:col-span-1">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <PieChart className="h-4 w-4" /> Statistics Overview
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[300px] flex items-center justify-center border rounded-lg border-dashed text-muted-foreground italic text-center p-4">
                            Real-time aggregation of 118M tokens in progress.<br />Charts will populate as data is indexed.
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default FrequencyStats;
