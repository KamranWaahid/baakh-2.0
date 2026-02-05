import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
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
import {
    Plus,
    Users,
    Settings,
    Trash2,
    Edit,
    MoreVertical
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { format } from 'date-fns';

const TeamList = () => {
    const queryClient = useQueryClient();

    const { data: teams, isLoading } = useQuery({
        queryKey: ['teams'],
        queryFn: async () => {
            const response = await api.get('/api/admin/teams');
            return response.data; // Paginator structure
        }
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/api/admin/teams/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['teams']);
        }
    });

    if (isLoading) return <div className="p-8">Loading teams...</div>;

    const teamList = teams?.data || [];

    return (
        <div className="p-8 space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Teams</h1>
                    <p className="text-gray-500 mt-2">Manage your teams and collaborations</p>
                </div>
                <Link to="/teams/create">
                    <Button className="flex items-center gap-2">
                        <Plus className="h-4 w-4" />
                        Create Team
                    </Button>
                </Link>
            </div>

            <div className="bg-white rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Owner</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Created At</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {teamList.map((team) => (
                            <TableRow key={team.id}>
                                <TableCell className="font-medium">
                                    <div className="flex flex-col">
                                        <span>{team.name}</span>
                                        <span className="text-xs text-gray-400">{team.description}</span>
                                    </div>
                                </TableCell>
                                <TableCell>{team.owner?.name}</TableCell>
                                <TableCell>{team.status}</TableCell>
                                <TableCell>{format(new Date(team.created_at), 'MMM d, yyyy')}</TableCell>
                                <TableCell className="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                <MoreVertical className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link to={`/teams/${team.id}/edit`} className="flex items-center gap-2 cursor-pointer">
                                                    <Edit className="h-4 w-4" /> Edit Details
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild>
                                                <Link to={`/teams/${team.id}/members`} className="flex items-center gap-2 cursor-pointer">
                                                    <Users className="h-4 w-4" /> Manage Members
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                className="text-red-600 focus:text-red-600 cursor-pointer"
                                                onClick={() => {
                                                    if (confirm('Are you sure? This cannot be undone.')) {
                                                        deleteMutation.mutate(team.id);
                                                    }
                                                }}
                                            >
                                                <Trash2 className="h-4 w-4 mr-2" /> Delete Team
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
};

export default TeamList;
