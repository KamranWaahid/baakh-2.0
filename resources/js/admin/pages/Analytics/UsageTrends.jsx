import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Activity, Clock, TrendingUp, TrendingDown, ArrowUpRight } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const UsageTrends = () => {
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
                            <TrendingUp className="h-4 w-4" /> Historical Word Usage
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[350px] flex items-center justify-center border rounded-lg border-dashed text-muted-foreground italic">
                            Time-series Word Frequency Chart Placeholder
                        </div>
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Trending Up</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {[
                                { word: 'ڊجيٽل', change: '+45%', trend: 'up' },
                                { word: 'آرٽيفيشل', change: '+32%', trend: 'up' },
                                { word: 'نيٽورڪ', change: '+28%', trend: 'up' },
                            ].map((item) => (
                                <div key={item.word} className="flex items-center justify-between">
                                    <span className="font-arabic text-lg" dir="rtl">{item.word}</span>
                                    <div className="flex items-center text-green-500 text-sm font-semibold">
                                        <ArrowUpRight className="h-4 w-4 mr-1" /> {item.change}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Trending Down</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {[
                                { word: 'ٽيليگراف', change: '-12%', trend: 'down' },
                                { word: 'فلاپي', change: '-8%', trend: 'down' },
                            ].map((item) => (
                                <div key={item.word} className="flex items-center justify-between opacity-70">
                                    <span className="font-arabic text-lg" dir="rtl">{item.word}</span>
                                    <div className="flex items-center text-muted-foreground text-sm font-semibold">
                                        <TrendingDown className="h-4 w-4 mr-1" /> {item.change}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-lg flex items-center gap-2"><Clock className="h-5 w-5" /> Seasonal Word Usage</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="h-[200px] flex items-center justify-center border rounded-lg border-dashed text-muted-foreground italic">
                        Heatmap of word usage by season/month
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default UsageTrends;
