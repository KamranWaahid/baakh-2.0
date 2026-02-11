import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Layers, Search, Filter, Share2, Info, Loader2 } from 'lucide-react';

const ContextClusters = () => {
    const { data: clusters, isLoading } = useQuery({
        queryKey: ['corpus-clusters'],
        queryFn: async () => {
            const res = await api.get('/api/admin/corpus/clusters');
            return res.data;
        }
    });

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Context Clusters</h2>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm"><Share2 className="mr-2 h-4 w-4" /> Export Graph</Button>
                </div>
            </div>

            {isLoading ? (
                <div className="flex items-center justify-center h-64">
                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {clusters?.map((cluster) => (
                        <Card key={cluster.name} className="hover:border-primary transition-colors cursor-pointer group">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium flex justify-between items-center">
                                    {cluster.name} Context
                                    <Badge variant="secondary">{cluster.weight}% weight</Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="text-xs text-muted-foreground flex flex-wrap gap-2">
                                    {cluster.keywords.map((kw) => (
                                        <Badge key={kw} variant="outline" className="group-hover:bg-primary/5">{kw}</Badge>
                                    ))}
                                </div>
                                <div className={`h-24 bg-${cluster.color}-50/50 rounded flex items-center justify-center text-xs italic border border-${cluster.color}-100`}>
                                    Vector visualization for {cluster.name}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            <Card>
                <CardHeader>
                    <CardTitle className="text-lg flex items-center gap-2"><Info className="h-5 w-5" /> About Context Clustering</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        Context clusters are automatically generated using word embedding models (Word2Vec/FastText) trained on the Baakh corpus.
                        They help editors understand how a lemma is used across different domains and identify sense disambiguation patterns.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
};

export default ContextClusters;
