import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../api/axios';
import useAuth from '../../hooks/useAuth';
import {
    MoreVertical,
    Plus,
    Edit,
    Trash2,
    User,
    Users
} from 'lucide-react';
import { Button } from '@/components/ui/button';
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

const userInitial = (user) => {
    const label = (user?.name || user?.email || user?.username || '?').trim();
    return label ? label.charAt(0).toUpperCase() : '?';
};

const formatRoleLabel = (name = '') => name.replace(/_/g, ' ');

const roleKey = (role, index) => role?.id || role?.name || index;
const roleLabel = (role) => formatRoleLabel(typeof role === 'string' ? role : (role?.name || ''));

const UserActions = ({ user, canEdit, onDelete, deleting }) => {
    if (!canEdit) return null;

    return (
        <div className="flex items-center justify-end gap-1">
            <Button variant="ghost" size="icon" className="h-8 w-8" asChild title="Edit user">
                <Link to={`/admin/users/${user.id}/edit`}>
                    <Edit className="h-4 w-4" />
                </Link>
            </Button>
            <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50"
                title="Delete user"
                disabled={deleting}
                onClick={() => {
                    if (confirm(`Delete ${user.name || user.email || 'this user'}? This cannot be undone.`)) {
                        onDelete(user.id);
                    }
                }}
            >
                <Trash2 className="h-4 w-4" />
            </Button>
        </div>
    );
};

const UserCard = ({ user, canEdit, onDelete, deleting }) => (
    <div className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm space-y-4">
        <div className="flex justify-between items-start gap-2">
            <div className="flex items-center gap-3 min-w-0">
                <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold shrink-0">
                    {userInitial(user)}
                </div>
                <div className="min-w-0">
                    <h3 className="font-semibold text-gray-900 leading-none truncate">{user.name}</h3>
                    <span className="text-xs text-gray-400 font-arabic" dir="rtl">{user.name_sd}</span>
                </div>
            </div>
            <UserActions user={user} canEdit={canEdit} onDelete={onDelete} deleting={deleting} />
        </div>

        <div className="grid grid-cols-2 gap-4 text-xs">
            <div className="space-y-1">
                <span className="text-gray-400 uppercase tracking-wider block">Credentials</span>
                <span className="font-medium text-gray-700 truncate block">{user.username}</span>
                <span className="text-gray-500 truncate block">{user.email}</span>
            </div>
            <div className="space-y-1">
                <span className="text-gray-400 uppercase tracking-wider block">Role & Status</span>
                <div className="flex flex-wrap gap-1 mb-1">
                    {user.roles?.map((role, index) => (
                        <Badge key={roleKey(role, index)} variant="secondary" className="text-[9px] px-1 py-0 border-none uppercase">
                            {roleLabel(role)}
                        </Badge>
                    ))}
                </div>
                <Badge variant={user.status === 'active' ? 'default' : 'destructive'} className="text-[9px] px-1 py-0 capitalize h-4">
                    {user.status}
                </Badge>
            </div>
        </div>
        {user.teams && user.teams.length > 0 && (
            <div className="pt-3 border-t">
                <span className="text-[10px] text-gray-400 uppercase tracking-wider mb-2 block">Assigned Teams</span>
                <div className="flex flex-wrap gap-1.5">
                    {user.teams.map(team => (
                        <Badge key={team.id} variant="outline" className="text-[10px] font-normal px-2 py-0 border-gray-100 bg-gray-50/50">
                            {team.name}
                        </Badge>
                    ))}
                </div>
            </div>
        )}
    </div>
);

