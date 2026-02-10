import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Layers, Search, Filter, Share2, Info } from 'lucide-react';

const ContextClusters = () => {
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Context Clusters</h2>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm"><Share2 className="mr-2 h-4 w-4" /> Export Graph</Button>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card className="hover:border-primary transition-colors cursor-pointer">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium flex justify-between items-center">
                            Education Context
                            <Badge variant="secondary">42% weight</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="text-xs text-muted-foreground flex flex-wrap gap-2">
                            <Badge variant="outline">school</Badge>
                            <Badge variant="outline">teacher</Badge>
                            <Badge variant="outline">student</Badge>
                            <Badge variant="outline">library</Badge>
                        </div>
                        <div className="h-24 bg-muted/30 rounded flex items-center justify-center text-xs italic">
                            Cluster visualization placeholder
                        </div>
                    </CardContent>
                </Card>

                <Card className="hover:border-primary transition-colors cursor-pointer">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium flex justify-between items-center">
                            Literature Context
                            <Badge variant="secondary">28% weight</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="text-xs text-muted-foreground flex flex-wrap gap-2">
                            <Badge variant="outline">poetry</Badge>
                            <Badge variant="outline">writer</Badge>
                            <Badge variant="outline">pages</Badge>
                            <Badge variant="outline">ink</Badge>
                        </div>
                        <div className="h-24 bg-muted/30 rounded flex items-center justify-center text-xs italic">
                            Cluster visualization placeholder
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-lg flex items-center gap-2"><Info className="h-5 w-5" /> About Context Clustering</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        Context clusters are automatically generated using word embedding models trained on the Baakh corpus.
                        They help editors understand how a lemma is used across different domains.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
};

export default ContextClusters;
