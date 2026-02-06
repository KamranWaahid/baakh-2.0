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
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Plus, Edit, Trash2, MapPin } from 'lucide-react';
import { useForm } from 'react-hook-form';

const CountriesList = () => {
    const queryClient = useQueryClient();
    const [selectedCountry, setSelectedCountry] = useState(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const { register, handleSubmit, reset, setValue, formState: { errors } } = useForm();
    const [activeTab, setActiveTab] = useState("sd");

    const { data: countries, isLoading } = useQuery({
        queryKey: ['countries'],
        queryFn: async () => {
            const response = await api.get('/api/admin/countries');
            return response.data;
        }
    });

    const mutation = useMutation({
        mutationFn: async (data) => {
            if (selectedCountry) {
                return api.put(`/api/admin/countries/${selectedCountry.id}`, data);
            }
            return api.post('/api/admin/countries', data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['countries']);
            setIsDialogOpen(false);
            reset();
            setSelectedCountry(null);
        }
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/api/admin/countries/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['countries']);
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to delete country');
        }
    });

    const onSubmit = (data) => {
        mutation.mutate(data);
    };

    const handleEdit = (country) => {
        setSelectedCountry(country);
        setValue('Abbreviation', country.Abbreviation);
        setValue('Continent', country.Continent);

        // Populate Details
        const sdDetail = country.details?.find(d => d.lang === 'sd');
        const enDetail = country.details?.find(d => d.lang === 'en');

        setValue('details.sd.countryName', sdDetail?.countryName || '');
        setValue('details.sd.countryDesc', sdDetail?.countryDesc || '');
        setValue('details.en.countryName', enDetail?.countryName || '');
        setValue('details.en.countryDesc', enDetail?.countryDesc || '');

        setActiveTab("sd");
        setIsDialogOpen(true);
    };

    const handleCreate = () => {
        setSelectedCountry(null);
        reset();
        setActiveTab("sd");
        setIsDialogOpen(true);
    };

    if (isLoading) return <div>Loading...</div>;

    const getDisplayName = (country) => {
        const sdName = country.details?.find(d => d.lang === 'sd')?.countryName;
        const enName = country.details?.find(d => d.lang === 'en')?.countryName;
        return sdName || enName || 'Unnamed Country';
    };

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight">Countries</h1>
                    <p className="text-gray-500 mt-1 md:mt-2 text-sm md:text-base">Manage supported countries</p>
                </div>
                <Button onClick={handleCreate} className="w-full sm:w-auto flex items-center gap-2">
                    <Plus className="h-4 w-4" /> Add Country
                </Button>
            </div>

            <div className="bg-white rounded-lg border overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="min-w-[150px]">Name</TableHead>
                            <TableHead>Abbreviation</TableHead>
                            <TableHead className="hidden md:table-cell">Continent</TableHead>
                            <TableHead className="hidden sm:table-cell">Languages</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {countries?.map((country) => (
                            <TableRow key={country.id}>
                                <TableCell className="font-medium whitespace-nowrap">
                                    <div className="flex items-center gap-2">
                                        <MapPin className="h-4 w-4 text-gray-400" />
                                        {getDisplayName(country)}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">{country.Abbreviation}</Badge>
                                </TableCell>
                                <TableCell className="hidden md:table-cell whitespace-nowrap">{country.Continent}</TableCell>
                                <TableCell className="hidden sm:table-cell">
                                    <div className="flex gap-1">
                                        {country.details?.some(d => d.lang === 'en') && <Badge variant="secondary" className="text-[10px]">EN</Badge>}
                                        {country.details?.some(d => d.lang === 'sd') && <Badge variant="secondary" className="text-[10px]">SD</Badge>}
                                    </div>
                                </TableCell>
                                <TableCell className="text-right whitespace-nowrap">
                                    <div className="flex justify-end gap-2">
                                        <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleEdit(country)}>
                                            <Edit className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50"
                                            onClick={() => {
                                                if (confirm('Delete this country?')) deleteMutation.mutate(country.id);
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
                        <DialogTitle>{selectedCountry ? 'Edit Country' : 'Add New Country'}</DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 mt-4">
                        <Tabs defaultValue="sd" value={activeTab} onValueChange={setActiveTab} className="w-full">
                            <TabsList className="grid w-full grid-cols-2">
                                <TabsTrigger value="sd">Sindhi (SD)</TabsTrigger>
                                <TabsTrigger value="en">English (EN)</TabsTrigger>
                            </TabsList>

                            <TabsContent value="sd" className="space-y-4 pt-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Country Name (Sindhi)</label>
                                    <Input
                                        {...register('details.sd.countryName', { required: 'Sindhi Name is required' })}
                                        placeholder="e.g. پاڪستان"
                                        className="text-right"
                                        dir="rtl"
                                    />
                                    {errors.details?.sd?.countryName && <p className="text-red-500 text-xs">{errors.details.sd.countryName.message}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Description (Sindhi)</label>
                                    <Input
                                        {...register('details.sd.countryDesc')}
                                        placeholder="Description"
                                        className="text-right"
                                        dir="rtl"
                                    />
                                </div>
                            </TabsContent>

                            <TabsContent value="en" className="space-y-4 pt-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Country Name (English)</label>
                                    <Input
                                        {...register('details.en.countryName')}
                                        placeholder="e.g. Pakistan"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Description (English)</label>
                                    <Input
                                        {...register('details.en.countryDesc')}
                                        placeholder="Description"
                                    />
                                </div>
                            </TabsContent>
                        </Tabs>

                        <div className="grid grid-cols-2 gap-4 border-t pt-4">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Abbreviation</label>
                                <Input
                                    {...register('Abbreviation')}
                                    placeholder="e.g. PK"
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Continent</label>
                                <Input
                                    {...register('Continent')}
                                    placeholder="e.g. Asia"
                                />
                            </div>
                        </div>

                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={mutation.isPending}>
                                {mutation.isPending ? 'Saving...' : (selectedCountry ? 'Update' : 'Create')}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default CountriesList;
