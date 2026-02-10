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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
    DropdownMenuLabel,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Trash2, Plus, Send, Eye, EyeOff, Star, Info, Settings, User, Folder, Tag as TagIcon, Link as LinkIcon, AlignCenter, ChevronDown, BookOpen, Bold, Italic, Strikethrough, Code, AlignLeft, AlignRight, AlignJustify, Link2, Quote, Languages } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Check, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';

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
    const { id } = useParams();
    const isEdit = !!id;
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [poetryContent, setPoetryContent] = useState('');
    const [romanTitle, setRomanTitle] = useState('');

    const [transliteratedText, setTransliteratedText] = useState('');
    const [isTransliterated, setIsTransliterated] = useState(isEdit); // Default true for edit, false for new
    const [slugError, setSlugError] = useState('');
    const [isCheckingSlug, setIsCheckingSlug] = useState(false);
    const [openPoet, setOpenPoet] = useState(false);
    const [openCategory, setOpenCategory] = useState(false);

    const [openTags, setOpenTags] = useState(false);
    const [script, setScript] = useState('perso'); // 'perso' | 'roman'

    // Reset transliteration status when content changes
    useEffect(() => {
        setIsTransliterated(false);
    }, [poetryContent]);

    const handleTransliterate = async () => {
        if (!poetryContent.trim()) return;

        try {
            // Transliterate Content
            const contentResponse = await api.post('/api/admin/romanizer/transliterate', {
                text: poetryContent
            });
            setTransliteratedText(contentResponse.data.transliterated_text);

            // Transliterate Title
            const currentTitle = form.getValues('poetry_title');
            if (currentTitle) {
                const titleResponse = await api.post('/api/admin/romanizer/transliterate', {
                    text: currentTitle
                });
                setRomanTitle(titleResponse.data.transliterated_text);
            }

            setIsTransliterated(true);
            setScript('roman'); // Switch to Roman view
        } catch (error) {
            console.error("Transliteration failed:", error);
            alert("Failed to transliterate. Please try again.");
        }
    };

    const checkSlugUnique = async (slug) => {
        if (!slug) return;
        setIsCheckingSlug(true);
        try {
            const response = await api.get(`/api/admin/poetry/check-slug`, {
                params: { slug, id: id }
            });
            if (response.data.exists) {
                setSlugError('This slug is already taken.');
            } else {
                setSlugError('');
            }
        } catch (error) {
            console.error("Slug check failed:", error);
        } finally {
            setIsCheckingSlug(false);
        }
    };

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

    // Auto-generate slug from title (only for new poetry)
    const title = form.watch('poetry_title');
    useEffect(() => {
        if (!isEdit && title) {
            const slug = title
                .toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            form.setValue('poetry_slug', slug);
            checkSlugUnique(slug); // Check uniqueness when auto-generated
        }
    }, [title, isEdit, form]);

    useEffect(() => {
        if (isEdit && poetry) {
            const persoTranslation = poetry.translations?.find(t => t.lang === 'sd') || poetry.translations?.[0];
            const romanTranslation = poetry.translations?.find(t => t.lang === 'en');

            form.reset({
                poetry_title: persoTranslation?.title || '',
                poetry_slug: poetry.poetry_slug || '',
                poet_id: poetry.poet_id?.toString() || '',
                category_id: poetry.category_id?.toString() || '',
                content_style: poetry.content_style || 'center',
                visibility: poetry.visibility === 1,
                is_featured: poetry.is_featured === 1,
                poetry_info: persoTranslation?.info || '',
                source: persoTranslation?.source || '',
                poetry_tags: JSON.parse(poetry.poetry_tags || '[]'),
            });

            // Set Roman Title
            setRomanTitle(romanTranslation?.title || '');

            // Filter and set content by language
            const persoCouplets = poetry.couplets?.filter(c => c.lang === 'sd') || [];
            // If no language specified (legacy), assume they are the main content (Perso)
            const displayPersoCouplets = persoCouplets.length > 0 ? persoCouplets : (poetry.couplets || []);

            const romanCouplets = poetry.couplets?.filter(c => c.lang === 'en') || [];

            setPoetryContent(displayPersoCouplets.map(c => c.couplet_text).join('\n\n'));
            setTransliteratedText(romanCouplets.map(c => c.couplet_text).join('\n\n'));
        }
    }, [isEdit, poetry, form]);

    const mutation = useMutation({
        mutationFn: async (data) => {
            if (isEdit) {
                return await api.put(`/api/admin/poetry/${id}`, data);
            }
            return await api.post('/api/admin/poetry', data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['poetry']);
            navigate('/admin/poetry');
        },
        onError: (error) => {
            alert('Error: ' + (error.response?.data?.message || error.message));
        },
    });

    const onSubmit = (data) => {
        const coupletTexts = poetryContent
            .split(/\n\s*\n/)
            .map(text => text.trim())
            .filter(text => text.length > 0);

        const transformedData = {
            ...data,
            ...data,
            couplets: coupletTexts.map(text => ({ couplet_text: text })),
            roman_title: romanTitle,
            roman_content: transliteratedText
                .split(/\n\s*\n/)
                .map(text => text.trim())
                .filter(text => text.length > 0)
                .map(text => ({ couplet_text: text }))
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

    if (isMetaLoading || (isEdit && isPoetryLoading)) {
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
                            <h2 className="text-xl font-semibold tracking-tight">
                                {isEdit ? 'Edit Poetry' : 'Create New Poetry'}
                            </h2>
                        </div>
                        <div className="flex items-center gap-4">
                            <Button variant="ghost" type="button" onClick={() => navigate('/admin/poetry')}>Cancel</Button>
                            <Button type="submit" disabled={mutation.isPending || !isTransliterated || !!slugError || isCheckingSlug} className="bg-primary hover:bg-primary/90 text-primary-foreground font-medium px-8">
                                {mutation.isPending ? 'Saving...' : (isEdit ? 'Update' : 'Publish')}
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div className="lg:col-span-2 space-y-0 bg-white rounded-xl shadow-sm border overflow-hidden min-h-[700px]">
                            <Tabs value={script} onValueChange={setScript} className="w-full">
                                <div className="flex items-center justify-between px-4 py-2 border-b bg-muted/5 sticky top-0 z-10 w-full">
                                    <TabsList className="h-9 bg-muted/50">
                                        <TabsTrigger value="perso" className="text-xs h-7 px-3 font-arabic">سنڌي (Perso)</TabsTrigger>
                                        <TabsTrigger value="roman" className="text-xs h-7 px-3 font-medium">Sindhi (roman)</TabsTrigger>
                                    </TabsList>

                                    <div className="flex items-center gap-3 text-xs text-muted-foreground/50 font-medium">
                                        <button
                                            type="button"
                                            className={`flex items-center gap-1 hover:text-foreground transition-colors ${!isTransliterated ? 'text-orange-600 font-bold bg-orange-50 px-2 py-0.5 rounded border border-orange-200' : ''}`}
                                            title="Transliterate (Required to Publish)"
                                            onClick={handleTransliterate}
                                        >
                                            <Languages className="h-3.5 w-3.5" />
                                            <span>Transliterate</span>
                                        </button>
                                        {/* formatting toolbar - only show in Perso mode */}
                                        {(script === 'perso' || script === 'roman') && (
                                            <>
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
                                                <div className="flex items-center">
                                                    <Button variant="ghost" size="icon" type="button" className="h-8 w-8" onClick={() => applyFormat('**')} title="Bold">
                                                        <Bold className="h-4 w-4" />
                                                    </Button>
                                                    <Button variant="ghost" size="icon" type="button" className="h-8 w-8" onClick={() => applyFormat('*')} title="Italic">
                                                        <Italic className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                </div>

                                <div className="p-6 md:p-10 space-y-4 max-w-4xl mx-auto w-full">
                                    <div className="flex items-center justify-between mb-4">
                                        <div className="flex items-center gap-2 text-xs text-muted-foreground/50 font-medium">
                                            <BookOpen className="h-3 w-3" /> <span>Baakh Publishing Editor</span>
                                        </div>
                                        <div className="text-xs text-muted-foreground/50 font-medium">
                                            <span>{poetryContent.split(/\n\s*\n/).filter(text => text.trim().length > 0).length.toString().padStart(2, '0')} Couplets</span>
                                        </div>
                                    </div>

                                    {script === 'perso' && (
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
                                                                className="w-full text-5xl font-bold border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none min-h-[60px] leading-tight bg-transparent text-right font-arabic"
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
                                    )}

                                    <div className="pt-6">
                                        <TabsContent value="perso" className="m-0 border-0 p-0 hover:outline-none focus:outline-none focus-visible:outline-none ring-0 focus:ring-0">
                                            <textarea
                                                id="poetry-editor"
                                                dir="rtl"
                                                lang="sd"
                                                className={`w-full p-0 text-2xl border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none min-h-[500px] bg-transparent leading-relaxed font-arabic ${form.watch('content_style') === 'center' ? 'text-center' :
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
                                        </TabsContent>
                                        <TabsContent value="roman" className="m-0 border-0 p-0 hover:outline-none focus:outline-none focus-visible:outline-none ring-0 focus:ring-0">
                                            <textarea
                                                dir="ltr"
                                                className="w-full text-5xl font-bold border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none min-h-[60px] leading-tight bg-transparent text-left font-sans mb-3"
                                                placeholder="Roman Title"
                                                value={romanTitle}
                                                onChange={(e) => {
                                                    setRomanTitle(e.target.value);
                                                    e.target.style.height = 'auto';
                                                    e.target.style.height = e.target.scrollHeight + 'px';
                                                }}
                                            />
                                            <textarea
                                                dir="ltr"
                                                className={`w-full p-0 text-xl border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none min-h-[500px] bg-transparent leading-relaxed font-sans ${form.watch('content_style') === 'center' ? 'text-center' :
                                                    form.watch('content_style') === 'start' ? 'text-left' :
                                                        form.watch('content_style') === 'end' ? 'text-right' : 'text-justify'
                                                    }`}
                                                placeholder="Transliterated text will appear here..."
                                                value={transliteratedText}
                                                onChange={(e) => setTransliteratedText(e.target.value)}
                                            />
                                        </TabsContent>
                                    </div>
                                </div>
                            </Tabs>
                        </div>

                        <div className="space-y-6">
                            <Card className="shadow-sm">
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
                                                    <FormLabel className="text-sm font-medium mb-2 block">Content Alignment</FormLabel>
                                                    <Select onValueChange={field.onChange} value={field.value}>
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
                                                    {slugError && <p className="text-[10px] text-destructive mt-1">{slugError}</p>}
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                </CardContent>
                                <CardFooter className="bg-muted/10 flex justify-between py-3">
                                    <Button variant="ghost" size="sm" type="button" className="text-destructive h-8 px-2" onClick={() => navigate('/admin/poetry')}>
                                        Cancel
                                    </Button>
                                    <Button size="sm" type="submit" className="h-8 px-4" disabled={mutation.isPending || !isTransliterated || !!slugError || isCheckingSlug}>
                                        {mutation.isPending ? 'Saving...' : (isEdit ? 'Update' : 'Publish')}
                                    </Button>
                                </CardFooter>
                            </Card>

                            <Card className="shadow-sm">
                                <CardHeader className="py-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <Folder className="h-4 w-4" /> Meta Info
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <FormField
                                        control={form.control}
                                        name="poet_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-sm font-medium flex items-center gap-2">
                                                    <User className="h-4 w-4 text-muted-foreground" /> Poet
                                                </FormLabel>
                                                <Popover open={openPoet} onOpenChange={setOpenPoet}>
                                                    <PopoverTrigger asChild>
                                                        <FormControl>
                                                            <Button
                                                                variant="outline"
                                                                role="combobox"
                                                                aria-expanded={openPoet}
                                                                className={cn(
                                                                    "w-full justify-between font-arabic",
                                                                    !field.value && "text-muted-foreground"
                                                                )}
                                                            >
                                                                {field.value
                                                                    ? meta?.poets.find((poet) => poet.id.toString() === field.value)?.name
                                                                    : "Select Poet"}
                                                                <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                            </Button>
                                                        </FormControl>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-[300px] p-0" align="start">
                                                        <Command>
                                                            <CommandInput placeholder="Search poet..." className="font-arabic text-right" />
                                                            <CommandList>
                                                                <CommandEmpty>No poet found.</CommandEmpty>
                                                                <CommandGroup>
                                                                    {meta?.poets.map((poet) => (
                                                                        <CommandItem
                                                                            value={`${poet.name} ${poet.id}`}
                                                                            key={poet.id}
                                                                            onSelect={() => {
                                                                                form.setValue("poet_id", poet.id.toString());
                                                                                setOpenPoet(false);
                                                                            }}
                                                                            className="font-arabic text-right flex flex-row-reverse justify-between"
                                                                        >
                                                                            {poet.name}
                                                                            <Check
                                                                                className={cn(
                                                                                    "mr-2 h-4 w-4",
                                                                                    poet.id.toString() === field.value
                                                                                        ? "opacity-100"
                                                                                        : "opacity-0"
                                                                                )}
                                                                            />
                                                                        </CommandItem>
                                                                    ))}
                                                                </CommandGroup>
                                                            </CommandList>
                                                        </Command>
                                                    </PopoverContent>
                                                </Popover>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="category_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-sm font-medium flex items-center gap-2">
                                                    <Folder className="h-4 w-4 text-muted-foreground" /> Category
                                                </FormLabel>
                                                <Popover open={openCategory} onOpenChange={setOpenCategory}>
                                                    <PopoverTrigger asChild>
                                                        <FormControl>
                                                            <Button
                                                                variant="outline"
                                                                role="combobox"
                                                                aria-expanded={openCategory}
                                                                className={cn(
                                                                    "w-full justify-between font-arabic",
                                                                    !field.value && "text-muted-foreground"
                                                                )}
                                                            >
                                                                {field.value
                                                                    ? meta?.categories.find((cat) => cat.id.toString() === field.value)?.name
                                                                    : "Select Category"}
                                                                <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                            </Button>
                                                        </FormControl>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-[300px] p-0" align="start">
                                                        <Command>
                                                            <CommandInput placeholder="Search category..." className="font-arabic text-right" />
                                                            <CommandList>
                                                                <CommandEmpty>No category found.</CommandEmpty>
                                                                <CommandGroup>
                                                                    {meta?.categories.map((cat) => (
                                                                        <CommandItem
                                                                            value={`${cat.name} ${cat.id}`}
                                                                            key={cat.id}
                                                                            onSelect={() => {
                                                                                form.setValue("category_id", cat.id.toString());
                                                                                setOpenCategory(false);
                                                                            }}
                                                                            className="font-arabic text-right flex flex-row-reverse justify-between"
                                                                        >
                                                                            {cat.name}
                                                                            <Check
                                                                                className={cn(
                                                                                    "mr-2 h-4 w-4",
                                                                                    cat.id.toString() === field.value
                                                                                        ? "opacity-100"
                                                                                        : "opacity-0"
                                                                                )}
                                                                            />
                                                                        </CommandItem>
                                                                    ))}
                                                                </CommandGroup>
                                                            </CommandList>
                                                        </Command>
                                                    </PopoverContent>
                                                </Popover>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="poetry_slug"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-sm font-medium flex items-center gap-2">
                                                    <LinkIcon className="h-4 w-4 text-muted-foreground" /> URL Slug
                                                </FormLabel>
                                                <FormControl>
                                                    <Input
                                                        {...field}
                                                        className={`h-8 text-xs font-mono ${slugError ? 'border-destructive' : ''}`}
                                                        onBlur={(e) => {
                                                            field.onBlur(e);
                                                            checkSlugUnique(e.target.value);
                                                        }}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                            </Card>

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
                                                <span key={tagId} className="bg-secondary text-secondary-foreground hover:bg-secondary/80 text-xs font-arabic px-2.5 py-1 rounded-md flex items-center gap-1.5 transition-colors">
                                                    {tag?.tag || tagId}
                                                    <Trash2 className="h-3 w-3 cursor-pointer opacity-70 hover:opacity-100" onClick={() => {
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
                                            <FormItem className="flex flex-col">
                                                <Popover open={openTags} onOpenChange={setOpenTags}>
                                                    <PopoverTrigger asChild>
                                                        <FormControl>
                                                            <Button
                                                                variant="outline"
                                                                role="combobox"
                                                                aria-expanded={openTags}
                                                                className="w-full justify-between font-arabic"
                                                            >
                                                                Select tags...
                                                                <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                            </Button>
                                                        </FormControl>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-[300px] p-0" align="start">
                                                        <Command>
                                                            <CommandInput placeholder="Search tags..." className="font-arabic text-right" />
                                                            <CommandList>
                                                                <CommandEmpty>No tag found.</CommandEmpty>
                                                                <CommandGroup>
                                                                    {meta?.tags.map((tag) => (
                                                                        <CommandItem
                                                                            value={`${tag.tag} ${tag.id}`}
                                                                            key={tag.id}
                                                                            onSelect={() => {
                                                                                const current = form.getValues("poetry_tags") || [];
                                                                                const tagId = tag.id.toString();
                                                                                if (!current.includes(tagId)) {
                                                                                    form.setValue("poetry_tags", [...current, tagId]);
                                                                                }
                                                                                setOpenTags(false);
                                                                            }}
                                                                            className="font-arabic text-right flex flex-row-reverse justify-between"
                                                                        >
                                                                            {tag.tag}
                                                                            <Check
                                                                                className={cn(
                                                                                    "mr-2 h-4 w-4",
                                                                                    (form.getValues("poetry_tags") || []).includes(tag.id.toString())
                                                                                        ? "opacity-100"
                                                                                        : "opacity-0"
                                                                                )}
                                                                            />
                                                                        </CommandItem>
                                                                    ))}
                                                                </CommandGroup>
                                                            </CommandList>
                                                        </Command>
                                                    </PopoverContent>
                                                </Popover>
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                            </Card>

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
                                                <FormLabel className="text-sm font-medium mb-2 block">Background</FormLabel>
                                                <FormControl>
                                                    <textarea
                                                        className="w-full min-h-[100px] p-2 text-sm border border-border/40 rounded-md focus:ring-1 focus:ring-primary/50 focus:border-primary/50 transition-all resize-none"
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
                                                <FormLabel className="text-sm font-medium mb-2 block">Source</FormLabel>
                                                <FormControl>
                                                    <Input placeholder="Book name..." {...field} className="h-8 text-sm" />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                            </Card>
                        </div>
                    </div>


                </form >
            </Form >
        </div >
    );
};

export default CreatePoetry;
