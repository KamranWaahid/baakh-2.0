import React, { useState, useEffect } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
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
import { Trash2, Plus, Send, Eye, EyeOff, Star, Info, Settings, User, Folder, Tag as TagIcon, Link as LinkIcon, AlignCenter } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import { Skeleton } from '@/components/ui/skeleton';

const poetrySchema = z.object({
    poetry_title: z.string().min(2, 'Title is required'),
    poetry_slug: z.string().min(2, 'Slug is required'),
    poet_id: z.string().min(1, 'Poet is required'),
    category_id: z.string().min(1, 'Category is required'),
    content_style: z.string().default('center'),
    visibility: z.boolean().default(true),
    is_featured: z.boolean().default(false),
    poetry_info: z.string().optional(),
    source: z.string().optional(),
    couplets: z.array(z.object({
        couplet_text: z.string().min(1, 'Couplet text is required'),
    })).min(1, 'At least one couplet is required'),
    poetry_tags: z.array(z.string()).optional(),
});

const CreatePoetry = () => {
    const navigate = useNavigate();
    const [searchTerm, setSearchTerm] = useState('');

    const { data: meta, isLoading: isMetaLoading } = useQuery({
        queryKey: ['poetry-meta'],
        queryFn: async () => {
            const response = await api.get('/api/admin/poetry/create');
            return response.data;
        }
    });

    const form = useForm({
        resolver: zodResolver(poetrySchema),
        defaultValues: {
            poetry_title: '',
            poetry_slug: '',
            poet_id: '',
            category_id: '',
            content_style: 'center',
            visibility: true,
            is_featured: false,
            poetry_info: '',
            source: '',
            couplets: [{ couplet_text: '' }],
            poetry_tags: [],
        },
    });

    const { fields, append, remove } = useFieldArray({
        control: form.control,
        name: "couplets",
    });

    // Auto-generate slug from title
    const title = form.watch('poetry_title');
    useEffect(() => {
        if (title) {
            const slug = title
                .toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            form.setValue('poetry_slug', slug);
        }
    }, [title, form]);

    const mutation = useMutation({
        mutationFn: async (data) => {
            return await api.post('/api/admin/poetry', data);
        },
        onSuccess: () => {
            navigate('/poetry');
        },
    });

    const onSubmit = (data) => {
        mutation.mutate(data);
    };

    if (isMetaLoading) {
        return <div className="p-8 space-y-4">
            <Skeleton className="h-10 w-1/3" />
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="md:col-span-2 space-y-4">
                    <Skeleton className="h-64 w-full" />
                    <Skeleton className="h-32 w-full" />
                </div>
                <div className="space-y-4">
                    <Skeleton className="h-48 w-full" />
                    <Skeleton className="h-48 w-full" />
                </div>
            </div>
        </div>;
    }

    return (
        <div className="pb-20">
            <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                    <div className="flex items-center justify-between mb-6">
                        <h2 className="text-3xl font-bold tracking-tight">Create New Poetry</h2>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" type="button" onClick={() => navigate('/poetry')}>Discard</Button>
                            <Button type="submit" disabled={mutation.isPending}>
                                <Send className="mr-2 h-4 w-4" /> {mutation.isPending ? 'Publishing...' : 'Publish'}
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Main Content Area */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Title Section */}
                            <Card className="border-none shadow-sm bg-white">
                                <CardContent className="pt-6">
                                    <FormField
                                        control={form.control}
                                        name="poetry_title"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormControl>
                                                    <input
                                                        className="w-full text-4xl font-bold border-none focus:outline-none placeholder:text-muted-foreground/30"
                                                        placeholder="Enter poetry title here"
                                                        {...field}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                            </Card>

                            {/* Couplets Editor Section */}
                            <Card className="shadow-sm">
                                <CardHeader className="border-b bg-muted/20 py-3">
                                    <div className="flex items-center justify-between">
                                        <CardTitle className="text-sm font-medium flex items-center gap-2">
                                            <AlignCenter className="h-4 w-4" /> Couplets
                                        </CardTitle>
                                        <div className="flex items-center gap-2">
                                            <Button type="button" variant="ghost" size="sm" onClick={() => append({ couplet_text: '' })}>
                                                <Plus className="h-4 w-4 mr-1" /> Add Couplet
                                            </Button>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="pt-6 space-y-4">
                                    {fields.map((field, index) => (
                                        <div key={field.id} className="flex gap-4 group">
                                            <div className="flex-1">
                                                <FormField
                                                    control={form.control}
                                                    name={`couplets.${index}.couplet_text`}
                                                    render={({ field }) => (
                                                        <FormItem>
                                                            <FormControl>
                                                                <textarea
                                                                    className="w-full min-h-[80px] p-4 text-lg border rounded-md focus:ring-1 focus:ring-primary focus:border-primary transition-all resize-none"
                                                                    placeholder={`Couplet ${index + 1}...`}
                                                                    {...field}
                                                                />
                                                            </FormControl>
                                                            <FormMessage />
                                                        </FormItem>
                                                    )}
                                                />
                                            </div>
                                            {fields.length > 1 && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="mt-4 opacity-0 group-hover:opacity-100 text-destructive hover:text-destructive hover:bg-destructive/10 transition-opacity"
                                                    onClick={() => remove(index)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                    ))}
                                </CardContent>
                                <CardFooter className="border-t bg-muted/5 py-3 justify-center">
                                    <Button type="button" variant="link" size="sm" onClick={() => append({ couplet_text: '' })}>
                                        <Plus className="h-4 w-4 mr-1" /> Add another couplet
                                    </Button>
                                </CardFooter>
                            </Card>

                            {/* Additional Information Section */}
                            <Card className="shadow-sm">
                                <CardHeader className="border-b py-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <Info className="h-4 w-4" /> Additional Information
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="pt-6 space-y-4">
                                    <FormField
                                        control={form.control}
                                        name="poetry_info"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>Background / Information</FormLabel>
                                                <FormControl>
                                                    <textarea
                                                        className="w-full min-h-[120px] p-3 border rounded-md focus:ring-1 focus:ring-primary focus:border-primary transition-all"
                                                        placeholder="Provide some background story or information about this poetry..."
                                                        {...field}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                    <FormField
                                        control={form.control}
                                        name="source"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>Source (Book, etc.)</FormLabel>
                                                <FormControl>
                                                    <Input placeholder="e.g. Shah Jo Risalo" {...field} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                            </Card>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Publish Status Card */}
                            <Card className="shadow-sm border-t-4 border-t-primary">
                                <CardHeader className="py-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <Settings className="h-4 w-4" /> Status & Visibility
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Eye className="h-4 w-4" /> Visibility
                                        </div>
                                        <FormField
                                            control={form.control}
                                            name="visibility"
                                            render={({ field }) => (
                                                <div className="flex items-center gap-2">
                                                    <span className="text-sm font-medium">{field.value ? 'Public' : 'Hidden'}</span>
                                                    <Checkbox
                                                        checked={field.value}
                                                        onCheckedChange={field.onChange}
                                                    />
                                                </div>
                                            )}
                                        />
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Star className="h-4 w-4" /> Feature Post
                                        </div>
                                        <FormField
                                            control={form.control}
                                            name="is_featured"
                                            render={({ field }) => (
                                                <Checkbox
                                                    checked={field.value}
                                                    onCheckedChange={field.onChange}
                                                />
                                            )}
                                        />
                                    </div>
                                    <div className="pt-2 border-t">
                                        <FormField
                                            control={form.control}
                                            name="content_style"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel className="text-xs uppercase text-muted-foreground font-bold">Content Alignment</FormLabel>
                                                    <Select onValueChange={field.onChange} defaultValue={field.value}>
                                                        <FormControl>
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Alignment" />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent>
                                                            {meta?.content_styles.map(style => (
                                                                <SelectItem key={style} value={style}>
                                                                    {style.charAt(0).toUpperCase() + style.slice(1)}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                </CardContent>
                                <CardFooter className="bg-muted/10 flex justify-between py-3">
                                    <Button variant="ghost" size="sm" type="button" className="text-destructive h-8 px-2" onClick={() => navigate('/poetry')}>
                                        Move to Trash
                                    </Button>
                                    <Button size="sm" type="submit" className="h-8 px-4" disabled={mutation.isPending}>
                                        {mutation.isPending ? 'Publishing...' : 'Publish'}
                                    </Button>
                                </CardFooter>
                            </Card>

                            {/* Metadata Card */}
                            <Card className="shadow-sm">
                                <CardHeader className="py-3">
                                    <CardTitle className="text-sm font-medium">Meta Data</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <FormField
                                        control={form.control}
                                        name="poet_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-xs uppercase text-muted-foreground font-bold flex items-center gap-1">
                                                    <User className="h-3 w-3" /> Poet
                                                </FormLabel>
                                                <Select onValueChange={field.onChange} defaultValue={field.value}>
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

                                    <FormField
                                        control={form.control}
                                        name="category_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-xs uppercase text-muted-foreground font-bold flex items-center gap-1">
                                                    <Folder className="h-3 w-3" /> Category
                                                </FormLabel>
                                                <Select onValueChange={field.onChange} defaultValue={field.value}>
                                                    <FormControl>
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select Category" />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent>
                                                        {meta?.categories.map(cat => (
                                                            <SelectItem key={cat.id} value={cat.id.toString()}>{cat.name}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="poetry_slug"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-xs uppercase text-muted-foreground font-bold flex items-center gap-1">
                                                    <LinkIcon className="h-3 w-3" /> URL Slug
                                                </FormLabel>
                                                <FormControl>
                                                    <Input {...field} className="h-8 text-xs font-mono" />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                            </Card>

                            {/* Tags Card */}
                            <Card className="shadow-sm">
                                <CardHeader className="py-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <TagIcon className="h-4 w-4" /> Tags
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex flex-wrap gap-2 mb-3">
                                        {form.watch('poetry_tags')?.map(tagId => {
                                            const tag = meta?.tags.find(t => t.id.toString() === tagId);
                                            return (
                                                <span key={tagId} className="bg-primary/10 text-primary text-[10px] px-2 py-0.5 rounded-full flex items-center gap-1">
                                                    {tag?.tag || tagId}
                                                    <Trash2 className="h-2 w-2 cursor-pointer" onClick={() => {
                                                        const current = form.getValues('poetry_tags');
                                                        form.setValue('poetry_tags', current.filter(id => id !== tagId));
                                                    }} />
                                                </span>
                                            );
                                        })}
                                    </div>
                                    <FormField
                                        control={form.control}
                                        name="poetry_tags"
                                        render={({ field }) => (
                                            <FormItem>
                                                <Select
                                                    onValueChange={(val) => {
                                                        const current = form.getValues('poetry_tags') || [];
                                                        if (!current.includes(val)) {
                                                            form.setValue('poetry_tags', [...current, val]);
                                                        }
                                                    }}
                                                >
                                                    <FormControl>
                                                        <SelectTrigger className="h-8 text-xs">
                                                            <SelectValue placeholder="Select Tags" />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent>
                                                        {meta?.tags.map(tag => (
                                                            <SelectItem key={tag.id} value={tag.id.toString()}>{tag.tag}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
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

export default CreatePoetry;
