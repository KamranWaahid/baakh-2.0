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
    type: z.string().min(1, 'Type is required'),
    topic_category_id: z.string().nullable().optional(),
    details: z.object({
        sd: z.object({
            name: z.string().min(1, 'Sindhi name is required'),
        }),
        en: z.object({
            name: z.string().optional(),
        }).optional(),
    }),
});

const TagForm = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const isEditing = !!id;

    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: {
            slug: '',
            type: '',
            topic_category_id: '',
            details: {
                sd: { name: '' },
                en: { name: '' },
            },
        },
    });

    // Fetch meta data (types and categories)
    const { data: meta } = useQuery({
        queryKey: ['tags-meta'],
        queryFn: async () => {
            const response = await api.get('/api/admin/tags');
            return response.data;
        }
    });

    const { data: tag, isLoading } = useQuery({
        queryKey: ['tag', id],
        queryFn: async () => {
            const response = await api.get(`/api/admin/tags/${id}`);
            return response.data;
        },
        enabled: isEditing,
    });

    useEffect(() => {
        if (tag) {
            form.reset({
                slug: tag.slug || '',
                type: tag.type || '',
                topic_category_id: tag.topic_category_id ? String(tag.topic_category_id) : '',
                details: {
                    sd: { name: tag.details?.sd?.name || '' },
                    en: { name: tag.details?.en?.name || '' },
                },
            });
        }
    }, [tag, form]);

    const mutation = useMutation({
        mutationFn: async (values) => {
            // Clean up topic_category_id if it's empty string
            const payload = {
                ...values,
                topic_category_id: values.topic_category_id === "none" || values.topic_category_id === "" ? null : values.topic_category_id
            };

            if (isEditing) {
                return api.put(`/api/admin/tags/${id}`, payload);
            }
            return api.post('/api/admin/tags', payload);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['tags']);
            alert(isEditing ? 'Tag updated successfully!' : 'Tag created successfully!');
            navigate('/admin/tags');
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to save tag');
        }
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
                    <Button variant="outline" size="icon" onClick={() => navigate('/admin/tags')}>
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight">
                        {isEditing ? 'Edit Tag' : 'Add New Tag'}
                    </h2>
                </div>
            </div>

            <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                    <div className="grid gap-6 grid-cols-1 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-xl">Tag Information</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <FormField
                                    control={form.control}
                                    name="slug"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Slug</FormLabel>
                                            <FormControl>
                                                <Input placeholder="e.g. love-poetry" {...field} />
                                            </FormControl>
                                            <FormDescription>Unique identifier for the tag.</FormDescription>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="type"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Type</FormLabel>
                                            <Select onValueChange={field.onChange} value={field.value}>
                                                <FormControl>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select type" />
                                                    </SelectTrigger>
                                                </FormControl>
                                                <SelectContent>
                                                    {meta?.available_types?.map(type => (
                                                        <SelectItem key={type.value} value={type.value}>{type.label}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="topic_category_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Topic Category</FormLabel>
                                            <Select onValueChange={field.onChange} value={field.value || "none"}>
                                                <FormControl>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select category (Optional)" />
                                                    </SelectTrigger>
                                                </FormControl>
                                                <SelectContent>
                                                    <SelectItem value="none">None</SelectItem>
                                                    {meta?.topic_categories?.map(cat => (
                                                        <SelectItem key={cat.id} value={String(cat.id)}>{cat.name}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <FormMessage />
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
                                    name="details.sd.name"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Name (Sindhi)</FormLabel>
                                            <FormControl>
                                                <Input dir="rtl" lang="sd" className="font-arabic" placeholder="محبت..." {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="details.en.name"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Name (English)</FormLabel>
                                            <FormControl>
                                                <Input placeholder="Love" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="flex justify-end gap-3">
                        <Button variant="outline" type="button" onClick={() => navigate('/admin/tags')}>Cancel</Button>
                        <Button type="submit" disabled={mutation.isPending}>
                            {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                            {isEditing ? 'Update Tag' : 'Create Tag'}
                        </Button>
                    </div>
                </form>
            </Form>
        </div>
    );
};

export default TagForm;
