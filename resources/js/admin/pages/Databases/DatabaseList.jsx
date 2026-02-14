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
import { Download, Trash2, Database, Plus, RefreshCw, Zap } from 'lucide-react';

const DatabaseList = () => {
    const queryClient = useQueryClient();
    const [isCreating, setIsCreating] = useState(false);
    const [isMigrating, setIsMigrating] = useState(false);

    const { data: backups, isLoading, isError, refetch } = useQuery({
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
            // No alert for cleaner UX
        },
        onError: (error) => {
            setIsCreating(false);
            const msg = error.response?.data?.message || 'Failed to create backup';
            alert(msg);
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

    const migrateMutation = useMutation({
        mutationFn: async () => {
            return api.post('/api/admin/databases/migrate');
        },
        onMutate: () => setIsMigrating(true),
        onSuccess: (response) => {
            setIsMigrating(false);
            const output = response.data.output || 'No output returned.';
            console.log("Migration Output:", output);
            alert("Migrations executed successfully!\n\nCheck console for detailed output.");
        },
        onError: (error) => {
            setIsMigrating(false);
            const msg = error.response?.data?.message || 'Migration failed';
            const detail = error.response?.data?.error || '';
            alert(`${msg}${detail ? ': ' + detail : ''}`);
        }
    });

    const handleCreate = () => {
        createMutation.mutate();
    };

    const handleMigrate = () => {
        if (confirm('CRITICAL: Are you sure you want to run database migrations? This will attempt to sync your local schema changes to this server. Ensure you have a backup first.')) {
            migrateMutation.mutate();
        }
    };

    const handleDownload = (fileName) => {
        // Use our standard api instance to fetch the blob
        api.get(`/api/admin/databases/download`, {
            params: { file_name: fileName },
            responseType: 'blob'
        })
            .then((response) => {
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', fileName);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            })
            .catch((err) => {
                console.error("Download failed", err);
                alert("Download failed. The file may have been moved or deleted.");
            });
    };

    if (isLoading) return <div className="p-8">Loading backups...</div>;

    return (
        <div className="p-8 space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Database Backups</h1>
                    <p className="text-gray-500 mt-2">Manage your database backups periodically</p>
                </div>
                <div className="flex flex-col sm:flex-row gap-3">
                    <Button
                        onClick={handleMigrate}
                        disabled={isMigrating || migrateMutation.isPending}
                        variant="secondary"
                        className="flex items-center gap-2 border-yellow-200 bg-yellow-50 hover:bg-yellow-100 text-yellow-800"
                    >
                        {isMigrating ? <RefreshCw className="h-4 w-4 animate-spin" /> : <Zap className="h-4 w-4" />}
                        {isMigrating ? 'Running Migrations...' : 'Run Migrations'}
                    </Button>
                    <Button onClick={handleCreate} disabled={isCreating || createMutation.isPending} className="flex items-center gap-2">
                        {isCreating ? <RefreshCw className="h-4 w-4 animate-spin" /> : <Plus className="h-4 w-4" />}
                        {isCreating ? 'Creating Backup...' : 'Create New Backup'}
                    </Button>
                </div>
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
