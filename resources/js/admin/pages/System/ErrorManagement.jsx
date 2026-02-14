import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import {
    Bug,
    AlertCircle,
    CheckCircle2,
    XCircle,
    Trash2,
    Eye,
    Filter,
    RefreshCw,
    User,
    Clock,
    Globe,
    Terminal,
    ChevronRight,
    Search as SearchIcon,
    AlertTriangle,
    Check
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { toast } from 'sonner';

const ErrorManagement = () => {
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [severityFilter, setSeverityFilter] = useState('all');
    const [selectedError, setSelectedError] = useState(null);
    const [isDetailsOpen, setIsDetailsOpen] = useState(false);
    const [page, setPage] = useState(1);

    // Fetch Errors
    const { data, isLoading, refetch } = useQuery({
        queryKey: ['system-errors', page, search, statusFilter, severityFilter],
        queryFn: async () => {
            const params = new URLSearchParams({
                page,
                search,
                status: statusFilter === 'all' ? '' : statusFilter,
                severity: severityFilter === 'all' ? '' : severityFilter
            });
            const response = await api.get(`/api/admin/system-errors?${params.toString()}`);
            return response.data;
        }
    });

    // Mutations
    const updateMutation = useMutation({
        mutationFn: ({ id, status }) => api.put(`/api/admin/system-errors/${id}`, { status }),
        onSuccess: () => {
            queryClient.invalidateQueries(['system-errors']);
            toast.success('Error status updated successfully');
            if (selectedError) {
                // Update local state if modal is open
                const updated = { ...selectedError, status: updateMutation.variables.status };
                setSelectedError(updated);
            }
        },
        onError: () => toast.error('Failed to update error status')
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/system-errors/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['system-errors']);
            toast.success('Error log deleted');
            setIsDetailsOpen(false);
        },
        onError: () => toast.error('Failed to delete error log')
    });

    const clearMutation = useMutation({
        mutationFn: (status) => api.post('/api/admin/system-errors/clear', { status: status === 'all' ? '' : status }),
        onSuccess: () => {
            queryClient.invalidateQueries(['system-errors']);
            toast.success('Error logs cleared');
        },
        onError: () => toast.error('Failed to clear logs')
    });

    const handleViewDetails = (error) => {
        setSelectedError(error);
        setIsDetailsOpen(true);
    };

    const getSeverityBadge = (severity) => {
        switch (severity) {
            case 'critical': return <Badge variant="destructive" className="bg-red-700 animate-pulse">CRITICAL</Badge>;
            case 'high': return <Badge variant="destructive">HIGH</Badge>;
            case 'medium': return <Badge className="bg-orange-500 hover:bg-orange-600">MEDIUM</Badge>;
            case 'low': return <Badge variant="secondary">LOW</Badge>;
            default: return <Badge variant="outline">{severity}</Badge>;
        }
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'pending': return <Badge variant="outline" className="text-amber-600 border-amber-200 bg-amber-50">Pending</Badge>;
            case 'resolved': return <Badge variant="outline" className="text-green-600 border-green-200 bg-green-50">Resolved</Badge>;
            case 'ignored': return <Badge variant="outline" className="text-gray-500 border-gray-200 bg-gray-50">Ignored</Badge>;
            default: return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <div className="p-4 lg:p-8 space-y-6 w-full">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
                        <Bug className="h-8 w-8 text-red-500" /> Error & Bug Management
                    </h1>
                    <p className="text-gray-500 mt-2">Monitor and track application exceptions in real-time.</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={() => refetch()} disabled={isLoading}>
                        <RefreshCw className={`h-4 w-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
                        Refresh
                    </Button>
                    <Select onValueChange={(v) => { if (window.confirm('Are you sure you want to clear these logs?')) clearMutation.mutate(v) }}>
                        <SelectTrigger className="w-[180px] bg-red-50 text-red-700 border-red-200">
                            <Trash2 className="h-4 w-4 mr-2" />
                            <SelectValue placeholder="Clear Logs..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Clear All Logs</SelectItem>
                            <SelectItem value="resolved">Clear Resolved Only</SelectItem>
                            <SelectItem value="ignored">Clear Ignored Only</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <Card>
                <CardHeader className="pb-3 border-b">
                    <div className="flex flex-col md:flex-row gap-4 justify-between items-end md:items-center">
                        <CardTitle className="text-sm font-medium">Capture History</CardTitle>
                        <div className="flex flex-wrap gap-2 items-center">
                            <div className="relative w-full md:w-64">
                                <SearchIcon className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search message, file, url..."
                                    className="pl-8"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>
                            <Select value={statusFilter} onValueChange={setStatusFilter}>
                                <SelectTrigger className="w-[130px]">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Any Status</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="resolved">Resolved</SelectItem>
                                    <SelectItem value="ignored">Ignored</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={severityFilter} onValueChange={setSeverityFilter}>
                                <SelectTrigger className="w-[130px]">
                                    <SelectValue placeholder="Severity" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Any Severity</SelectItem>
                                    <SelectItem value="critical">Critical</SelectItem>
                                    <SelectItem value="high">High</SelectItem>
                                    <SelectItem value="medium">Medium</SelectItem>
                                    <SelectItem value="low">Low</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-slate-50 italic">
                                    <TableHead className="w-[80px]">ID</TableHead>
                                    <TableHead>Error Message</TableHead>
                                    <TableHead className="w-[100px]">Severity</TableHead>
                                    <TableHead className="w-[100px]">Status</TableHead>
                                    <TableHead className="w-[180px]">Reported</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center">Loading errors...</TableCell>
                                    </TableRow>
                                ) : data?.data?.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center">
                                            <div className="flex flex-col items-center gap-2 text-muted-foreground">
                                                <Check className="h-8 w-8 text-green-500" />
                                                No errors found. Your application is bug-free! (Hopefully)
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    data.data.map((error) => (
                                        <TableRow key={error.id} className="hover:bg-slate-50 cursor-pointer" onClick={() => handleViewDetails(error)}>
                                            <TableCell className="font-mono text-xs text-muted-foreground">{error.id}</TableCell>
                                            <TableCell>
                                                <div className="flex flex-col gap-1 max-w-md">
                                                    <span className="font-bold truncate" title={error.message}>{error.message}</span>
                                                    <span className="text-[10px] text-muted-foreground font-mono truncate">{error.file}:{error.line}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>{getSeverityBadge(error.severity)}</TableCell>
                                            <TableCell>{getStatusBadge(error.status)}</TableCell>
                                            <TableCell className="text-xs">
                                                <div className="flex flex-col">
                                                    <span>{new Date(error.created_at).toLocaleString()}</span>
                                                    <span className="text-[10px] text-muted-foreground italic">env: {error.environment}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button variant="ghost" size="icon" className="h-8 w-8 text-blue-500" onClick={(e) => { e.stopPropagation(); handleViewDetails(error); }}>
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                    {error.status !== 'resolved' && (
                                                        <Button variant="ghost" size="icon" className="h-8 w-8 text-green-500" onClick={(e) => { e.stopPropagation(); updateMutation.mutate({ id: error.id, status: 'resolved' }); }}>
                                                            <CheckCircle2 className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                    <Button variant="ghost" size="icon" className="h-8 w-8 text-red-500" onClick={(e) => { e.stopPropagation(); if (window.confirm('Delete this log?')) deleteMutation.mutate(error.id); }}>
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
                </CardContent>
                <CardFooter className="flex items-center justify-between p-4 border-t">
                    <p className="text-xs text-muted-foreground italic">
                        Showing {data?.from || 0} to {data?.to || 0} of {data?.total || 0} logs
                    </p>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!data?.prev_page_url}
                            onClick={() => setPage(page - 1)}
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!data?.next_page_url}
                            onClick={() => setPage(page + 1)}
                        >
                            Next
                        </Button>
                    </div>
                </CardFooter>
            </Card>

            {/* ERROR DETAILS MODAL */}
            <Dialog open={isDetailsOpen} onOpenChange={setIsDetailsOpen}>
                <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <div className="flex items-start justify-between">
                            <div className="space-y-1 pr-8">
                                <DialogTitle className="text-xl font-bold break-words">{selectedError?.message}</DialogTitle>
                                <Badge variant="outline" className="font-mono text-[10px] break-all">{selectedError?.file}:{selectedError?.line}</Badge>
                            </div>
                            <div className="flex flex-col items-end gap-2 shrink-0">
                                {selectedError && getSeverityBadge(selectedError.severity)}
                                {selectedError && getStatusBadge(selectedError.status)}
                            </div>
                        </div>
                    </DialogHeader>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 py-4">
                        <section className="space-y-4">
                            <h4 className="text-sm font-bold flex items-center gap-2 border-b pb-2">
                                <Globe className="h-4 w-4" /> Request Context
                            </h4>
                            <div className="grid grid-cols-2 gap-2 text-xs">
                                <span className="text-muted-foreground font-semibold">URL:</span>
                                <span className="break-all font-mono">{selectedError?.url}</span>
                                <span className="text-muted-foreground font-semibold">Method:</span>
                                <Badge variant="outline" className="w-fit font-bold">{selectedError?.method}</Badge>
                                <span className="text-muted-foreground font-semibold">IP Address:</span>
                                <span>{selectedError?.ip}</span>
                                <span className="text-muted-foreground font-semibold">User Agent:</span>
                                <span className="text-[10px] italic break-words">{selectedError?.user_agent}</span>
                            </div>

                            <h4 className="text-sm font-bold flex items-center gap-2 border-b pb-2 mt-6">
                                <User className="h-4 w-4" /> Reporter Details
                            </h4>
                            <div className="grid grid-cols-2 gap-2 text-xs">
                                <span className="text-muted-foreground font-semibold">User:</span>
                                <span>{selectedError?.user ? `${selectedError.user.name} (#${selectedError.user_id})` : 'Guest / System'}</span>
                                <span className="text-muted-foreground font-semibold">Environment:</span>
                                <span className="uppercase font-bold text-blue-600">{selectedError?.environment}</span>
                                <span className="text-muted-foreground font-semibold">Reported At:</span>
                                <span>{selectedError && new Date(selectedError.created_at).toLocaleString()}</span>
                            </div>
                        </section>

                        <section className="space-y-4">
                            <h4 className="text-sm font-bold flex items-center gap-2 border-b pb-2">
                                <Terminal className="h-4 w-4" /> Stack Trace
                            </h4>
                            <div className="bg-slate-950 rounded-md p-4 overflow-auto max-h-[350px] custom-scrollbar">
                                <pre className="text-[10px] font-mono text-slate-300 whitespace-pre text-wrap leading-relaxed">
                                    {selectedError?.trace || 'No trace available.'}
                                </pre>
                            </div>
                        </section>
                    </div>

                    <DialogFooter className="border-t pt-4">
                        <div className="flex justify-between items-center w-full">
                            <Button variant="outline" size="sm" className="text-red-500" onClick={() => deleteMutation.mutate(selectedError.id)} disabled={deleteMutation.isPending}>
                                <Trash2 className="h-4 w-4 mr-2" /> Delete Log
                            </Button>
                            <div className="flex gap-2">
                                <Button variant="secondary" size="sm" onClick={() => updateMutation.mutate({ id: selectedError.id, status: 'ignored' })} disabled={updateMutation.isPending || selectedError?.status === 'ignored'}>
                                    <AlertTriangle className="h-4 w-4 mr-2" /> Ignore
                                </Button>
                                <Button size="sm" className="bg-green-600 hover:bg-green-700" onClick={() => updateMutation.mutate({ id: selectedError.id, status: 'resolved' })} disabled={updateMutation.isPending || selectedError?.status === 'resolved'}>
                                    <CheckCircle2 className="h-4 w-4 mr-2" /> Mark Resolved
                                </Button>
                            </div>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default ErrorManagement;
