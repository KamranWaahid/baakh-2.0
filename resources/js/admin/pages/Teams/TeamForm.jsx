import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { useMutation, useQueryClient, useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

const TeamForm = () => {
    const { id } = useParams();
    const isEditMode = !!id;
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { register, handleSubmit, reset, setValue, formState: { errors } } = useForm({
        defaultValues: {
            admin_role: 'admin'
        }
    });

    const { data: team, isLoading } = useQuery({
        queryKey: ['team', id],
        queryFn: async () => {
            const response = await api.get(`/api/admin/teams/${id}`);
            return response.data;
        },
        enabled: isEditMode
    });

    const { data: roles } = useQuery({
        queryKey: ['roles'],
        queryFn: async () => {
            const response = await api.get('/api/admin/roles');
            return response.data;
        }
    });

    useEffect(() => {
        if (team) {
            reset({
                name: team.name,
                description: team.description
            });
        }
    }, [team, reset]);

    const mutation = useMutation({
        mutationFn: async (data) => {
            if (isEditMode) {
                return api.put(`/api/admin/teams/${id}`, data);
            }
            return api.post('/api/admin/teams', data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['teams']);
            navigate('/teams');
        }
    });

    const onSubmit = (data) => {
        mutation.mutate(data);
    };

    if (isEditMode && isLoading) return <div>Loading...</div>;

    return (
        <div className="max-w-2xl mx-auto p-4 md:p-8">
            <Button variant="ghost" onClick={() => navigate('/teams')} className="mb-4 md:mb-6 pl-0 hover:pl-2 transition-all flex items-center">
                <ArrowLeft className="h-4 w-4 mr-2" /> Back to Teams
            </Button>

            <div className="grid gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Team Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form id="team-form" onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Team Name</label>
                                <Input
                                    {...register('name', { required: 'Team name is required' })}
                                    placeholder="e.g. Content Team"
                                />
                                {errors.name && <p className="text-red-500 text-sm">{errors.name.message}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Description</label>
                                <Textarea
                                    {...register('description')}
                                    placeholder="Brief description of the team's purpose"
                                    rows={3}
                                />
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {!isEditMode && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Admin Account Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Full Name (English)</label>
                                    <Input
                                        {...register('admin_name', { required: !isEditMode ? 'Full Name is required' : false })}
                                        placeholder="e.g. John Doe"
                                    />
                                    {errors.admin_name && <p className="text-red-500 text-sm">{errors.admin_name.message}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Full Name (Sindhi)</label>
                                    <Input
                                        {...register('admin_name_sd')}
                                        placeholder="مثال: جان دو"
                                        dir="rtl"
                                    />
                                </div>
                            </div>

                            <div className="grid sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Username</label>
                                    <Input
                                        {...register('admin_username', { required: !isEditMode ? 'Username is required' : false })}
                                        placeholder="johndoe"
                                    />
                                    {errors.admin_username && <p className="text-red-500 text-sm">{errors.admin_username.message}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Email Address</label>
                                    <Input
                                        type="email"
                                        {...register('admin_email', { required: !isEditMode ? 'Email is required' : false })}
                                        placeholder="john@example.com"
                                    />
                                    {errors.admin_email && <p className="text-red-500 text-sm">{errors.admin_email.message}</p>}
                                </div>
                            </div>

                            <div className="grid sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Phone Number</label>
                                    <Input
                                        {...register('admin_phone')}
                                        placeholder="+92..."
                                    />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">WhatsApp Number</label>
                                    <Input
                                        {...register('admin_whatsapp')}
                                        placeholder="+92..."
                                    />
                                </div>
                            </div>

                            <div className="grid sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Password</label>
                                    <Input
                                        type="password"
                                        {...register('admin_password', { required: !isEditMode ? 'Password is required' : false, minLength: { value: 8, message: 'Min 8 chars' } })}
                                        placeholder="••••••••"
                                    />
                                    {errors.admin_password && <p className="text-red-500 text-sm">{errors.admin_password.message}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">System Role</label>
                                    <Select onValueChange={(val) => setValue('admin_role', val)} defaultValue="admin">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {roles?.map((role) => (
                                                <SelectItem key={role.id} value={role.name}>
                                                    {role.name.replace('_', ' ').charAt(0).toUpperCase() + role.name.replace('_', ' ').slice(1)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {mutation.isError && (
                    <div className="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md text-sm">
                        <p className="font-bold">Error saving team:</p>
                        {mutation.error.response?.data?.errors ? (
                            <ul className="list-disc list-inside mt-1">
                                {Object.values(mutation.error.response.data.errors).flat().map((err, i) => (
                                    <li key={i}>{err}</li>
                                ))}
                            </ul>
                        ) : (
                            <p>{mutation.error.response?.data?.message || mutation.error.message}</p>
                        )}
                    </div>
                )}

                <Button type="submit" form="team-form" disabled={mutation.isPending} className="w-full">
                    {mutation.isPending ? 'Saving...' : (isEditMode ? 'Update Team' : 'Create Team & Admin')}
                </Button>
            </div>
        </div>
    );
};

export default TeamForm;
