import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Loader2, Database, Plus, CheckCircle2 } from 'lucide-react';
import { toast } from 'sonner';

const ScrapedDataList = () => {
    const [page, setPage] = useState(1);
    const queryClient = useQueryClient();

    const { data: scrapesData, isLoading } = useQuery({
        queryKey: ['scrapedData', page],
        queryFn: async () => {
            const res = await api.get(`/api/admin/dictionary/scraped-data?page=${page}&per_page=50`);
            return res.data;
        },
        keepPreviousData: true
    });

    const createLemma = useMutation({
        mutationFn: async ({ word, scraped_data }) => {
            // Create lemma
            const res = await api.post('/api/admin/dictionary/lemmas', { lemma: word, status: 'pending' });

            // Add senses
            if (scraped_data && scraped_data.length > 0) {
                const addPromises = scraped_data.map(sense =>
                    api.post(`/api/admin/dictionary/senses`, {
                        lemma_id: res.data.id,
                        definition: sense.text,
                        domain: sense.source,
                    })
                );
                await Promise.allSettled(addPromises);
            }
            return res.data;
        },
        onSuccess: (data, variables) => {
            toast.success(`Imported "${variables.word}" into Baakh WordNet successfully!`);
            // Refresh table to reflect imported stats if we update status locally
        },
        onError: () => toast.error('Failed to import into Dictionary.')
    });

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Isolated Scraped Data</h2>
                    <p className="text-muted-foreground mt-1">
                        Review auto-scraped definitions from Sindhila before deciding to import them into the permanent Dictionary.
                    </p>
                </div>
            </div>

            <Card className="border-primary/20 bg-primary/5">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Database className="h-5 w-5 text-primary" /> Review Sindhila Scrape Queue
                    </CardTitle>
                    <CardDescription>
                        Records shown here are not yet in the active dictionary unless marked "Imported".
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="flex justify-center p-8"><Loader2 className="animate-spin h-8 w-8 text-muted-foreground" /></div>
                    ) : (scrapesData && scrapesData.data.length > 0) ? (
                        <div className="space-y-4">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/10">
                                        <TableHead className="w-[150px]">Word</TableHead>
                                        <TableHead>Execution Status</TableHead>
                                        <TableHead className="text-right">Senses / payload</TableHead>
                                        <TableHead className="text-right w-[180px]">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {scrapesData.data.map((scrape) => (
                                        <TableRow key={scrape.id}>
                                            <TableCell className="font-arabic text-xl font-bold">{scrape.word}</TableCell>
                                            <TableCell>
                                                {scrape.status === 'pending' && <Badge variant="secondary">Pending Review</Badge>}
                                                {scrape.status === 'imported' && <Badge className="bg-green-100 text-green-800">Already Imported</Badge>}
                                                {scrape.status === 'error_parsing' && <Badge variant="destructive">Parse Error</Badge>}
                                            </TableCell>
                                            <TableCell className="text-right text-muted-foreground">
                                                {scrape.scraped_data ? `${scrape.scraped_data.length} definitions found` : 'No valid payload'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    disabled={!scrape.scraped_data || scrape.status === 'imported' || createLemma.isPending}
                                                    onClick={() => createLemma.mutate({ word: scrape.word, scraped_data: scrape.scraped_data })}
                                                >
                                                    {createLemma.isPending && createLemma.variables?.word === scrape.word ? (
                                                        <Loader2 className="animate-spin h-4 w-4 mr-2" />
                                                    ) : (
                                                        <Plus className="h-4 w-4 mr-2" />
                                                    )}
                                                    Import
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            <div className="flex justify-between items-center mt-4 text-sm text-muted-foreground">
                                <div>
                                    Showing {scrapesData.from} to {scrapesData.to} of {scrapesData.total} entries
                                </div>
                                <div className="space-x-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={page === 1}
                                        onClick={() => setPage(page - 1)}
                                    >
                                        Previous
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={page === scrapesData.last_page}
                                        onClick={() => setPage(page + 1)}
                                    >
                                        Next
                                    </Button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="p-8 text-center text-muted-foreground">No disconnected scrape data found.</div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
};

export default ScrapedDataList;
