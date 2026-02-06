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
import { Plus, Edit, Trash2, MapPin } from 'lucide-react';
import { useForm, Controller } from 'react-hook-form';

const CitiesList = () => {
    const queryClient = useQueryClient();
    const [selectedCity, setSelectedCity] = useState(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const { register, handleSubmit, reset, setValue, control, formState: { errors } } = useForm();
    const [filterProvince, setFilterProvince] = useState('all');
    const [activeTab, setActiveTab] = useState("sd");

    // Fetch Provinces for dropdown
    const { data: provinces } = useQuery({
        queryKey: ['provinces'],
        queryFn: async () => {
            const response = await api.get('/api/admin/provinces');
            return response.data;
        }
    });

    const { data: cities, isLoading } = useQuery({
        queryKey: ['cities', filterProvince],
        queryFn: async () => {
            const params = filterProvince !== 'all' ? { province_id: filterProvince } : {};
            const response = await api.get('/api/admin/cities', { params });
            return response.data;
        }
    });

    const mutation = useMutation({
        mutationFn: async (data) => {
            if (selectedCity) {
                return api.put(`/api/admin/cities/${selectedCity.id}`, data);
            }
            return api.post('/api/admin/cities', data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['cities']);
            setIsDialogOpen(false);
            reset();
            setSelectedCity(null);
        }
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/api/admin/cities/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['cities']);
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to delete city');
        }
    });

    const onSubmit = (data) => {
        mutation.mutate(data);
    };

    const handleEdit = (city) => {
        setSelectedCity(city);
        setValue('province_id', city.province_id?.toString());
        setValue('geo_lat', city.geo_lat);
        setValue('geo_long', city.geo_long);

        // Populate Details
        const sdDetail = city.details.find(d => d.lang === 'sd');
        const enDetail = city.details.find(d => d.lang === 'en');

        setValue('details.sd.city_name', sdDetail?.city_name || '');
        setValue('details.en.city_name', enDetail?.city_name || '');

        setActiveTab("sd");
        setIsDialogOpen(true);
    };

    const handleCreate = () => {
        setSelectedCity(null);
        reset();
        if (filterProvince !== 'all') {
            setValue('province_id', filterProvince);
        }
        setActiveTab("sd");
        setIsDialogOpen(true);
    };

    const getDisplayName = (city) => {
        const sdName = city.details?.find(d => d.lang === 'sd')?.city_name;
        const enName = city.details?.find(d => d.lang === 'en')?.city_name;
        return sdName || enName || 'Unnamed City';
    };

    const getProvinceName = (province) => {
        if (!province) return '-';
        const sdName = province.details?.find(d => d.lang === 'sd')?.province_name;
        const enName = province.details?.find(d => d.lang === 'en')?.province_name;
        return sdName || enName || 'Unnamed Province';
    }

    if (isLoading) return <div>Loading...</div>;

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight">Cities</h1>
                    <p className="text-gray-500 mt-1 md:mt-2 text-sm md:text-base">Manage cities within provinces</p>
                </div>
                <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <Select value={filterProvince} onValueChange={setFilterProvince}>
                        <SelectTrigger className="w-full sm:w-[200px]">
                            <SelectValue placeholder="Filter by Province" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Provinces</SelectItem>
                            {provinces?.map(p => (
                                <SelectItem key={p.id} value={p.id.toString()}>{getProvinceName(p)}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Button onClick={handleCreate} className="w-full sm:w-auto flex items-center gap-2">
                        <Plus className="h-4 w-4" /> Add City
                    </Button>
                </div>
            </div>

            <div className="bg-white rounded-lg border overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="min-w-[150px]">City Name</TableHead>
                            <TableHead>Province</TableHead>
                            <TableHead className="hidden md:table-cell">Coordinates</TableHead>
                            <TableHead className="hidden sm:table-cell">Languages</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {cities?.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={5} className="text-center py-4 text-gray-500">No cities found.</TableCell>
                            </TableRow>
                        ) : cities?.map((city) => (
                            <TableRow key={city.id}>
                                <TableCell className="font-medium whitespace-nowrap">
                                    <div className="flex items-center gap-2">
                                        <MapPin className="h-4 w-4 text-gray-400" />
                                        {getDisplayName(city)}
                                    </div>
                                </TableCell>
                                <TableCell className="whitespace-nowrap">
                                    <Badge variant="secondary">{getProvinceName(city.province)}</Badge>
                                </TableCell>
                                <TableCell className="text-[10px] text-gray-400 hidden md:table-cell whitespace-nowrap">
                                    {city.geo_lat && city.geo_long ? `${city.geo_lat}, ${city.geo_long}` : '-'}
                                </TableCell>
                                <TableCell className="hidden sm:table-cell">
                                    <div className="flex gap-1">
                                        {city.details.some(d => d.lang === 'en') && <Badge variant="secondary" className="text-[10px]">EN</Badge>}
                                        {city.details.some(d => d.lang === 'sd') && <Badge variant="secondary" className="text-[10px]">SD</Badge>}
                                    </div>
                                </TableCell>
                                <TableCell className="text-right whitespace-nowrap">
                                    <div className="flex justify-end gap-2">
                                        <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleEdit(city)}>
                                            <Edit className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50"
                                            onClick={() => {
                                                if (confirm('Delete this city?')) deleteMutation.mutate(city.id);
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
                        <DialogTitle>{selectedCity ? 'Edit City' : 'Add New City'}</DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 mt-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Province</label>
                            <Controller
                                name="province_id"
                                control={control}
                                rules={{ required: 'Province is required' }}
                                render={({ field }) => (
                                    <Select onValueChange={field.onChange} value={field.value?.toString()}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Province" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {provinces?.map(p => (
                                                <SelectItem key={p.id} value={p.id.toString()}>{getProvinceName(p)}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                            {errors.province_id && <p className="text-red-500 text-xs">{errors.province_id.message}</p>}
                        </div>

                        <Tabs defaultValue="sd" value={activeTab} onValueChange={setActiveTab} className="w-full">
                            <TabsList className="grid w-full grid-cols-2">
                                <TabsTrigger value="sd">Sindhi (SD)</TabsTrigger>
                                <TabsTrigger value="en">English (EN)</TabsTrigger>
                            </TabsList>

                            <TabsContent value="sd" className="space-y-4 pt-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">City Name (Sindhi)</label>
                                    <Input
                                        {...register('details.sd.city_name', { required: 'Sindhi Name is required' })}
                                        placeholder="e.g. ڪراچي"
                                        className="text-right"
                                        dir="rtl"
                                    />
                                    {errors.details?.sd?.city_name && <p className="text-red-500 text-xs">{errors.details.sd.city_name.message}</p>}
                                </div>
                            </TabsContent>

                            <TabsContent value="en" className="space-y-4 pt-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">City Name (English)</label>
                                    <Input
                                        {...register('details.en.city_name')}
                                        placeholder="e.g. Karachi"
                                    />
                                </div>
                            </TabsContent>
                        </Tabs>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Latitude</label>
                                <Input
                                    {...register('geo_lat')}
                                    placeholder="Lat"
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Longitude</label>
                                <Input
                                    {...register('geo_long')}
                                    placeholder="Long"
                                />
                            </div>
                        </div>

                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={mutation.isPending}>
                                {mutation.isPending ? 'Saving...' : (selectedCity ? 'Update' : 'Create')}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default CitiesList;
