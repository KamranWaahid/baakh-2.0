import React from 'react';
import { useForm } from 'react-hook-form';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import useAuth from '../hooks/useAuth';
import api from '../api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'sonner';

const Settings = () => {
    const { user } = useAuth();
    const queryClient = useQueryClient();

    const { register, handleSubmit, formState: { errors } } = useForm({
        defaultValues: {
            name: user?.name || '',
            email: user?.email || '',
        }
    });

    const updateProfileMutation = useMutation({
        mutationFn: (data) => api.put(`/api/admin/users/${user.id}`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['auth-user']);
            toast.success('Profile updated successfully');
        },
        onError: (err) => {
            toast.error(err.response?.data?.message || 'Failed to update profile');
        }
    });

    const onSubmit = (data) => {
        updateProfileMutation.mutate(data);
    };

    return (
        <div className="space-y-6 max-w-2xl">
            <div>
                <h1 className="text-3xl font-bold tracking-tight">Settings</h1>
                <p className="text-muted-foreground">
                    Manage your account settings and preferences.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Profile Information</CardTitle>
                    <CardDescription>Update your account details.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Name</label>
                            <Input {...register('name', { required: 'Name is required' })} />
                            {errors.name && <p className="text-red-500 text-sm">{errors.name.message}</p>}
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Email</label>
                            <Input type="email" {...register('email', { required: 'Email is required' })} />
                            {errors.email && <p className="text-red-500 text-sm">{errors.email.message}</p>}
                        </div>

                        <Button type="submit" disabled={updateProfileMutation.isPending}>
                            {updateProfileMutation.isPending ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>System Preferences</CardTitle>
                    <CardDescription>Configure how you interact with the admin panel.</CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">Additional settings coming soon.</p>
                </CardContent>
            </Card>
        </div>
    );
};

export default Settings;
