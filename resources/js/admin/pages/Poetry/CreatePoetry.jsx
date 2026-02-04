import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
    DropdownMenuLabel,
} from '@/components/ui/dropdown-menu';
import { Trash2, Plus, Send, Eye, EyeOff, Star, Info, Settings, User, Folder, Tag as TagIcon, Link as LinkIcon, AlignCenter, ChevronDown, BookOpen, Bold, Italic, Strikethrough, Code, AlignLeft, AlignRight, AlignJustify, Link2, Quote, Languages } from 'lucide-react';
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
    poetry_tags: z.array(z.string()).optional(),
});

const CreatePoetry = () => {
    const navigate = useNavigate();
    const [poetryContent, setPoetryContent] = useState('');

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
            poetry_tags: [],
        },
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
        // Transform the single text block into couplets array
        // Splits by one or more empty lines
        const coupletTexts = poetryContent
            .split(/\n\s*\n/)
            .map(text => text.trim())
            .filter(text => text.length > 0);

        const transformedData = {
            ...data,
            couplets: coupletTexts.map(text => ({ couplet_text: text }))
        };

        if (transformedData.couplets.length === 0) {
            alert('Please write some poetry first.');
            return;
        }

        mutation.mutate(transformedData);
    };

    const applyFormat = (prefix, suffix = prefix) => {
        const el = document.getElementById('poetry-editor');
        if (!el) return;
        const start = el.selectionStart;
        const end = el.selectionEnd;
        const text = el.value;
        const before = text.substring(0, start);
        const selection = text.substring(start, end);
        const after = text.substring(end);

        const newText = before + prefix + selection + suffix + after;
        setPoetryContent(newText);

        setTimeout(() => {
            el.focus();
            el.setSelectionRange(start + prefix.length, end + prefix.length);
        }, 10);
    };

    const cycleAlignment = () => {
        const styles = ['center', 'start', 'end', 'justified'];
        const current = form.getValues('content_style');
        const next = styles[(styles.indexOf(current) + 1) % styles.length];
        form.setValue('content_style', next);
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
                    <div className="flex items-center justify-between mb-8 border-b pb-4">
                        <div className="flex items-center gap-4">
                            <h2 className="text-xl font-semibold tracking-tight">Create New Poetry</h2>
                        </div>
                        <div className="flex items-center gap-4">
                            <Button variant="ghost" type="button" onClick={() => navigate('/poetry')}>Cancel</Button>
                            <Button type="submit" disabled={mutation.isPending} className="bg-primary hover:bg-primary/90 text-primary-foreground font-medium px-8">
                                {mutation.isPending ? 'Publishing...' : 'Publish'}
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Main Content Area - Editor Canvas */}
                        <div className="lg:col-span-2 space-y-0 bg-white rounded-xl shadow-sm border overflow-hidden min-h-[700px]">
                            {/* Editor Toolbar */}
                            <div className="flex items-center gap-1 p-2 border-b bg-muted/5 sticky top-0 z-10 overflow-x-auto no-scrollbar">
                                <Button variant="ghost" size="icon" type="button" className="h-8 w-8" onClick={() => setPoetryContent(prev => prev + '\n\n')} title="Add Couplet Space">
                                    <Plus className="h-4 w-4" />
                                </Button>

                                <div className="h-4 w-[1px] bg-border mx-1" />

                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="sm" type="button" className="h-8 px-2 flex items-center gap-1">
                                            Style <ChevronDown className="h-3 w-3" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="start" className="w-48">
                                        <DropdownMenuLabel>Paragraph Style</DropdownMenuLabel>
                                        <DropdownMenuItem onClick={() => applyFormat('# ', '')}>Heading 1</DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => applyFormat('## ', '')}>Heading 2</DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => applyFormat('> ', '')}><Quote className="h-4 w-4 mr-2" /> Blockquote</DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => applyFormat('- ', '')}>Bullet List</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                <div className="h-4 w-[1px] bg-border mx-1" />

                                <div className="flex items-center">
                                    <Button variant="ghost" size="icon" type="button" className="h-8 w-8" onClick={() => applyFormat('**')} title="Bold">
                                        <Bold className="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" type="button" className="h-8 w-8" onClick={() => applyFormat('*')} title="Italic">
                                        <Italic className="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" type="button" className="h-8 w-8" onClick={() => applyFormat('~~')} title="Strikethrough">
                                        <Strikethrough className="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" type="button" className="h-8 w-8" onClick={() => applyFormat('`')} title="Code">
                                        <Code className="h-4 w-4" />
                                    </Button>
                                </div>

                                <div className="h-4 w-[1px] bg-border mx-1" />

                                <Button variant="ghost" size="icon" type="button" className="h-8 w-8" onClick={() => applyFormat('[', '](url)')} title="Link">
                                    <Link2 className="h-4 w-4" />
                                </Button>

                                <Button variant="ghost" size="icon" type="button" className="h-8 w-8" onClick={() => {
                                    document.querySelector('[name="poetry_tags"]')?.scrollIntoView({ behavior: 'smooth' });
                                }} title="Tags">
                                    <TagIcon className="h-4 w-4" />
                                </Button>

                                <Button variant="ghost" size="icon" type="button" className="h-8 w-8" onClick={cycleAlignment} title="Change Alignment">
                                    {form.watch('content_style') === 'center' && <AlignCenter className="h-4 w-4" />}
                                    {form.watch('content_style') === 'start' && <AlignLeft className="h-4 w-4" />}
                                    {form.watch('content_style') === 'end' && <AlignRight className="h-4 w-4" />}
                                    {form.watch('content_style') === 'justified' && <AlignJustify className="h-4 w-4" />}
                                </Button>

                                <div className="h-4 w-[1px] bg-border mx-1" />

                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="sm" type="button" className="h-8 px-2 flex items-center gap-1">
                                            Button <ChevronDown className="h-3 w-3" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="start">
                                        <DropdownMenuItem>Primary Button</DropdownMenuItem>
                                        <DropdownMenuItem>Outline Button</DropdownMenuItem>
                                        <DropdownMenuItem>Link Button</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                <div className="h-4 w-[1px] bg-border mx-1" />

                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="sm" type="button" className="h-8 px-2 flex items-center gap-1">
                                            More <ChevronDown className="h-3 w-3" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-48">
                                        <DropdownMenuItem onClick={() => { setPoetryContent(''); form.reset(); }}>Clear All</DropdownMenuItem>
                                        <DropdownMenuItem>Import from File</DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem onClick={() => navigate('/poetry')}>View All Poetry</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>

                            <div className="p-6 md:p-10 space-y-4 max-w-4xl mx-auto w-full">
                                {/* Top Label Placeholder */}
                                <div className="flex items-center justify-between mb-6">
                                    <div className="flex items-center gap-2 text-xs text-muted-foreground/50 font-medium">
                                        <BookOpen className="h-3 w-3" /> <span>Baakh Publishing Editor</span>
                                    </div>
                                    <div className="flex items-center gap-3 text-xs text-muted-foreground/50 font-medium">
                                        <button type="button" className="hover:text-muted-foreground transition-colors" title="Transliteration">
                                            <Languages className="h-3.5 w-3.5" />
                                        </button>
                                        <span>{poetryContent.split(/\n\s*\n/).filter(text => text.trim().length > 0).length.toString().padStart(2, '0')} Couplets</span>
                                    </div>
                                </div>

                                {/* Title Section */}
                                <div className="space-y-3">
                                    <FormField
                                        control={form.control}
                                        name="poetry_title"
                                        render={({ field }) => (
                                            <FormItem className="space-y-0">
                                                <FormControl>
                                                    <textarea
                                                        dir="rtl"
                                                        lang="sd"
                                                        className="w-full text-5xl font-bold border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none min-h-[60px] leading-tight bg-transparent text-right"
                                                        placeholder="عنوان"
                                                        {...field}
                                                        onChange={(e) => {
                                                            field.onChange(e);
                                                            e.target.style.height = 'auto';
                                                            e.target.style.height = e.target.scrollHeight + 'px';
                                                        }}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </div>

                                {/* Single Poetry Canvas */}
                                <div className="pt-6">
                                    <textarea
                                        id="poetry-editor"
                                        dir="rtl"
                                        lang="sd"
                                        className={`w-full p-0 text-2xl font-serif border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none min-h-[500px] bg-transparent leading-relaxed ${form.watch('content_style') === 'center' ? 'text-center' :
                                                form.watch('content_style') === 'start' ? 'text-right' :
                                                    form.watch('content_style') === 'end' ? 'text-left' : 'text-justify'
                                            }`}
                                        placeholder="پنهنجي شاعري هتي لکو... نئين شعر لاءِ هڪ خالي لڪير ڇڏيو."
                                        value={poetryContent}
                                        onChange={(e) => {
                                            setPoetryContent(e.target.value);
                                            e.target.style.height = 'auto';
                                            e.target.style.height = e.target.scrollHeight + 'px';
                                        }}
                                    />
                                </div>
                            </div>
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

                            {/* Additional Information Card */}
                            <Card className="shadow-sm">
                                <CardHeader className="py-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <Info className="h-4 w-4" /> Additional Info
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <FormField
                                        control={form.control}
                                        name="poetry_info"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-xs uppercase text-muted-foreground font-bold">Background</FormLabel>
                                                <FormControl>
                                                    <textarea
                                                        className="w-full min-h-[100px] p-2 text-sm border rounded-md focus:ring-1 focus:ring-primary focus:border-primary transition-all resize-none"
                                                        placeholder="Story..."
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
                                                <FormLabel className="text-xs uppercase text-muted-foreground font-bold">Source</FormLabel>
                                                <FormControl>
                                                    <Input placeholder="Book name..." {...field} className="h-8 text-sm" />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
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
                                                            <SelectValue placeholder="Add Tags" />
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
