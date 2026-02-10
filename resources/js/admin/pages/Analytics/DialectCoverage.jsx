import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Map, Flag, Info, Globe, Layers } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const DialectCoverage = () => {
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Dialect Coverage</h2>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm"><Map className="mr-2 h-4 w-4" /> View Map</Button>
                    <Button size="sm">Update Coverage</Button>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {[
                    { name: 'Vicholi', coverage: '94%', count: '22,400', color: 'bg-blue-500' },
                    { name: 'Lari', coverage: '78%', count: '18,200', color: 'bg-green-500' },
                    { name: 'Thari', coverage: '62%', count: '14,100', color: 'bg-amber-500' },
                    { name: 'Siraiki', coverage: '55%', count: '12,800', color: 'bg-purple-500' },
                ].map((dialect) => (
                    <Card key={dialect.name}>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium flex justify-between items-center">
                                {dialect.name}
                                <div className={`h-2 w-2 rounded-full ${dialect.color}`} />
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{dialect.coverage}</div>
                            <p className="text-xs text-muted-foreground mt-1">{dialect.count} lemmas covered</p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Globe className="h-4 w-4" /> Regional Lexical Distribution
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="h-[400px] flex items-center justify-center border rounded-lg border-dashed text-muted-foreground italic">
                        Geographical Coverage Map Visualization Placeholder
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
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-arabic" dir="rtl">ٻڪرو vs ڇيلو</span>
                            <Badge variant="secondary">Daily Use</Badge>
                        </div>
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-arabic" dir="rtl">ڄڀ vs زبان</span>
                            <Badge variant="secondary">Anatomy</Badge>
                        </div>
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
                            Identified low coverage in <strong>Kutchi</strong> dialect (32%).
                            Recommended mission: Collect word forms from southern region.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default DialectCoverage;
