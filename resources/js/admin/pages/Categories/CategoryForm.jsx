import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ChevronLeft, Save, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Form,
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import api from '../../api/axios';

const formSchema = z.object({
    slug: z.string().min(1, 'Slug is required'),
    is_featured: z.boolean().default(false),
    gender: z.string().nullable().optional(),
    content_style: z.string().nullable().optional(),
    details: z.object({
        sd: z.object({
            cat_name: z.string().min(1, 'Sindhi name is required'),
        }),
        en: z.object({
            cat_name: z.string().optional(),
        }).optional(),
    }),
});

const CategoryForm = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const isEditing = !!id;

    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: {
            slug: '',
            is_featured: false,
            gender: 'none',
            content_style: 'grid',
            details: {
                sd: { cat_name: '' },
                en: { cat_name: '' },
            },
        },
    });

    const { data: category, isLoading } = useQuery({
        queryKey: ['category', id],
        queryFn: async () => {
            const response = await api.get(`/api/admin/categories/${id}`);
            return response.data;
        },
        enabled: isEditing,
    });

    useEffect(() => {
        if (category) {
            const sdDetail = category.details.find(d => d.lang === 'sd') || { cat_name: '' };
            const enDetail = category.details.find(d => d.lang === 'en') || { cat_name: '' };

            form.reset({
                slug: category.slug,
                is_featured: category.is_featured === 1 || category.is_featured === true,
                gender: category.gender || 'none',
                content_style: category.content_style || 'grid',
                details: {
                    sd: { cat_name: sdDetail.cat_name },
                    en: { cat_name: enDetail.cat_name },
                },
            });
        }
    }, [category, form]);

    const mutation = useMutation({
        mutationFn: async (values) => {
            if (isEditing) {
                return api.put(`/api/admin/categories/${id}`, values);
            }
            // For creation, we might need a "label" for auto-slug if slug is empty
            const payload = { ...values, label: values.details.sd.cat_name };
            return api.post('/api/admin/categories', payload);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['categories']);
            navigate('/admin/new/categories');
        },
    });

    const onSubmit = (values) => {
        mutation.mutate(values);
    };

    if (isEditing && isLoading) {
        return <div className="flex items-center justify-center h-64"><Loader2 className="h-8 w-8 animate-spin" /></div>;
    }

    return (
        <div className="max-w-4xl mx-auto pb-10 px-4 md:px-0">
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="icon" onClick={() => navigate('/categories')}>
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight">
                        {isEditing ? 'Edit Category' : 'Add New Category'}
                    </h2>
                </div>
            </div>

            <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                    <div className="grid gap-6 grid-cols-1 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-xl">Basic Information</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <FormField
                                    control={form.control}
                                    name="slug"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Slug</FormLabel>
                                            <FormControl>
                                                <Input placeholder="e.g. ghazal" {...field} />
                                            </FormControl>
                                            <FormDescription>Unique URL identifier.</FormDescription>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="gender"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Gender</FormLabel>
                                            <Select onValueChange={field.onChange} value={field.value || 'none'}>
                                                <FormControl>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select gender" />
                                                    </SelectTrigger>
                                                </FormControl>
                                                <SelectContent>
                                                    <SelectItem value="none">None/Any</SelectItem>
                                                    <SelectItem value="male">Male</SelectItem>
                                                    <SelectItem value="female">Female</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="is_featured"
                                    render={({ field }) => (
                                        <FormItem className="flex flex-row items-start space-x-3 space-y-0 rounded-md border p-4">
                                            <FormControl>
                                                <Checkbox
                                                    checked={field.value}
                                                    onCheckedChange={field.onChange}
                                                />
                                            </FormControl>
                                            <div className="space-y-1 leading-none">
                                                <FormLabel>Featured Category</FormLabel>
                                                <FormDescription>
                                                    Show this category in featured sections.
                                                </FormDescription>
                                            </div>
                                        </FormItem>
                                    )}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-xl">Translations</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <FormField
                                    control={form.control}
                                    name="details.sd.cat_name"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Name (Sindhi)</FormLabel>
                                            <FormControl>
                                                <Input dir="rtl" lang="sd" placeholder="غزل" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="details.en.cat_name"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Name (English)</FormLabel>
                                            <FormControl>
                                                <Input placeholder="Ghazal" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="flex flex-col-reverse sm:flex-row justify-end gap-3">
                        <Button variant="outline" type="button" className="w-full sm:w-auto" onClick={() => navigate('/categories')}>Cancel</Button>
                        <Button type="submit" className="w-full sm:w-auto" disabled={mutation.isPending}>
                            {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                            {isEditing ? 'Update Category' : 'Create Category'}
                        </Button>
                    </div>
                </form>
            </Form>
        </div>
    );
};

export default CategoryForm;
