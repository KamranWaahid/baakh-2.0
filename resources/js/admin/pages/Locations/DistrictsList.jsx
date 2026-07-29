import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Tabs, TabsContent, TabsList, TabsTrigger,
} from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Plus, Edit, Trash2, Map, Braces } from 'lucide-react';
import { useForm, Controller } from 'react-hook-form';
import LocationJsonImportModal from './LocationJsonImportModal';

const DistrictsList = () => {
    const queryClient = useQueryClient();
    const [selected, setSelected] = useState(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [jsonOpen, setJsonOpen] = useState(false);
    const [filterProvince, setFilterProvince] = useState('all');
    const [activeTab, setActiveTab] = useState('sd');
    const { register, handleSubmit, reset, setValue, control, formState: { errors } } = useForm();

    const { data: provinces } = useQuery({
        queryKey: ['provinces'],
        queryFn: async () => (await api.get('/api/admin/provinces')).data,
    });

    const { data: districts, isLoading } = useQuery({
        queryKey: ['districts', filterProvince],
        queryFn: async () => {
            const params = filterProvince !== 'all' ? { province_id: filterProvince } : {};
            return (await api.get('/api/admin/districts', { params })).data;
        },
    });

    const mutation = useMutation({
        mutationFn: async (data) => {
            if (selected) {
                return api.put(`/api/admin/districts/${selected.id}`, data);
            }
            return api.post('/api/admin/districts', data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['districts'] });
            setIsDialogOpen(false);
            reset();
            setSelected(null);
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to save district');
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/districts/${id}`),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['districts'] }),
        onError: (error) => alert(error.response?.data?.message || 'Failed to delete district'),
    });

    const handleEdit = (district) => {
        setSelected(district);
        setValue('province_id', district.province_id?.toString());
        setValue('details.sd.district_name', district.names?.sd || '');
        setValue('details.en.district_name', district.names?.en || '');
        setActiveTab('sd');
        setIsDialogOpen(true);
    };

    const handleCreate = () => {
        setSelected(null);
        reset();
        if (filterProvince !== 'all') setValue('province_id', filterProvince);
        setActiveTab('sd');
        setIsDialogOpen(true);
    };

    if (isLoading) return <div>Loading...</div>;

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight">Districts</h1>
                    <p className="text-gray-500 mt-1 md:mt-2 text-sm md:text-base">Sindh districts (province → district → taluka)</p>
                </div>
                <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <Select value={filterProvince} onValueChange={setFilterProvince}>
                        <SelectTrigger className="w-full sm:w-[200px]">
                            <SelectValue placeholder="Filter by Province" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Provinces</SelectItem>
                            {provinces?.map((p) => (
                                <SelectItem key={p.id} value={p.id.toString()}>{p.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button variant="outline" onClick={() => setJsonOpen(true)} className="flex items-center gap-2">
                        <Braces className="h-4 w-4" /> Import JSON
                    </Button>
                    <Button onClick={handleCreate} className="flex items-center gap-2">
                        <Plus className="h-4 w-4" /> Add District
                    </Button>
                </div>
            </div>

            <LocationJsonImportModal open={jsonOpen} onOpenChange={setJsonOpen} type="districts" />

            <div className="bg-white rounded-lg border overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>District</TableHead>
                            <TableHead>Province</TableHead>
                            <TableHead className="hidden sm:table-cell">Talukas</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {districts?.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={4} className="text-center py-4 text-gray-500">
                                    No districts yet. Use Import JSON for Sindh districts.
                                </TableCell>
                            </TableRow>
                        ) : districts?.map((d) => (
                            <TableRow key={d.id}>
                                <TableCell>
                                    <div className="flex items-start gap-2">
                                        <Map className="h-4 w-4 text-gray-400 mt-0.5" />
                                        <div>
                                            {d.names?.en && <div>{d.names.en}</div>}
                                            {d.names?.sd && <div className="font-arabic" dir="rtl">{d.names.sd}</div>}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>{d.province?.details?.find((x) => x.lang === 'en')?.province_name || d.province?.details?.[0]?.province_name || '-'}</TableCell>
                                <TableCell className="hidden sm:table-cell">
                                    <Badge variant="secondary">{d.talukas_count ?? d.talukas?.length ?? 0}</Badge>
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-2">
                                        <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleEdit(d)}>
                                            <Edit className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-destructive"
                                            onClick={() => {
                                                if (confirm('Delete this district?')) deleteMutation.mutate(d.id);
                                            }}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{selected ? 'Edit District' : 'Add District'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit((data) => mutation.mutate(data))} className="space-y-4">
                        <Controller
                            name="province_id"
                            control={control}
                            rules={{ required: true }}
                            render={({ field }) => (
                                <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger><SelectValue placeholder="Province" /></SelectTrigger>
                                    <SelectContent>
                                        {provinces?.map((p) => (
                                            <SelectItem key={p.id} value={p.id.toString()}>{p.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                        />
                        {errors.province_id && <p className="text-xs text-destructive">Province is required</p>}

                        <Tabs value={activeTab} onValueChange={setActiveTab}>
                            <TabsList>
                                <TabsTrigger value="sd">SD</TabsTrigger>
                                <TabsTrigger value="en">EN</TabsTrigger>
                            </TabsList>
                            <TabsContent value="sd" className="space-y-2">
                                <Input {...register('details.sd.district_name', { required: true })} placeholder="ضلعو" className="font-arabic" dir="rtl" />
                            </TabsContent>
                            <TabsContent value="en" className="space-y-2">
                                <Input {...register('details.en.district_name')} placeholder="District name" />
                            </TabsContent>
                        </Tabs>

                        <Button type="submit" disabled={mutation.isPending} className="w-full">
                            {mutation.isPending ? 'Saving...' : 'Save'}
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default DistrictsList;
