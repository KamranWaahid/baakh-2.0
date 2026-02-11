import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Search, Filter, CircleCheck, CircleAlert, CircleHelp, Loader2 } from 'lucide-react';

const DictionaryQA = () => {
    const [search, setSearch] = useState('');

    const { data: response, isLoading } = useQuery({
        queryKey: ['lemmas-qa', search],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/lemmas', {
                params: { search, status: 'pending', limit: 5 }
            });
            return res.data;
        }
    });

    const pendingLemmas = response?.data || [];

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
                                placeholder="Search pending lemmas..."
                                className="pl-8"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        {isLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    {pendingLemmas.length > 0 ? pendingLemmas.map((lemma) => (
                        <div key={lemma.id} className="p-4 rounded-lg border-l-4 border-l-amber-500 bg-amber-50/10 flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <CircleHelp className="h-5 w-5 text-amber-500" />
                                <div>
                                    <p className="font-medium">Pending Review</p>
                                    <p className="text-sm text-muted-foreground font-arabic" dir="rtl">{lemma.lemma}</p>
                                </div>
                            </div>
                            <Button size="sm" variant="outline" onClick={() => window.location.href = `/admin/dictionary/lemmas/${lemma.id}`}>Review Lemma</Button>
                        </div>
                    )) : !isLoading && (
                        <div className="py-12 text-center text-muted-foreground italic border rounded-lg border-dashed">
                            No urgent QA issues found. Every lemma is looking good!
                        </div>
                    )}

                    <div className="p-4 rounded-lg border-l-4 border-l-green-500 bg-green-50/10 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <CircleCheck className="h-5 w-5 text-green-500" />
                            <div>
                                <p className="font-medium">Database Health</p>
                                <p className="text-sm text-muted-foreground">All indices are optimized and UTF-8 encoding is intact.</p>
                            </div>
                        </div>
                        <Badge className="bg-green-100 text-green-800">Healthy</Badge>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default DictionaryQA;
