import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Download, Trash2, Database, Plus, RefreshCw } from 'lucide-react';

const DatabaseList = () => {
    const queryClient = useQueryClient();
    const [isCreating, setIsCreating] = useState(false);

    const { data: backups, isLoading, isError } = useQuery({
        queryKey: ['backups'],
        queryFn: async () => {
            const response = await api.get('/api/admin/databases');
            return response.data;
        }
    });

    const createMutation = useMutation({
        mutationFn: async () => {
            return api.post('/api/admin/databases');
        },
        onMutate: () => setIsCreating(true),
        onSuccess: () => {
            queryClient.invalidateQueries(['backups']);
            setIsCreating(false);
            alert('Backup created successfully');
        },
        onError: (error) => {
            setIsCreating(false);
            alert(error.response?.data?.message || 'Failed to create backup');
        }
    });

    const deleteMutation = useMutation({
        mutationFn: async (fileName) => {
            await api.delete(`/api/admin/databases/${fileName}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['backups']);
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to delete backup');
        }
    });

    const handleCreate = () => {
        createMutation.mutate();
    };

    const handleDownload = (fileName) => {
        // Trigger download directly via window location or hidden iframe to handle stream
        const url = `/api/admin/databases/download?file_name=${fileName}`;
        // We need to use our axios instance logic to handle auth, but for download links 
        // usually we need a token in URL or cookie auth. Since we use Sanctum with cookies,
        // opening in new tab/window works if cookies are shared (SameSite).
        // Alternatively, use axios to get blob.

        api.get(url, { responseType: 'blob' })
            .then((response) => {
                const href = window.URL.createObjectURL(response.data);
                const link = document.createElement('a');
                link.href = href;
                link.setAttribute('download', fileName);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            })
            .catch((err) => console.error("Download failed", err));
    };

    if (isLoading) return <div className="p-8">Loading backups...</div>;

    return (
        <div className="p-8 space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Database Backups</h1>
                    <p className="text-gray-500 mt-2">Manage your database backups periodically</p>
                </div>
                <Button onClick={handleCreate} disabled={isCreating || createMutation.isPending} className="flex items-center gap-2">
                    {isCreating ? <RefreshCw className="h-4 w-4 animate-spin" /> : <Plus className="h-4 w-4" />}
                    {isCreating ? 'Creating Backup...' : 'Create New Backup'}
                </Button>
            </div>

            <div className="bg-white rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>File Name</TableHead>
                            <TableHead>Size</TableHead>
                            <TableHead>Created At</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {!backups || backups.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={4} className="text-center py-8 text-gray-500">
                                    No backups found. Create one to get started.
                                </TableCell>
                            </TableRow>
                        ) : (
                            backups.map((backup, index) => (
                                <TableRow key={index}>
                                    <TableCell className="font-medium flex items-center gap-2">
                                        <Database className="h-4 w-4 text-blue-500" />
                                        {backup.file_name}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{backup.file_size}</Badge>
                                    </TableCell>
                                    <TableCell>{backup.last_modified}</TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => handleDownload(backup.file_name)}
                                                title="Download"
                                            >
                                                <Download className="h-4 w-4 text-gray-600" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                onClick={() => {
                                                    if (confirm('Are you sure you want to delete this backup?'))
                                                        deleteMutation.mutate(backup.file_name);
                                                }}
                                                disabled={deleteMutation.isPending}
                                                title="Delete"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
};

export default DatabaseList;
