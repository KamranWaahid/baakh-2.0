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
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { toast } from 'sonner';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/components/ui/select';

const ReportManagement = () => {
    const queryClient = useQueryClient();
    const [selectedReport, setSelectedReport] = useState(null);
    const [statusFilter, setStatusFilter] = useState('pending');

    const { data: reports, isLoading } = useQuery({
        queryKey: ['admin-reports', statusFilter],
        queryFn: async () => {
            const resp = await api.get(`/api/admin/reports?status=${statusFilter}`);
            return resp.data;
        }
    });

    const updateStatusMutation = useMutation({
        mutationFn: async ({ id, status }) => {
            return api.put(`/api/admin/reports/${id}`, { status });
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['admin-reports']);
            toast.success('Report status updated');
            setSelectedReport(null);
        }
    });

    const getStatusColor = (status) => {
        switch (status) {
            case 'pending': return 'bg-yellow-100 text-yellow-800';
            case 'resolved': return 'bg-green-100 text-green-800';
            case 'ignored': return 'bg-gray-100 text-gray-800';
            default: return 'bg-blue-100 text-blue-800';
        }
    };

    return (
        <div className="p-8 space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Reports Management</h1>
                    <p className="text-muted-foreground">Review and handle user reports.</p>
                </div>
                <Select value={statusFilter} onValueChange={setStatusFilter}>
                    <SelectTrigger className="w-[180px]">
                        <SelectValue placeholder="Filter by status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="resolved">Resolved</SelectItem>
                        <SelectItem value="ignored">Ignored</SelectItem>
                        <SelectItem value="all">All Statuses</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Target</TableHead>
                                <TableHead>Reporter</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {isLoading ? (
                                <TableRow><TableCell colSpan={6} className="text-center py-8 text-muted-foreground">Loading reports...</TableCell></TableRow>
                            ) : reports?.data?.length > 0 ? reports.data.map((report) => (
                                <TableRow key={report.id}>
                                    <TableCell className="font-medium">
                                        {report.poetry?.info?.title || report.poet?.poet_name || 'Generic Report'}
                                    </TableCell>
                                    <TableCell>{report.user?.name || 'Guest'}</TableCell>
                                    <TableCell className="max-w-[200px] truncate">{report.reason}</TableCell>
                                    <TableCell>{new Date(report.created_at).toLocaleDateString()}</TableCell>
                                    <TableCell>
                                        <Badge variant="secondary" className={getStatusColor(report.status)}>
                                            {report.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="outline" size="sm" onClick={() => setSelectedReport(report)}>
                                            Handle
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            )) : (
                                <TableRow><TableCell colSpan={6} className="text-center py-8 text-muted-foreground">No reports found.</TableCell></TableRow>
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Dialog open={!!selectedReport} onOpenChange={() => setSelectedReport(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Handle Report #{selectedReport?.id}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="grid grid-cols-4 items-center gap-4">
                            <span className="font-bold">Target:</span>
                            <span className="col-span-3">{selectedReport?.poetry?.info?.title || selectedReport?.poet?.poet_name || 'N/A'}</span>
                        </div>
                        <div className="grid grid-cols-4 items-center gap-4">
                            <span className="font-bold">Reason:</span>
                            <span className="col-span-3">{selectedReport?.reason}</span>
                        </div>
                        <div className="grid grid-cols-4 items-center gap-4">
                            <span className="font-bold">Reporter:</span>
                            <span className="col-span-3">{selectedReport?.user?.name || 'Guest'}</span>
                        </div>
                    </div>
                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => updateStatusMutation.mutate({ id: selectedReport.id, status: 'ignored' })}>
                            Ignore
                        </Button>
                        <Button variant="default" className="bg-green-600 hover:bg-green-700" onClick={() => updateStatusMutation.mutate({ id: selectedReport.id, status: 'resolved' })}>
                            Mark Resolved
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default ReportManagement;
