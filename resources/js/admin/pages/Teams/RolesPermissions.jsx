import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { Shield, Plus, Edit, Trash2 } from 'lucide-react';
import { useForm } from 'react-hook-form';

const RolesPermissions = () => {
    const queryClient = useQueryClient();
    const [selectedRole, setSelectedRole] = useState(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const { register, handleSubmit, reset, setValue, watch } = useForm();

    const { data: roles, isLoading: rolesLoading } = useQuery({
        queryKey: ['roles'],
        queryFn: async () => {
            const response = await api.get('/api/admin/roles');
            return response.data;
        }
    });

    const { data: permissionsObj, isLoading: permissionsLoading } = useQuery({
        queryKey: ['permissions'],
        queryFn: async () => {
            const response = await api.get('/api/admin/permissions');
            return response.data;
        }
    });

    const mutation = useMutation({
        mutationFn: async (data) => {
            if (selectedRole) {
                return api.put(`/api/admin/roles/${selectedRole.id}`, data);
            }
            return api.post('/api/admin/roles', data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['roles']);
            setIsDialogOpen(false);
            reset();
            setSelectedRole(null);
        }
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/api/admin/roles/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['roles']);
        }
    });

    const onSubmit = (data) => {
        mutation.mutate(data);
    };

    const handleEdit = (role) => {
        setSelectedRole(role);
        setValue('name', role.name);
        setValue('description', role.description);
        setValue('permissions', role.permissions.map(p => p.name));
        setIsDialogOpen(true);
    };

    const handleCreate = () => {
        setSelectedRole(null);
        reset();
        setIsDialogOpen(true);
    };

    const { data: user } = useQuery({
        queryKey: ['auth-user'],
        queryFn: async () => {
            const response = await api.get('/api/user');
            return response.data;
        }
    });

    if (rolesLoading || permissionsLoading || !user) return <div>Loading...</div>;

    const isSuperAdmin = user?.roles?.some(r => r.name === 'super_admin');

    if (!isSuperAdmin) {
        return <div className="p-8">Access Denied. You must be a Super Admin to view this page.</div>;
    }

    const groupedPermissions = permissionsObj?.grouped || {};

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight">Roles & Permissions</h1>
                    <p className="text-gray-500 mt-1 md:mt-2 text-sm md:text-base">Manage system roles and access control</p>
                </div>
                <Button onClick={handleCreate} className="w-full sm:w-auto flex items-center gap-2">
                    <Plus className="h-4 w-4" /> Create Role
                </Button>
            </div>

            {/* Mobile Card View */}
            <div className="grid grid-cols-1 gap-4 md:hidden">
                {roles?.map((role) => (
                    <div key={role.id} className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm space-y-4">
                        <div className="flex justify-between items-start">
                            <div className="flex items-center gap-3">
                                <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                    <Shield className="h-5 w-5" />
                                </div>
                                <div className="space-y-0.5">
                                    <h3 className="font-semibold text-gray-900 capitalize leading-none">{role.name.replace('_', ' ')}</h3>
                                    <span className="text-[10px] text-gray-400 font-mono uppercase tracking-tighter">{role.guard_name} Guard</span>
                                </div>
                            </div>
                            <div className="flex items-center gap-1">
                                <Button variant="ghost" size="icon" className="h-9 w-9" onClick={() => handleEdit(role)}>
                                    <Edit className="h-4 w-4" />
                                </Button>
                                {role.name !== 'super_admin' && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-9 w-9 text-red-600"
                                        onClick={() => {
                                            if (confirm('Delete this role?')) deleteMutation.mutate(role.id);
                                        }}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        </div>

                        <div className="space-y-3">
                            <div>
                                <span className="text-[10px] text-gray-400 uppercase tracking-wider block mb-1">Description</span>
                                <p className="text-sm text-gray-600 line-clamp-2 leading-relaxed">
                                    {role.description || 'No description provided.'}
                                </p>
                            </div>
                            <div className="pt-3 border-t flex items-center justify-between">
                                <span className="text-xs font-medium text-gray-700">Capabilities</span>
                                <Badge variant="secondary" className="text-[10px] px-1.5 py-0 h-5 font-normal">
                                    {role.permissions.length} Permissions
                                </Badge>
                            </div>
                        </div>
                    </div>
                ))}
                {roles?.length === 0 && (
                    <div className="py-12 text-center text-gray-400 italic">No roles found.</div>
                )}
            </div>

            {/* Desktop Table View */}
            <div className="hidden md:block rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader className="bg-gray-50/50">
                        <TableRow>
                            <TableHead className="min-w-[150px]">Role Name</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Permissions</TableHead>
                            <TableHead className="hidden md:table-cell">Guard</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {roles?.map((role) => (
                            <TableRow key={role.id} className="hover:bg-gray-50/50 transition-colors">
                                <TableCell className="font-semibold capitalize whitespace-nowrap text-gray-900">{role.name.replace('_', ' ')}</TableCell>
                                <TableCell className="text-sm text-gray-500 max-w-[250px] truncate" title={role.description}>
                                    {role.description || '-'}
                                </TableCell>
                                <TableCell className="whitespace-nowrap">
                                    <Badge variant="secondary" className="text-[10px] font-normal uppercase px-1.5">{role.permissions.length} perms</Badge>
                                </TableCell>
                                <TableCell className="text-gray-400 hidden md:table-cell whitespace-nowrap text-xs uppercase font-mono tracking-tighter">{role.guard_name}</TableCell>
                                <TableCell className="text-right whitespace-nowrap pr-6">
                                    <div className="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" className="h-8 w-8 opacity-60 hover:opacity-100" onClick={() => handleEdit(role)}>
                                            <Edit className="h-4 w-4" />
                                        </Button>
                                        {role.name !== 'super_admin' && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-red-600 opacity-60 hover:opacity-100 hover:bg-red-50"
                                                onClick={() => {
                                                    if (confirm('Delete this role?')) deleteMutation.mutate(role.id);
                                                }}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        )}
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent className="max-w-4xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{selectedRole ? 'Edit Role' : 'Create New Role'}</DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6 mt-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Role Name</label>
                            <Input
                                {...register('name', { required: true })}
                                placeholder="e.g. moderator"
                                disabled={selectedRole && ['super_admin', 'admin'].includes(selectedRole.name)}
                            />
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Description</label>
                            <Input
                                {...register('description')}
                                placeholder="Describe the role's responsibilities..."
                            />
                        </div>

                        <div className="space-y-4">
                            <label className="text-sm font-medium">Permissions</label>
                            <div className="grid grid-cols-2 lg:grid-cols-3 gap-6">
                                {Object.entries(groupedPermissions).map(([group, perms]) => (
                                    <div key={group} className="border p-4 rounded-lg">
                                        <h3 className="font-semibold capitalize mb-3 text-sm">{group.replace('_', ' ')}</h3>
                                        <div className="space-y-2">
                                            {perms.map((perm) => (
                                                <div key={perm.id} className="flex items-center space-x-2">
                                                    <Checkbox
                                                        id={`perm-${perm.id}`}
                                                        value={perm.name}
                                                        {...register('permissions')}
                                                    />
                                                    <label
                                                        htmlFor={`perm-${perm.id}`}
                                                        className="text-sm leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                                    >
                                                        {perm.name.replace(/_/g, ' ')}
                                                    </label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>Cancel</Button>
                            <Button type="submit">{selectedRole ? 'Update Role' : 'Create Role'}</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default RolesPermissions;
