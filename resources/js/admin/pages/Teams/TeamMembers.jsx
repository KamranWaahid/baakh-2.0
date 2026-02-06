import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useParams, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import api from '../../api/axios';
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
import { Trash2, ArrowLeft, Plus } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const TeamMembers = () => {
    const { id: teamId } = useParams();
    const queryClient = useQueryClient();
    const { register, handleSubmit, reset, formState: { errors }, setValue } = useForm();

    // Fetch team details
    const { data: team } = useQuery({
        queryKey: ['team', teamId],
        queryFn: async () => {
            const response = await api.get(`/api/admin/teams/${teamId}`);
            return response.data;
        }
    });

    // Fetch members
    const { data: members, isLoading } = useQuery({
        queryKey: ['team-members', teamId],
        queryFn: async () => {
            const response = await api.get(`/api/admin/teams/${teamId}/members`);
            return response.data;
        }
    });

    // Add Member Mutation
    const addMemberMutation = useMutation({
        mutationFn: async (data) => {
            return api.post(`/api/admin/teams/${teamId}/members`, data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['team-members', teamId]);
            reset();
        }
    });

    // Remove Member Mutation
    const removeMemberMutation = useMutation({
        mutationFn: async (userId) => {
            return api.delete(`/api/admin/teams/${teamId}/members/${userId}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['team-members', teamId]);
        }
    });

    // Update Role Mutation
    const updateRoleMutation = useMutation({
        mutationFn: async ({ userId, role }) => {
            return api.put(`/api/admin/teams/${teamId}/members/${userId}`, { role });
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['team-members', teamId]);
        }
    });

    const onAddMember = (data) => {
        addMemberMutation.mutate(data);
    };

    if (isLoading) return <div>Loading members...</div>;

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex items-center gap-3 md:gap-4">
                <Link to="/teams">
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
                {/* Add Member Form */}
                <Card className="lg:col-span-1 h-fit">
                    <CardHeader className="pb-3 md:pb-6">
                        <CardTitle className="text-lg">Add New Member</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit(onAddMember)} className="space-y-4">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">User Email</label>
                                <Input
                                    {...register('email', { required: 'Email is required' })}
                                    placeholder="user@example.com"
                                    type="email"
                                    className="h-10"
                                />
                                {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email.message}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Role</label>
                                <Select onValueChange={(val) => setValue('role', val)} defaultValue="member">
                                    <SelectTrigger className="h-10">
                                        <SelectValue placeholder="Select role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="member">Member</SelectItem>
                                        <SelectItem value="admin">Admin</SelectItem>
                                        <SelectItem value="owner">Owner</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <Button type="submit" className="w-full h-10" disabled={addMemberMutation.isPending}>
                                <Plus className="h-4 w-4 mr-2" /> Add Member
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Members List */}
                <Card className="lg:col-span-2">
                    <CardHeader className="pb-3 md:pb-6">
                        <CardTitle className="text-lg text-primary">Team Members ({members?.length || 0})</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0 sm:p-6">
                        <div className="rounded-md border-x sm:border overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="min-w-[150px]">User</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead className="hidden sm:table-cell">Joined</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
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
                                                    defaultValue={member.role}
                                                    onValueChange={(val) => updateRoleMutation.mutate({ userId: member.user_id, role: val })}
                                                    disabled={updateRoleMutation.isPending || member.role === 'owner'}
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
                                            <TableCell className="text-xs text-gray-400 whitespace-nowrap hidden sm:table-cell">
                                                {new Date(member.joined_at).toLocaleDateString()}
                                            </TableCell>
                                            <TableCell className="text-right whitespace-nowrap">
                                                {member.role !== 'owner' && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50"
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
                                        </TableRow>
                                    ))}
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
