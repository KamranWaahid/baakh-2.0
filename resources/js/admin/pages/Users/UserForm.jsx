import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { useMutation, useQueryClient, useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, AlertCircle } from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

// Simple Error Boundary to catch render crashes
class UserFormErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error("UserForm Error:", error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="p-8 text-center space-y-4 max-w-2xl mx-auto">
                    <AlertCircle className="h-12 w-12 text-red-500 mx-auto" />
                    <h2 className="text-xl font-bold text-red-600">Something went wrong</h2>
                    <p className="text-gray-500 text-sm bg-gray-50 p-4 rounded border font-mono text-left overflow-auto">
                        {this.state.error?.toString()}
                    </p>
                    <Button onClick={() => window.location.reload()}>Reload Page</Button>
                </div>
            );
        }
        return this.props.children;
    }
}

const UserFormContent = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { register, handleSubmit, reset, setValue, watch, formState: { errors } } = useForm({
        defaultValues: {
            status: 'active',
            role: 'admin'
        }
    });

    const { data: user, isLoading: isLoadingUser, isError: isUserError, error: userError } = useQuery({
        queryKey: ['user', id],
        queryFn: async () => {
            console.log("Fetching user details for ID:", id);
            const response = await api.get(`/api/admin/users/${id}`);
            return response.data;
        },
        retry: 1
    });

    const { data: roles, isLoading: isLoadingRoles } = useQuery({
        queryKey: ['roles'],
        queryFn: async () => {
            console.log("Fetching roles list");
            const response = await api.get('/api/admin/roles');
            return response.data;
        }
    });

    useEffect(() => {
        if (user) {
            console.log("User data loaded, resetting form", user);
            reset({
                name: user.name || '',
                name_sd: user.name_sd || '',
                username: user.username || '',
                email: user.email || '',
                phone: user.phone || '',
                whatsapp: user.whatsapp || '',
                status: user.status || 'active',
                role: (user.roles && user.roles.length > 0) ? user.roles[0].name : 'admin'
            });
        }
    }, [user, reset]);

    // Register these manually since they are handled by Select components
    useEffect(() => {
        register('status');
        register('role');
    }, [register]);

    const currentStatus = watch('status');
    const currentRole = watch('role');

    const mutation = useMutation({
        mutationFn: async (data) => {
            return api.put(`/api/admin/users/${id}`, data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['admins']);
            navigate('/admin/teams');
        }
    });

    const onSubmit = (data) => {
        console.log("Submitting form data:", data);
        mutation.mutate(data);
    };

    if (isLoadingUser || isLoadingRoles) {
        return <div className="p-8 text-center text-gray-500 animate-pulse">Loading user details and roles...</div>;
    }

    if (isUserError || !user) {
        return (
            <div className="p-8 text-center space-y-4 max-w-2xl mx-auto">
                <AlertCircle className="h-12 w-12 text-red-500 mx-auto" />
                <h2 className="text-xl font-bold">Error Loading User</h2>
                <p className="text-gray-500">
                    {userError?.response?.data?.message || userError?.message || "User not found or an error occurred."}
                </p>
                <Button onClick={() => navigate('/admin/teams')}>Back to Admins & Teams</Button>
            </div>
        );
    }

    return (
        <div className="max-w-2xl mx-auto p-4 md:p-8 fade-in">
            <Button variant="ghost" onClick={() => navigate('/admin/teams')} className="mb-4 md:mb-6 pl-0 hover:pl-2 transition-all flex items-center">
                <ArrowLeft className="h-4 w-4 mr-2" /> Back to Admins & Teams
            </Button>

            <div className="grid gap-6">
                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>Edit Admin User</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                            <div className="grid sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Full Name (English)</label>
                                    <Input
                                        {...register('name', { required: 'Full Name is required' })}
                                        placeholder="e.g. John Doe"
                                    />
                                    {errors.name && <p className="text-red-500 text-sm">{errors.name.message}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Full Name (Sindhi)</label>
                                    <Input
                                        {...register('name_sd')}
                                        placeholder="مثال: جان دو"
                                        dir="rtl"
                                    />
                                </div>
                            </div>

                            <div className="grid sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Username</label>
                                    <Input
                                        {...register('username', { required: 'Username is required' })}
                                        placeholder="johndoe"
                                    />
                                    {errors.username && <p className="text-red-500 text-sm">{errors.username.message}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Email Address</label>
                                    <Input
                                        type="email"
                                        {...register('email', { required: 'Email is required' })}
                                        placeholder="john@example.com"
                                    />
                                    {errors.email && <p className="text-red-500 text-sm">{errors.email.message}</p>}
                                </div>
                            </div>

                            <div className="grid sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Phone Number</label>
                                    <Input
                                        {...register('phone')}
                                        placeholder="+92..."
                                    />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">WhatsApp Number</label>
                                    <Input
                                        {...register('whatsapp')}
                                        placeholder="+92..."
                                    />
                                </div>
                            </div>

                            <div className="grid sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">System Role</label>
                                    <Select
                                        value={currentRole || ''}
                                        onValueChange={(val) => setValue('role', val, { shouldDirty: true })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Array.isArray(roles) && roles.length > 0 ? roles.map((role) => {
                                                const roleName = role?.name || '';
                                                const label = roleName.replace('_', ' ');
                                                const formattedLabel = label ? (label.charAt(0).toUpperCase() + label.slice(1)) : 'Unknown Role';

                                                return (
                                                    <SelectItem key={role.id || roleName} value={roleName}>
                                                        {formattedLabel}
                                                    </SelectItem>
                                                );
                                            }) : (
                                                <SelectItem value="admin">Admin (Default)</SelectItem>
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Account Status</label>
                                    <Select
                                        value={currentStatus || 'active'}
                                        onValueChange={(val) => setValue('status', val, { shouldDirty: true })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Active</SelectItem>
                                            <SelectItem value="suspended">Suspended</SelectItem>
                                            <SelectItem value="deleted">Deleted (Soft)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            {mutation.isError && (
                                <div className="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md text-sm">
                                    <p className="font-bold">Error updating user:</p>
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

                            <div className="flex gap-4 pt-2">
                                <Button type="button" variant="outline" onClick={() => navigate('/admin/teams')} className="w-full">
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={mutation.isPending} className="w-full">
                                    {mutation.isPending ? 'Saving...' : 'Update Admin User'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

const UserForm = () => (
    <UserFormErrorBoundary>
        <UserFormContent />
    </UserFormErrorBoundary>
);

export default UserForm;
