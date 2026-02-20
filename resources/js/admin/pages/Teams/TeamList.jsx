import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../api/axios';
import useAuth from '../../hooks/useAuth';
import {
    MoreVertical,
    Plus,
    Search,
    Filter,
    Edit,
    Trash2,
    Shield,
    CheckCircle,
    XCircle,
    Mail,
    Phone,
    User,
    Users
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from "@/components/ui/tabs";
import { format } from 'date-fns';

const TeamList = () => {
    const queryClient = useQueryClient();
    const [activeTab, setActiveTab] = useState('teams');
    const { isSuperAdmin, canManage } = useAuth();

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

    const { data: viewers, isLoading: isLoadingViewers } = useQuery({
        queryKey: ['viewers'],
        queryFn: async () => {
            const response = await api.get('/api/admin/users?role=viewer');
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

    const deleteUserMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/api/admin/users/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['admins']);
            queryClient.invalidateQueries(['viewers']);
        }
    });


    if (isLoadingTeams || isLoadingAdmins || isLoadingViewers) return <div className="p-8">Loading...</div>;

    const teamList = teams?.data || [];
    const adminList = admins?.data || [];
    const viewerList = viewers?.data || [];

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
                <div className="space-y-1">
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-gray-900 font-inter">Admins & Teams</h1>
                    <p className="text-gray-500 text-sm md:text-base max-w-2xl">Manage your administrative users and collaborative teams</p>
                </div>
                {canManage && (
                    <div className="flex flex-col gap-2 w-full sm:w-auto">
                        <Link to="/admin/teams/create?simple=true" className="w-full">
                            <Button variant="outline" className="w-full justify-start sm:justify-center flex items-center gap-2 h-10 border-gray-200">
                                <Plus className="h-4 w-4" />
                                <span>Create Team</span>
                            </Button>
                        </Link>
                        {isSuperAdmin && (
                            <Link to="/admin/teams/create" className="w-full">
                                <Button className="w-full justify-start sm:justify-center flex items-center gap-2 h-10 shadow-sm">
                                    <Plus className="h-4 w-4" />
                                    <span>Team & Admin</span>
                                </Button>
                            </Link>
                        )}
                    </div>
                )}
            </div>

            <Tabs defaultValue="teams" onValueChange={setActiveTab}>
                <TabsList className="grid w-full grid-cols-3 max-w-[500px]">
                    <TabsTrigger value="teams">Teams ({teamList.length})</TabsTrigger>
                    <TabsTrigger value="admins">Admins ({adminList.length})</TabsTrigger>
                    <TabsTrigger value="viewers">Viewers ({viewerList.length})</TabsTrigger>
                </TabsList>

                <TabsContent value="teams" className="mt-6">
                    {/* Mobile Card View */}
                    <div className="grid grid-cols-1 gap-4 md:hidden">
                        {teamList.map((team) => (
                            <div key={team.id} className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm space-y-3">
                                <div className="flex justify-between items-start">
                                    <div className="space-y-1">
                                        <h3 className="font-semibold text-gray-900">{team.name}</h3>
                                        <p className="text-xs text-gray-400 line-clamp-2">{team.description}</p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge variant="outline" className="capitalize text-[10px]">
                                            {team.status}
                                        </Badge>
                                        {canManage && (
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
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
                                        )}
                                    </div>
                                </div>
                                <div className="pt-3 border-t flex items-center justify-between text-xs text-gray-500">
                                    <div className="flex items-center gap-1.5">
                                        <User className="h-3 w-3" />
                                        <span>{team.owner?.name}</span>
                                    </div>
                                    <div className="flex items-center gap-1.5">
                                        <span>{format(new Date(team.created_at), 'MMM d, yyyy')}</span>
                                    </div>
                                </div>
                            </div>
                        ))}
                        {teamList.length === 0 && (
                            <div className="py-12 text-center bg-gray-50 rounded-lg border border-dashed border-gray-200">
                                <p className="text-sm text-gray-400 font-inter">No teams found.</p>
                            </div>
                        )}
                    </div>

                    {/* Desktop Table View */}
                    <div className="hidden md:block bg-white rounded-xl border shadow-sm overflow-hidden">
                        <Table>
                            <TableHeader className="bg-gray-50/50">
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
                                    <TableRow key={team.id} className="hover:bg-gray-50/50 transition-colors">
                                        <TableCell className="font-medium">
                                            <div className="flex flex-col max-w-[200px] sm:max-w-[300px] gap-1">
                                                <span className="truncate text-gray-900">{team.name}</span>
                                                <span className="text-xs text-gray-400 whitespace-normal line-clamp-2">{team.description}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-gray-600">{team.owner?.name}</TableCell>
                                        <TableCell className="hidden sm:table-cell whitespace-nowrap capitalize">
                                            <Badge variant="secondary" className="font-normal text-xs">{team.status}</Badge>
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell whitespace-nowrap text-xs text-gray-400">{format(new Date(team.created_at), 'MMM d, yyyy')}</TableCell>
                                        <TableCell className="text-right">
                                            {canManage && (
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" className="h-8 w-8 p-0 opacity-60 hover:opacity-100">
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
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>

                <TabsContent value="admins" className="mt-6">
                    {/* Mobile Card View */}
                    <div className="grid grid-cols-1 gap-4 md:hidden">
                        {adminList.map((admin) => (
                            <div key={admin.id} className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm space-y-4">
                                <div className="flex justify-between items-start">
                                    <div className="flex items-center gap-3">
                                        <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                                            {admin.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-gray-900 leading-none">{admin.name}</h3>
                                            <span className="text-xs text-gray-400 font-arabic" dir="rtl">{admin.name_sd}</span>
                                        </div>
                                    </div>
                                    {isSuperAdmin && (
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
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
                                                        if (confirm('Are you sure you want to delete this user?')) {
                                                            deleteUserMutation.mutate(admin.id);
                                                        }
                                                    }}
                                                >
                                                    <Trash2 className="h-4 w-4 mr-2" /> Delete User
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4 text-xs">
                                    <div className="space-y-1">
                                        <span className="text-gray-400 uppercase tracking-wider block">Credentials</span>
                                        <span className="font-medium text-gray-700 truncate block">{admin.username}</span>
                                        <span className="text-gray-500 truncate block">{admin.email}</span>
                                    </div>
                                    <div className="space-y-1">
                                        <span className="text-gray-400 uppercase tracking-wider block">Role & Status</span>
                                        <div className="flex flex-wrap gap-1 mb-1">
                                            {admin.roles?.map(role => (
                                                <Badge key={role.id} variant="secondary" className="text-[9px] px-1 py-0 border-none uppercase">
                                                    {role.name.replace('_', ' ')}
                                                </Badge>
                                            ))}
                                        </div>
                                        <Badge variant={admin.status === 'active' ? 'default' : 'destructive'} className="text-[9px] px-1 py-0 capitalize h-4">
                                            {admin.status}
                                        </Badge>
                                    </div>
                                </div>
                                {admin.teams && admin.teams.length > 0 && (
                                    <div className="pt-3 border-t">
                                        <span className="text-[10px] text-gray-400 uppercase tracking-wider mb-2 block">Assigned Teams</span>
                                        <div className="flex flex-wrap gap-1.5">
                                            {admin.teams.map(team => (
                                                <Badge key={team.id} variant="outline" className="text-[10px] font-normal px-2 py-0 border-gray-100 bg-gray-50/50">
                                                    {team.name}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))}
                        {adminList.length === 0 && (
                            <div className="py-12 text-center bg-gray-50 rounded-lg border border-dashed border-gray-200">
                                <p className="text-sm text-gray-400 font-inter">No admin users found.</p>
                            </div>
                        )}
                    </div>

                    {/* Desktop Table View */}
                    <div className="hidden md:block bg-white rounded-xl border shadow-sm overflow-hidden">
                        <Table>
                            <TableHeader className="bg-gray-50/50">
                                <TableRow>
                                    <TableHead className="min-w-[150px]">Admin Name</TableHead>
                                    <TableHead>Username / Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead className="hidden md:table-cell">Team</TableHead>
                                    <TableHead className="hidden sm:table-cell text-center">Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {adminList.map((admin) => (
                                    <TableRow key={admin.id} className="hover:bg-gray-50/50 transition-colors">
                                        <TableCell className="font-medium whitespace-nowrap">
                                            <div className="flex flex-col">
                                                <span className="text-gray-900">{admin.name}</span>
                                                <span className="text-xs text-gray-400 font-arabic" dir="rtl">{admin.name_sd}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col text-sm">
                                                <span className="font-medium text-gray-700">{admin.username}</span>
                                                <span className="text-xs text-gray-500">{admin.email}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            <div className="flex flex-wrap gap-1">
                                                {admin.roles?.map(role => (
                                                    <Badge key={role.id} variant="secondary" className="text-[10px] uppercase font-normal">
                                                        {role.name.replace('_', ' ')}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            <div className="flex flex-wrap gap-1">
                                                {admin.teams?.map(team => (
                                                    <Badge key={team.id} variant="outline" className="text-[10px] font-normal">
                                                        {team.name}
                                                    </Badge>
                                                ))}
                                                {(!admin.teams || admin.teams.length === 0) && <span className="text-xs text-gray-400">-</span>}
                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell text-center">
                                            <Badge variant={admin.status === 'active' ? 'default' : 'destructive'} className="capitalize h-5 text-[10px]">
                                                {admin.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {isSuperAdmin && (
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" className="h-8 w-8 p-0 opacity-60 hover:opacity-100">
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
                                                                if (confirm('Are you sure you want to delete this user?')) {
                                                                    deleteUserMutation.mutate(admin.id);
                                                                }
                                                            }}
                                                        >
                                                            <Trash2 className="h-4 w-4 mr-2" /> Delete User
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>
                <TabsContent value="viewers" className="mt-6">
                    {/* Reuse the same user list UI for viewers */}
                    <div className="grid grid-cols-1 gap-4 md:hidden">
                        {viewerList.map((viewer) => (
                            <div key={viewer.id} className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm space-y-4">
                                <div className="flex justify-between items-start">
                                    <div className="flex items-center gap-3">
                                        <div className="h-10 w-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold">
                                            {viewer.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-gray-900 leading-none">{viewer.name}</h3>
                                            <span className="text-xs text-gray-400 font-arabic" dir="rtl">{viewer.name_sd}</span>
                                        </div>
                                    </div>
                                    {isSuperAdmin && (
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                                    <MoreVertical className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem asChild>
                                                    <Link to={`/admin/users/${viewer.id}/edit`} className="flex items-center gap-2 cursor-pointer">
                                                        <Edit className="h-4 w-4" /> Edit User
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    className="text-red-600 focus:text-red-600 cursor-pointer"
                                                    onClick={() => {
                                                        if (confirm('Are you sure you want to delete this user?')) {
                                                            deleteUserMutation.mutate(viewer.id);
                                                        }
                                                    }}
                                                >
                                                    <Trash2 className="h-4 w-4 mr-2" /> Delete User
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    )}
                                </div>
                                <div className="grid grid-cols-2 gap-4 text-xs">
                                    <div className="space-y-1">
                                        <span className="text-gray-400 uppercase tracking-wider block">Credentials</span>
                                        <span className="font-medium text-gray-700 truncate block">{viewer.username}</span>
                                        <span className="text-gray-500 truncate block">{viewer.email}</span>
                                    </div>
                                    <div className="space-y-1">
                                        <span className="text-gray-400 uppercase tracking-wider block">Role & Status</span>
                                        <div className="flex flex-wrap gap-1 mb-1">
                                            {viewer.roles?.map(role => (
                                                <Badge key={role.id} variant="secondary" className="text-[9px] px-1 py-0 border-none uppercase">
                                                    {role.name.replace('_', ' ')}
                                                </Badge>
                                            ))}
                                        </div>
                                        <Badge variant={viewer.status === 'active' ? 'default' : 'destructive'} className="text-[9px] px-1 py-0 capitalize h-4">
                                            {viewer.status}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        ))}
                        {viewerList.length === 0 && (
                            <div className="py-12 text-center bg-gray-50 rounded-lg border border-dashed border-gray-200">
                                <p className="text-sm text-gray-400 font-inter">No viewers found.</p>
                            </div>
                        )}
                    </div>

                    <div className="hidden md:block bg-white rounded-xl border shadow-sm overflow-hidden">
                        <Table>
                            <TableHeader className="bg-gray-50/50">
                                <TableRow>
                                    <TableHead className="min-w-[150px]">Viewer Name</TableHead>
                                    <TableHead>Username / Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead className="hidden sm:table-cell text-center">Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {viewerList.map((viewer) => (
                                    <TableRow key={viewer.id} className="hover:bg-gray-50/50 transition-colors">
                                        <TableCell className="font-medium whitespace-nowrap">
                                            <div className="flex flex-col">
                                                <span className="text-gray-900">{viewer.name}</span>
                                                <span className="text-xs text-gray-400 font-arabic" dir="rtl">{viewer.name_sd}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col text-sm">
                                                <span className="font-medium text-gray-700">{viewer.username}</span>
                                                <span className="text-xs text-xs text-gray-500">{viewer.email}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            <div className="flex flex-wrap gap-1">
                                                {viewer.roles?.map(role => (
                                                    <Badge key={role.id} variant="secondary" className="text-[10px] uppercase font-normal">
                                                        {role.name.replace('_', ' ')}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell text-center">
                                            <Badge variant={viewer.status === 'active' ? 'default' : 'destructive'} className="capitalize h-5 text-[10px]">
                                                {viewer.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {isSuperAdmin && (
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" className="h-8 w-8 p-0 opacity-60 hover:opacity-100">
                                                            <MoreVertical className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem asChild>
                                                            <Link to={`/admin/users/${viewer.id}/edit`} className="flex items-center gap-2 cursor-pointer">
                                                                <Edit className="h-4 w-4" /> Edit User
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            className="text-red-600 focus:text-red-600 cursor-pointer"
                                                            onClick={() => {
                                                                if (confirm('Are you sure you want to delete this user?')) {
                                                                    deleteUserMutation.mutate(viewer.id);
                                                                }
                                                            }}
                                                        >
                                                            <Trash2 className="h-4 w-4 mr-2" /> Delete User
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default TeamList;
