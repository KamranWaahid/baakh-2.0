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
        setValue('permissions', role.permissions.map(p => p.name));
        setIsDialogOpen(true);
    };

    const handleCreate = () => {
        setSelectedRole(null);
        reset();
        setIsDialogOpen(true);
    };

    if (rolesLoading || permissionsLoading) return <div>Loading...</div>;

    const groupedPermissions = permissionsObj?.grouped || {};

    return (
        <div className="p-8 space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Roles & Permissions</h1>
                    <p className="text-gray-500 mt-2">Manage system roles and access control</p>
                </div>
                <Button onClick={handleCreate} className="flex items-center gap-2">
                    <Plus className="h-4 w-4" /> Create Role
                </Button>
            </div>

            <div className="bg-white rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Role Name</TableHead>
                            <TableHead>Permissions Count</TableHead>
                            <TableHead>Guard</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {roles?.map((role) => (
                            <TableRow key={role.id}>
                                <TableCell className="font-medium capitalize">{role.name.replace('_', ' ')}</TableCell>
                                <TableCell>
                                    <Badge variant="secondary">{role.permissions.length} permissions</Badge>
                                </TableCell>
                                <TableCell className="text-muted-foreground">{role.guard_name}</TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-2">
                                        <Button variant="ghost" size="icon" onClick={() => handleEdit(role)}>
                                            <Edit className="h-4 w-4" />
                                        </Button>
                                        {!['super_admin', 'admin', 'editor', 'contributor', 'viewer'].includes(role.name) && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-red-600 hover:text-red-700 hover:bg-red-50"
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
