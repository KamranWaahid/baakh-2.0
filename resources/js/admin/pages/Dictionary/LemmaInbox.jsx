import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Layers,
    Search,
    Filter,
    ChevronLeft,
    ChevronRight,
    Loader2,
    Edit2
} from 'lucide-react';
import { Link } from 'react-router-dom';

const LemmaInbox = () => {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const { data: response, isLoading } = useQuery({
        queryKey: ['lemmas', search, page],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/lemmas', {
                params: { search, page, limit: 10, status: 'pending' }
            });
            return res.data;
        },
        placeholderData: (previousData) => previousData
    });

    const lemmas = response?.data || [];
    const meta = response || {};

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Lemma Inbox</h2>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm">
                        <Filter className="mr-2 h-4 w-4" /> Filters
                    </Button>
                    <Button size="sm">
                        Refresh
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-4">
                        <div className="relative flex-1 max-w-sm">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search lemmas..."
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
                                    <TableHead>Lemma</TableHead>
                                    <TableHead>POS</TableHead>
                                    <TableHead>Senses</TableHead>
                                    <TableHead>Frequency</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {lemmas.length > 0 ? (
                                    lemmas.map((lemma) => (
                                        <TableRow key={lemma.id}>
                                            <TableCell className="font-arabic text-xl">{lemma.lemma}</TableCell>
                                            <TableCell>{lemma.pos || '-'}</TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">{lemma.senses_count || 0}</Badge>
                                            </TableCell>
                                            <TableCell>{lemma.frequency}%</TableCell>
                                            <TableCell>
                                                <Badge variant={
                                                    lemma.status === 'approved' ? 'success' :
                                                        lemma.status === 'rejected' ? 'destructive' : 'outline'
                                                }>
                                                    {lemma.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button size="sm" variant="outline" asChild>
                                                        <Link to={`/admin/dictionary/lemmas/${lemma.id}`}>
                                                            <Edit2 className="mr-2 h-3 w-3" /> Edit
                                                        </Link>
                                                    </Button>
                                                    <Button size="sm">Approve</Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : !isLoading ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center text-muted-foreground">
                                            No lemmas found. Start by importing from corpus or adding manually.
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
                                onClick={() => setPage(p => Math.max(1, p - 1))}
                                disabled={page === 1}
                            >
                                <ChevronLeft className="h-4 w-4" /> Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setPage(p => p + 1)}
                                disabled={!meta.next_page_url}
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

export default LemmaInbox;
