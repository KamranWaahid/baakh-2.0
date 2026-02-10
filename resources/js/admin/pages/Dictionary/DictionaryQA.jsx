import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Search, Filter, CircleCheck, CircleAlert, CircleHelp, MessageSquare } from 'lucide-react';

const DictionaryQA = () => {
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">QA & Search</h2>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm"><CircleCheck className="mr-2 h-4 w-4" /> Run Quality Check</Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-4">
                        <div className="relative flex-1 max-w-sm">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search queries or issues..."
                                className="pl-8"
                            />
                        </div>
                        <Button variant="outline" size="sm"><Filter className="mr-2 h-4 w-4" /> Filters</Button>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="p-4 rounded-lg border-l-4 border-l-red-500 bg-red-50/10 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <CircleAlert className="h-5 w-5 text-red-500" />
                            <div>
                                <p className="font-medium">Missing Definition</p>
                                <p className="text-sm text-muted-foreground font-arabic" dir="rtl">ڪتابڙو</p>
                            </div>
                        </div>
                        <Button size="sm" variant="outline">Fix Issue</Button>
                    </div>

                    <div className="p-4 rounded-lg border-l-4 border-l-amber-500 bg-amber-50/10 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <CircleHelp className="h-5 w-5 text-amber-500" />
                            <div>
                                <p className="font-medium">Unresolved Variant Ambiguity</p>
                                <p className="text-sm text-muted-foreground">Multiple dialects linked as primary.</p>
                            </div>
                        </div>
                        <Button size="sm" variant="outline">Review</Button>
                    </div>

                    <div className="p-4 rounded-lg border-l-4 border-l-green-500 bg-green-50/10 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <CircleCheck className="h-5 w-5 text-green-500" />
                            <div>
                                <p className="font-medium">Consensus Reached</p>
                                <p className="text-sm text-muted-foreground">New lemma "اسمارٽ فون" approved by 3 editors.</p>
                            </div>
                        </div>
                        <Badge className="bg-green-100 text-green-800">Verified</Badge>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default DictionaryQA;
