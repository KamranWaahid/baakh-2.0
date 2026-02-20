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
import { Download, Trash2, Database, Plus, RefreshCw, Zap, Eye, FileJson } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";

const DatabaseList = () => {
    const queryClient = useQueryClient();
    const [isCreating, setIsCreating] = useState(false);
    const [isMigrating, setIsMigrating] = useState(false);
    const [selectedTable, setSelectedTable] = useState(null);
    const [schemaData, setSchemaData] = useState(null);
    const [isSchemaLoading, setIsSchemaLoading] = useState(false);
    const [isSchemaOpen, setIsSchemaOpen] = useState(false);
    const [dbTables, setDbTables] = useState([]);

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
            alert("Migrations executed successfully!");
        },
        onError: (error) => {
            setIsMigrating(false);
            const data = error.response?.data;
            const msg = `MIGRATION FAILED!\n\n` +
                `Message: ${data?.message || 'Unknown error'}\n` +
                `Error: ${data?.error || 'N/A'}\n` +
                `Location: ${data?.details || 'N/A'}\n\n` +
                `Check logs for full stack trace.`;
            alert(msg);
        }
    });

    const repairMutation = useMutation({
        mutationFn: async () => {
            return api.post('/api/admin/databases/repair-permissions');
        },
        onSuccess: (response) => {
            alert(response.data.message || "Permissions repaired successfully!");
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Permission repair failed');
        }
    });

    const clearCacheMutation = useMutation({
        mutationFn: async () => {
            return api.post('/api/admin/databases/clear-cache');
        },
        onSuccess: (response) => {
            alert(response.data.message || "Cache cleared successfully!");
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Cache clearing failed');
        }
    });

    const schemaMutation = useMutation({
        mutationFn: async (tableName) => {
            setIsSchemaLoading(true);
            setSelectedTable(tableName);
            setIsSchemaOpen(true);
            const response = await api.get(`/api/admin/databases/schema?table=${tableName}`);
            return response.data;
        },
        onSuccess: (data) => {
            setSchemaData(data);
            setIsSchemaLoading(false);
        },
        onError: (error) => {
            setIsSchemaLoading(false);
            alert(error.response?.data?.error || 'Failed to fetch schema');
            setIsSchemaOpen(false);
        }
    });

    const statusMutation = useMutation({
        mutationFn: async () => {
            const response = await api.get('/api/admin/databases/status');
            return response.data;
        },
        onSuccess: (data) => {
            console.log("DB Status:", data);
            setDbTables(data.all_tables || []);
            const msg = `Database: ${data.database}\n` +
                `Tables Count: ${data.tables_count}\n` +
                `Pending Migrations: ${data.pending_migrations_count}\n` +
                `Notifications Table: ${data.notifications_table_exists ? 'EXISTS' : 'MISSING'}\n\n` +
                `Last 10 Tables: ${data.last_ten_tables.join(', ')}\n\n` +
                `Migration Status Summary:\n${data.migration_status_summary}`;
            alert(msg);
        },
        onError: (error) => {
            alert(error.response?.data?.error || 'Status check failed');
        }
    });

    const syncMutation = useMutation({
        mutationFn: async () => {
            const response = await api.post('/api/admin/databases/sync');
            return response.data;
        },
        onSuccess: (data) => {
            alert(`SYNC SUCCESSFUL!\n\n${data.message}\n\nLogs:\n${data.logs.join('\n')}`);
        },
        onError: (error) => {
            alert(error.response?.data?.error || 'Sync failed');
        }
    });

    const handleCreate = () => {
        createMutation.mutate();
    };

    const handleMigrate = () => {
        if (confirm('CRITICAL: Are you sure you want to run database migrations?')) {
            migrateMutation.mutate();
        }
    };

    const handleRepair = () => {
        if (confirm('REPAIR: This will reset the permission cache and re-seed roles. Continue?')) {
            repairMutation.mutate();
        }
    };

    const handleClearCache = () => {
        if (confirm('CLEAR: This will flush the application optimization cache. Continue?')) {
            clearCacheMutation.mutate();
        }
    };

    const handleCheckStatus = () => {
        statusMutation.mutate();
    };

    const handleSync = () => {
        if (confirm('SYNC: This will mark existing tables as migrated. Use this ONLY if you see "Base table already exists" errors. Continue?')) {
            syncMutation.mutate();
        }
    };

    const handleViewSchema = (tableName) => {
        schemaMutation.mutate(tableName);
    };

    const handleDownload = (fileName) => {
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
        <div className="p-8 space-y-8">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Database & Maintenance</h1>
                    <p className="text-gray-500 mt-2">Manage your database backups and system health</p>
                </div>
                <div className="flex gap-3">
                    <Button variant="secondary" onClick={handleSync} disabled={syncMutation.isPending} className="flex items-center gap-2">
                        {syncMutation.isPending ? <RefreshCw className="h-4 w-4 animate-spin" /> : <Database className="h-4 w-4" />}
                        {syncMutation.isPending ? 'Syncing...' : 'Sync Database State'}
                    </Button>
                    <Button variant="outline" onClick={handleCheckStatus} disabled={statusMutation.isPending} className="flex items-center gap-2 border-gray-300">
                        {statusMutation.isPending ? <RefreshCw className="h-4 w-4 animate-spin" /> : <RefreshCw className="h-4 w-4" />}
                        {statusMutation.isPending ? 'Checking...' : 'Check Status'}
                    </Button>
                    <Button onClick={handleCreate} disabled={isCreating || createMutation.isPending} className="flex items-center gap-2">
                        {isCreating ? <RefreshCw className="h-4 w-4 animate-spin" /> : <Plus className="h-4 w-4" />}
                        {isCreating ? 'Creating Backup...' : 'Create New Backup'}
                    </Button>
                </div>
            </div>

            {/* Maintenance Center Section */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white p-6 rounded-xl border border-yellow-100 shadow-sm space-y-4">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-yellow-50 rounded-lg">
                            <Zap className="h-5 w-5 text-yellow-600" />
                        </div>
                        <h3 className="font-semibold text-gray-900">Database Sync</h3>
                    </div>
                    <p className="text-sm text-gray-500">Apply new database schema changes to the live server.</p>
                    <Button
                        variant="soft"
                        className="w-full bg-yellow-50 hover:bg-yellow-100 text-yellow-800 border-none"
                        onClick={handleMigrate}
                        disabled={isMigrating || migrateMutation.isPending}
                    >
                        {isMigrating ? 'Migrating...' : 'Run Migrations'}
                    </Button>
                </div>

                <div className="bg-white p-6 rounded-xl border border-blue-100 shadow-sm space-y-4">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-blue-50 rounded-lg">
                            <RefreshCw className="h-5 w-5 text-blue-600" />
                        </div>
                        <h3 className="font-semibold text-gray-900">Application Cache</h3>
                    </div>
                    <p className="text-sm text-gray-500">Flush all cached views, routes, and config files.</p>
                    <Button
                        variant="soft"
                        className="w-full bg-blue-50 hover:bg-blue-100 text-blue-800 border-none"
                        onClick={handleClearCache}
                        disabled={clearCacheMutation.isPending}
                    >
                        {clearCacheMutation.isPending ? 'Clearing...' : 'Clear Cache'}
                    </Button>
                </div>

                <div className="bg-white p-6 rounded-xl border border-red-100 shadow-sm space-y-4">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-red-50 rounded-lg">
                            <Trash2 className="h-5 w-5 text-red-600" />
                        </div>
                        <h3 className="font-semibold text-gray-900">Permission Repair</h3>
                    </div>
                    <p className="text-sm text-gray-500">Emergency reset of roles and access permissions.</p>
                    <Button
                        variant="soft"
                        className="w-full bg-red-50 hover:bg-red-100 text-red-800 border-none"
                        onClick={handleRepair}
                        disabled={repairMutation.isPending}
                    >
                        {repairMutation.isPending ? 'Repairing...' : 'Repair Permissions'}
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

            {/* Database Tables Section */}
            {dbTables.length > 0 && (
                <div className="bg-white rounded-lg border p-6 space-y-4">
                    <div className="flex items-center gap-2">
                        <Database className="h-5 w-5 text-gray-400" />
                        <h2 className="text-xl font-bold">Database Tables</h2>
                        <Badge variant="secondary" className="ml-auto">{dbTables.length} Total</Badge>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        {dbTables.map((table) => (
                            <div key={table} className="group flex items-center justify-between p-3 rounded-md border bg-gray-50 hover:bg-gray-100 transition-colors">
                                <span className="text-sm font-medium text-gray-700 truncate" title={table}>{table}</span>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity"
                                    onClick={() => handleViewSchema(table)}
                                >
                                    <Eye className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Schema Modal */}
            <Dialog open={isSchemaOpen} onOpenChange={setIsSchemaOpen}>
                <DialogContent className="max-w-4xl max-h-[85vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <FileJson className="h-5 w-5 text-blue-500" />
                            Table Schema: <span className="text-blue-600">{selectedTable}</span>
                        </DialogTitle>
                        <DialogDescription>
                            Detailed column information and CREATE statement for the selected table.
                        </DialogDescription>
                    </DialogHeader>

                    {isSchemaLoading ? (
                        <div className="py-20 flex flex-col items-center justify-center gap-3">
                            <RefreshCw className="h-8 w-8 animate-spin text-blue-500" />
                            <p className="text-sm text-gray-500 font-medium">Extracting schema...</p>
                        </div>
                    ) : schemaData ? (
                        <div className="space-y-6">
                            <div>
                                <h4 className="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                    <span className="w-2 h-2 rounded-full bg-blue-500"></span>
                                    Columns
                                </h4>
                                <div className="rounded-md border overflow-hidden">
                                    <Table>
                                        <TableHeader className="bg-gray-50">
                                            <TableRow>
                                                <TableHead className="font-bold">Field</TableHead>
                                                <TableHead className="font-bold">Type</TableHead>
                                                <TableHead className="font-bold">Null</TableHead>
                                                <TableHead className="font-bold">Key</TableHead>
                                                <TableHead className="font-bold">Default</TableHead>
                                                <TableHead className="font-bold">Extra</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {schemaData.columns.map((col, idx) => (
                                                <TableRow key={idx}>
                                                    <TableCell className="font-medium text-blue-600">{col.Field}</TableCell>
                                                    <TableCell className="font-mono text-xs">{col.Type}</TableCell>
                                                    <TableCell className="text-xs">{col.Null}</TableCell>
                                                    <TableCell>
                                                        {col.Key && <Badge variant={col.Key === 'PRI' ? 'destructive' : 'outline'} className="text-[10px] px-1 h-4">{col.Key}</Badge>}
                                                    </TableCell>
                                                    <TableCell className="text-xs italic">{col.Default ?? 'NULL'}</TableCell>
                                                    <TableCell className="text-xs text-gray-500">{col.Extra}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>

                            <div>
                                <h4 className="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                    <span className="w-2 h-2 rounded-full bg-green-500"></span>
                                    Create Statement
                                </h4>
                                <div className="p-4 bg-gray-950 rounded-lg overflow-x-auto border border-gray-800 shadow-inner">
                                    <pre className="text-xs text-green-400 font-mono leading-relaxed">
                                        {schemaData.create_sql}
                                    </pre>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="py-20 text-center text-gray-500">
                            Failed to load schema data.
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default DatabaseList;
