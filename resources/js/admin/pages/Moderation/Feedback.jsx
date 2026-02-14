import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../api/axios';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { toast } from 'sonner';
import { Trash2 } from 'lucide-react';

const FeedbackManagement = () => {
    const queryClient = useQueryClient();

    const { data: feedback, isLoading } = useQuery({
        queryKey: ['admin-feedback'],
        queryFn: async () => {
            const resp = await api.get('/api/admin/feedback');
            return resp.data;
        }
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            return api.delete(`/api/admin/feedback/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['admin-feedback']);
            toast.success('Feedback deleted');
        }
    });

    const renderStars = (rating) => {
        return (
            <div className="flex gap-0.5">
                {Array.from({ length: 5 }).map((_, i) => (
                    <span key={i} className={`text-sm ${i < rating ? 'text-yellow-400' : 'text-gray-200'}`}>★</span>
                ))}
            </div>
        );
    };

    return (
        <div className="p-8 space-y-6">
            <div>
                <h1 className="text-3xl font-bold tracking-tight">User Feedback</h1>
                <p className="text-muted-foreground">Monitor what users are saying about the platform.</p>
            </div>

            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>User</TableHead>
                                <TableHead>Rating</TableHead>
                                <TableHead>Message</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {isLoading ? (
                                <TableRow><TableCell colSpan={5} className="text-center py-8 text-muted-foreground">Loading feedback...</TableCell></TableRow>
                            ) : feedback?.data?.length > 0 ? feedback.data.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">
                                        {item.user?.name || 'Anonymous'}
                                    </TableCell>
                                    <TableCell>{renderStars(item.rating)}</TableCell>
                                    <TableCell className="max-w-[400px] whitespace-normal">
                                        <p className="text-sm">"{item.message}"</p>
                                    </TableCell>
                                    <TableCell>{new Date(item.created_at).toLocaleDateString()}</TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                            onClick={() => {
                                                if (confirm('Delete this feedback?')) deleteMutation.mutate(item.id);
                                            }}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            )) : (
                                <TableRow><TableCell colSpan={5} className="text-center py-8 text-muted-foreground">No feedback found.</TableCell></TableRow>
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
};

export default FeedbackManagement;
