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
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";

const TeamList = () => {
    const queryClient = useQueryClient();
    const [activeTab, setActiveTab] = useState('teams');

    const { data: teams, isLoading: isLoadingTeams } = useQuery({
        queryKey: ['teams'],
        queryFn: async () => {
            const response = await api.get('/api/admin/teams');
            return response.data;
        }
    });

    const { data: admins, isLoading: isLoadingAdmins } = useQuery({
        queryKey: ['admins'],
        queryFn: async () => {
            const response = await api.get('/api/admin/users');
            return response.data;
        }
    });

    const deleteTeamMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/api/admin/teams/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['teams']);
        }
    });

    const deleteAdminMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/api/admin/users/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['admins']);
        }
    });

    if (isLoadingTeams || isLoadingAdmins) return <div className="p-8">Loading...</div>;

    const teamList = teams?.data || [];
    const adminList = admins?.data || [];

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight">Admins & Teams</h1>
                    <p className="text-gray-500 mt-1 md:mt-2 text-sm md:text-base">Manage your administrative users and collaborative teams</p>
                </div>
                <div className="flex gap-2 w-full sm:w-auto">
                    <Link to="/admin/teams/create" className="w-full sm:w-auto">
                        <Button className="w-full sm:w-auto flex items-center gap-2">
                            <Plus className="h-4 w-4" />
                            Create New
                        </Button>
                    </Link>
                </div>
            </div>

            <Tabs defaultValue="teams" onValueChange={setActiveTab}>
                <TabsList className="grid w-full grid-cols-2 max-w-[400px]">
                    <TabsTrigger value="teams">Teams ({teamList.length})</TabsTrigger>
                    <TabsTrigger value="admins">Admins ({adminList.length})</TabsTrigger>
                </TabsList>

                <TabsContent value="teams" className="mt-6">
                    <div className="bg-white rounded-lg border overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="min-w-[150px]">Name</TableHead>
                                    <TableHead>Owner</TableHead>
                                    <TableHead className="hidden sm:table-cell">Status</TableHead>
                                    <TableHead className="hidden md:table-cell">Created At</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {teamList.map((team) => (
                                    <TableRow key={team.id}>
                                        <TableCell className="font-medium whitespace-nowrap">
                                            <div className="flex flex-col">
                                                <span>{team.name}</span>
                                                <span className="text-xs text-gray-400">{team.description}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">{team.owner?.name}</TableCell>
                                        <TableCell className="hidden sm:table-cell whitespace-nowrap capitalize">{team.status}</TableCell>
                                        <TableCell className="hidden md:table-cell whitespace-nowrap text-xs text-gray-400">{format(new Date(team.created_at), 'MMM d, yyyy')}</TableCell>
                                        <TableCell className="text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" className="h-8 w-8 p-0">
                                                        <MoreVertical className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link to={`/admin/teams/${team.id}/edit`} className="flex items-center gap-2 cursor-pointer">
                                                            <Edit className="h-4 w-4" /> Edit Details
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link to={`/admin/teams/${team.id}/members`} className="flex items-center gap-2 cursor-pointer">
                                                            <Users className="h-4 w-4" /> Manage Members
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        className="text-red-600 focus:text-red-600 cursor-pointer"
                                                        onClick={() => {
                                                            if (confirm('Are you sure? This cannot be undone.')) {
                                                                deleteTeamMutation.mutate(team.id);
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
                                {teamList.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center text-muted-foreground">
                                            No teams found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>

                <TabsContent value="admins" className="mt-6">
                    <div className="bg-white rounded-lg border overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="min-w-[150px]">Admin Name</TableHead>
                                    <TableHead>Username / Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead className="hidden sm:table-cell text-center">Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {adminList.map((admin) => (
                                    <TableRow key={admin.id}>
                                        <TableCell className="font-medium whitespace-nowrap">
                                            <div className="flex flex-col">
                                                <span>{admin.name}</span>
                                                <span className="text-xs text-gray-400" dir="rtl">{admin.name_sd}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col text-sm">
                                                <span className="font-medium">{admin.username}</span>
                                                <span className="text-xs text-gray-500">{admin.email}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            <div className="flex flex-wrap gap-1">
                                                {admin.roles?.map(role => (
                                                    <Badge key={role.id} variant="secondary" className="text-[10px] uppercase">
                                                        {role.name.replace('_', ' ')}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell text-center">
                                            <Badge variant={admin.status === 'active' ? 'default' : 'destructive'} className="capitalize">
                                                {admin.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" className="h-8 w-8 p-0">
                                                        <MoreVertical className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link to={`/admin/users/${admin.id}/edit`} className="flex items-center gap-2 cursor-pointer">
                                                            <Edit className="h-4 w-4" /> Edit User
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        className="text-red-600 focus:text-red-600 cursor-pointer"
                                                        onClick={() => {
                                                            if (confirm('Are you sure you want to delete this admin user?')) {
                                                                deleteAdminMutation.mutate(admin.id);
                                                            }
                                                        }}
                                                    >
                                                        <Trash2 className="h-4 w-4 mr-2" /> Delete User
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {adminList.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center text-muted-foreground">
                                            No admin users found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default TeamList;