const UserTable = ({ users, canEdit, onDelete, deleting, showTeams = true }) => (
    <div className="hidden md:block bg-white rounded-xl border shadow-sm overflow-hidden">
        <Table>
            <TableHeader className="bg-gray-50/50">
                <TableRow>
                    <TableHead className="min-w-[150px]">Name</TableHead>
                    <TableHead>Username / Email</TableHead>
                    <TableHead>Role</TableHead>
                    {showTeams && <TableHead className="hidden md:table-cell">Team</TableHead>}
                    <TableHead className="hidden sm:table-cell text-center">Status</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {users.map((user) => (
                    <TableRow key={user.id} className="hover:bg-gray-50/50 transition-colors">
                        <TableCell className="font-medium whitespace-nowrap">
                            <div className="flex flex-col">
                                <span className="text-gray-900">{user.name}</span>
                                <span className="text-xs text-gray-400 font-arabic" dir="rtl">{user.name_sd}</span>
                            </div>
                        </TableCell>
                        <TableCell>
                            <div className="flex flex-col text-sm">
                                <span className="font-medium text-gray-700">{user.username}</span>
                                <span className="text-xs text-gray-500">{user.email}</span>
                            </div>
                        </TableCell>
                        <TableCell className="whitespace-nowrap">
                            <div className="flex flex-wrap gap-1">
                                {user.roles?.map((role, index) => (
                                    <Badge key={roleKey(role, index)} variant="secondary" className="text-[10px] uppercase font-normal">
                                        {roleLabel(role)}
                                    </Badge>
                                ))}
                            </div>
                        </TableCell>
                        {showTeams && (
                            <TableCell className="hidden md:table-cell">
                                <div className="flex flex-wrap gap-1">
                                    {user.teams?.map(team => (
                                        <Badge key={team.id} variant="outline" className="text-[10px] font-normal">
                                            {team.name}
                                        </Badge>
                                    ))}
                                    {(!user.teams || user.teams.length === 0) && <span className="text-xs text-gray-400">-</span>}
                                </div>
                            </TableCell>
                        )}
                        <TableCell className="hidden sm:table-cell text-center">
                            <Badge variant={user.status === 'active' ? 'default' : 'destructive'} className="capitalize h-5 text-[10px]">
                                {user.status}
                            </Badge>
                        </TableCell>
                        <TableCell className="text-right">
                            <UserActions user={user} canEdit={canEdit} onDelete={onDelete} deleting={deleting} />
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    </div>
);

const EmptyState = ({ label }) => (
    <div className="py-12 text-center bg-gray-50 rounded-lg border border-dashed border-gray-200">
        <p className="text-sm text-gray-400 font-inter">{label}</p>
    </div>
);

const TeamList = () => {
    const queryClient = useQueryClient();
    const [activeTab, setActiveTab] = useState('teams');
    const [actionError, setActionError] = useState(null);
    const { isSuperAdmin, canManage, canManageUsers } = useAuth();
    const canEditUsers = canManageUsers;

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
            const response = await api.get('/api/admin/users?group=admins');
            return response.data;
        }
    });

    const { data: editors, isLoading: isLoadingEditors } = useQuery({
        queryKey: ['editors'],
        queryFn: async () => {
            const response = await api.get('/api/admin/users?group=editors');
            return response.data;
        }
    });

    const { data: viewers, isLoading: isLoadingViewers } = useQuery({
        queryKey: ['viewers'],
        queryFn: async () => {
            const response = await api.get('/api/admin/users?group=users');
            return response.data;
        }
    });

    const deleteTeamMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/api/admin/teams/${id}`);
        },
        onSuccess: () => {
            setActionError(null);
            queryClient.invalidateQueries(['teams']);
        },
        onError: (error) => {
            setActionError(error.response?.data?.message || 'Failed to delete team');
        }
    });

    const deleteUserMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/api/admin/users/${id}`);
        },
        onSuccess: () => {
            setActionError(null);
            queryClient.invalidateQueries(['admins']);
            queryClient.invalidateQueries(['editors']);
            queryClient.invalidateQueries(['viewers']);
        },
        onError: (error) => {
            setActionError(error.response?.data?.message || 'Failed to delete user');
        }
    });

    if (isLoadingTeams || isLoadingAdmins || isLoadingEditors || isLoadingViewers) {
        return <div className="p-8">Loading...</div>;
    }

    const teamList = teams?.data || [];
    const adminList = admins?.data || [];
    const editorList = editors?.data || [];
    const viewerList = viewers?.data || [];

    const createUserRole =
        activeTab === 'editors' ? 'editor' :
        activeTab === 'viewers' ? 'viewer' :
        'admin';

    const handleDeleteUser = (id) => deleteUserMutation.mutate(id);

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
                <div className="space-y-1">
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-gray-900 font-inter">Admins & Teams</h1>
                    <p className="text-gray-500 text-sm md:text-base max-w-2xl">Manage admins, editors, users, and collaborative teams</p>
                </div>
                {canManage && (
                    <div className="flex flex-col gap-2 w-full sm:w-auto">
                        {canEditUsers && (
                            <Link to={`/admin/users/create?role=${createUserRole}`} className="w-full">
                                <Button className="w-full justify-start sm:justify-center flex items-center gap-2 h-10 shadow-sm">
                                    <Plus className="h-4 w-4" />
                                    <span>Add User</span>
                                </Button>
                            </Link>
                        )}
                        <Link to="/admin/teams/create?simple=true" className="w-full">
                            <Button variant="outline" className="w-full justify-start sm:justify-center flex items-center gap-2 h-10 border-gray-200">
                                <Plus className="h-4 w-4" />
                                <span>Create Team</span>
                            </Button>
                        </Link>
                        {isSuperAdmin && (
                            <Link to="/admin/teams/create" className="w-full">
                                <Button variant="outline" className="w-full justify-start sm:justify-center flex items-center gap-2 h-10 border-gray-200">
                                    <Plus className="h-4 w-4" />
                                    <span>Team & Admin</span>
                                </Button>
                            </Link>
                        )}
                    </div>
                )}
            </div>

            {actionError && (
                <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {actionError}
                </div>
            )}

            <Tabs defaultValue="teams" onValueChange={setActiveTab}>
                <TabsList className="grid w-full grid-cols-2 sm:grid-cols-4 max-w-full sm:max-w-[720px] h-auto">
                    <TabsTrigger value="teams" className="text-xs sm:text-sm px-1 sm:px-3">Teams ({teamList.length})</TabsTrigger>
                    <TabsTrigger value="admins" className="text-xs sm:text-sm px-1 sm:px-3">Admins ({adminList.length})</TabsTrigger>
                    <TabsTrigger value="editors" className="text-xs sm:text-sm px-1 sm:px-3">Editors ({editorList.length})</TabsTrigger>
                    <TabsTrigger value="viewers" className="text-xs sm:text-sm px-1 sm:px-3">Users ({viewerList.length})</TabsTrigger>
                </TabsList>

                <TabsContent value="teams" className="mt-6">
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
                        {teamList.length === 0 && <EmptyState label="No teams found." />}
                    </div>

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

                <TabsContent value="admins" className="mt-6 space-y-4">
                    <div className="grid grid-cols-1 gap-4 md:hidden">
                        {adminList.map((admin) => (
                            <UserCard
                                key={admin.id}
                                user={admin}
                                canEdit={canEditUsers}
                                onDelete={handleDeleteUser}
                                deleting={deleteUserMutation.isPending}
                            />
                        ))}
                        {adminList.length === 0 && <EmptyState label="No admin users found." />}
                    </div>
                    <UserTable
                        users={adminList}
                        canEdit={canEditUsers}
                        onDelete={handleDeleteUser}
                        deleting={deleteUserMutation.isPending}
                    />
                </TabsContent>

                <TabsContent value="editors" className="mt-6 space-y-4">
                    <div className="grid grid-cols-1 gap-4 md:hidden">
                        {editorList.map((editor) => (
                            <UserCard
                                key={editor.id}
                                user={editor}
                                canEdit={canEditUsers}
                                onDelete={handleDeleteUser}
                                deleting={deleteUserMutation.isPending}
                            />
                        ))}
                        {editorList.length === 0 && <EmptyState label="No editors found." />}
                    </div>
                    <UserTable
                        users={editorList}
                        canEdit={canEditUsers}
                        onDelete={handleDeleteUser}
                        deleting={deleteUserMutation.isPending}
                    />
                </TabsContent>

                <TabsContent value="viewers" className="mt-6 space-y-4">
                    <div className="grid grid-cols-1 gap-4 md:hidden">
                        {viewerList.map((viewer) => (
                            <UserCard
                                key={viewer.id}
                                user={viewer}
                                canEdit={canEditUsers}
                                onDelete={handleDeleteUser}
                                deleting={deleteUserMutation.isPending}
                            />
                        ))}
                        {viewerList.length === 0 && <EmptyState label="No users found." />}
                    </div>
                    <UserTable
                        users={viewerList}
                        canEdit={canEditUsers}
                        onDelete={handleDeleteUser}
                        deleting={deleteUserMutation.isPending}
                        showTeams={false}
                    />
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default TeamList;
