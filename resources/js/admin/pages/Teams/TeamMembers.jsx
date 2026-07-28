import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useParams, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import api from '../../api/axios';
import useAuth from '../../hooks/useAuth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Trash2, ArrowLeft } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

const formatRoleLabel = (name = '') => {
    const label = name.replace(/_/g, ' ');
    return label ? label.charAt(0).toUpperCase() + label.slice(1) : 'Role';
};

const TeamMembers = () => {
    const { id: teamId } = useParams();
    const queryClient = useQueryClient();
    const { canManage, canDelete } = useAuth();
    const [formError, setFormError] = useState(null);

    const { register, handleSubmit, reset, formState: { errors }, setValue, watch } = useForm({
        defaultValues: {
            role: 'member',
            system_role: 'viewer'
        }
    });

    const teamRole = watch('role');
    const systemRole = watch('system_role');

    const { data: team } = useQuery({
        queryKey: ['team', teamId],
        queryFn: async () => {
            const response = await api.get(`/api/admin/teams/${teamId}`);
            return response.data;
        }
    });

    const { data: members, isLoading } = useQuery({
        queryKey: ['team-members', teamId],
        queryFn: async () => {
            const response = await api.get(`/api/admin/teams/${teamId}/members`);
            return response.data;
        }
    });

    const { data: roles } = useQuery({
        queryKey: ['roles'],
        queryFn: async () => {
            const response = await api.get('/api/admin/roles');
            return response.data;
        }
    });

    const addMemberMutation = useMutation({
        mutationFn: async (data) => {
            return api.post(`/api/admin/teams/${teamId}/members`, data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['team-members', teamId]);
            queryClient.invalidateQueries(['admins']);
            queryClient.invalidateQueries(['editors']);
            queryClient.invalidateQueries(['viewers']);
            setFormError(null);
            reset({ role: 'member', system_role: 'viewer' });
        },
        onError: (error) => {
            const errorsPayload = error.response?.data?.errors;
            if (errorsPayload) {
                setFormError(Object.values(errorsPayload).flat().join(' '));
            } else {
                setFormError(error.response?.data?.message || 'Failed to add member');
            }
        }
    });

    const removeMemberMutation = useMutation({
        mutationFn: async (userId) => {
            return api.delete(`/api/admin/teams/${teamId}/members/${userId}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['team-members', teamId]);
        }
    });

    const updateRoleMutation = useMutation({
        mutationFn: async ({ userId, role }) => {
            return api.put(`/api/admin/teams/${teamId}/members/${userId}`, { role });
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['team-members', teamId]);
        }
    });

    const onAddMember = (data) => {
        setFormError(null);
        addMemberMutation.mutate(data);
    };

    if (isLoading) return <div className="p-8">Loading members...</div>;

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex items-center gap-3 md:gap-4">
                <Link to="/admin/teams">
                    <Button variant="ghost" size="icon" className="h-8 w-8 md:h-10 md:w-10">
                        <ArrowLeft className="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 className="text-xl md:text-2xl font-bold tracking-tight">Manage Members</h1>
                    <p className="text-gray-500 text-sm md:text-base">{team?.name}</p>
                </div>
            </div>

            <div className="grid gap-6 grid-cols-1 lg:grid-cols-3">
                {canManage && (
                    <Card className="lg:col-span-2 h-fit">
                        <CardHeader className="pb-3 md:pb-6">
                            <CardTitle className="text-lg">Add New Member</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit(onAddMember)} className="space-y-4">
                                <div className="grid sm:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Full Name (English)</label>
                                        <Input {...register('name')} placeholder="John Doe" className="h-10" />
                                        <p className="text-xs text-gray-400">Required only when creating a new account.</p>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Full Name (Sindhi)</label>
                                        <Input {...register('name_sd')} placeholder="جان دو" dir="rtl" className="h-10" />
                                    </div>
                                </div>

                                <div className="grid sm:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Username</label>
                                        <Input {...register('username')} placeholder="johndoe" className="h-10" />
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Email Address *</label>
                                        <Input {...register('email', { required: 'Email is required' })} type="email" placeholder="john@example.com" className="h-10" />
                                        {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email.message}</p>}
                                    </div>
                                </div>

                                <div className="grid sm:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Password</label>
                                        <Input
                                            type="password"
                                            {...register('password', {
                                                minLength: { value: 8, message: 'Min 8 characters' }
                                            })}
                                            placeholder="••••••••"
                                            className="h-10"
                                        />
                                        {errors.password && <p className="text-red-500 text-xs">{errors.password.message}</p>}
                                        <p className="text-xs text-gray-400">Required when creating a new account. Ignored if email already exists.</p>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Phone Number</label>
                                        <Input {...register('phone')} placeholder="+92..." className="h-10" />
                                    </div>
                                </div>

                                <div className="grid sm:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Team Role</label>
                                        <Select value={teamRole} onValueChange={(v) => setValue('role', v)}>
                                            <SelectTrigger className="h-10">
                                                <SelectValue placeholder="Select team role" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="member">Member</SelectItem>
                                                <SelectItem value="admin">Admin</SelectItem>
                                                <SelectItem value="owner">Owner</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">System Role</label>
                                        <Select value={systemRole} onValueChange={(v) => setValue('system_role', v)}>
                                            <SelectTrigger className="h-10">
                                                <SelectValue placeholder="Select system role" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {(Array.isArray(roles) ? roles : []).map((role) => (
                                                    <SelectItem key={role.id || role.name} value={role.name}>
                                                        {formatRoleLabel(role.name)}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <p className="text-xs text-gray-400">Controls access across the admin panel (admin, editor, viewer…).</p>
                                    </div>
                                </div>

                                {formError && (
                                    <div className="bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded-md text-sm">
                                        {formError}
                                    </div>
                                )}

                                <Button type="submit" className="w-full sm:w-auto h-11 px-8" disabled={addMemberMutation.isPending}>
                                    {addMemberMutation.isPending ? 'Adding...' : 'Add Member to Team'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card className={canManage ? "lg:col-span-3" : "col-span-1 lg:col-span-3"}>
                    <CardHeader className="pb-3 md:pb-6">
                        <CardTitle className="text-lg text-primary">Team Members ({members?.length || 0})</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0 sm:p-6">
                        <div className="rounded-md border-x sm:border overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="min-w-[150px]">User</TableHead>
                                        <TableHead>Team Role</TableHead>
                                        <TableHead className="hidden md:table-cell">System Role</TableHead>
                                        <TableHead className="hidden sm:table-cell">Joined</TableHead>
                                        {canManage && <TableHead className="text-right">Actions</TableHead>}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {members?.map((member) => (
                                        <TableRow key={member.id}>
                                            <TableCell className="whitespace-nowrap">
                                                <div className="flex flex-col">
                                                    <span className="font-medium">{member.user?.name}</span>
                                                    <span className="text-xs text-gray-500">{member.user?.email}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                <Select
                                                    value={member.role}
                                                    onValueChange={(val) => updateRoleMutation.mutate({ userId: member.user_id, role: val })}
                                                    disabled={!canManage || updateRoleMutation.isPending || member.role === 'owner'}
                                                >
                                                    <SelectTrigger className="w-[110px] h-8 text-xs">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="member">Member</SelectItem>
                                                        <SelectItem value="admin">Admin</SelectItem>
                                                        <SelectItem value="owner">Owner</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <div className="flex flex-wrap gap-1">
                                                    {(member.user?.roles || []).map((role) => (
                                                        <Badge key={role.id || role.name} variant="secondary" className="text-[10px] uppercase font-normal">
                                                            {formatRoleLabel(role.name)}
                                                        </Badge>
                                                    ))}
                                                    {(!member.user?.roles || member.user.roles.length === 0) && (
                                                        <span className="text-xs text-gray-400">-</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-xs text-gray-400 whitespace-nowrap hidden sm:table-cell">
                                                {member.joined_at ? new Date(member.joined_at).toLocaleDateString() : '-'}
                                            </TableCell>
                                            {canManage && (
                                                <TableCell className="text-right whitespace-nowrap">
                                                    {member.role !== 'owner' && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50"
                                                            disabled={!canDelete || removeMemberMutation.isPending}
                                                            onClick={() => {
                                                                if (confirm(`Remove ${member.user?.name} from team?`)) {
                                                                    removeMemberMutation.mutate(member.user_id);
                                                                }
                                                            }}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))}
                                    {(!members || members.length === 0) && (
                                        <TableRow>
                                            <TableCell colSpan={5} className="h-24 text-center text-muted-foreground">
                                                No members found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default TeamMembers;
