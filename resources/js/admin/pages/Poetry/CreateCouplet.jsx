import React, { useState, useEffect, useCallback, useRef } from 'react';
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
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Trash2, Plus, Eye, EyeOff, Star, Settings, User, Folder, Tag as TagIcon, Link as LinkIcon, AlignCenter, ChevronDown, BookOpen, Bold, Italic, Strikethrough, Code, AlignLeft, AlignRight, AlignJustify, Link2, Quote, Languages, ChevronsUpDown, Check, Info, SpellCheck, Loader2, Shuffle, RefreshCw } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import { Skeleton } from '@/components/ui/skeleton';
import { toast } from 'sonner';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";
import { cn } from "@/lib/utils";
import PoetryLughatSensePicker from './PoetryLughatSensePicker';
import PoetryLughatMissingHighlight from './PoetryLughatMissingHighlight';
import LughatLemmaEditorJsonModal from '../Lughat/LughatLemmaEditorJsonModal';

function clampCoupletToTwoLines(text) {
    const normalized = String(text ?? '').replace(/\r\n?/g, '\n');
    return normalized.split('\n').slice(0, 2).join('\n');
}

function coupletNonEmptyLineCount(text) {
    return clampCoupletToTwoLines(text)
        .split('\n')
        .filter((line) => line.trim() !== '').length;
}

const coupletSchema = z.object({
    couplet_slug: z.string().min(2, 'Slug is required'),
    poet_id: z.string().min(1, 'Poet is required'),
    topic_category_id: z.string().optional().nullable(),
    visibility: z.boolean().default(true),
    is_featured: z.boolean().default(false),
    couplet_tags: z.array(z.string()).optional(),
    book_id: z.string().optional().nullable(),
    page_start: z.string().optional().nullable(),
    page_end: z.string().optional().nullable(),
});

