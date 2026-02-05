import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Trash2, Plus, Eye, EyeOff, Star, Settings, User, Folder, Tag as TagIcon, Link as LinkIcon, AlignCenter, ChevronDown, BookOpen, Bold, Italic, Strikethrough, Code, AlignLeft, AlignRight, AlignJustify, Link2, Quote, Languages } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import { Skeleton } from '@/components/ui/skeleton';

const coupletSchema = z.object({
    poetry_title: z.string().min(2, 'Title is required'),
    poetry_slug: z.string().min(2, 'Slug is required'),
    poet_id: z.string().min(1, 'Poet is required'),
    content_style: z.string().default('center'),
    visibility: z.boolean().default(true),
    is_featured: z.boolean().default(false),
    poetry_tags: z.array(z.string()).optional(),
});

const CreateCouplet = () => {
    const { id } = useParams();
    const isEdit = !!id;
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [coupletContent, setCoupletContent] = useState('');
    const [showTransliteration, setShowTransliteration] = useState(false);
    const [transliteratedText, setTransliteratedText] = useState('');

    const { data: meta, isLoading: isMetaLoading } = useQuery({
        queryKey: ['poetry-meta'],
        queryFn: async () => {
            const response = await api.get('/api/admin/poetry/create');
            return response.data;
        }
    });

    const { data: poetry, isLoading: isPoetryLoading } = useQuery({
        queryKey: ['poetry', id],
        queryFn: async () => {
            const response = await api.get(`/api/admin/poetry/${id}`);
            return response.data;
        },
        enabled: isEdit,
    });

    const form = useForm({
        resolver: zodResolver(coupletSchema),
        defaultValues: {
            poetry_title: '',
            poetry_slug: '',
            poet_id: '',
            content_style: 'center',
            visibility: true,
            is_featured: false,
            poetry_tags: [],
        }
    });

    // Auto-generate slug from title (only for new)
    const title = form.watch('poetry_title');
    useEffect(() => {
        if (!isEdit && title) {
            const slug = title
                .toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            form.setValue('poetry_slug', slug);
        }
    }, [title, isEdit, form]);

    useEffect(() => {
        if (isEdit && poetry) {
            const translation = poetry.translations?.find(t => t.lang === 'sd') || poetry.translations?.[0];
            form.reset({
                poetry_title: translation?.title || '',
                poetry_slug: poetry.poetry_slug || '',
                poet_id: poetry.poet_id?.toString() || '',
                content_style: poetry.content_style || 'center',
                visibility: poetry.visibility === 1,
                is_featured: poetry.is_featured === 1,
                poetry_tags: JSON.parse(poetry.poetry_tags || '[]'),
            });
            setCoupletContent(poetry.couplets?.[0]?.couplet_text || '');
        }
    }, [isEdit, poetry, form]);

    const mutation = useMutation({
        mutationFn: async (data) => {
            const payload = {
                ...data,
                category_id: null, // Independent couplets have no category
                couplets: [
                    {
                        couplet_text: coupletContent.trim()
                    }
                ]
            };

            if (isEdit) {
                return await api.put(`/api/admin/poetry/${id}`, payload);
            }
            return await api.post('/api/admin/poetry', payload);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['couplets', 'poetry']);
            navigate('/couplets');
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to save couplet');
        }
    });

    const onSubmit = (data) => {
        const lines = coupletContent.split('\n').filter(line => line.trim() !== '');
        if (lines.length !== 2) {
            alert('Couplet must contain exactly 2 lines');
            return;
        }
        mutation.mutate(data);
    };

    const lineCount = coupletContent.split('\n').filter(line => line.trim() !== '').length;

    if (isMetaLoading || (isEdit && isPoetryLoading)) {
        return <div className="p-8 space-y-4">
            <Skeleton className="h-10 w-1/3" />
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-2 space-y-4">
                    <Skeleton className="h-64 w-full" />
                </div>
                <div className="space-y-4">
                    <Skeleton className="h-48 w-full" />
                </div>
            </div>
        </div>;
    }

    return (
        <div className="pb-20">
            <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                    <div className="flex items-center justify-between mb-8 border-b pb-4">
                        <div className="flex items-center gap-4">
                            <h2 className="text-xl font-semibold tracking-tight">
                                {isEdit ? 'Edit Couplet' : 'Create New Couplet'}
                            </h2>
                        </div>
                        <div className="flex items-center gap-4">
                            <Button variant="ghost" type="button" onClick={() => navigate('/couplets')}>Cancel</Button>
                            <Button type="submit" disabled={mutation.isPending || lineCount !== 2} className="bg-primary hover:bg-primary/90 text-primary-foreground font-medium px-8">
                                {mutation.isPending ? 'Saving...' : (isEdit ? 'Update' : 'Publish')}
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div className="lg:col-span-2 space-y-0 bg-white rounded-xl shadow-sm border overflow-hidden min-h-[500px]">
                            <div className="p-6 md:p-10 space-y-4 max-w-4xl mx-auto w-full">
                                <div className="flex items-center justify-between mb-6">
                                    <div className="flex items-center gap-2 text-xs text-muted-foreground/50 font-medium">
                                        <BookOpen className="h-3 w-3" /> <span>Independent Couplet Editor</span>
                                    </div>
                                    <div className="flex items-center gap-3 text-xs text-muted-foreground/50 font-medium">
                                        <span>{lineCount.toString().padStart(2, '0')} / 02 Lines</span>
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <FormField
                                        control={form.control}
                                        name="poetry_title"
                                        render={({ field }) => (
                                            <FormItem className="space-y-0">
                                                <FormControl>
                                                    <textarea
                                                        dir="rtl"
                                                        className="w-full text-4xl font-bold border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none min-h-[60px] leading-tight bg-transparent text-right font-arabic"
                                                        placeholder="عنوان"
                                                        {...field}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </div>

                                <div className="pt-6">
                                    <textarea
                                        dir="rtl"
                                        className={`w-full p-0 text-3xl border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none min-h-[300px] bg-transparent leading-relaxed font-arabic text-center`}
                                        placeholder="پنهنجو شعر هتي لکو... صرف 2 لائينون"
                                        value={coupletContent}
                                        onChange={(e) => {
                                            const lines = e.target.value.split('\n');
                                            if (lines.length <= 2) {
                                                setCoupletContent(e.target.value);
                                            }
                                        }}
                                    />
                                    {lineCount !== 2 && lineCount > 0 && (
                                        <p className="text-sm text-muted-foreground mt-4 text-center">
                                            Please write exactly 2 lines for the couplet.
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="space-y-6">
                            <Card className="shadow-sm">
                                <CardHeader className="py-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <Settings className="h-4 w-4" /> Status
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <FormField
                                        control={form.control}
                                        name="visibility"
                                        render={({ field }) => (
                                            <FormItem className="flex items-center justify-between">
                                                <FormLabel className="text-xs uppercase text-muted-foreground/50 font-bold">Visibility</FormLabel>
                                                <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                                            </FormItem>
                                        )}
                                    />
                                    <FormField
                                        control={form.control}
                                        name="is_featured"
                                        render={({ field }) => (
                                            <FormItem className="flex items-center justify-between">
                                                <FormLabel className="text-xs uppercase text-muted-foreground/50 font-bold">Featured</FormLabel>
                                                <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                                <CardFooter className="py-3 border-t">
                                    <Button size="sm" className="w-full" disabled={mutation.isPending || lineCount !== 2}>
                                        {mutation.isPending ? 'Saving...' : (isEdit ? 'Update Couplet' : 'Publish Couplet')}
                                    </Button>
                                </CardFooter>
                            </Card>

                            <Card className="shadow-sm">
                                <CardHeader className="py-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <User className="h-4 w-4" /> Poet
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <FormField
                                        control={form.control}
                                        name="poet_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <Select onValueChange={field.onChange} value={field.value}>
                                                    <FormControl>
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select Poet" />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent>
                                                        {meta?.poets.map(poet => (
                                                            <SelectItem key={poet.id} value={poet.id.toString()}>{poet.name}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                            </Card>

                            <Card className="shadow-sm">
                                <CardHeader className="py-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <LinkIcon className="h-4 w-4" /> URL Slug
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <FormField
                                        control={form.control}
                                        name="poetry_slug"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormControl>
                                                    <Input {...field} className="h-8 text-xs font-mono" />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </form>
            </Form>
        </div>
    );
};

export default CreateCouplet;
