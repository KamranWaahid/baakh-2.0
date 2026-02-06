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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Plus, Edit, Trash2, Globe } from 'lucide-react';
import { useForm, Controller } from 'react-hook-form';

const LanguagesList = () => {
    const queryClient = useQueryClient();
    const [selectedLanguage, setSelectedLanguage] = useState(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const { register, handleSubmit, reset, setValue, control, formState: { errors } } = useForm();

    const { data: languages, isLoading } = useQuery({
        queryKey: ['languages'],
        queryFn: async () => {
            const response = await api.get('/api/admin/languages');
            return response.data;
        }
    });

    const mutation = useMutation({
        mutationFn: async (data) => {
            if (selectedLanguage) {
                return api.put(`/api/admin/languages/${selectedLanguage.id}`, data);
            }
            return api.post('/api/admin/languages', data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['languages']);
            setIsDialogOpen(false);
            reset();
            setSelectedLanguage(null);
        }
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/api/admin/languages/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['languages']);
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to delete language');
        }
    });

    const onSubmit = (data) => {
        mutation.mutate(data);
    };

    const handleEdit = (language) => {
        setSelectedLanguage(language);
        setValue('lang_title', language.lang_title);
        setValue('lang_code', language.lang_code);
        setValue('lang_dir', language.lang_dir);
        setValue('lang_folder', language.lang_folder);
        setValue('is_default', !!language.is_default);
        setIsDialogOpen(true);
    };

    const handleCreate = () => {
        setSelectedLanguage(null);
        reset({
            lang_dir: 'ltr',
            is_default: false
        });
        setIsDialogOpen(true);
    };

    if (isLoading) return <div>Loading...</div>;

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight">Languages</h1>
                    <p className="text-gray-500 mt-1 md:mt-2 text-sm md:text-base">Manage system languages and localization settings</p>
                </div>
                <Button onClick={handleCreate} className="w-full sm:w-auto flex items-center gap-2">
                    <Plus className="h-4 w-4" /> Add Language
                </Button>
            </div>

            <div className="bg-white rounded-lg border overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="min-w-[150px]">Title</TableHead>
                            <TableHead>Code</TableHead>
                            <TableHead className="hidden md:table-cell">Direction</TableHead>
                            <TableHead className="hidden lg:table-cell">Folder</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {languages?.map((lang) => (
                            <TableRow key={lang.id}>
                                <TableCell className="font-medium whitespace-nowrap">
                                    <div className="flex items-center gap-2">
                                        <Globe className="h-4 w-4 text-gray-400" />
                                        {lang.lang_title}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline" className="uppercase">{lang.lang_code}</Badge>
                                </TableCell>
                                <TableCell className="uppercase hidden md:table-cell">{lang.lang_dir}</TableCell>
                                <TableCell className="hidden lg:table-cell">{lang.lang_folder || '-'}</TableCell>
                                <TableCell>
                                    {lang.is_default ? (
                                        <Badge variant="default" className="text-[10px]">Default</Badge>
                                    ) : null}
                                </TableCell>
                                <TableCell className="text-right whitespace-nowrap">
                                    <div className="flex justify-end gap-2">
                                        <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleEdit(lang)}>
                                            <Edit className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50"
                                            onClick={() => {
                                                if (confirm('Delete this language?')) deleteMutation.mutate(lang.id);
                                            }}
                                            disabled={deleteMutation.isPending || lang.is_default}
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
                        <DialogTitle>{selectedLanguage ? 'Edit Language' : 'Add New Language'}</DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 mt-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Language Title</label>
                            <Input
                                {...register('lang_title', { required: 'Title is required' })}
                                placeholder="e.g. English"
                            />
                            {errors.lang_title && <p className="text-red-500 text-xs">{errors.lang_title.message}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Code (ISO)</label>
                                <Input
                                    {...register('lang_code', { required: 'Code is required' })}
                                    placeholder="e.g. en"
                                />
                                {errors.lang_code && <p className="text-red-500 text-xs">{errors.lang_code.message}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Direction</label>
                                <Controller
                                    name="lang_dir"
                                    control={control}
                                    defaultValue="ltr"
                                    render={({ field }) => (
                                        <Select onValueChange={field.onChange} defaultValue={field.value} value={field.value}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select direction" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="ltr">LTR (Left to Right)</SelectItem>
                                                <SelectItem value="rtl">RTL (Right to Left)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Folder Name (Optional)</label>
                            <Input
                                {...register('lang_folder')}
                                placeholder="e.g. english"
                            />
                        </div>

                        <div className="flex items-center space-x-2 pt-2">
                            <Controller
                                name="is_default"
                                control={control}
                                render={({ field }) => (
                                    <Checkbox
                                        id="is_default"
                                        checked={field.value}
                                        onCheckedChange={field.onChange}
                                    />
                                )}
                            />
                            <label
                                htmlFor="is_default"
                                className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >
                                Set as default language
                            </label>
                        </div>

                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={mutation.isPending}>
                                {mutation.isPending ? 'Saving...' : (selectedLanguage ? 'Update' : 'Create')}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default LanguagesList;
