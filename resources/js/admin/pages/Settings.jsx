import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import useAuth from '../hooks/useAuth';
import api from '../api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'sonner';
import { Image as ImageIcon, Wand2 } from 'lucide-react';

const Settings = () => {
    const { user } = useAuth();
    const queryClient = useQueryClient();
    const [optimizationLogs, setOptimizationLogs] = useState('');

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

    const optimizeImagesMutation = useMutation({
        mutationFn: () => api.post('/api/admin/performance/optimize-images'),
        onSuccess: (res) => {
            toast.success(res.data.message || 'Image optimization completed');
            if (res.data.output) {
                setOptimizationLogs(res.data.output);
            }
        },
        onError: (err) => {
            toast.error(err.response?.data?.message || 'Failed to optimize images');
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
                    <CardTitle>System Tools</CardTitle>
                    <CardDescription>Administrative actions to maintain system health and performance.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between border rounded-lg p-4 bg-gray-50/50">
                        <div className="space-y-1 mb-4 sm:mb-0">
                            <div className="flex items-center gap-2">
                                <ImageIcon className="h-4 w-4 text-blue-500" />
                                <h4 className="text-sm font-medium">Optimize Poet Avatars</h4>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Scan the database and automatically resize and compress all poet profile pictures to WebP format.
                            </p>
                            {optimizationLogs && (
                                <div className="mt-4 p-3 bg-black rounded text-green-400 font-mono text-xs whitespace-pre-wrap overflow-x-auto max-h-48 scrollbar-thin">
                                    {optimizationLogs}
                                </div>
                            )}
                        </div>
                        <Button
                            variant="secondary"
                            onClick={() => optimizeImagesMutation.mutate()}
                            disabled={optimizeImagesMutation.isPending}
                            className="shrink-0 ml-4 group min-w-[160px]"
                        >
                            {optimizeImagesMutation.isPending ? (
                                <>
                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-primary mr-2"></div>
                                    Optimizing...
                                </>
                            ) : (
                                <>
                                    <Wand2 className="h-4 w-4 mr-2 group-hover:text-primary transition-colors" />
                                    Run Optimization
                                </>
                            )}
                        </Button>
                    </div>
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