const CreateCouplet = () => {
    const { id } = useParams();
    const isEdit = !!id;
    const navigate = useNavigate();
    const [openTags, setOpenTags] = useState(false);
    const [openPoet, setOpenPoet] = useState(false);
    const [openBook, setOpenBook] = useState(false);
    const [isCheckingSlug, setIsCheckingSlug] = useState(false);
    const [romanContent, setRomanContent] = useState('');
    const [isTransliterated, setIsTransliterated] = useState(isEdit);
    const [script, setScript] = useState('perso'); // 'perso' | 'roman'
    const [slugError, setSlugError] = useState(null);
    const [coupletContent, setCoupletContent] = useState('');
    const [lughatRomanReady, setLughatRomanReady] = useState(false);
    const [lughatRomanWords, setLughatRomanWords] = useState([]);
    const [lughatRomanChecking, setLughatRomanChecking] = useState(false);
    const [openingLughatSurface, setOpeningLughatSurface] = useState(null);
    const [viewingLemmaId, setViewingLemmaId] = useState(null);
    const [editingCoupletText, setEditingCoupletText] = useState(false);
    const [lughatCheckNonce, setLughatCheckNonce] = useState(0);
    const [legacyRomanSnapshot, setLegacyRomanSnapshot] = useState(null);
    const [sensePickerMode, setSensePickerMode] = useState(false);
    const [senseAnnotations, setSenseAnnotations] = useState([]);
    const [expressionAnnotations, setExpressionAnnotations] = useState([]);
    const queryClient = useQueryClient();

    const { data: meta, isLoading: isMetaLoading } = useQuery({
        queryKey: ['poetry-meta'],
        queryFn: async () => {
            const response = await api.get('/api/admin/poetry/create');
            return response.data;
        }
    });

    const { data: couplet, isLoading: isCoupletLoading } = useQuery({
        queryKey: ['couplet', id],
        queryFn: async () => {
            const response = await api.get(`/api/admin/couplets/${id}`);
            return response.data;
        },
        enabled: isEdit,
    });

    // Prevent auto-updates on initial load for Edit mode
    const allowAutoUpdates = useRef(!isEdit);

    const runLughatRomanPipeline = useCallback(async ({ bodyText, updateSlug = false } = {}) => {
        const bodyValue = clampCoupletToTwoLines(bodyText ?? coupletContent ?? '');

        if (!bodyValue.trim()) {
            setRomanContent('');
            setLughatRomanWords([]);
            setLughatRomanReady(false);
            setIsTransliterated(true);
            return;
        }

        setLughatRomanChecking(true);
        setIsTransliterated(false);
        try {
            const [checkRes, translitRes] = await Promise.all([
                api.post('/api/admin/poetry/lughat-roman-check', {
                    title: '',
                    text: bodyValue,
                }),
                api.post('/api/admin/poetry/lughat-roman-transliterate', {
                    title: '',
                    text: bodyValue,
                }),
            ]);

            const check = checkRes.data || {};
            const ready = !!check.ready;
            setLughatRomanWords(Array.isArray(check.words) ? check.words : []);
            setLughatRomanReady(ready);

            const romanBody = clampCoupletToTwoLines(translitRes.data?.roman_content ?? '');

            if (!isEdit || ready || !legacyRomanSnapshot) {
                setRomanContent(romanBody);
            } else {
                setRomanContent(legacyRomanSnapshot);
            }
            setIsTransliterated(true);

            if (updateSlug && romanBody && !/[\u0600-\u06FF]/.test(romanBody)) {
                const firstLine = romanBody.split('\n')[0].trim();
                const slug = firstLine
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                if (slug) {
                    form.setValue('couplet_slug', slug);
                    checkSlugUnique(slug);
                }
            }
        } catch (error) {
            console.error('Baakh Lughat roman check failed:', error);
            setLughatRomanReady(false);
            setIsTransliterated(false);
        } finally {
            setLughatRomanChecking(false);
        }
    }, [coupletContent, isEdit, legacyRomanSnapshot]);

    useEffect(() => {
        if (!allowAutoUpdates.current) return;

        const timer = setTimeout(() => {
            runLughatRomanPipeline({
                bodyText: coupletContent,
                updateSlug: !isEdit,
            });
        }, 400);

        return () => clearTimeout(timer);
    }, [coupletContent, runLughatRomanPipeline, isEdit, lughatCheckNonce]);

    const applyFormat = (prefix, suffix = prefix) => {
        const el = document.getElementById('couplet-editor');
        if (!el) return;
        const start = el.selectionStart;
        const end = el.selectionEnd;
        const text = el.value;
        const before = text.substring(0, start);
        const selection = text.substring(start, end);
        const after = text.substring(end);

        const newText = clampCoupletToTwoLines(before + prefix + selection + suffix + after);
        setCoupletContent(newText);

        setTimeout(() => {
            el.focus();
            el.setSelectionRange(start + prefix.length, end + prefix.length);
        }, 10);
    };

    const checkSlugUnique = async (slug) => {
        if (!slug || isEdit) return;
        setIsCheckingSlug(true);
        setSlugError(null);
        try {
            const response = await api.get(`/api/admin/couplets/check-slug?slug=${slug}${isEdit ? `&id=${id}` : ''}`);
            if (!response.data.available) {
                setSlugError('This slug is already taken.');
            }
        } catch (error) {
            console.error("Slug check failed:", error);
        } finally {
            setIsCheckingSlug(false);
        }
    };

    const form = useForm({
        resolver: zodResolver(coupletSchema),
        defaultValues: {
            couplet_slug: '',
            poet_id: '',
            topic_category_id: '',
            visibility: true,
            is_featured: false,
            couplet_tags: [],
            book_id: '',
            page_start: '',
            page_end: '',
        }
    });

    // Auto-generate slug from first line using Baakh Lughat roman (only for new)
    const generateSlug = async (content) => {
        if (!content) return;
        const firstLine = clampCoupletToTwoLines(content).split('\n')[0].trim();
        if (!firstLine) return;

        try {
            const response = await api.post('/api/admin/poetry/lughat-roman-transliterate', {
                title: '',
                text: firstLine,
            });
            let roman = response.data?.roman_content || response.data?.transliterated_text || '';

            let slug = roman
                .toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');

            if (!slug) return;

            let isAvailable = false;
            let counter = 0;
            let tempSlug = slug;

            while (!isAvailable && counter < 10) {
                const checkRes = await api.get(`/api/admin/couplets/check-slug?slug=${tempSlug}${isEdit ? `&id=${id}` : ''}`);
                if (checkRes.data.available) {
                    isAvailable = true;
                    slug = tempSlug;
                } else {
                    counter++;
                    tempSlug = `${slug}-${counter}`;
                }
            }

            form.setValue('couplet_slug', slug);
            setSlugError(null);
        } catch (error) {
            console.error("Slug generation failed:", error);
        }
    };

    useEffect(() => {
        if (isEdit && couplet) {
            form.reset({
                couplet_slug: couplet.couplet_slug || '',
                poet_id: couplet.poet_id?.toString() || '',
                topic_category_id: couplet.topic_category_id?.toString() || '',
                visibility: couplet.visibility === 1 || couplet.visibility === true,
                is_featured: couplet.is_featured === 1 || couplet.is_featured === true,
                couplet_tags: JSON.parse(couplet.couplet_tags || '[]'),
                book_id: couplet.book_id?.toString() || '',
                page_start: couplet.page_start?.toString() || '',
                page_end: couplet.page_end?.toString() || '',
            });
            const loaded = clampCoupletToTwoLines(couplet.couplet_text || '');
            setCoupletContent(loaded);
            const legacyBody = clampCoupletToTwoLines(couplet.roman_text || '');
            setLegacyRomanSnapshot(legacyBody);
            setRomanContent(legacyBody);
            setIsTransliterated(true);
            setLughatRomanReady(false);

            setTimeout(() => {
                allowAutoUpdates.current = true;
                setLughatCheckNonce((n) => n + 1);
            }, 800);
        }
    }, [isEdit, couplet, form]);

    const mutation = useMutation({
        mutationFn: async (data) => {
            const payload = {
                poet_id: data.poet_id,
                topic_category_id: data.topic_category_id,
                couplet_text: clampCoupletToTwoLines(coupletContent).trim(),
                lang: 'sd',
                couplet_slug: data.couplet_slug,
                couplet_tags: data.couplet_tags,
                book_id: data.book_id,
                page_start: data.page_start,
                page_end: data.page_end,
                roman_content: clampCoupletToTwoLines(romanContent).trim(),
                visibility: data.visibility,
                is_featured: data.is_featured,
            };

            if (isEdit) {
                return await api.put(`/api/admin/couplets/${id}`, payload);
            }
            return await api.post('/api/admin/couplets', payload);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['couplets']);
            navigate('/admin/couplets');
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to save couplet');
        }
    });

    const refineHesudharMutation = useMutation({
        mutationFn: async () => {
            if (isEdit) {
                const res = await api.post(`/api/admin/couplets/${id}/refine-hesudhar`);
                return res.data;
            }
            const res = await api.post('/api/admin/hesudhar/standardize', { text: coupletContent });
            return { standardized_text: res.data.standardized_text, message: 'Editor text refined with Hesudhar (not saved yet).' };
        },
        onSuccess: (data) => {
            if (data?.standardized_text != null) {
                setCoupletContent(clampCoupletToTwoLines(data.standardized_text));
                toast.success(data.message || 'Text refined. Save to keep changes.');
                return;
            }
            if (data?.couplet_text) {
                setCoupletContent(clampCoupletToTwoLines(data.couplet_text));
            }
            queryClient.invalidateQueries(['couplet', id]);
            queryClient.invalidateQueries(['couplets']);
            toast.success(data.message || 'Couplet refined and saved.');
        },
        onError: (error) => {
            toast.error(error.response?.data?.message || 'Hesudhar refine failed.');
        },
    });

    const handleRefineHesudhar = () => {
        if (!coupletContent.trim() && !isEdit) {
            toast.error('Add couplet text first.');
            return;
        }
        const msg = isEdit
            ? 'Refine this couplet with Hesudhar and update the database?'
            : 'Refine the editor text with Hesudhar? (Save afterwards to keep it.)';
        if (!window.confirm(msg)) return;
        refineHesudharMutation.mutate();
    };

    const unresolvedLughatWords = lughatRomanWords.filter(
        (w) => w.status === 'missing_word' || w.status === 'missing_roman' || w.status === 'ambiguous'
    );

    const openLughatWord = async (word) => {
        if (!word) return;
        setOpeningLughatSurface(word.surface);
        try {
            let lemmaId = word.lemma_id;
            if (!lemmaId) {
                const res = await api.post('/api/admin/lughat/lemmas/stub-from-surface', {
                    surface: word.surface,
                });
                lemmaId = res.data?.lemma_id;
                if (res.data?.created) {
                    toast.success(`Stub created for “${res.data.lemma || word.surface}”.`);
                }
            }
            if (!lemmaId) {
                toast.error('Could not open Baakh Lughat entry.');
                return;
            }
            setViewingLemmaId(lemmaId);
        } catch (error) {
            const existingId = error?.response?.data?.existing_id;
            if (existingId) {
                setViewingLemmaId(existingId);
                return;
            }
            toast.error(error.response?.data?.message || 'Failed to open Baakh Lughat entry.');
        } finally {
            setOpeningLughatSurface(null);
        }
    };

    const handleLughatCheckAgain = () => {
        runLughatRomanPipeline({
            bodyText: coupletContent,
            updateSlug: !isEdit,
        });
    };

    const showMissingHighlight = !sensePickerMode
        && !editingCoupletText
        && unresolvedLughatWords.length > 0
        && !!coupletContent.trim();

    useEffect(() => {
        if (lughatRomanReady && editingCoupletText) {
            setEditingCoupletText(false);
        }
    }, [lughatRomanReady, editingCoupletText]);

    const handleTwoLineKeyDown = (e) => {
        if (e.key !== 'Enter') return;
        const value = e.target.value ?? '';
        const pos = e.target.selectionStart ?? 0;
        const lineIndex = value.slice(0, pos).split('\n').length - 1;
        if (lineIndex >= 1) {
            e.preventDefault();
        }
    };

    const onSubmit = (data) => {
        const lines = coupletNonEmptyLineCount(coupletContent);
        if (lines !== 2) {
            toast.error('Couplet must contain exactly 2 lines.');
            return;
        }
        if (!lughatRomanReady) {
            toast.error('Add missing Baakh Lughat words (with Roman spelling) before publishing.');
            return;
        }
        mutation.mutate(data);
    };

    const lineCount = coupletNonEmptyLineCount(coupletContent);
    const canPublish = !mutation.isPending
        && lineCount === 2
        && !slugError
        && !isCheckingSlug
        && lughatRomanReady
        && !lughatRomanChecking;

    if (isMetaLoading || (isEdit && isCoupletLoading)) {
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
                            <Button
                                variant="outline"
                                type="button"
                                onClick={handleRefineHesudhar}
                                disabled={refineHesudharMutation.isPending}
                            >
                                {refineHesudharMutation.isPending
                                    ? <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    : <SpellCheck className="mr-2 h-4 w-4" />}
                                Refine Hesudhar
                            </Button>
                            <Button variant="ghost" type="button" onClick={() => navigate('/admin/couplets')}>Cancel</Button>
                            <Button type="submit" disabled={!canPublish} className="bg-primary hover:bg-primary/90 text-primary-foreground font-medium px-8">
                                {mutation.isPending ? 'Saving...' : (isEdit ? 'Update' : 'Publish')}
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div className="lg:col-span-2 space-y-0 bg-white rounded-xl shadow-sm border overflow-hidden min-h-[420px] md:min-h-[700px]">
                            <Tabs value={script} onValueChange={setScript} className="w-full">
                                <div className="flex items-center justify-between px-4 py-2 border-b bg-muted/5 sticky top-0 z-10 w-full">
                                    <TabsList className="h-9 bg-muted/50">
                                        <TabsTrigger value="perso" className="text-xs h-7 px-3 font-arabic">سنڌي (Perso)</TabsTrigger>
                                        <TabsTrigger value="roman" className="text-xs h-7 px-3 font-medium">Sindhi (roman)</TabsTrigger>
                                    </TabsList>

                                    <div className="flex items-center gap-3 text-xs text-muted-foreground/50 font-medium">
                                        <div className="flex items-center gap-1 text-xs text-muted-foreground/80 font-medium px-2 py-1 rounded bg-muted/20">
                                            {lughatRomanChecking ? (
                                                <span className="flex items-center gap-1"><Loader2 className="h-3 w-3 animate-spin" /> Checking Lughat…</span>
                                            ) : lughatRomanReady ? (
                                                <span className="flex items-center gap-1 text-green-600"><Check className="h-3 w-3" /> Lughat Roman ready</span>
                                            ) : (
                                                <span className="flex items-center gap-1 text-amber-700"><Languages className="h-3 w-3" /> Missing Lughat words</span>
                                            )}
                                        </div>
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
                                    <div className="flex items-center justify-between mb-4 gap-3 flex-wrap">
                                        <div className="flex items-center gap-2 text-xs text-muted-foreground/50 font-medium">
                                            <BookOpen className="h-3 w-3" /> <span>Independent Couplet Editor</span>
                                        </div>
                                        <div className="flex items-center gap-3 flex-wrap justify-end">
                                            {script === 'perso' && unresolvedLughatWords.length > 0 && (
                                                <>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        className="h-7 text-xs gap-1.5"
                                                        onClick={handleLughatCheckAgain}
                                                        disabled={lughatRomanChecking}
                                                    >
                                                        {lughatRomanChecking
                                                            ? <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                                            : <RefreshCw className="h-3.5 w-3.5" />}
                                                        Check Again
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant={editingCoupletText ? 'default' : 'outline'}
                                                        className="h-7 text-xs gap-1.5"
                                                        onClick={() => {
                                                            setEditingCoupletText((v) => !v);
                                                            setSensePickerMode(false);
                                                        }}
                                                    >
                                                        {editingCoupletText ? 'Show highlights' : 'Edit text'}
                                                    </Button>
                                                </>
                                            )}
                                            {script === 'perso' && (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant={sensePickerMode ? 'default' : 'outline'}
                                                    className="h-7 text-xs gap-1.5"
                                                    onClick={() => {
                                                        setSensePickerMode((v) => !v);
                                                        setEditingCoupletText(false);
                                                    }}
                                                >
                                                    <Shuffle className="h-3.5 w-3.5" />
                                                    {sensePickerMode ? 'Editing text' : 'Lughat senses'}
                                                    {senseAnnotations.length + expressionAnnotations.length > 0 && (
                                                        <span className="ml-0.5 rounded-full bg-background/20 px-1.5 text-[10px]">
                                                            {senseAnnotations.length + expressionAnnotations.length}
                                                        </span>
                                                    )}
                                                </Button>
                                            )}
                                            <div className="text-xs text-muted-foreground/50 font-medium whitespace-nowrap">
                                                <span>{lineCount.toString().padStart(2, '0')} / 02 Lines</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="pt-6">
                                        <TabsContent value="perso" className="m-0 border-0 p-0 hover:outline-none focus:outline-none focus-visible:outline-none ring-0 focus:ring-0">
                                            {sensePickerMode ? (
                                                <PoetryLughatSensePicker
                                                    content={coupletContent}
                                                    poetryId={null}
                                                    annotations={senseAnnotations}
                                                    onChange={setSenseAnnotations}
                                                    expressionAnnotations={expressionAnnotations}
                                                    onExpressionChange={setExpressionAnnotations}
                                                    contentStyle="center"
                                                />
                                            ) : (
                                                <div className="space-y-4">
                                                    {showMissingHighlight && (
                                                        <PoetryLughatMissingHighlight
                                                            content={coupletContent}
                                                            unresolvedWords={unresolvedLughatWords}
                                                            contentStyle="center"
                                                            openingSurface={openingLughatSurface}
                                                            onOpenWord={openLughatWord}
                                                        />
                                                    )}
                                                    {(!showMissingHighlight) && (
                                                    <textarea
                                                        id="couplet-editor"
                                                        dir="rtl"
                                                        lang="sd"
                                                        rows={2}
                                                        className="w-full p-0 text-2xl border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none overflow-hidden bg-transparent leading-relaxed font-arabic text-center [field-sizing:content]"
                                                        placeholder={"پهريون مصرع\nٻيون مصرع"}
                                                        value={coupletContent}
                                                        onKeyDown={handleTwoLineKeyDown}
                                                        onChange={(e) => {
                                                            setCoupletContent(clampCoupletToTwoLines(e.target.value));
                                                            e.target.style.height = 'auto';
                                                            e.target.style.height = `${e.target.scrollHeight}px`;
                                                        }}
                                                        onPaste={(e) => {
                                                            e.preventDefault();
                                                            const pasted = e.clipboardData.getData('text');
                                                            const el = e.target;
                                                            const start = el.selectionStart ?? coupletContent.length;
                                                            const end = el.selectionEnd ?? coupletContent.length;
                                                            const next = clampCoupletToTwoLines(
                                                                coupletContent.slice(0, start) + pasted + coupletContent.slice(end)
                                                            );
                                                            setCoupletContent(next);
                                                        }}
                                                    />
                                                    )}
                                                    {lineCount !== 2 && lineCount > 0 && (
                                                        <p className="text-sm text-muted-foreground mt-4 text-center">
                                                            Write exactly two lines. A third line cannot be added.
                                                        </p>
                                                    )}
                                                </div>
                                            )}
                                        </TabsContent>
                                        <TabsContent value="roman" className="m-0 border-0 p-0 hover:outline-none focus:outline-none focus-visible:outline-none ring-0 focus:ring-0">
                                            <textarea
                                                dir="ltr"
                                                rows={2}
                                                className="w-full p-0 text-xl border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none overflow-hidden bg-transparent leading-relaxed font-sans text-center [field-sizing:content]"
                                                placeholder="Transliterated text will appear here..."
                                                value={romanContent}
                                                onKeyDown={handleTwoLineKeyDown}
                                                onChange={(e) => setRomanContent(clampCoupletToTwoLines(e.target.value))}
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
                                    <Button size="sm" className="w-full" disabled={!canPublish}>
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
                                                                    ? meta?.poets?.find((poet) => poet.id.toString() === field.value)?.name
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
                                                                    {meta?.poets?.map((poet) => (
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
                                </CardContent>
                            </Card>

                            <Card className="shadow-sm">
                                <CardHeader className="py-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <Folder className="h-4 w-4" /> Topic Category
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <FormField
                                        control={form.control}
                                        name="topic_category_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <Select onValueChange={field.onChange} value={field.value || undefined}>
                                                    <FormControl>
                                                        <SelectTrigger className="font-arabic h-9">
                                                            <SelectValue placeholder="Select Topic Category (Optional)" />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent>
                                                        <SelectItem value="none">None</SelectItem>
                                                        {meta?.topic_categories?.map((cat) => (
                                                            <SelectItem key={cat.id} value={cat.id.toString()} className="font-arabic">
                                                                {cat.name}
                                                            </SelectItem>
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
                                        <BookOpen className="h-4 w-4" /> Book & Progress
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <FormField
                                        control={form.control}
                                        name="book_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-xs uppercase text-muted-foreground/50 font-bold">Select Book</FormLabel>
                                                <Popover open={openBook} onOpenChange={setOpenBook}>
                                                    <PopoverTrigger asChild>
                                                        <FormControl>
                                                            <Button
                                                                variant="outline"
                                                                role="combobox"
                                                                aria-expanded={openBook}
                                                                className={cn(
                                                                    "w-full justify-between h-8 text-xs font-normal border-muted-foreground/20",
                                                                    !field.value && "text-muted-foreground/40"
                                                                )}
                                                            >
                                                                {field.value && field.value !== 'none'
                                                                    ? meta?.books?.find((book) => book.id.toString() === field.value)?.title
                                                                    : "Select Book (Optional)"}
                                                                <ChevronsUpDown className="ml-2 h-3 w-3 shrink-0 opacity-50" />
                                                            </Button>
                                                        </FormControl>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-[300px] p-0" align="start">
                                                        <Command>
                                                            <CommandInput placeholder="Search book..." className="h-9 text-xs" />
                                                            <CommandList>
                                                                <CommandEmpty className="text-xs py-2 text-center text-muted-foreground">No book found.</CommandEmpty>
                                                                <CommandGroup>
                                                                    <CommandItem
                                                                        value="none"
                                                                        onSelect={() => {
                                                                            form.setValue("book_id", null);
                                                                            setOpenBook(false);
                                                                        }}
                                                                        className="text-xs"
                                                                    >
                                                                        None
                                                                        <Check
                                                                            className={cn(
                                                                                "ml-auto h-3 w-3",
                                                                                !field.value || field.value === 'none'
                                                                                    ? "opacity-100"
                                                                                    : "opacity-0"
                                                                            )}
                                                                        />
                                                                    </CommandItem>
                                                                    {meta?.books?.filter(b => !form.watch('poet_id') || b.poet_id.toString() === form.watch('poet_id')).map((book) => (
                                                                        <CommandItem
                                                                            value={`${book.title} ${book.id}`}
                                                                            key={book.id}
                                                                            onSelect={() => {
                                                                                form.setValue("book_id", book.id.toString());
                                                                                setOpenBook(false);
                                                                            }}
                                                                            className="text-xs"
                                                                        >
                                                                            {book.title}
                                                                            <Check
                                                                                className={cn(
                                                                                    "ml-auto h-3 w-3",
                                                                                    book.id.toString() === field.value
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
                                                {form.watch('book_id') && form.watch('book_id') !== 'none' && (
                                                    <div className="mt-1 px-2 py-1 bg-primary/5 rounded border border-primary/10 flex justify-between items-center">
                                                        <span className="text-[10px] font-medium text-primary">Pages completed:</span>
                                                        <span className="text-[10px] font-bold text-primary">
                                                            {meta?.books?.find(b => b.id.toString() === form.watch('book_id'))?.last_page || 0} / {meta?.books?.find(b => b.id.toString() === form.watch('book_id'))?.total_pages || '?'}
                                                        </span>
                                                    </div>
                                                )}
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />

                                    {form.watch('book_id') && form.watch('book_id') !== 'none' && (
                                        <div className="grid grid-cols-2 gap-3">
                                            <FormField
                                                control={form.control}
                                                name="page_start"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel className="text-[10px] uppercase text-muted-foreground/50 font-bold">Start</FormLabel>
                                                        <FormControl>
                                                            <Input {...field} type="number" className="h-7 text-xs" />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                            <FormField
                                                control={form.control}
                                                name="page_end"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel className="text-[10px] uppercase text-muted-foreground/50 font-bold">End</FormLabel>
                                                        <FormControl>
                                                            <Input {...field} type="number" className="h-7 text-xs" />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                        </div>
                                    )}
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
                                        {form.watch('couplet_tags')?.map(tagId => {
                                            let foundTag = null;
                                            if (meta?.tags) {
                                                Object.values(meta.tags).forEach(group => {
                                                    if (Array.isArray(group)) {
                                                        const t = group.find(t => t.id.toString() === tagId);
                                                        if (t) foundTag = t;
                                                    }
                                                });
                                            }
                                            return (foundTag && (
                                                <span key={tagId} className="bg-secondary text-secondary-foreground hover:bg-secondary/80 text-[10px] font-medium px-2 py-0.5 rounded-md flex items-center gap-1.5 transition-colors">
                                                    {foundTag.tag}
                                                    <Trash2 className="h-3 w-3 cursor-pointer opacity-70 hover:opacity-100" onClick={() => {
                                                        const current = form.getValues('couplet_tags');
                                                        form.setValue('couplet_tags', current.filter(id => id !== tagId));
                                                    }} />
                                                </span>
                                            ));
                                        })}
                                    </div>
                                    <FormField
                                        control={form.control}
                                        name="couplet_tags"
                                        render={({ field }) => (
                                            <FormItem className="flex flex-col">
                                                <Popover open={openTags} onOpenChange={setOpenTags}>
                                                    <PopoverTrigger asChild>
                                                        <FormControl>
                                                            <Button
                                                                variant="outline"
                                                                role="combobox"
                                                                aria-expanded={openTags}
                                                                className="w-full justify-between"
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
                                                                {meta?.tags && Object.entries(meta.tags).map(([groupName, groupTags]) => (
                                                                    Array.isArray(groupTags) && (
                                                                        <CommandGroup heading={groupName} key={groupName}>
                                                                            {groupTags.map((tag) => (
                                                                                <CommandItem
                                                                                    value={`${tag.tag} ${tag.id}`}
                                                                                    key={tag.id}
                                                                                    onSelect={() => {
                                                                                        const current = form.getValues("couplet_tags") || [];
                                                                                        const tagId = tag.id.toString();
                                                                                        if (!current.includes(tagId)) {
                                                                                            form.setValue("couplet_tags", [...current, tagId]);
                                                                                        }
                                                                                        setOpenTags(false);
                                                                                    }}
                                                                                    className="flex justify-between"
                                                                                >
                                                                                    {tag.tag}
                                                                                    <Check
                                                                                        className={cn(
                                                                                            "mr-2 h-4 w-4",
                                                                                            (form.getValues("couplet_tags") || []).includes(tag.id.toString())
                                                                                                ? "opacity-100"
                                                                                                : "opacity-0"
                                                                                        )}
                                                                                    />
                                                                                </CommandItem>
                                                                            ))}
                                                                        </CommandGroup>
                                                                    )
                                                                ))}
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
                                        <LinkIcon className="h-4 w-4" /> URL Slug
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <FormField
                                        control={form.control}
                                        name="couplet_slug"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormControl>
                                                    <div className="relative">
                                                        <Input
                                                            {...field}
                                                            className={`h-8 text-xs font-mono pr-8 ${slugError ? 'border-destructive' : ''}`}
                                                            onBlur={(e) => {
                                                                field.onBlur(e);
                                                                checkSlugUnique(e.target.value);
                                                            }}
                                                        />
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            className="absolute right-0 top-0 h-8 w-8 text-muted-foreground/50 hover:text-primary"
                                                            onClick={() => generateSlug(coupletContent)}
                                                            title="Regenerate slug from text"
                                                        >
                                                            <Languages className="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                </FormControl>
                                                <FormMessage />
                                                {slugError && <p className="text-[10px] text-destructive mt-1">{slugError}</p>}
                                                {isCheckingSlug && <p className="text-[10px] text-muted-foreground mt-1 text-primary">Checking...</p>}
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </form>
            </Form>

            <LughatLemmaEditorJsonModal
                lemmaId={viewingLemmaId}
                onClose={() => setViewingLemmaId(null)}
                onImported={() => {
                    setViewingLemmaId(null);
                    handleLughatCheckAgain();
                }}
            />
        </div >
    );
};

export default CreateCouplet;
