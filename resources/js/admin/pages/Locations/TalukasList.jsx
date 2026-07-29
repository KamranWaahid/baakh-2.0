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
import { Plus, Edit, Trash2, MapPin, Braces } from 'lucide-react';
import { useForm, Controller } from 'react-hook-form';
import LocationJsonImportModal from './LocationJsonImportModal';

const TalukasList = () => {
    const queryClient = useQueryClient();
    const [selected, setSelected] = useState(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [jsonOpen, setJsonOpen] = useState(false);
    const [filterDistrict, setFilterDistrict] = useState('all');
    const [activeTab, setActiveTab] = useState('sd');
    const { register, handleSubmit, reset, setValue, control, formState: { errors } } = useForm();

    const { data: districts } = useQuery({
        queryKey: ['districts'],
        queryFn: async () => (await api.get('/api/admin/districts')).data,
    });

    const { data: talukas, isLoading } = useQuery({
        queryKey: ['talukas', filterDistrict],
        queryFn: async () => {
            const params = filterDistrict !== 'all' ? { district_id: filterDistrict } : {};
            return (await api.get('/api/admin/talukas', { params })).data;
        },
    });

    const mutation = useMutation({
        mutationFn: async (data) => {
            if (selected) {
                return api.put(`/api/admin/talukas/${selected.id}`, data);
            }
            return api.post('/api/admin/talukas', data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['talukas'] });
            queryClient.invalidateQueries({ queryKey: ['districts'] });
            setIsDialogOpen(false);
            reset();
            setSelected(null);
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to save taluka');
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/talukas/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['talukas'] });
            queryClient.invalidateQueries({ queryKey: ['districts'] });
        },
        onError: (error) => alert(error.response?.data?.message || 'Failed to delete taluka'),
    });

    const handleEdit = (taluka) => {
        setSelected(taluka);
        setValue('district_id', taluka.district_id?.toString());
        setValue('details.sd.taluka_name', taluka.names?.sd || '');
        setValue('details.en.taluka_name', taluka.names?.en || '');
        setActiveTab('sd');
        setIsDialogOpen(true);
    };

    const handleCreate = () => {
        setSelected(null);
        reset();
        if (filterDistrict !== 'all') setValue('district_id', filterDistrict);
        setActiveTab('sd');
        setIsDialogOpen(true);
    };

    if (isLoading) return <div>Loading...</div>;

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight">Talukas</h1>
                    <p className="text-gray-500 mt-1 md:mt-2 text-sm md:text-base">Sindh talukas under districts</p>
                </div>
                <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <Select value={filterDistrict} onValueChange={setFilterDistrict}>
                        <SelectTrigger className="w-full sm:w-[220px]">
                            <SelectValue placeholder="Filter by District" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Districts</SelectItem>
                            {districts?.map((d) => (
                                <SelectItem key={d.id} value={d.id.toString()}>
                                    {d.names?.en || d.names?.sd || d.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button variant="outline" onClick={() => setJsonOpen(true)} className="flex items-center gap-2">
                        <Braces className="h-4 w-4" /> Import JSON
                    </Button>
                    <Button onClick={handleCreate} className="flex items-center gap-2">
                        <Plus className="h-4 w-4" /> Add Taluka
                    </Button>
                </div>
            </div>

            <LocationJsonImportModal open={jsonOpen} onOpenChange={setJsonOpen} type="talukas" />

            <div className="bg-white rounded-lg border overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Taluka</TableHead>
                            <TableHead>District</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {talukas?.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={3} className="text-center py-4 text-gray-500">
                                    No talukas yet. Import Sindh districts/talukas via JSON.
                                </TableCell>
                            </TableRow>
                        ) : talukas?.map((t) => (
                            <TableRow key={t.id}>
                                <TableCell>
                                    <div className="flex items-start gap-2">
                                        <MapPin className="h-4 w-4 text-gray-400 mt-0.5" />
                                        <div>
                                            {t.names?.en && <div>{t.names.en}</div>}
                                            {t.names?.sd && <div className="font-arabic" dir="rtl">{t.names.sd}</div>}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {t.district?.details?.find((x) => x.lang === 'en')?.district_name
                                        || t.district?.details?.[0]?.district_name
                                        || '-'}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-2">
                                        <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleEdit(t)}>
                                            <Edit className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-destructive"
                                            onClick={() => {
                                                if (confirm('Delete this taluka?')) deleteMutation.mutate(t.id);
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
                        <DialogTitle>{selected ? 'Edit Taluka' : 'Add Taluka'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit((data) => mutation.mutate(data))} className="space-y-4">
                        <Controller
                            name="district_id"
                            control={control}
                            rules={{ required: true }}
                            render={({ field }) => (
                                <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger><SelectValue placeholder="District" /></SelectTrigger>
                                    <SelectContent>
                                        {districts?.map((d) => (
                                            <SelectItem key={d.id} value={d.id.toString()}>
                                                {d.names?.en || d.names?.sd || d.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                        />
                        {errors.district_id && <p className="text-xs text-destructive">District is required</p>}

                        <Tabs value={activeTab} onValueChange={setActiveTab}>
                            <TabsList>
                                <TabsTrigger value="sd">SD</TabsTrigger>
                                <TabsTrigger value="en">EN</TabsTrigger>
                            </TabsList>
                            <TabsContent value="sd">
                                <Input {...register('details.sd.taluka_name', { required: true })} placeholder="تعلقه" className="font-arabic" dir="rtl" />
                            </TabsContent>
                            <TabsContent value="en">
                                <Input {...register('details.en.taluka_name')} placeholder="Taluka name" />
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

export default TalukasList;
