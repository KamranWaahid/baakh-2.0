import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    const navigate = useNavigate();
    const [coupletContent, setCoupletContent] = useState('');
    const [showTransliteration, setShowTransliteration] = useState(false);
    const [transliteratedText, setTransliteratedText] = useState('');

    // Sindhi to Roman transliteration mapping
    const transliterateSindhi = (text) => {
        const sindhiToRoman = {
            'ا': 'a', 'ب': 'b', 'ٻ': 'bb', 'پ': 'p', 'ت': 't', 'ٿ': 'tt', 'ٽ': 'ṭ',
            'ث': 's', 'ج': 'j', 'ڄ': 'jj', 'جھ': 'jh', 'ڃ': 'ñ', 'چ': 'ch', 'ڇ': 'chh',
            'ح': 'h', 'خ': 'kh', 'د': 'd', 'ڌ': 'dh', 'ڊ': 'dd', 'ڏ': 'ḍ', 'ذ': 'z',
            'ر': 'r', 'ڙ': 'rr', 'ز': 'z', 'س': 's', 'ش': 'sh', 'ص': 's', 'ض': 'z',
            'ط': 't', 'ظ': 'z', 'ع': "'", 'غ': 'gh', 'ف': 'f', 'ق': 'q', 'ڪ': 'k',
            'گ': 'g', 'ڳ': 'gg', 'گھ': 'gh', 'ل': 'l', 'م': 'm', 'ن': 'n', 'ڻ': 'nn',
            'و': 'w', 'ه': 'h', 'ھ': 'h', 'ي': 'y', 'ي': 'i', 'ئ': "'",
            'َ': 'a', 'ُ': 'u', 'ِ': 'i', 'ً': 'an', 'ٌ': 'un', 'ٍ': 'in',
            'ّ': '', 'ْ': '', 'ٰ': 'ā', 'ء': "'",
            '۽': 'ain', '۾': 'mein', '؟': '?', '،': ',', '۔': '.'
        };

        let result = '';
        for (let i = 0; i < text.length; i++) {
            const char = text[i];
            result += sindhiToRoman[char] || char;
        }
        return result;
    };

    const handleTransliterate = () => {
        const transliterated = transliterateSindhi(coupletContent);
        setTransliteratedText(transliterated);
        setShowTransliteration(true);
    };

    const { data: meta, isLoading: isMetaLoading } = useQuery({
        queryKey: ['poetry-meta'],
        queryFn: async () => {
            const response = await api.get('/api/admin/poetry/create');
            return response.data;
        }
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

    // Auto-generate slug from title
    useEffect(() => {
        const subscription = form.watch((value, { name }) => {
            if (name === 'poetry_title' && value.poetry_title) {
                const slug = value.poetry_title
                    .toLowerCase()
                    .replace(/\s+/g, '-')
                    .replace(/[^\w\-]+/g, '')
                    .replace(/\-\-+/g, '-');
                form.setValue('poetry_slug', slug);
            }
        });
        return () => subscription.unsubscribe();
    }, [form]);

    const mutation = useMutation({
        mutationFn: async (data) => {
            // Parse couplet content (exactly 2 lines)
            const lines = coupletContent.split('\n').filter(line => line.trim() !== '');

            // Validation: Must be exactly 2 lines
            if (lines.length !== 2) {
                throw new Error('Couplet must contain exactly 2 lines');
            }

            const payload = {
                ...data,
                couplets: [
                    {
                        first_part: lines[0],
                        second_part: lines[1]
                    }
                ],
                // No category_id for couplets
            };

            const response = await api.post('/api/admin/poetry', payload);
            return response.data;
        },
        onSuccess: () => {
            navigate('/admin/new/poetry');
        },
        onError: (error) => {
            console.error('Error creating couplet:', error);
            alert(error.message || 'Failed to create couplet');
        }
    });

    const onSubmit = (data) => {
        mutation.mutate(data);
    };

    // Count lines in couplet content
    const lineCount = coupletContent.split('\n').filter(line => line.trim() !== '').length;

    const applyFormat = (prefix, suffix = prefix) => {
        const el = document.getElementById('couplet-editor');
        if (!el) return;
        const start = el.selectionStart;
        const end = el.selectionEnd;
        const text = el.value;
        const before = text.substring(0, start);
        const selection = text.substring(start, end);
        const after = text.substring(end);

        const newText = before + prefix + selection + suffix + after;
        setCoupletContent(newText);

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
                            <h2 className="text-xl font-semibold tracking-tight">Create New Couplet</h2>
                        </div>
                        <div className="flex items-center gap-4">
                            <Button variant="ghost" type="button" onClick={() => navigate('/poetry')}>Cancel</Button>
                            <Button type="submit" disabled={mutation.isPending || lineCount !== 2} className="bg-primary hover:bg-primary/90 text-primary-foreground font-medium px-8">
                                {mutation.isPending ? 'Publishing...' : 'Publish'}
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Main Content Area - Editor Canvas */}
                        <div className="lg:col-span-2 space-y-0 bg-white rounded-xl shadow-sm border overflow-hidden min-h-[700px]">
                            {/* Editor Toolbar */}
                            <div className="flex items-center gap-1 p-2 border-b bg-muted/5 sticky top-0 z-10 overflow-x-auto no-scrollbar">
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
                                            More <ChevronDown className="h-3 w-3" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-48">
                                        <DropdownMenuItem onClick={() => { setCoupletContent(''); form.reset(); }}>Clear All</DropdownMenuItem>
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
                                        <button
                                            type="button"
                                            className="hover:text-muted-foreground transition-colors"
                                            title="Transliteration"
                                            onClick={handleTransliterate}
                                        >
                                            <Languages className="h-3.5 w-3.5" />
                                        </button>
                                        <span>{lineCount.toString().padStart(2, '0')} Lines</span>
                                    </div>
                                </div>

                                {/* Transliteration Dialog */}
                                <Dialog open={showTransliteration} onOpenChange={setShowTransliteration}>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Romanized Text</DialogTitle>
                                            <DialogDescription>
                                                Sindhi text converted to Roman script
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="mt-4">
                                            <div className="p-4 bg-muted rounded-md font-mono text-sm whitespace-pre-wrap">
                                                {transliteratedText}
                                            </div>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="mt-3"
                                                onClick={() => {
                                                    navigator.clipboard.writeText(transliteratedText);
                                                }}
                                            >
                                                Copy to Clipboard
                                            </Button>
                                        </div>
                                    </DialogContent>
                                </Dialog>

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

                                {/* Single Couplet Canvas */}
                                <div className="pt-6">
                                    <textarea
                                        id="couplet-editor"
                                        dir="rtl"
                                        lang="sd"
                                        className={`w-full p-0 text-2xl border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none min-h-[500px] bg-transparent leading-relaxed font-arabic ${form.watch('content_style') === 'center' ? 'text-center' :
                                            form.watch('content_style') === 'start' ? 'text-right' :
                                                form.watch('content_style') === 'end' ? 'text-left' : 'text-justify'
                                            }`}
                                        placeholder="پنهنجو شعر هتي لکو... صرف 2 لائينون"
                                        value={coupletContent}
                                        onChange={(e) => {
                                            const lines = e.target.value.split('\n');
                                            // Restrict to maximum 2 lines
                                            if (lines.length <= 2) {
                                                setCoupletContent(e.target.value);
                                                e.target.style.height = 'auto';
                                                e.target.style.height = e.target.scrollHeight + 'px';
                                            }
                                        }}
                                    />
                                    {lineCount > 2 && (
                                        <p className="text-sm text-destructive mt-2">
                                            Couplet can only have 2 lines. Please remove extra lines.
                                        </p>
                                    )}
                                    {lineCount !== 2 && lineCount > 0 && (
                                        <p className="text-sm text-muted-foreground mt-2">
                                            Please write exactly 2 lines for the couplet.
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Status & Visibility Card */}
                            <Card className="shadow-sm">
                                <CardHeader className="py-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <Settings className="h-4 w-4" /> Status & Visibility
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <FormField
                                        control={form.control}
                                        name="visibility"
                                        render={({ field }) => (
                                            <FormItem className="flex items-center justify-between">
                                                <FormLabel className="text-xs uppercase text-muted-foreground/50 font-bold flex items-center gap-1">
                                                    {field.value ? <Eye className="h-3 w-3" /> : <EyeOff className="h-3 w-3" />} Visibility
                                                </FormLabel>
                                                <div className="flex items-center gap-2">
                                                    <Checkbox
                                                        checked={field.value}
                                                        onCheckedChange={field.onChange}
                                                        className="transition-all duration-200 data-[state=checked]:animate-in data-[state=checked]:zoom-in-50"
                                                    />
                                                </div>
                                            </FormItem>
                                        )}
                                    />
                                    <FormField
                                        control={form.control}
                                        name="is_featured"
                                        render={({ field }) => (
                                            <FormItem className="flex items-center justify-between">
                                                <FormLabel className="text-xs uppercase text-muted-foreground/50 font-bold flex items-center gap-1">
                                                    <Star className="h-3 w-3" /> Feature Post
                                                </FormLabel>
                                                <Checkbox
                                                    checked={field.value}
                                                    onCheckedChange={field.onChange}
                                                    className="transition-all duration-200 data-[state=checked]:animate-in data-[state=checked]:zoom-in-50"
                                                />
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                            </Card>

                            {/* Meta Info Card */}
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

export default CreateCouplet;
