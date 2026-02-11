import React, { useState } from 'react';
import { useQuery, keepPreviousData } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Search,
    Filter,
    BookOpen,
    Tag as TagIcon,
    MoreVertical,
    ExternalLink,
    ChevronLeft,
    ChevronRight,
    Loader2
} from 'lucide-react';

const SentenceExplorer = () => {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const { data: response, isLoading, error } = useQuery({
        queryKey: ['corpus-sentences', search, page],
        queryFn: async () => {
            console.log('Fetching corpus sentences:', { search, page });
            const res = await api.get('/api/admin/corpus/sentences', {
                params: { search, page, limit: 15 }
            });
            console.log('Corpus response:', res.data);
            return res.data;
        },
        placeholderData: keepPreviousData
    });

    const sentences = response?.data || [];
    const meta = response || {};

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Sentence Explorer</h2>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm"><Filter className="mr-2 h-4 w-4" /> Filters</Button>
                    <Button size="sm"><BookOpen className="mr-2 h-4 w-4" /> Export Corpus</Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-4">
                        <div className="relative flex-1 max-w-sm">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search sentences..."
                                className="pl-8"
                                value={search}
                                onChange={(e) => {
                                    setSearch(e.target.value);
                                    setPage(1);
                                }}
                            />
                        </div>
                        {isLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[60%]">Sentence</TableHead>
                                    <TableHead>Source</TableHead>
                                    <TableHead>Tokens</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sentences.length > 0 ? (
                                    sentences.map((sentence) => (
                                        <TableRow key={sentence.id} className="group">
                                            <TableCell className="font-arabic text-xl py-4" dir="rtl">
                                                {sentence.sentence}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{sentence.source || 'General'}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <span className="text-xs text-muted-foreground">
                                                    {sentence.token_count} tokens
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button variant="ghost" size="icon"><ExternalLink className="h-4 w-4" /></Button>
                                                <Button variant="ghost" size="icon"><MoreVertical className="h-4 w-4" /></Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : !isLoading ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="h-24 text-center">
                                            No sentences found.
                                        </TableCell>
                                    </TableRow>
                                ) : null}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="flex items-center justify-between space-x-2 py-4">
                        <div className="text-sm text-muted-foreground">
                            Showing <strong>{meta.from || 0}</strong> to <strong>{meta.to || 0}</strong> of <strong>{meta.total || 0}</strong> results
                        </div>
                        <div className="flex items-center space-x-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setPage((old) => Math.max(old - 1, 1))}
                                disabled={page === 1}
                            >
                                <ChevronLeft className="h-4 w-4" /> Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setPage((old) => (sentences.length ? old + 1 : old))}
                                disabled={!response?.next_page_url}
                            >
                                Next <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default SentenceExplorer;
