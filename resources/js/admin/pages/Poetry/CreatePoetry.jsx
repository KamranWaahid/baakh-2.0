import React, { useState, useEffect, useLayoutEffect, useRef, useCallback } from 'react';
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
import { Trash2, Plus, Send, Eye, EyeOff, Star, Info, Settings, User, Folder, Tag as TagIcon, Link as LinkIcon, AlignCenter, ChevronDown, BookOpen, Bold, Italic, Strikethrough, Code, AlignLeft, AlignRight, AlignJustify, Link2, Quote, Languages, SpellCheck, Loader2, Shuffle, RefreshCw } from 'lucide-react';
import PoetryLughatSensePicker from './PoetryLughatSensePicker';
import PoetryLughatMissingHighlight from './PoetryLughatMissingHighlight';
import LughatLemmaEditorJsonModal from '../Lughat/LughatLemmaEditorJsonModal';
import { Checkbox } from '@/components/ui/checkbox';
import { Skeleton } from '@/components/ui/skeleton';
import { toast } from 'sonner';
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
    topic_category_id: z.string().optional().nullable(),
    content_style: z.string().default('center'),
    dictionary_source: z.enum(['general', 'lughat']).default('lughat'),
    visibility: z.boolean().default(true),
    is_featured: z.boolean().default(false),
    poetry_info: z.string().optional(),
    source: z.string().optional(),
    poetry_tags: z.array(z.string()).optional(),
    book_id: z.string().optional().nullable(),
    page_start: z.string().optional().nullable(),
    page_end: z.string().optional().nullable(),
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
    const [lughatRomanReady, setLughatRomanReady] = useState(false);
    const [lughatRomanWords, setLughatRomanWords] = useState([]);
    const [lughatRomanChecking, setLughatRomanChecking] = useState(false);
    const [openingLughatSurface, setOpeningLughatSurface] = useState(null);
    const [viewingLemmaId, setViewingLemmaId] = useState(null);
    const [editingPoetryText, setEditingPoetryText] = useState(false);
    const [lughatCheckNonce, setLughatCheckNonce] = useState(0);
    const [legacyRomanSnapshot, setLegacyRomanSnapshot] = useState(null);
    const [slugError, setSlugError] = useState('');
    const [isCheckingSlug, setIsCheckingSlug] = useState(false);
    const [openPoet, setOpenPoet] = useState(false);
    const [openCategory, setOpenCategory] = useState(false);
    const [openTopicCategory, setOpenTopicCategory] = useState(false);
    const [openTags, setOpenTags] = useState(false);
    const [openBook, setOpenBook] = useState(false);
    const [script, setScript] = useState('perso'); // 'perso' | 'roman'
    const [sensePickerMode, setSensePickerMode] = useState(false);
    const [senseAnnotations, setSenseAnnotations] = useState([]);
    const [expressionAnnotations, setExpressionAnnotations] = useState([]);
    const poetryEditorRef = useRef(null);
    const romanEditorRef = useRef(null);

    const autosizeTextarea = useCallback((el, { minHeight = 280 } = {}) => {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = `${Math.max(el.scrollHeight, minHeight)}px`;
    }, []);

    // Prevent auto-updates on initial load for Edit mode
    const allowAutoUpdates = useRef(!isEdit);

    // Grow editors with content (typing, paste, edit load, auto-transliteration).
    useLayoutEffect(() => {
        if (!sensePickerMode) {
            autosizeTextarea(poetryEditorRef.current, { minHeight: 280 });
        }
        autosizeTextarea(romanEditorRef.current, { minHeight: 280 });
    }, [poetryContent, transliteratedText, sensePickerMode, script, autosizeTextarea]);

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
            topic_category_id: '',
            content_style: 'center',
            dictionary_source: 'lughat',
            visibility: true,
            is_featured: false,
            poetry_info: '',
            source: '',
            poetry_tags: [],
            book_id: '',
            page_start: '',
            page_end: '',
        },
    });

    const title = form.watch('poetry_title');

    const runLughatRomanPipeline = useCallback(async ({ titleText, bodyText, updateSlug = false } = {}) => {
        const titleValue = titleText ?? form.getValues('poetry_title') ?? '';
        const bodyValue = bodyText ?? poetryContent ?? '';

        if (!titleValue.trim() && !bodyValue.trim()) {
            setTransliteratedText('');
            setRomanTitle('');
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
                    title: titleValue,
                    text: bodyValue,
                }),
                api.post('/api/admin/poetry/lughat-roman-transliterate', {
                    title: titleValue,
                    text: bodyValue,
                }),
            ]);

            const check = checkRes.data || {};
            const ready = !!check.ready;
            setLughatRomanWords(Array.isArray(check.words) ? check.words : []);
            setLughatRomanReady(ready);

            const romanBody = translitRes.data?.roman_content ?? '';
            const romanTitleValue = translitRes.data?.roman_title ?? '';

            // New poetry: always preview Lughat roman.
            // Legacy edit: keep saved Roman until every word is ready, then rebuild.
            if (!isEdit || ready || !legacyRomanSnapshot) {
                setTransliteratedText(romanBody);
                setRomanTitle(romanTitleValue);
            } else {
                setTransliteratedText(legacyRomanSnapshot.body || '');
                setRomanTitle(legacyRomanSnapshot.title || '');
            }
            setIsTransliterated(true);

            if (updateSlug && romanTitleValue && !/[\u0600-\u06FF]/.test(romanTitleValue)) {
                const slug = romanTitleValue
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                if (slug) {
                    form.setValue('poetry_slug', slug);
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
    }, [form, poetryContent, isEdit, legacyRomanSnapshot]);

    // Live Baakh Lughat check + roman for title + body
    useEffect(() => {
        if (!allowAutoUpdates.current) return;

        const timer = setTimeout(() => {
            runLughatRomanPipeline({
                titleText: title,
                bodyText: poetryContent,
                updateSlug: !isEdit,
            });
        }, 400);

        return () => clearTimeout(timer);
    }, [title, poetryContent, runLughatRomanPipeline, isEdit, lughatCheckNonce]);

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
            toast.error(error.response?.data?.message || 'Failed to open Baakh Lughat entry.');
        } finally {
            setOpeningLughatSurface(null);
        }
    };

    const handleLughatCheckAgain = () => {
        runLughatRomanPipeline({
            titleText: title,
            bodyText: poetryContent,
            updateSlug: !isEdit,
        });
    };

    const showMissingHighlight = !sensePickerMode
        && !editingPoetryText
        && unresolvedLughatWords.length > 0
        && !!poetryContent.trim();

    // When all words resolve, leave edit-text mode so the normal editor returns.
    useEffect(() => {
        if (lughatRomanReady && editingPoetryText) {
            setEditingPoetryText(false);
        }
    }, [lughatRomanReady, editingPoetryText]);

    useEffect(() => {
        if (isEdit && poetry) {
            if (poetry.sense_annotations) {
                setSenseAnnotations(
                    (poetry.sense_annotations || []).map((a) => ({
                        ...a,
                        promote: a.promoted !== false,
                    }))
                );
            }
            if (poetry.expression_annotations) {
                setExpressionAnnotations(poetry.expression_annotations || []);
            }
        }
    }, [isEdit, poetry]);

    useEffect(() => {
        if (isEdit && poetry) {
            const persoTranslation = poetry.translations?.find(t => t.lang === 'sd') || poetry.translations?.[0];
            const romanTranslation = poetry.translations?.find(t => t.lang === 'en');

            form.reset({
                poetry_title: persoTranslation?.title || '',
                poetry_slug: poetry.poetry_slug || '',
                poet_id: poetry.poet_id?.toString() || '',
                category_id: poetry.category_id?.toString() || '',
                topic_category_id: poetry.topic_category_id?.toString() || '',
                content_style: poetry.content_style || 'center',
                dictionary_source: poetry.dictionary_source === 'lughat' ? 'lughat' : 'general',
                visibility: poetry.visibility === 1,
                is_featured: poetry.is_featured === 1,
                poetry_info: persoTranslation?.info || '',
                source: persoTranslation?.source || '',
                poetry_tags: JSON.parse(poetry.poetry_tags || '[]'),
                book_id: poetry.book_id?.toString() || '',
                page_start: poetry.page_start?.toString() || '',
                page_end: poetry.page_end?.toString() || '',
            });

            // Set Roman Title
            setRomanTitle(romanTranslation?.title || '');

            // Filter and set content by language
            const persoCouplets = poetry.couplets?.filter(c => c.lang === 'sd') || [];
            // If no language specified (legacy), assume they are the main content (Perso)
            const displayPersoCouplets = persoCouplets.length > 0 ? persoCouplets : (poetry.couplets || []);

            const romanCouplets = poetry.couplets?.filter(c => c.lang === 'en') || [];

            setPoetryContent(displayPersoCouplets.map(c => c.couplet_text).join('\n\n'));
            const legacyBody = romanCouplets.map(c => c.couplet_text).join('\n\n');
            const legacyTitle = romanTranslation?.title || '';
            // Keep legacy saved Roman until Lughat validation succeeds, then rebuild.
            setLegacyRomanSnapshot({ title: legacyTitle, body: legacyBody });
            setTransliteratedText(legacyBody);
            setRomanTitle(legacyTitle);
            setIsTransliterated(true);
            setLughatRomanReady(false);

            // Enable auto-updates after initial data load, then validate vs Baakh Lughat.
            setTimeout(() => {
                allowAutoUpdates.current = true;
                setLughatCheckNonce((n) => n + 1);
            }, 800);
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

    const refineHesudharMutation = useMutation({
        mutationFn: async () => {
            if (isEdit) {
                const res = await api.post(`/api/admin/poetry/${id}/refine-hesudhar`);
                return res.data;
            }
            const res = await api.post('/api/admin/hesudhar/standardize', { text: poetryContent });
            return { standardized_text: res.data.standardized_text, message: 'Editor text refined with Hesudhar (not saved yet).' };
        },
        onSuccess: (data) => {
            if (data?.standardized_text != null) {
                setPoetryContent(data.standardized_text);
                toast.success(data.message || 'Text refined. Save to keep changes.');
                return;
            }
            queryClient.invalidateQueries(['poetry', id]);
            queryClient.invalidateQueries(['poetry']);
            toast.success(data.message || 'Poetry refined and saved.');
        },
        onError: (error) => {
            toast.error(error.response?.data?.message || 'Hesudhar refine failed.');
        },
    });

    const handleRefineHesudhar = () => {
        if (!poetryContent.trim() && !isEdit) {
            toast.error('Add poetry text first.');
            return;
        }
        const msg = isEdit
            ? 'Refine this poetry with Hesudhar and update the database?'
            : 'Refine the editor text with Hesudhar? (Save afterwards to keep it.)';
        if (!window.confirm(msg)) return;
        refineHesudharMutation.mutate();
    };
    const onSubmit = (data) => {
        const coupletTexts = poetryContent
            .split(/\n\s*\n/)
            .map(text => text.trim())
            .filter(text => text.length > 0);

        if (!lughatRomanReady) {
            toast.error('Add missing Baakh Lughat words (with Roman spelling) before publishing.');
            return;
        }

        const transformedData = {
            ...data,
            dictionary_source: data.dictionary_source || 'lughat',
            romanization_source: 'baakh_lughat',
            couplets: coupletTexts.map(text => ({ couplet_text: text })),
            roman_title: romanTitle,
            roman_content: transliteratedText
                .split(/\n\s*\n/)
                .map(text => text.trim())
                .filter(text => text.length > 0)
                .map(text => ({ couplet_text: text })),
            sense_annotations: senseAnnotations.map((a) => ({
                couplet_index: a.couplet_index,
                token_index: a.token_index,
                sense_id: a.sense_id,
                surface_form: a.surface_form,
                note: a.note || null,
                promote: a.promote !== false,
            })),
            expression_annotations: expressionAnnotations.map((a) => ({
                couplet_index: a.couplet_index,
                start_token_index: a.start_token_index,
                end_token_index: a.end_token_index,
                surface_text: a.surface_text,
                expression_type: a.expression_type || 'izafat',
                literal_gloss: a.literal_gloss || null,
                poetic_gloss: a.poetic_gloss || null,
                note: a.note || null,
            })),
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
                            <Button variant="ghost" type="button" onClick={() => navigate('/admin/poetry')}>Cancel</Button>
                            <Button
                                type="submit"
                                disabled={mutation.isPending || !isTransliterated || !!slugError || isCheckingSlug || !lughatRomanReady || lughatRomanChecking}
                                className="bg-primary hover:bg-primary/90 text-primary-foreground font-medium px-8"
                            >
                                {mutation.isPending ? 'Saving...' : (isEdit ? 'Update' : 'Publish')}
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div className="lg:col-span-2 space-y-0 bg-white rounded-xl shadow-sm border min-h-[420px] md:min-h-[560px] h-auto self-start">
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
                                            ) : isTransliterated ? (
                                                <span className="flex items-center gap-1 text-amber-700"><Languages className="h-3 w-3" /> Missing Lughat words</span>
                                            ) : (
                                                <span className="flex items-center gap-1"><Languages className="h-3 w-3" /> Waiting…</span>
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
                                            <BookOpen className="h-3 w-3" /> <span>Baakh Publishing Editor</span>
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
                                                        variant={editingPoetryText ? 'default' : 'outline'}
                                                        className="h-7 text-xs gap-1.5"
                                                        onClick={() => {
                                                            setEditingPoetryText((v) => !v);
                                                            setSensePickerMode(false);
                                                        }}
                                                    >
                                                        {editingPoetryText ? 'Show highlights' : 'Edit text'}
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
                                                        setEditingPoetryText(false);
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
                                            <div className="text-xs text-muted-foreground/50 font-medium">
                                                <span>{poetryContent.split(/\n\s*\n/).filter(text => text.trim().length > 0).length.toString().padStart(2, '0')} Couplets</span>
                                            </div>
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
                                            {sensePickerMode ? (
                                                <PoetryLughatSensePicker
                                                    content={poetryContent}
                                                    poetryId={isEdit ? id : null}
                                                    annotations={senseAnnotations}
                                                    onChange={setSenseAnnotations}
                                                    expressionAnnotations={expressionAnnotations}
                                                    onExpressionChange={setExpressionAnnotations}
                                                    contentStyle={form.watch('content_style')}
                                                />
                                            ) : showMissingHighlight ? (
                                                <div className="space-y-3">
                                                    <p className="text-xs text-amber-800/80 bg-amber-50 border border-amber-100 rounded-md px-3 py-2">
                                                        Highlighted words are missing from Baakh Lughat or have no Roman spelling.
                                                        Click the eye to open AI JSON (Copy for AI → Input JSON), same as Lughat Home.
                                                    </p>
                                                    <PoetryLughatMissingHighlight
                                                        content={poetryContent}
                                                        unresolvedWords={unresolvedLughatWords}
                                                        contentStyle={form.watch('content_style')}
                                                        openingSurface={openingLughatSurface}
                                                        onOpenWord={openLughatWord}
                                                    />
                                                </div>
                                            ) : (
                                            <textarea
                                                id="poetry-editor"
                                                ref={poetryEditorRef}
                                                dir="rtl"
                                                lang="sd"
                                                rows={8}
                                                className={`w-full p-0 text-2xl border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none overflow-hidden min-h-[280px] bg-transparent leading-relaxed font-arabic ${
                                                    form.watch('content_style') === 'center' ? 'text-center'
                                                        : form.watch('content_style') === 'start' ? 'text-right'
                                                            : form.watch('content_style') === 'end' ? 'text-left'
                                                                : form.watch('content_style') === 'left' ? 'text-left'
                                                                    : form.watch('content_style') === 'right' ? 'text-right'
                                                                        : 'text-justify'
                                                    }`}
                                                placeholder="پنهنجي شاعري هتي لکو... نئين شعر لاءِ هڪ خالي لڪير ڇڏيو."
                                                value={poetryContent}
                                                onChange={(e) => {
                                                    setPoetryContent(e.target.value);
                                                    autosizeTextarea(e.target, { minHeight: 280 });
                                                }}
                                            />
                                            )}
                                        </TabsContent>
                                        <TabsContent value="roman" className="m-0 border-0 p-0 hover:outline-none focus:outline-none focus-visible:outline-none ring-0 focus:ring-0">
                                            <textarea
                                                dir="ltr"
                                                className="w-full text-5xl font-bold border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none overflow-hidden min-h-[60px] leading-tight bg-transparent text-left font-sans mb-3"
                                                placeholder="Roman Title"
                                                value={romanTitle}
                                                onChange={(e) => {
                                                    setRomanTitle(e.target.value);
                                                    autosizeTextarea(e.target, { minHeight: 60 });
                                                }}
                                            />
                                            <textarea
                                                ref={romanEditorRef}
                                                dir="ltr"
                                                rows={8}
                                                className={`w-full p-0 text-xl border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/15 resize-none overflow-hidden min-h-[280px] bg-transparent leading-relaxed font-sans ${form.watch('content_style') === 'center' ? 'text-center' :
                                                    form.watch('content_style') === 'start' ? 'text-left' :
                                                        form.watch('content_style') === 'end' ? 'text-right' : 'text-justify'
                                                    }`}
                                                placeholder="Transliterated text will appear here..."
                                                value={transliteratedText}
                                                onChange={(e) => {
                                                    setTransliteratedText(e.target.value);
                                                    autosizeTextarea(e.target, { minHeight: 280 });
                                                }}
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
                                            render={({ field }) => {
                                                const styles = meta?.content_styles?.length
                                                    ? meta.content_styles
                                                    : ['justified', 'center', 'start', 'end'];
                                                const labels = {
                                                    center: 'Center',
                                                    start: 'Start (right in Sindhi)',
                                                    end: 'End (left in Sindhi)',
                                                    justified: 'Justified',
                                                    justify: 'Justified',
                                                    left: 'Left',
                                                    right: 'Right',
                                                };
                                                return (
                                                <FormItem>
                                                    <FormLabel className="text-sm font-medium mb-2 block">Content Alignment</FormLabel>
                                                    <Select
                                                        value={field.value || 'center'}
                                                        onValueChange={(value) => {
                                                            field.onChange(value);
                                                            form.setValue('content_style', value, {
                                                                shouldDirty: true,
                                                                shouldTouch: true,
                                                            });
                                                        }}
                                                    >
                                                        <FormControl>
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Alignment" />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent>
                                                            {styles.map((style) => (
                                                                <SelectItem key={style} value={style}>
                                                                    {labels[style] || (style.charAt(0).toUpperCase() + style.slice(1))}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <p className="text-[10px] text-muted-foreground mt-1.5">
                                                        Applies to the editor preview and the public poem body.
                                                    </p>
                                                    <FormMessage />
                                                    {slugError && <p className="text-[10px] text-destructive mt-1">{slugError}</p>}
                                                </FormItem>
                                                );
                                            }}
                                        />
                                    </div>
                                </CardContent>
                                <CardFooter className="bg-muted/10 flex flex-col gap-2 py-3 px-6">
                                    <Button
                                        variant={form.watch('dictionary_source') === 'lughat' ? 'default' : 'outline'}
                                        size="sm"
                                        type="button"
                                        className="h-8 w-full"
                                        title={
                                            form.watch('dictionary_source') === 'lughat'
                                                ? 'Public meanings use Baakh Lughat (click to switch to general dictionary)'
                                                : 'Public meanings use general dictionary (click to switch to Baakh Lughat)'
                                        }
                                        onClick={() => {
                                            const next = form.getValues('dictionary_source') === 'lughat'
                                                ? 'general'
                                                : 'lughat';
                                            form.setValue('dictionary_source', next, {
                                                shouldDirty: true,
                                                shouldTouch: true,
                                            });
                                            toast.success(
                                                next === 'lughat'
                                                    ? 'Dictionary: Baakh Lughat — save to apply on the public site'
                                                    : 'Dictionary: General — save to apply on the public site'
                                            );
                                        }}
                                    >
                                        <Shuffle className="h-3.5 w-3.5 mr-1.5 shrink-0" />
                                        Shift Dictionary
                                        {form.watch('dictionary_source') === 'lughat' ? (
                                            <span className="ml-1.5 text-[10px] opacity-80">· باک لغت</span>
                                        ) : null}
                                    </Button>
                                    <div className="flex w-full items-center justify-between gap-2">
                                        <Button variant="ghost" size="sm" type="button" className="text-destructive h-8 px-2" onClick={() => navigate('/admin/poetry')}>
                                            Cancel
                                        </Button>
                                        <Button size="sm" type="submit" className="h-8 px-4" disabled={mutation.isPending || !isTransliterated || !!slugError || isCheckingSlug || !lughatRomanReady || lughatRomanChecking}>
                                            {mutation.isPending ? 'Saving...' : (isEdit ? 'Update' : 'Publish')}
                                        </Button>
                                    </div>
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
                                                                    ? meta?.categories?.find((cat) => cat.id.toString() === field.value)?.name
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
                                                                    {meta?.categories?.map((cat) => (
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
                                        name="topic_category_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-sm font-medium flex items-center gap-2">
                                                    <BookOpen className="h-4 w-4 text-muted-foreground" /> Topic Category
                                                </FormLabel>
                                                <Popover open={openTopicCategory} onOpenChange={setOpenTopicCategory}>
                                                    <PopoverTrigger asChild>
                                                        <FormControl>
                                                            <Button
                                                                variant="outline"
                                                                role="combobox"
                                                                aria-expanded={openTopicCategory}
                                                                className={cn(
                                                                    "w-full justify-between",
                                                                    !field.value && "text-muted-foreground"
                                                                )}
                                                            >
                                                                {field.value
                                                                    ? meta?.topic_categories?.find((cat) => cat.id.toString() === field.value)?.name
                                                                    : "Select Topic"}
                                                                <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                            </Button>
                                                        </FormControl>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-[300px] p-0" align="start">
                                                        <Command>
                                                            <CommandInput placeholder="Search topic..." />
                                                            <CommandList>
                                                                <CommandEmpty>No topic found.</CommandEmpty>
                                                                <CommandGroup>
                                                                    {meta?.topic_categories?.map((cat) => (
                                                                        <CommandItem
                                                                            value={`${cat.name} ${cat.id}`}
                                                                            key={cat.id}
                                                                            onSelect={() => {
                                                                                form.setValue("topic_category_id", cat.id.toString());
                                                                                setOpenTopicCategory(false);
                                                                            }}
                                                                            className="flex justify-between"
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

                                    {/* Book Selection & Progress tracking */}
                                    <FormField
                                        control={form.control}
                                        name="book_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-sm font-medium flex items-center gap-2">
                                                    <BookOpen className="h-4 w-4 text-muted-foreground" /> Select Book
                                                </FormLabel>
                                                <Popover open={openBook} onOpenChange={setOpenBook}>
                                                    <PopoverTrigger asChild>
                                                        <FormControl>
                                                            <Button
                                                                variant="outline"
                                                                role="combobox"
                                                                aria-expanded={openBook}
                                                                className={cn(
                                                                    "w-full justify-between h-10 font-normal",
                                                                    !field.value && "text-muted-foreground/50"
                                                                )}
                                                            >
                                                                {field.value && field.value !== 'none'
                                                                    ? meta?.books?.find((book) => book.id.toString() === field.value)?.title
                                                                    : "Select Book (Optional)"}
                                                                <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                            </Button>
                                                        </FormControl>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-[400px] p-0" align="start">
                                                        <Command>
                                                            <CommandInput placeholder="Search book..." className="h-9" />
                                                            <CommandList>
                                                                <CommandEmpty>No book found.</CommandEmpty>
                                                                <CommandGroup>
                                                                    <CommandItem
                                                                        value="none"
                                                                        onSelect={() => {
                                                                            form.setValue("book_id", null);
                                                                            setOpenBook(false);
                                                                        }}
                                                                    >
                                                                        None
                                                                        <Check
                                                                            className={cn(
                                                                                "ml-auto h-4 w-4",
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
                                                                        >
                                                                            {book.title}
                                                                            <Check
                                                                                className={cn(
                                                                                    "ml-auto h-4 w-4",
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
                                                    <div className="mt-1 px-2 py-1 bg-primary/5 rounded border border-primary/10 flex justify-between items-center animate-in fade-in slide-in-from-top-1">
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
                                        <div className="grid grid-cols-2 gap-4 animate-in fade-in slide-in-from-top-2">
                                            <FormField
                                                control={form.control}
                                                name="page_start"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel className="text-[11px] font-medium">Page Start</FormLabel>
                                                        <FormControl>
                                                            <Input {...field} type="number" className="h-8 text-xs" placeholder="e.g. 12" />
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
                                                        <FormLabel className="text-[11px] font-medium">Page End</FormLabel>
                                                        <FormControl>
                                                            <Input {...field} type="number" className="h-8 text-xs" placeholder="e.g. 15" />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                        </div>
                                    )}

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
                                                        const current = form.getValues('poetry_tags');
                                                        form.setValue('poetry_tags', current.filter(id => id !== tagId));
                                                    }} />
                                                </span>
                                            ));
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
                                                                {meta?.tags && Object.entries(meta.tags).map(([groupName, groupTags]) => (
                                                                    Array.isArray(groupTags) && (
                                                                        <CommandGroup heading={groupName} key={groupName}>
                                                                            {groupTags.map((tag) => (
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
                                                                                    className="flex justify-between"
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
        </div>
    );
};

export default CreatePoetry;
