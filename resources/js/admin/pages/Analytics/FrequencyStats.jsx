import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { BarChart, TrendingUp, PieChart, Download, Calendar } from 'lucide-react';

const FrequencyStats = () => {
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
                        <CardTitle className="text-sm font-medium text-muted-foreground uppercase">Total Lemmas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">24,512</div>
                        <p className="text-xs text-green-500 flex items-center mt-1">
                            <TrendingUp className="h-3 w-3 mr-1" /> +2.4% from last month
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground uppercase">Unique Word Forms</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">142,890</div>
                        <p className="text-xs text-green-500 flex items-center mt-1">
                            <TrendingUp className="h-3 w-3 mr-1" /> +1.8% from last month
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground uppercase">Corpus Size</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">1.2M</div>
                        <p className="text-xs text-muted-foreground mt-1">
                            sentences analyzed
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card className="lg:col-span-1">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <BarChart className="h-4 w-4" /> POS Distribution
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[300px] flex items-center justify-center border rounded-lg border-dashed text-muted-foreground italic">
                            POS Distribution Chart Placeholder
                        </div>
                    </CardContent>
                </Card>
                <Card className="lg:col-span-1">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <PieChart className="h-4 w-4" /> Domain Coverage
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[300px] flex items-center justify-center border rounded-lg border-dashed text-muted-foreground italic">
                            Domain Coverage Chart Placeholder
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default FrequencyStats;
