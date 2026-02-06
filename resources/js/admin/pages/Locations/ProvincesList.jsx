import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
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
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Plus, Edit, Trash2, Map } from 'lucide-react';
import { useForm, Controller } from 'react-hook-form';

const ProvincesList = () => {
    const queryClient = useQueryClient();
    const [selectedProvince, setSelectedProvince] = useState(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const { register, handleSubmit, reset, setValue, control, formState: { errors } } = useForm();
    const [filterCountry, setFilterCountry] = useState('all');
    const [activeTab, setActiveTab] = useState("sd");

    // Fetch Countries for dropdown
    const { data: countries } = useQuery({
        queryKey: ['countries'],
        queryFn: async () => {
            const response = await api.get('/api/admin/countries');
            return response.data;
        }
    });

    const { data: provinces, isLoading } = useQuery({
        queryKey: ['provinces', filterCountry],
        queryFn: async () => {
            const params = filterCountry !== 'all' ? { country_id: filterCountry } : {};
            const response = await api.get('/api/admin/provinces', { params });
            return response.data;
        }
    });

    const mutation = useMutation({
        mutationFn: async (data) => {
            if (selectedProvince) {
                return api.put(`/api/admin/provinces/${selectedProvince.id}`, data);
            }
            return api.post('/api/admin/provinces', data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['provinces']);
            setIsDialogOpen(false);
            reset();
            setSelectedProvince(null);
        }
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/api/admin/provinces/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['provinces']);
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to delete province');
        }
    });

    const onSubmit = (data) => {
        mutation.mutate(data);
    };

    const handleEdit = (province) => {
        setSelectedProvince(province);
        setValue('country_id', province.country_id?.toString());

        // Populate Details
        const sdDetail = province.details.find(d => d.lang === 'sd');
        const enDetail = province.details.find(d => d.lang === 'en');

        setValue('details.sd.province_name', sdDetail?.province_name || '');
        setValue('details.en.province_name', enDetail?.province_name || '');

        setActiveTab("sd");
        setIsDialogOpen(true);
    };

    const handleCreate = () => {
        setSelectedProvince(null);
        reset();
        if (filterCountry !== 'all') {
            setValue('country_id', filterCountry);
        }
        setActiveTab("sd");
        setIsDialogOpen(true);
    };

    const getDisplayName = (province) => {
        const sdName = province.details?.find(d => d.lang === 'sd')?.province_name;
        const enName = province.details?.find(d => d.lang === 'en')?.province_name;
        return sdName || enName || 'Unnamed Province';
    };

    const getCountryName = (country) => {
        if (!country) return '-';
        const sdName = country.details?.find(d => d.lang === 'sd')?.countryName;
        const enName = country.details?.find(d => d.lang === 'en')?.countryName;
        return sdName || enName || 'Unnamed Country';
    }

    if (isLoading) return <div>Loading...</div>;

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight">Provinces / States</h1>
                    <p className="text-gray-500 mt-1 md:mt-2 text-sm md:text-base">Manage provinces for each country</p>
                </div>
                <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <Select value={filterCountry} onValueChange={setFilterCountry}>
                        <SelectTrigger className="w-full sm:w-[180px]">
                            <SelectValue placeholder="Filter by Country" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Countries</SelectItem>
                            {countries?.map(c => (
                                <SelectItem key={c.id} value={c.id.toString()}>{getCountryName(c)}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Button onClick={handleCreate} className="w-full sm:w-auto flex items-center gap-2">
                        <Plus className="h-4 w-4" /> Add Province
                    </Button>
                </div>
            </div>

            <div className="bg-white rounded-lg border overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="min-w-[150px]">Province Name</TableHead>
                            <TableHead>Country</TableHead>
                            <TableHead className="hidden sm:table-cell">Languages</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {provinces?.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={4} className="text-center py-4 text-gray-500">No provinces found.</TableCell>
                            </TableRow>
                        ) : provinces?.map((province) => (
                            <TableRow key={province.id}>
                                <TableCell className="font-medium whitespace-nowrap">
                                    <div className="flex items-center gap-2">
                                        <Map className="h-4 w-4 text-gray-400" />
                                        {getDisplayName(province)}
                                    </div>
                                </TableCell>
                                <TableCell className="whitespace-nowrap">
                                    <Badge variant="secondary">{getCountryName(province.country)}</Badge>
                                </TableCell>
                                <TableCell className="hidden sm:table-cell">
                                    <div className="flex gap-1">
                                        {province.details.some(d => d.lang === 'en') && <Badge variant="secondary" className="text-[10px]">EN</Badge>}
                                        {province.details.some(d => d.lang === 'sd') && <Badge variant="secondary" className="text-[10px]">SD</Badge>}
                                    </div>
                                </TableCell>
                                <TableCell className="text-right whitespace-nowrap">
                                    <div className="flex justify-end gap-2">
                                        <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleEdit(province)}>
                                            <Edit className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50"
                                            onClick={() => {
                                                if (confirm('Delete this province?')) deleteMutation.mutate(province.id);
                                            }}
                                            disabled={deleteMutation.isPending}
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
                        <DialogTitle>{selectedProvince ? 'Edit Province' : 'Add New Province'}</DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 mt-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Country</label>
                            <Controller
                                name="country_id"
                                control={control}
                                rules={{ required: 'Country is required' }}
                                render={({ field }) => (
                                    <Select onValueChange={field.onChange} value={field.value?.toString()}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Country" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {countries?.map(c => (
                                                <SelectItem key={c.id} value={c.id.toString()}>{getCountryName(c)}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                            {errors.country_id && <p className="text-red-500 text-xs">{errors.country_id.message}</p>}
                        </div>

                        <Tabs defaultValue="sd" value={activeTab} onValueChange={setActiveTab} className="w-full">
                            <TabsList className="grid w-full grid-cols-2">
                                <TabsTrigger value="sd">Sindhi (SD)</TabsTrigger>
                                <TabsTrigger value="en">English (EN)</TabsTrigger>
                            </TabsList>

                            <TabsContent value="sd" className="space-y-4 pt-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Province Name (Sindhi)</label>
                                    <Input
                                        {...register('details.sd.province_name', { required: 'Sindhi Name is required' })}
                                        placeholder="e.g. سنڌ"
                                        className="text-right"
                                        dir="rtl"
                                    />
                                    {errors.details?.sd?.province_name && <p className="text-red-500 text-xs">{errors.details.sd.province_name.message}</p>}
                                </div>
                            </TabsContent>

                            <TabsContent value="en" className="space-y-4 pt-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Province Name (English)</label>
                                    <Input
                                        {...register('details.en.province_name')}
                                        placeholder="e.g. Sindh"
                                    />
                                </div>
                            </TabsContent>
                        </Tabs>

                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={mutation.isPending}>
                                {mutation.isPending ? 'Saving...' : (selectedProvince ? 'Update' : 'Create')}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default ProvincesList;
