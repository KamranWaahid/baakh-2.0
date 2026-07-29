import React, { useState, useEffect, useCallback, useRef } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
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
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import {
    Check, ChevronsUpDown, Settings, Eye, Star, Folder, Mic2, Music2,
    Plus, Trash2, GripVertical, MessageSquare, BookOpen, Languages,
    ArrowUp, ArrowDown, Feather, Quote, Info, Link2, ExternalLink,
    ImagePlus, X, Layers, Braces,
} from 'lucide-react';
import LyricsEditorJsonModal from './LyricsEditorJsonModal';
import { SECTION_META } from './lyricsStructure';

const coverUrl = (path) => {
    if (!path) return '';
    if (/^https?:\/\//i.test(path) || path.startsWith('blob:') || path.startsWith('data:')) return path;
    return path.startsWith('/') ? path : `/${path}`;
};

const lyricsSchema = z.object({
    lyrics_title: z.string().min(2, 'Title is required'),
    lyrics_slug: z.string().min(2, 'Slug is required'),
    singer_id: z.string().optional().nullable(),
    band_id: z.string().optional().nullable(),
    genre_id: z.string().optional().nullable(),
    poetry_id: z.string().optional().nullable(),
    content_style: z.string().default('center'),
    visibility: z.boolean().default(true),
    is_featured: z.boolean().default(false),
    lyrics_info: z.string().optional(),
    source: z.string().optional(),
    music_url: z.string().optional().nullable(),
    music_title: z.string().optional().nullable(),
    music_type: z.string().optional().nullable(),
    youtube_url: z.string().optional().nullable(),
    spotify_url: z.string().optional().nullable(),
    deezer_url: z.string().optional().nullable(),
});

const detectMusicType = (url) => {
    if (!url?.trim()) return '';
    const u = url.toLowerCase();
    if (u.includes('youtube.com') || u.includes('youtu.be')) return 'youtube';
    if (/\.(mp3|m4a|ogg|wav|aac)(\?|$)/i.test(u)) return 'audio';
    return 'other';
};

const KIND_META = {
    sung: {
        label: 'Sung',
        hint: 'Main sung verse',
        icon: Music2,
    },
    couplet: {
        label: 'Couplet',
        hint: 'Couplet from a poet (start / mid song)',
        icon: Feather,
    },
    music: {
        label: 'Music',
        hint: 'Music starts / instrumental cue between lines',
        icon: Music2,
    },
    spoken: {
        label: 'Spoken',
        hint: 'Spoken lines between verses',
        icon: MessageSquare,
    },
    explanation: {
        label: 'Explanation',
        hint: 'Singer explaining a line',
        icon: Quote,
    },
    other: {
        label: 'Other',
        hint: 'Other segment',
        icon: Info,
    },
};

const emptyPart = (kind = 'sung', section = null) => ({
    _key: `${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
    kind,
    section: section || null,
    role: kind === 'couplet' ? 'intro' : (kind === 'music' ? 'mid' : 'body'),
    relation: kind === 'couplet' ? 'exact' : 'original',
    poet_id: '',
    poetry_id: '',
    poetry_title: '',
    couplet_id: '',
    source_lyrics_id: '',
    source_lyrics_title: '',
    source_part_id: '',
    text_sd: kind === 'music' ? '♪ موسيقي شروع' : '',
    text_roman: kind === 'music' ? '♪ Music starts' : '',
});

const CreateLyrics = () => {
    const { id } = useParams();
    const isEdit = !!id;
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const [romanTitle, setRomanTitle] = useState('');
    const [parts, setParts] = useState([emptyPart('sung')]);
    const [script, setScript] = useState('perso');
    const [slugError, setSlugError] = useState('');
    const [isCheckingSlug, setIsCheckingSlug] = useState(false);
    const [openSinger, setOpenSinger] = useState(false);
    const [openBand, setOpenBand] = useState(false);
    const [collaborators, setCollaborators] = useState([]);
    const [jsonModalOpen, setJsonModalOpen] = useState(false);
    const [openPoetFor, setOpenPoetFor] = useState(null);
    const [openPoetryFor, setOpenPoetryFor] = useState(null);
    const [poetrySearch, setPoetrySearch] = useState('');
    const [poetryResults, setPoetryResults] = useState([]);
    const [poetrySearching, setPoetrySearching] = useState(false);
    const [linkedPoetry, setLinkedPoetry] = useState(null); // { partKey, detail }
    const [openSongPoetry, setOpenSongPoetry] = useState(false);
    const [songPoetryMeta, setSongPoetryMeta] = useState(null); // { title, poet_name, couplets? }
    const [openLyricsFor, setOpenLyricsFor] = useState(null);
    const [lyricsSearch, setLyricsSearch] = useState('');
    const [lyricsResults, setLyricsResults] = useState([]);
    const [lyricsSearching, setLyricsSearching] = useState(false);
    const [linkedLyrics, setLinkedLyrics] = useState(null); // { partKey, detail }
    const [coverImage, setCoverImage] = useState(null);
    const [coverPreview, setCoverPreview] = useState('');
    const [removeCover, setRemoveCover] = useState(false);
    const coverInputRef = useRef(null);
    const [isTransliterating, setIsTransliterating] = useState(false);
    const [isTransliterated, setIsTransliterated] = useState(isEdit);
    const [hasSindhiChars, setHasSindhiChars] = useState(false);
    const [newSingerOpen, setNewSingerOpen] = useState(false);
    const [newSinger, setNewSinger] = useState({
        singer_name: '',
        singer_name_roman: '',
        singer_laqab: '',
        singer_laqab_roman: '',
        tagline: '',
        tagline_roman: '',
        birth_place: '',
        death_place: '',
        singer_bio: '',
        singer_bio_roman: '',
        singer_slug: '',
        date_of_birth: '',
        date_of_death: '',
        visibility: true,
        is_featured: false,
    });
    const [singerImage, setSingerImage] = useState(null);
    const [singerPreview, setSingerPreview] = useState('');

    const resetNewSinger = () => {
        setNewSinger({
            singer_name: '',
            singer_name_roman: '',
            singer_laqab: '',
            singer_laqab_roman: '',
            tagline: '',
            tagline_roman: '',
            birth_place: '',
            death_place: '',
            singer_bio: '',
            singer_bio_roman: '',
            singer_slug: '',
            date_of_birth: '',
            date_of_death: '',
            visibility: true,
            is_featured: false,
        });
        setSingerImage(null);
        if (singerPreview) URL.revokeObjectURL(singerPreview);
        setSingerPreview('');
    };

    const allowAutoUpdates = useRef(!isEdit);
    const translitTimers = useRef({});

    const form = useForm({
        resolver: zodResolver(lyricsSchema),
        defaultValues: {
            lyrics_title: '',
            lyrics_slug: '',
            singer_id: '',
            band_id: '',
            genre_id: '',
            poetry_id: '',
            content_style: 'center',
            visibility: true,
            is_featured: false,
            lyrics_info: '',
            source: '',
            music_url: '',
            music_title: '',
            music_type: '',
            youtube_url: '',
            spotify_url: '',
            deezer_url: '',
        },
    });

    const { data: meta, isLoading: isMetaLoading, refetch: refetchMeta } = useQuery({
        queryKey: ['lyrics-meta'],
        queryFn: async () => (await api.get('/api/admin/lyrics/create')).data,
    });

    const { data: lyrics, isLoading: isLyricsLoading } = useQuery({
        queryKey: ['lyrics', id],
        queryFn: async () => (await api.get(`/api/admin/lyrics/${id}`)).data,
        enabled: isEdit,
    });

    const checkSlugUnique = async (slug) => {
        if (!slug) return;
        setIsCheckingSlug(true);
        try {
            const response = await api.get('/api/admin/lyrics/check-slug', {
                params: { slug, id },
            });
            setSlugError(response.data.exists ? 'This slug is already taken.' : '');
        } catch (e) {
            console.error(e);
        } finally {
            setIsCheckingSlug(false);
        }
    };

    const transliterate = useCallback(async (text) => {
        if (!text?.trim()) return '';
        const response = await api.post('/api/admin/romanizer/transliterate', { text });
        return response.data.transliterated_text || '';
    }, []);

    const markRomanJobDone = useCallback((key) => {
        delete translitTimers.current[key];
        if (Object.keys(translitTimers.current).length === 0) {
            setIsTransliterating(false);
            setIsTransliterated(true);
        }
    }, []);

    // Title → roman + slug (same romanizer as poetry)
    const title = form.watch('lyrics_title');
    useEffect(() => {
        if (!allowAutoUpdates.current) return;
        if (!title?.trim()) {
            setRomanTitle('');
            return;
        }

        if (translitTimers.current.title) clearTimeout(translitTimers.current.title);
        setIsTransliterated(false);
        setIsTransliterating(true);

        translitTimers.current.title = setTimeout(async () => {
            try {
                const roman = await transliterate(title);
                setRomanTitle(roman);
                const slug = roman
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                if (slug) {
                    form.setValue('lyrics_slug', slug);
                    checkSlugUnique(slug);
                }
            } catch (e) {
                console.error(e);
            } finally {
                markRomanJobDone('title');
            }
        }, 400);

        return () => {
            if (translitTimers.current.title) {
                clearTimeout(translitTimers.current.title);
                delete translitTimers.current.title;
            }
        };
    }, [title, transliterate, markRomanJobDone, form]);

    // Auto-romanize each part's Sindhi text (same romanizer as poetry)
    const schedulePartRoman = useCallback((key, textSd) => {
        if (!allowAutoUpdates.current) return;
        if (translitTimers.current[key]) clearTimeout(translitTimers.current[key]);

        if (!textSd?.trim()) {
            setParts((prev) => prev.map((p) => (p._key === key ? { ...p, text_roman: '' } : p)));
            markRomanJobDone(key);
            return;
        }

        setIsTransliterated(false);
        setIsTransliterating(true);

        translitTimers.current[key] = setTimeout(async () => {
            try {
                const roman = await transliterate(textSd);
                setParts((prev) => prev.map((p) => (p._key === key ? { ...p, text_roman: roman } : p)));
            } catch (e) {
                console.error(e);
            } finally {
                markRomanJobDone(key);
            }
        }, 300);
    }, [transliterate, markRomanJobDone]);

    const setPartSindhi = useCallback((key, textSd) => {
        setParts((prev) => prev.map((p) => (p._key === key ? { ...p, text_sd: textSd } : p)));
        schedulePartRoman(key, textSd);
    }, [schedulePartRoman]);

    // Fill missing roman when opening Roman tab
    useEffect(() => {
        if (script !== 'roman' || !allowAutoUpdates.current) return;
        parts.forEach((p) => {
            if (p.text_sd?.trim() && !p.text_roman?.trim()) {
                schedulePartRoman(p._key, p.text_sd);
            }
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [script]);

    useEffect(() => {
        const sindhiRegex = /[\u0600-\u06FF]/;
        const anyRomanHasSindhi = parts.some((p) => sindhiRegex.test(p.text_roman || ''));
        setHasSindhiChars(anyRomanHasSindhi || sindhiRegex.test(romanTitle));
    }, [parts, romanTitle]);

    useEffect(() => {
        if (!isEdit || !lyrics) return;

        const perso = lyrics.translations?.find((t) => t.lang === 'sd') || lyrics.translations?.[0];
        const roman = lyrics.translations?.find((t) => t.lang === 'en');

        form.reset({
            lyrics_title: perso?.title || '',
            lyrics_slug: lyrics.lyrics_slug || '',
            singer_id: lyrics.singer_id?.toString() || '',
            band_id: lyrics.band_id?.toString() || '',
            genre_id: lyrics.genre_id?.toString() || '',
            poetry_id: lyrics.poetry_id?.toString() || '',
            content_style: lyrics.content_style || 'center',
            visibility: !!lyrics.visibility,
            is_featured: !!lyrics.is_featured,
            lyrics_info: perso?.info || '',
            source: perso?.source || '',
            music_url: lyrics.music_url || '',
            music_title: lyrics.music_title || '',
            music_type: lyrics.music_type || detectMusicType(lyrics.music_url || ''),
            youtube_url: lyrics.listen_links?.youtube || '',
            spotify_url: lyrics.listen_links?.spotify || '',
            deezer_url: lyrics.listen_links?.deezer || '',
        });
        setCollaborators(
            (lyrics.collaborators || []).map((c, i) => ({
                key: `c-${c.type}-${c.id}-${i}`,
                type: c.type || 'singer',
                id: c.id?.toString() || '',
                role: c.role || 'feat',
            })),
        );
        setRomanTitle(roman?.title || '');
        setCoverImage(null);
        setRemoveCover(false);
        setCoverPreview(lyrics.cover_image ? coverUrl(lyrics.cover_image) : '');
        if (coverInputRef.current) coverInputRef.current.value = '';

        const loaded = (lyrics.parts || []).map((p, i) => ({
            _key: `loaded-${p.id || i}`,
            kind: p.kind || 'sung',
            section: p.section || null,
            role: p.role || '',
            relation: p.relation || 'original',
            poet_id: p.poet_id?.toString() || '',
            poetry_id: p.poetry_id?.toString() || '',
            poetry_title: p.poetry?.info?.title || p.poetry?.translations?.find?.((t) => t.lang === 'sd')?.title || '',
            couplet_id: p.couplet_id?.toString() || '',
            source_lyrics_id: p.source_lyrics_id?.toString() || '',
            source_lyrics_title: p.source_lyrics?.info?.title || '',
            source_part_id: p.source_part_id?.toString() || '',
            text_sd: p.text_sd || '',
            text_roman: p.text_roman || '',
        }));
        setParts(loaded.length ? loaded : [emptyPart('sung')]);

        if (lyrics.poetry_id) {
            const title = lyrics.poetry?.info?.title
                || lyrics.poetry?.translations?.find?.((t) => t.lang === 'sd')?.title
                || `Poetry #${lyrics.poetry_id}`;
            const poetName = lyrics.poetry?.poet_details?.poet_laqab
                || lyrics.poetry?.poet_details?.poet_name
                || null;
            setSongPoetryMeta({ title, poet_name: poetName });
            api.get(`/api/admin/lyrics/poetry/${lyrics.poetry_id}/couplets`)
                .then((res) => {
                    setSongPoetryMeta({
                        title: res.data.title || title,
                        poet_name: res.data.poet_name || poetName,
                        couplets: res.data.couplets || [],
                    });
                })
                .catch(() => { /* keep basic meta */ });
        } else {
            setSongPoetryMeta(null);
        }

        // Fill poetry titles for linked parts
        loaded.filter((p) => p.poetry_id && !p.poetry_title).forEach(async (p) => {
            try {
                const res = await api.get(`/api/admin/lyrics/poetry/${p.poetry_id}/couplets`);
                updatePart(p._key, { poetry_title: res.data.title, poet_id: res.data.poet_id?.toString() || p.poet_id });
            } catch (_) { /* ignore */ }
        });

        loaded.filter((p) => p.source_lyrics_id && !p.source_lyrics_title).forEach(async (p) => {
            try {
                const res = await api.get(`/api/admin/lyrics/source/${p.source_lyrics_id}/parts`);
                updatePart(p._key, { source_lyrics_title: res.data.title });
            } catch (_) { /* ignore */ }
        });

        setTimeout(() => { allowAutoUpdates.current = true; }, 800);
    }, [isEdit, lyrics, form]);

    const mutation = useMutation({
        mutationFn: async (values) => {
            const payload = {
                ...values,
                singer_id: values.singer_id || null,
                band_id: values.band_id || null,
                genre_id: values.genre_id || null,
                poetry_id: values.poetry_id || null,
                collaborators: collaborators
                    .filter((c) => c.id)
                    .map((c, i) => ({
                        type: c.type,
                        id: Number(c.id),
                        role: c.role || 'feat',
                        sort_order: i,
                    })),
                music_url: values.music_url?.trim() || null,
                music_title: values.music_title?.trim() || null,
                music_type: values.music_type || detectMusicType(values.music_url) || null,
                youtube_url: values.youtube_url?.trim() || '',
                spotify_url: values.spotify_url?.trim() || '',
                deezer_url: values.deezer_url?.trim() || '',
                roman_title: romanTitle,
                parts: parts.map((p, i) => ({
                    sort_order: i,
                    kind: p.kind,
                    section: p.section || null,
                    role: p.role || null,
                    relation: p.relation || 'original',
                    poet_id: p.poet_id || null,
                    poetry_id: p.poetry_id || null,
                    couplet_id: p.couplet_id || null,
                    source_lyrics_id: p.source_lyrics_id || null,
                    source_part_id: p.source_part_id || null,
                    text_sd: p.text_sd,
                    text_roman: p.text_roman,
                })),
            };

            const res = isEdit
                ? await api.put(`/api/admin/lyrics/${id}`, payload)
                : await api.post('/api/admin/lyrics', payload);

            const lyricsId = isEdit ? id : res.data.id;

            if ((coverImage || removeCover) && lyricsId) {
                const fd = new FormData();
                if (coverImage) fd.append('cover_image', coverImage);
                if (removeCover) fd.append('remove_cover', '1');
                await api.post(`/api/admin/lyrics/${lyricsId}/cover`, fd);
            }

            return res;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['lyrics'] });
            navigate('/admin/lyrics');
        },
        onError: (err) => {
            alert(err.response?.data?.message || 'Failed to save lyrics');
        },
    });

    const handleCoverSelect = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!allowed.includes(file.type)) {
            alert('Please select a JPEG, PNG, or WebP image');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert('Image must be under 10MB');
            return;
        }
        setCoverImage(file);
        setRemoveCover(false);
        setCoverPreview(URL.createObjectURL(file));
    };

    const handleCoverRemove = () => {
        setCoverImage(null);
        setCoverPreview('');
        setRemoveCover(true);
        if (coverInputRef.current) coverInputRef.current.value = '';
    };

    const createSingerMutation = useMutation({
        mutationFn: async () => {
            const fd = new FormData();
            Object.entries(newSinger).forEach(([key, value]) => {
                if (value === '' || value === null || value === undefined) return;
                if (typeof value === 'boolean') {
                    fd.append(key, value ? '1' : '0');
                } else {
                    fd.append(key, value);
                }
            });
            if (singerImage) {
                fd.append('image', singerImage);
            }
            const res = await api.post('/api/admin/singers', fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            return res.data.singer;
        },
        onSuccess: (singer) => {
            refetchMeta();
            form.setValue('singer_id', singer.id.toString());
            setNewSingerOpen(false);
            resetNewSinger();
        },
        onError: (err) => {
            alert(err.response?.data?.message || 'Failed to create singer');
        },
    });

    const updatePart = (key, patch) => {
        setParts((prev) => prev.map((p) => (p._key === key ? { ...p, ...patch } : p)));
    };

    const addPart = (kind) => {
        setParts((prev) => [...prev, emptyPart(kind)]);
    };

    const insertPartAfter = (index, kind) => {
        setParts((prev) => {
            const next = [...prev];
            next.splice(index + 1, 0, emptyPart(kind));
            return next;
        });
    };

    const searchPoetry = useCallback(async (q, poetId) => {
        setPoetrySearching(true);
        try {
            const res = await api.get('/api/admin/lyrics/search-poetry', {
                params: { search: q || '', poet_id: poetId || undefined },
            });
            setPoetryResults(res.data.data || []);
        } catch (e) {
            console.error(e);
            setPoetryResults([]);
        } finally {
            setPoetrySearching(false);
        }
    }, []);

    useEffect(() => {
        if (!openPoetryFor && !openSongPoetry) return;
        const part = openPoetryFor ? parts.find((p) => p._key === openPoetryFor) : null;
        const timer = setTimeout(() => {
            searchPoetry(poetrySearch, part?.poet_id || undefined);
        }, 300);
        return () => clearTimeout(timer);
    }, [openPoetryFor, openSongPoetry, poetrySearch, searchPoetry, parts]);

    const linkSongPoetry = async (poetryItem) => {
        try {
            const res = await api.get(`/api/admin/lyrics/poetry/${poetryItem.id}/couplets`);
            const detail = res.data;
            form.setValue('poetry_id', detail.id.toString());
            setSongPoetryMeta({
                title: detail.title,
                poet_name: detail.poet_name,
                couplets: detail.couplets || [],
            });
            setOpenSongPoetry(false);
            setPoetrySearch('');
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to load poetry');
        }
    };

    const clearSongPoetry = () => {
        form.setValue('poetry_id', '');
        setSongPoetryMeta(null);
    };

    const insertSongPoetryAsParts = () => {
        const couplets = songPoetryMeta?.couplets || [];
        const poetryId = form.getValues('poetry_id');
        if (!poetryId || couplets.length === 0) {
            alert('Attach a full poem first (with couplets).');
            return;
        }
        const newParts = couplets.map((c, i) => ({
            ...emptyPart('couplet'),
            _key: `poem-${poetryId}-${c.id || i}-${Date.now()}`,
            role: i === 0 ? 'intro' : (i === couplets.length - 1 ? 'outro' : 'mid'),
            relation: 'exact',
            poetry_id: poetryId.toString(),
            poetry_title: songPoetryMeta.title || '',
            couplet_id: c.id?.toString() || '',
            text_sd: c.text_sd || '',
            text_roman: c.text_roman || '',
        }));
        setParts((prev) => {
            const onlyEmpty = prev.length === 1
                && !prev[0].text_sd?.trim()
                && !prev[0].text_roman?.trim()
                && !prev[0].poetry_id;
            return onlyEmpty ? newParts : [...prev, ...newParts];
        });
        setIsTransliterated(true);
    };

    const linkPoetryToPart = async (partKey, poetryItem) => {
        try {
            const res = await api.get(`/api/admin/lyrics/poetry/${poetryItem.id}/couplets`);
            const detail = res.data;
            updatePart(partKey, {
                poetry_id: detail.id.toString(),
                poetry_title: detail.title,
                poet_id: detail.poet_id?.toString() || '',
                kind: 'couplet',
                relation: 'exact',
            });
            setLinkedPoetry({ partKey, detail });
            setOpenPoetryFor(null);
            setPoetrySearch('');
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to load poetry');
        }
    };

    const applyCoupletFromPoetry = (partKey, couplet, relation = 'exact') => {
        const textSd = couplet.text_sd || '';
        const textRoman = couplet.text_roman || '';
        updatePart(partKey, {
            couplet_id: couplet.id.toString(),
            text_sd: textSd,
            text_roman: textRoman,
            relation,
            kind: 'couplet',
        });
        if (textSd && !textRoman) {
            schedulePartRoman(partKey, textSd);
        } else {
            setIsTransliterated(true);
        }
        setLinkedPoetry(null);
    };

    const clearPoetryLink = (partKey) => {
        updatePart(partKey, {
            poetry_id: '',
            poetry_title: '',
            couplet_id: '',
        });
    };

    const searchLyricsArchive = useCallback(async (q) => {
        setLyricsSearching(true);
        try {
            const res = await api.get('/api/admin/lyrics/search-lyrics', {
                params: { search: q || '', exclude_id: id || undefined },
            });
            setLyricsResults(res.data.data || []);
        } catch (e) {
            console.error(e);
            setLyricsResults([]);
        } finally {
            setLyricsSearching(false);
        }
    }, [id]);

    useEffect(() => {
        if (!openLyricsFor) return;
        const timer = setTimeout(() => {
            searchLyricsArchive(lyricsSearch);
        }, 300);
        return () => clearTimeout(timer);
    }, [openLyricsFor, lyricsSearch, searchLyricsArchive]);

    const linkLyricsToPart = async (partKey, lyricsItem) => {
        try {
            const res = await api.get(`/api/admin/lyrics/source/${lyricsItem.id}/parts`);
            const detail = res.data;
            updatePart(partKey, {
                source_lyrics_id: detail.id.toString(),
                source_lyrics_title: detail.title,
                source_part_id: '',
                relation: 'exact',
            });
            setLinkedLyrics({ partKey, detail });
            setOpenLyricsFor(null);
            setLyricsSearch('');
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to load lyrics');
        }
    };

    const applyPartFromLyrics = (partKey, sourcePart, relation = 'exact') => {
        const textSd = sourcePart.text_sd || '';
        const textRoman = sourcePart.text_roman || '';
        updatePart(partKey, {
            source_part_id: sourcePart.id.toString(),
            text_sd: textSd,
            text_roman: textRoman,
            kind: sourcePart.kind === 'music' ? 'music' : (sourcePart.kind === 'couplet' ? 'couplet' : 'sung'),
            relation,
        });
        if (textSd && !textRoman) {
            schedulePartRoman(partKey, textSd);
        } else {
            setIsTransliterated(true);
        }
        setLinkedLyrics(null);
    };

    const applyWholeLyrics = (partKey, detail) => {
        const textParts = (detail.parts || []).filter((p) => p.kind !== 'music' && (p.text_sd || p.text_roman));
        const all = textParts.map((p) => p.text_sd).filter(Boolean).join('\n\n');
        const allRoman = textParts.map((p) => p.text_roman).filter(Boolean).join('\n\n');
        updatePart(partKey, {
            source_part_id: '',
            text_sd: all,
            text_roman: allRoman,
            kind: 'sung',
            relation: 'exact',
        });
        if (all && !allRoman) {
            schedulePartRoman(partKey, all);
        } else {
            setIsTransliterated(true);
        }
        setLinkedLyrics(null);
    };

    const clearLyricsLink = (partKey) => {
        updatePart(partKey, {
            source_lyrics_id: '',
            source_lyrics_title: '',
            source_part_id: '',
        });
    };

    const removePart = (key) => {
        setParts((prev) => {
            if (prev.length <= 1) return prev;
            const idx = prev.findIndex((p) => p._key === key);
            if (idx === 0) return prev;
            return prev.filter((p) => p._key !== key);
        });
    };

    const movePart = (index, dir) => {
        setParts((prev) => {
            const next = [...prev];
            const target = index + dir;
            if (target < 0 || target >= next.length) return prev;
            [next[index], next[target]] = [next[target], next[index]];
            return next;
        });
    };

    const onSubmit = (values) => {
        const hasText = parts.some((p) => p.text_sd?.trim() || p.text_roman?.trim());
        if (!hasText) {
            alert('Add at least one part with text.');
            return;
        }
        mutation.mutate(values);
    };

    const poetName = (poetId) =>
        meta?.poets?.find((p) => p.id.toString() === poetId)?.name;

    const singerName = (singerId) =>
        meta?.singers?.find((s) => s.id.toString() === singerId)?.name;
    const bandName = (bandId) =>
        meta?.bands?.find((b) => b.id.toString() === bandId)?.name;
    const collabLabel = (c) => {
        if (!c.id) return 'Select…';
        if (c.type === 'band') return bandName(c.id) || `Band #${c.id}`;
        return singerName(c.id) || `Artist #${c.id}`;
    };

    if (isMetaLoading || (isEdit && isLyricsLoading)) {
        return (
            <div className="space-y-4 p-4">
                <Skeleton className="h-10 w-64" />
                <Skeleton className="h-[60vh] w-full rounded-xl" />
            </div>
        );
    }

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4 p-4 md:p-0">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 className="text-2xl md:text-3xl font-bold tracking-tight">
                            {isEdit ? 'Edit Lyrics' : 'Create Lyrics'}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {isEdit
                                ? 'Update song lyrics, music cues, and linked poetry'
                                : 'Compose a Sindhi song with sung lines, couplets, spoken parts, explanations and music cues'}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button type="button" variant="outline" onClick={() => navigate('/admin/lyrics')}>
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={mutation.isPending || !isTransliterated || isTransliterating || !!slugError || isCheckingSlug || hasSindhiChars}
                        >
                            {mutation.isPending ? 'Saving...' : (isEdit ? 'Update' : 'Publish')}
                        </Button>
                    </div>
                </div>

                {hasSindhiChars && (
                    <div className="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-2 text-sm text-destructive">
                        Roman fields still contain Perso-Arabic characters. Fix them before publishing.
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 space-y-4">
                        <div className="bg-white dark:bg-card rounded-xl border shadow-sm overflow-hidden">
                            <div className="border-b px-4 py-2 flex items-center justify-between gap-3 flex-wrap">
                                <Tabs value={script} onValueChange={setScript}>
                                    <TabsList className="h-9">
                                        <TabsTrigger value="perso" className="gap-1.5">
                                            Sindhi
                                        </TabsTrigger>
                                        <TabsTrigger value="roman" className="gap-1.5">
                                            <Languages className="h-3.5 w-3.5" /> Roman
                                        </TabsTrigger>
                                    </TabsList>
                                </Tabs>
                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <Music2 className="h-3 w-3" />
                                    <span>Baakh Lyrics Editor</span>
                                    <span className="opacity-40">·</span>
                                    <span>{String(parts.length).padStart(2, '0')} parts</span>
                                    {isTransliterating ? (
                                        <span className="text-amber-600">Transliterating…</span>
                                    ) : isTransliterated ? (
                                        <span className="text-emerald-600">Auto-Transliterated</span>
                                    ) : null}
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="h-7 text-xs ms-1"
                                        onClick={() => setJsonModalOpen(true)}
                                    >
                                        <Braces className="h-3.5 w-3.5 mr-1" />
                                        Lyrics JSON
                                    </Button>
                                </div>
                            </div>

                            <div className="p-6 md:p-8 space-y-6 max-w-3xl mx-auto w-full">
                                {script === 'perso' ? (
                                    <FormField
                                        control={form.control}
                                        name="lyrics_title"
                                        render={({ field }) => (
                                            <FormItem className="space-y-0">
                                                <FormControl>
                                                    <textarea
                                                        dir="rtl"
                                                        lang="sd"
                                                        className="w-full text-4xl md:text-5xl font-bold border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/20 resize-none min-h-[56px] leading-tight bg-transparent text-right font-arabic"
                                                        placeholder="عنوان"
                                                        {...field}
                                                        onChange={(e) => {
                                                            field.onChange(e);
                                                            e.target.style.height = 'auto';
                                                            e.target.style.height = `${e.target.scrollHeight}px`;
                                                        }}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                ) : (
                                    <textarea
                                        dir="ltr"
                                        className="w-full text-4xl md:text-5xl font-bold border-none focus:outline-none focus:ring-0 placeholder:text-muted-foreground/20 resize-none min-h-[56px] leading-tight bg-transparent text-left font-sans"
                                        placeholder="Roman title"
                                        value={romanTitle}
                                        onChange={(e) => {
                                            setRomanTitle(e.target.value);
                                            setIsTransliterated(true);
                                            e.target.style.height = 'auto';
                                            e.target.style.height = `${e.target.scrollHeight}px`;
                                        }}
                                    />
                                )}

                                {/* Song timeline */}
                                <div className="space-y-5 pt-2">
                                    {parts.map((part, index) => {
                                        const metaKind = KIND_META[part.kind] || KIND_META.other;
                                        const Icon = metaKind.icon;
                                        const isMusicCue = part.kind === 'music';

                                        if (isMusicCue) {
                                            return (
                                                <div key={part._key} className="relative py-1">
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex-1 h-px bg-border" />
                                                        <div className="flex items-center gap-2 rounded-full border border-border bg-background px-3 py-1.5">
                                                            <Music2 className="h-4 w-4 text-muted-foreground shrink-0" />
                                                            <input
                                                                dir={script === 'perso' ? 'rtl' : 'ltr'}
                                                                lang={script === 'perso' ? 'sd' : undefined}
                                                                className={cn(
                                                                    'bg-transparent border-none focus:outline-none text-sm min-w-[8rem] max-w-[16rem]',
                                                                    script === 'perso' ? 'font-arabic text-right' : 'font-sans text-left'
                                                                )}
                                                                placeholder={script === 'perso' ? '♪ موسيقي شروع' : '♪ Music starts'}
                                                                value={script === 'perso' ? part.text_sd : part.text_roman}
                                                                onChange={(e) => {
                                                                    if (script === 'perso') {
                                                                        setPartSindhi(part._key, e.target.value);
                                                                    } else {
                                                                        updatePart(part._key, { text_roman: e.target.value });
                                                                        setIsTransliterated(true);
                                                                    }
                                                                }}
                                                            />
                                                            <span className="text-[10px] text-muted-foreground tabular-nums">#{index + 1}</span>
                                                            <Button type="button" variant="ghost" size="icon" className="h-6 w-6" onClick={() => movePart(index, -1)} disabled={index === 0}>
                                                                <ArrowUp className="h-3 w-3" />
                                                            </Button>
                                                            <Button type="button" variant="ghost" size="icon" className="h-6 w-6" onClick={() => movePart(index, 1)} disabled={index === parts.length - 1}>
                                                                <ArrowDown className="h-3 w-3" />
                                                            </Button>
                                                            {index > 0 && (
                                                                <Button type="button" variant="ghost" size="icon" className="h-6 w-6 text-destructive" onClick={() => removePart(part._key)}>
                                                                    <Trash2 className="h-3 w-3" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                        <div className="flex-1 h-px bg-border" />
                                                    </div>
                                                </div>
                                            );
                                        }

                                        return (
                                            <div
                                                key={part._key}
                                                className="rounded-lg border border-border bg-card p-3 md:p-4 space-y-4"
                                            >
                                                <div className="flex items-center justify-between gap-2 flex-wrap">
                                                    <div className="flex items-center gap-2 text-sm">
                                                        <GripVertical className="h-4 w-4 text-muted-foreground/40" />
                                                        <Icon className="h-4 w-4 text-muted-foreground" />
                                                        <span className="font-medium">{metaKind.label}</span>
                                                        {part.section && SECTION_META[part.section] && (
                                                            <span className="inline-flex items-center rounded-full border bg-background px-2.5 py-0.5 text-xs font-medium">
                                                                [{SECTION_META[part.section].label}]
                                                            </span>
                                                        )}
                                                        <span className="text-[10px] text-muted-foreground/60 tabular-nums">
                                                            #{index + 1}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-7 px-2 text-xs text-muted-foreground hover:text-foreground"
                                                            title="Insert music cue after this part"
                                                            onClick={() => insertPartAfter(index, 'music')}
                                                        >
                                                            <Music2 className="h-3.5 w-3.5 mr-1" />
                                                            <span className="hidden sm:inline">Music</span>
                                                        </Button>
                                                        <Button type="button" variant="ghost" size="icon" className="h-7 w-7" onClick={() => movePart(index, -1)} disabled={index === 0}>
                                                            <ArrowUp className="h-3.5 w-3.5" />
                                                        </Button>
                                                        <Button type="button" variant="ghost" size="icon" className="h-7 w-7" onClick={() => movePart(index, 1)} disabled={index === parts.length - 1}>
                                                            <ArrowDown className="h-3.5 w-3.5" />
                                                        </Button>
                                                        {index > 0 && (
                                                            <Button type="button" variant="ghost" size="icon" className="h-7 w-7 text-destructive" onClick={() => removePart(part._key)}>
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                    <Select
                                                        value={part.section || '_none'}
                                                        onValueChange={(v) => {
                                                            if (v === '_none') {
                                                                updatePart(part._key, { section: null });
                                                                return;
                                                            }
                                                            const meta = SECTION_META[v];
                                                            updatePart(part._key, {
                                                                section: v,
                                                                ...(meta ? {
                                                                    kind: part.kind === 'couplet' ? part.kind : meta.kind,
                                                                    role: meta.role,
                                                                    ...(meta.kind === 'music' && !part.text_sd ? {
                                                                        text_sd: '♪ موسيقي شروع',
                                                                        text_roman: '♪ Music starts',
                                                                    } : {}),
                                                                } : {}),
                                                            });
                                                        }}
                                                    >
                                                        <SelectTrigger className="h-8 text-xs">
                                                            <SelectValue placeholder="Section" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="_none">Section…</SelectItem>
                                                            {Object.entries(SECTION_META).map(([k, m]) => (
                                                                <SelectItem key={k} value={k}>{m.label}</SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>

                                                    <Select
                                                        value={part.kind}
                                                        onValueChange={(v) => updatePart(part._key, {
                                                            kind: v,
                                                            relation: v === 'couplet' ? (part.relation === 'original' ? 'exact' : part.relation) : part.relation,
                                                            ...(v === 'music' && !part.text_sd ? { text_sd: '♪ موسيقي شروع', text_roman: '♪ Music starts' } : {}),
                                                        })}
                                                    >
                                                        <SelectTrigger className="h-8 text-xs">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {Object.entries(KIND_META).map(([k, m]) => (
                                                                <SelectItem key={k} value={k}>
                                                                    {m.label}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>

                                                    <Select
                                                        value={part.role || 'body'}
                                                        onValueChange={(v) => updatePart(part._key, { role: v })}
                                                    >
                                                        <SelectTrigger className="h-8 text-xs">
                                                            <SelectValue placeholder="Position" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="intro">Intro</SelectItem>
                                                            <SelectItem value="mid">Mid</SelectItem>
                                                            <SelectItem value="body">Body</SelectItem>
                                                            <SelectItem value="outro">Outro</SelectItem>
                                                            <SelectItem value="other">Other</SelectItem>
                                                        </SelectContent>
                                                    </Select>

                                                    {(part.kind === 'couplet' || part.poet_id) && (
                                                        <>
                                                            <Select
                                                                value={part.relation || 'exact'}
                                                                onValueChange={(v) => updatePart(part._key, { relation: v })}
                                                            >
                                                                <SelectTrigger className="h-8 text-xs">
                                                                    <SelectValue />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="exact">Exact</SelectItem>
                                                                    <SelectItem value="adapted">Adapted</SelectItem>
                                                                    <SelectItem value="inspired">Inspired</SelectItem>
                                                                    <SelectItem value="original">Original</SelectItem>
                                                                    <SelectItem value="unknown">Unknown</SelectItem>
                                                                </SelectContent>
                                                            </Select>

                                                            <Popover open={openPoetFor === part._key} onOpenChange={(o) => setOpenPoetFor(o ? part._key : null)}>
                                                                <PopoverTrigger asChild>
                                                                    <Button
                                                                        type="button"
                                                                        variant="outline"
                                                                        role="combobox"
                                                                        className={cn('h-8 text-xs justify-between font-arabic', !part.poet_id && 'text-muted-foreground')}
                                                                    >
                                                                        {part.poet_id ? poetName(part.poet_id) : 'Poet'}
                                                                        <ChevronsUpDown className="ml-1 h-3 w-3 shrink-0 opacity-50" />
                                                                    </Button>
                                                                </PopoverTrigger>
                                                                <PopoverContent className="w-[280px] p-0" align="start">
                                                                    <Command>
                                                                        <CommandInput placeholder="Search poet..." className="font-arabic text-right" />
                                                                        <CommandList>
                                                                            <CommandEmpty>No poet found.</CommandEmpty>
                                                                            <CommandGroup>
                                                                                <CommandItem
                                                                                    value="none"
                                                                                    onSelect={() => {
                                                                                        updatePart(part._key, { poet_id: '' });
                                                                                        setOpenPoetFor(null);
                                                                                    }}
                                                                                >
                                                                                    — No poet —
                                                                                </CommandItem>
                                                                                {meta?.poets?.map((poet) => (
                                                                                    <CommandItem
                                                                                        key={poet.id}
                                                                                        value={`${poet.name} ${poet.id}`}
                                                                                        onSelect={() => {
                                                                                            updatePart(part._key, { poet_id: poet.id.toString() });
                                                                                            setOpenPoetFor(null);
                                                                                        }}
                                                                                        className="font-arabic text-right flex flex-row-reverse justify-between"
                                                                                    >
                                                                                        {poet.name}
                                                                                        <Check className={cn('h-4 w-4', poet.id.toString() === part.poet_id ? 'opacity-100' : 'opacity-0')} />
                                                                                    </CommandItem>
                                                                                ))}
                                                                            </CommandGroup>
                                                                        </CommandList>
                                                                    </Command>
                                                                </PopoverContent>
                                                            </Popover>
                                                        </>
                                                    )}
                                                </div>

                                                {(part.kind === 'couplet' || part.kind === 'sung') && (
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Popover
                                                            open={openPoetryFor === part._key}
                                                            onOpenChange={(o) => {
                                                                setOpenPoetryFor(o ? part._key : null);
                                                                if (o) {
                                                                    setPoetrySearch('');
                                                                    searchPoetry('', part.poet_id || undefined);
                                                                }
                                                            }}
                                                        >
                                                            <PopoverTrigger asChild>
                                                                <Button type="button" variant="outline" size="sm" className="h-8 gap-1.5 text-xs">
                                                                    <BookOpen className="h-3.5 w-3.5" />
                                                                    {part.poetry_id
                                                                        ? (part.poetry_title || `Poetry #${part.poetry_id}`)
                                                                        : 'Link poetry'}
                                                                    <ChevronsUpDown className="h-3 w-3 opacity-50" />
                                                                </Button>
                                                            </PopoverTrigger>
                                                            <PopoverContent className="w-[340px] p-0" align="start">
                                                                <Command shouldFilter={false}>
                                                                    <CommandInput
                                                                        placeholder="Search poetry title or poet…"
                                                                        value={poetrySearch}
                                                                        onValueChange={setPoetrySearch}
                                                                        className="font-arabic text-right"
                                                                    />
                                                                    <CommandList>
                                                                        <CommandEmpty>
                                                                            {poetrySearching ? 'Searching…' : 'No poetry found.'}
                                                                        </CommandEmpty>
                                                                        <CommandGroup heading="Available poetry">
                                                                            {poetryResults.map((item) => (
                                                                                <CommandItem
                                                                                    key={item.id}
                                                                                    value={`${item.title} ${item.poet_name} ${item.id}`}
                                                                                    onSelect={() => linkPoetryToPart(part._key, item)}
                                                                                    className="flex flex-col items-stretch gap-0.5 py-2"
                                                                                >
                                                                                    <span className="font-arabic text-right w-full" dir="rtl">{item.title}</span>
                                                                                    <span className="text-[11px] text-muted-foreground text-right font-arabic w-full" dir="rtl">
                                                                                        {item.poet_name || 'Unknown poet'}
                                                                                    </span>
                                                                                </CommandItem>
                                                                            ))}
                                                                        </CommandGroup>
                                                                    </CommandList>
                                                                </Command>
                                                            </PopoverContent>
                                                        </Popover>

                                                        <Popover
                                                            open={openLyricsFor === part._key}
                                                            onOpenChange={(o) => {
                                                                setOpenLyricsFor(o ? part._key : null);
                                                                if (o) {
                                                                    setLyricsSearch('');
                                                                    searchLyricsArchive('');
                                                                }
                                                            }}
                                                        >
                                                            <PopoverTrigger asChild>
                                                                <Button type="button" variant="outline" size="sm" className="h-8 gap-1.5 text-xs">
                                                                    <Music2 className="h-3.5 w-3.5" />
                                                                    {part.source_lyrics_id
                                                                        ? (part.source_lyrics_title || `Lyrics #${part.source_lyrics_id}`)
                                                                        : 'Link lyrics'}
                                                                    <ChevronsUpDown className="h-3 w-3 opacity-50" />
                                                                </Button>
                                                            </PopoverTrigger>
                                                            <PopoverContent className="w-[340px] p-0" align="start">
                                                                <Command shouldFilter={false}>
                                                                    <CommandInput
                                                                        placeholder="Search song lyrics or singer…"
                                                                        value={lyricsSearch}
                                                                        onValueChange={setLyricsSearch}
                                                                        className="font-arabic text-right"
                                                                    />
                                                                    <CommandList>
                                                                        <CommandEmpty>
                                                                            {lyricsSearching ? 'Searching…' : 'No lyrics found.'}
                                                                        </CommandEmpty>
                                                                        <CommandGroup heading="Available lyrics">
                                                                            {lyricsResults.map((item) => (
                                                                                <CommandItem
                                                                                    key={item.id}
                                                                                    value={`${item.title} ${item.singer_name} ${item.id}`}
                                                                                    onSelect={() => linkLyricsToPart(part._key, item)}
                                                                                    className="flex flex-col items-stretch gap-0.5 py-2"
                                                                                >
                                                                                    <span className="font-arabic text-right w-full" dir="rtl">{item.title}</span>
                                                                                    <span className="text-[11px] text-muted-foreground w-full text-right">
                                                                                        <span className="font-arabic" dir="rtl">{item.singer_name || 'Unknown singer'}</span>
                                                                                        {item.parts_count ? ` · ${item.parts_count} parts` : ''}
                                                                                    </span>
                                                                                </CommandItem>
                                                                            ))}
                                                                        </CommandGroup>
                                                                    </CommandList>
                                                                </Command>
                                                            </PopoverContent>
                                                        </Popover>

                                                        {part.poetry_id && (
                                                            <>
                                                                <Button
                                                                    type="button"
                                                                    variant="secondary"
                                                                    size="sm"
                                                                    className="h-8 text-xs"
                                                                    onClick={async () => {
                                                                        try {
                                                                            const res = await api.get(`/api/admin/lyrics/poetry/${part.poetry_id}/couplets`);
                                                                            setLinkedPoetry({ partKey: part._key, detail: res.data });
                                                                        } catch (e) {
                                                                            alert('Failed to load couplets');
                                                                        }
                                                                    }}
                                                                >
                                                                    Pick couplet
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    className="h-8 text-xs text-muted-foreground"
                                                                    onClick={() => clearPoetryLink(part._key)}
                                                                >
                                                                    Unlink poetry
                                                                </Button>
                                                            </>
                                                        )}

                                                        {part.source_lyrics_id && (
                                                            <>
                                                                <Button
                                                                    type="button"
                                                                    variant="secondary"
                                                                    size="sm"
                                                                    className="h-8 text-xs"
                                                                    onClick={async () => {
                                                                        try {
                                                                            const res = await api.get(`/api/admin/lyrics/source/${part.source_lyrics_id}/parts`);
                                                                            setLinkedLyrics({ partKey: part._key, detail: res.data });
                                                                        } catch (e) {
                                                                            alert('Failed to load lyrics parts');
                                                                        }
                                                                    }}
                                                                >
                                                                    Pick part
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    className="h-8 text-xs text-muted-foreground"
                                                                    onClick={() => clearLyricsLink(part._key)}
                                                                >
                                                                    Unlink lyrics
                                                                </Button>
                                                            </>
                                                        )}
                                                    </div>
                                                )}

                                                {script === 'perso' ? (
                                                    <Textarea
                                                        dir="rtl"
                                                        lang="sd"
                                                        rows={4}
                                                        className={cn(
                                                            'min-h-[112px] resize-y text-lg leading-8 font-arabic text-right py-3',
                                                            part.kind === 'spoken' || part.kind === 'explanation' ? 'italic opacity-90' : ''
                                                        )}
                                                        placeholder={
                                                            part.kind === 'couplet' ? 'بيت هتي لکو…'
                                                                : part.kind === 'spoken' ? 'ڳالھہ هتي لکو…'
                                                                    : part.kind === 'explanation' ? 'وضاحت هتي لکو…'
                                                                        : 'ٻول هتي لکو…'
                                                        }
                                                        value={part.text_sd}
                                                        onChange={(e) => setPartSindhi(part._key, e.target.value)}
                                                    />
                                                ) : (
                                                    <Textarea
                                                        dir="ltr"
                                                        rows={4}
                                                        className={cn(
                                                            'min-h-[112px] resize-y text-base leading-8 font-sans text-left py-3',
                                                            part.kind === 'spoken' || part.kind === 'explanation' ? 'italic opacity-90' : ''
                                                        )}
                                                        placeholder="Auto-transliterated roman text…"
                                                        value={part.text_roman}
                                                        onChange={(e) => {
                                                            updatePart(part._key, { text_roman: e.target.value });
                                                            setIsTransliterated(true);
                                                        }}
                                                    />
                                                )}

                                                {(part.poet_id || part.poetry_id || part.source_lyrics_id) && (
                                                    <p className="text-[11px] text-muted-foreground">
                                                        {part.poetry_title && (
                                                            <span className="font-arabic" dir="rtl">Poetry: {part.poetry_title}</span>
                                                        )}
                                                        {part.poet_id && (
                                                            <span className="font-arabic" dir="rtl">
                                                                {part.poetry_title ? ' · ' : ''}
                                                                Poet: {poetName(part.poet_id)}
                                                            </span>
                                                        )}
                                                        {part.source_lyrics_title && (
                                                            <span className="font-arabic" dir="rtl">
                                                                {(part.poetry_title || part.poet_id) ? ' · ' : ''}
                                                                Lyrics: {part.source_lyrics_title}
                                                            </span>
                                                        )}
                                                        {part.relation === 'adapted' && ' · Adapted in performance'}
                                                        {part.couplet_id && ` · Couplet #${part.couplet_id}`}
                                                        {part.source_part_id && ` · Part #${part.source_part_id}`}
                                                    </p>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>

                                <div className="flex flex-wrap gap-2 pt-2 border-t">
                                    <span className="text-xs text-muted-foreground self-center mr-1">Add part:</span>
                                    {Object.entries(KIND_META).filter(([k]) => k !== 'other').map(([kind, m]) => {
                                        const Icon = m.icon;
                                        return (
                                            <Button
                                                key={kind}
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="h-8 gap-1.5"
                                                onClick={() => addPart(kind)}
                                            >
                                                <Plus className="h-3.5 w-3.5" />
                                                <Icon className="h-3.5 w-3.5" />
                                                {m.label}
                                            </Button>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
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
                                                <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                                            </div>
                                        )}
                                    />
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Star className="h-4 w-4" /> Feature
                                    </div>
                                    <FormField
                                        control={form.control}
                                        name="is_featured"
                                        render={({ field }) => (
                                            <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                                        )}
                                    />
                                </div>
                                <FormField
                                    control={form.control}
                                    name="content_style"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm">Alignment</FormLabel>
                                            <Select onValueChange={field.onChange} value={field.value}>
                                                <FormControl>
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                </FormControl>
                                                <SelectContent>
                                                    {(meta?.content_styles || ['center']).map((s) => (
                                                        <SelectItem key={s} value={s}>
                                                            {s.charAt(0).toUpperCase() + s.slice(1)}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </FormItem>
                                    )}
                                />
                            </CardContent>
                            <CardFooter className="bg-muted/10 flex justify-between py-3">
                                <Button variant="ghost" size="sm" type="button" className="text-destructive h-8" onClick={() => navigate('/admin/lyrics')}>
                                    Cancel
                                </Button>
                                <Button size="sm" type="submit" className="h-8" disabled={mutation.isPending || !isTransliterated || isTransliterating || !!slugError || isCheckingSlug || hasSindhiChars}>
                                    {mutation.isPending ? 'Saving...' : (isEdit ? 'Update' : 'Publish')}
                                </Button>
                            </CardFooter>
                        </Card>

                        <Card className="shadow-sm">
                            <CardHeader className="py-3">
                                <CardTitle className="text-sm font-medium flex items-center gap-2">
                                    <ImagePlus className="h-4 w-4" /> Cover image
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {coverPreview ? (
                                    <div className="relative group">
                                        <img
                                            src={coverPreview}
                                            alt="Lyrics cover preview"
                                            className="w-full aspect-square object-cover rounded-lg border"
                                        />
                                        <button
                                            type="button"
                                            onClick={handleCoverRemove}
                                            className="absolute top-2 right-2 bg-destructive text-destructive-foreground rounded-full p-1.5 opacity-90 group-hover:opacity-100 transition-opacity shadow"
                                            aria-label="Remove cover"
                                        >
                                            <X className="h-4 w-4" />
                                        </button>
                                    </div>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => coverInputRef.current?.click()}
                                        className="w-full aspect-square border border-dashed border-muted-foreground/30 rounded-lg flex flex-col items-center justify-center gap-2 hover:border-foreground/40 hover:bg-muted/30 transition-colors"
                                    >
                                        <ImagePlus className="h-6 w-6 text-muted-foreground" />
                                        <span className="text-sm text-muted-foreground">Upload cover</span>
                                        <span className="text-[11px] text-muted-foreground/70">JPEG, PNG or WebP · Max 10MB</span>
                                    </button>
                                )}

                                <input
                                    ref={coverInputRef}
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                    onChange={handleCoverSelect}
                                    className="hidden"
                                />

                                {coverPreview && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="w-full"
                                        onClick={() => coverInputRef.current?.click()}
                                    >
                                        Change image
                                    </Button>
                                )}
                            </CardContent>
                        </Card>

                        <Card className="shadow-sm">
                            <CardHeader className="py-3">
                                <CardTitle className="text-sm font-medium flex items-center gap-2">
                                    <Music2 className="h-4 w-4" /> Music
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <FormField
                                    control={form.control}
                                    name="music_url"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm flex items-center gap-2">
                                                <Link2 className="h-4 w-4 text-muted-foreground" /> Song URL
                                            </FormLabel>
                                            <FormControl>
                                                <Input
                                                    {...field}
                                                    value={field.value || ''}
                                                    placeholder="YouTube / mp3 / SoundCloud…"
                                                    onChange={(e) => {
                                                        field.onChange(e);
                                                        form.setValue('music_type', detectMusicType(e.target.value));
                                                    }}
                                                />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="music_title"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm">Track / video title</FormLabel>
                                            <FormControl>
                                                <Input
                                                    {...field}
                                                    value={field.value || ''}
                                                    placeholder="Optional label for this recording"
                                                />
                                            </FormControl>
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="music_type"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm">Type</FormLabel>
                                            <Select
                                                onValueChange={field.onChange}
                                                value={field.value || detectMusicType(form.watch('music_url')) || 'other'}
                                            >
                                                <FormControl>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Auto" />
                                                    </SelectTrigger>
                                                </FormControl>
                                                <SelectContent>
                                                    <SelectItem value="youtube">YouTube</SelectItem>
                                                    <SelectItem value="audio">Audio file</SelectItem>
                                                    <SelectItem value="other">Other link</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </FormItem>
                                    )}
                                />

                                {form.watch('music_url') && (
                                    <a
                                        href={form.watch('music_url')}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                                    >
                                        <ExternalLink className="h-3 w-3" />
                                        Open music link
                                    </a>
                                )}

                                <div className="space-y-3 pt-2 border-t">
                                    <p className="text-xs text-muted-foreground">Platform listen links (icons on Bol)</p>
                                    <FormField
                                        control={form.control}
                                        name="youtube_url"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-sm">YouTube</FormLabel>
                                                <FormControl>
                                                    <Input {...field} value={field.value || ''} placeholder="https://youtube.com/…" />
                                                </FormControl>
                                            </FormItem>
                                        )}
                                    />
                                    <FormField
                                        control={form.control}
                                        name="spotify_url"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-sm">Spotify</FormLabel>
                                                <FormControl>
                                                    <Input {...field} value={field.value || ''} placeholder="https://open.spotify.com/…" />
                                                </FormControl>
                                            </FormItem>
                                        )}
                                    />
                                    <FormField
                                        control={form.control}
                                        name="deezer_url"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel className="text-sm">Deezer</FormLabel>
                                                <FormControl>
                                                    <Input {...field} value={field.value || ''} placeholder="https://www.deezer.com/…" />
                                                </FormControl>
                                            </FormItem>
                                        )}
                                    />
                                </div>

                                {(() => {
                                    const url = form.watch('music_url') || '';
                                    const yt = url.match(/(?:youtu\.be\/|v=|embed\/)([A-Za-z0-9_-]{6,})/);
                                    if (!yt) return null;
                                    return (
                                        <div className="rounded-md overflow-hidden border aspect-video bg-muted">
                                            <iframe
                                                title="Music preview"
                                                src={`https://www.youtube.com/embed/${yt[1]}`}
                                                className="w-full h-full"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowFullScreen
                                            />
                                        </div>
                                    );
                                })()}
                            </CardContent>
                        </Card>

                        <Card className="shadow-sm">
                            <CardHeader className="py-3">
                                <CardTitle className="text-sm font-medium flex items-center gap-2">
                                    <Folder className="h-4 w-4" /> Meta
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <FormField
                                    control={form.control}
                                    name="singer_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm flex items-center gap-2">
                                                <Mic2 className="h-4 w-4 text-muted-foreground" /> Singer
                                            </FormLabel>
                                            <Popover open={openSinger} onOpenChange={setOpenSinger}>
                                                <PopoverTrigger asChild>
                                                    <FormControl>
                                                        <Button
                                                            variant="outline"
                                                            role="combobox"
                                                            className={cn('w-full justify-between font-arabic', !field.value && 'text-muted-foreground')}
                                                        >
                                                            {field.value ? singerName(field.value) : 'Select singer (optional)'}
                                                            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                        </Button>
                                                    </FormControl>
                                                </PopoverTrigger>
                                                <PopoverContent className="w-[300px] p-0" align="start">
                                                    <Command>
                                                        <CommandInput placeholder="Search singer..." className="font-arabic text-right" />
                                                        <CommandList>
                                                            <CommandEmpty>No singer found.</CommandEmpty>
                                                            <CommandGroup>
                                                                <CommandItem
                                                                    value="none"
                                                                    onSelect={() => {
                                                                        form.setValue('singer_id', '');
                                                                        setOpenSinger(false);
                                                                    }}
                                                                >
                                                                    — No singer —
                                                                </CommandItem>
                                                                {meta?.singers?.map((s) => (
                                                                    <CommandItem
                                                                        key={s.id}
                                                                        value={`${s.name} ${s.id}`}
                                                                        onSelect={() => {
                                                                            form.setValue('singer_id', s.id.toString());
                                                                            setOpenSinger(false);
                                                                        }}
                                                                        className="font-arabic text-right flex flex-row-reverse justify-between"
                                                                    >
                                                                        {s.name}
                                                                        <Check className={cn('h-4 w-4', s.id.toString() === field.value ? 'opacity-100' : 'opacity-0')} />
                                                                    </CommandItem>
                                                                ))}
                                                            </CommandGroup>
                                                        </CommandList>
                                                    </Command>
                                                </PopoverContent>
                                            </Popover>
                                            <Button
                                                type="button"
                                                variant="link"
                                                className="h-auto p-0 text-xs"
                                                onClick={() => setNewSingerOpen(true)}
                                            >
                                                + Add new singer
                                            </Button>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="band_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm">Band (بينڊ)</FormLabel>
                                            <Popover open={openBand} onOpenChange={setOpenBand}>
                                                <PopoverTrigger asChild>
                                                    <FormControl>
                                                        <Button
                                                            variant="outline"
                                                            role="combobox"
                                                            className={cn('w-full justify-between font-arabic', !field.value && 'text-muted-foreground')}
                                                        >
                                                            {field.value ? bandName(field.value) : 'Select band (optional)'}
                                                            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                        </Button>
                                                    </FormControl>
                                                </PopoverTrigger>
                                                <PopoverContent className="w-[300px] p-0" align="start">
                                                    <Command>
                                                        <CommandInput placeholder="Search band..." className="font-arabic text-right" />
                                                        <CommandList>
                                                            <CommandEmpty>No band found.</CommandEmpty>
                                                            <CommandGroup>
                                                                <CommandItem
                                                                    value="none"
                                                                    onSelect={() => {
                                                                        form.setValue('band_id', '');
                                                                        setOpenBand(false);
                                                                    }}
                                                                >
                                                                    — No band —
                                                                </CommandItem>
                                                                {meta?.bands?.map((b) => (
                                                                    <CommandItem
                                                                        key={b.id}
                                                                        value={`${b.name} ${b.id}`}
                                                                        onSelect={() => {
                                                                            form.setValue('band_id', b.id.toString());
                                                                            setOpenBand(false);
                                                                        }}
                                                                        className="font-arabic text-right flex flex-row-reverse justify-between"
                                                                    >
                                                                        {b.name}
                                                                        <Check className={cn('h-4 w-4', b.id.toString() === field.value ? 'opacity-100' : 'opacity-0')} />
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

                                <div className="space-y-2 rounded-md border p-3">
                                    <div className="flex items-center justify-between gap-2">
                                        <label className="text-sm font-medium">Collaborations</label>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setCollaborators((prev) => [
                                                ...prev,
                                                { key: `new-${Date.now()}`, type: 'singer', id: '', role: 'feat' },
                                            ])}
                                        >
                                            + Add
                                        </Button>
                                    </div>
                                    <p className="text-xs text-muted-foreground">Feat / with / collab artists or bands.</p>
                                    {collaborators.length === 0 && (
                                        <p className="text-xs text-muted-foreground">None yet.</p>
                                    )}
                                    {collaborators.map((c) => (
                                        <div key={c.key} className="grid gap-2 sm:grid-cols-[88px_1fr_88px_auto] items-center">
                                            <Select
                                                value={c.type}
                                                onValueChange={(v) => setCollaborators((prev) => prev.map((row) => (
                                                    row.key === c.key ? { ...row, type: v, id: '' } : row
                                                )))}
                                            >
                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="singer">Artist</SelectItem>
                                                    <SelectItem value="band">Band</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Select
                                                value={c.id || 'none'}
                                                onValueChange={(v) => setCollaborators((prev) => prev.map((row) => (
                                                    row.key === c.key ? { ...row, id: v === 'none' ? '' : v } : row
                                                )))}
                                            >
                                                <SelectTrigger className="font-arabic">
                                                    <SelectValue placeholder={collabLabel(c)} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">Select…</SelectItem>
                                                    {(c.type === 'band' ? meta?.bands : meta?.singers)?.map((item) => (
                                                        <SelectItem key={item.id} value={item.id.toString()} className="font-arabic">
                                                            {item.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <Select
                                                value={c.role || 'feat'}
                                                onValueChange={(v) => setCollaborators((prev) => prev.map((row) => (
                                                    row.key === c.key ? { ...row, role: v } : row
                                                )))}
                                            >
                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="feat">feat</SelectItem>
                                                    <SelectItem value="with">with</SelectItem>
                                                    <SelectItem value="collab">collab</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => setCollaborators((prev) => prev.filter((row) => row.key !== c.key))}
                                            >
                                                <X className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>

                                <FormField
                                    control={form.control}
                                    name="genre_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm flex items-center gap-2">
                                                <Layers className="h-4 w-4 text-muted-foreground" /> Genre
                                            </FormLabel>
                                            <Select
                                                value={field.value || 'none'}
                                                onValueChange={(v) => field.onChange(v === 'none' ? '' : v)}
                                            >
                                                <FormControl>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select genre (optional)" />
                                                    </SelectTrigger>
                                                </FormControl>
                                                <SelectContent>
                                                    <SelectItem value="none">No genre</SelectItem>
                                                    {(meta?.genres || []).map((g) => (
                                                        <SelectItem key={g.id} value={g.id.toString()}>
                                                            <span className="font-arabic" dir="rtl">{g.name}</span>
                                                            {g.name_en ? ` · ${g.name_en}` : ''}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="poetry_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm flex items-center gap-2">
                                                <Feather className="h-4 w-4 text-muted-foreground" /> Full poetry (optional)
                                            </FormLabel>
                                            <p className="text-[11px] text-muted-foreground -mt-1 mb-1">
                                                Attach a complete poem from the archive. Lyric parts stay separate — visitors can read both.
                                            </p>
                                            {field.value && songPoetryMeta ? (
                                                <div className="rounded-lg border bg-muted/30 p-3 space-y-2">
                                                    <div className="font-arabic text-sm" dir="rtl">{songPoetryMeta.title}</div>
                                                    {songPoetryMeta.poet_name && (
                                                        <div className="font-arabic text-xs text-muted-foreground" dir="rtl">
                                                            {songPoetryMeta.poet_name}
                                                        </div>
                                                    )}
                                                    <div className="text-[11px] text-muted-foreground">
                                                        {(songPoetryMeta.couplets || []).length} couplets · shown on public song page
                                                    </div>
                                                    <div className="flex flex-wrap gap-2">
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="secondary"
                                                            onClick={insertSongPoetryAsParts}
                                                            disabled={!songPoetryMeta.couplets?.length}
                                                        >
                                                            Insert as couplet parts
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => {
                                                                setOpenSongPoetry(true);
                                                                setPoetrySearch('');
                                                                searchPoetry('');
                                                            }}
                                                        >
                                                            Change
                                                        </Button>
                                                        <Button type="button" size="sm" variant="ghost" onClick={clearSongPoetry}>
                                                            Remove
                                                        </Button>
                                                    </div>
                                                </div>
                                            ) : (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    className="w-full justify-start"
                                                    onClick={() => {
                                                        setOpenSongPoetry(true);
                                                        setPoetrySearch('');
                                                        searchPoetry('');
                                                    }}
                                                >
                                                    <BookOpen className="h-4 w-4 mr-2" />
                                                    Attach full poem…
                                                </Button>
                                            )}
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="lyrics_slug"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm">URL Slug</FormLabel>
                                            <FormControl>
                                                <Input
                                                    {...field}
                                                    onBlur={(e) => checkSlugUnique(e.target.value)}
                                                    className="font-mono text-sm"
                                                />
                                            </FormControl>
                                            {slugError && <p className="text-[10px] text-destructive">{slugError}</p>}
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="source"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm">Source note</FormLabel>
                                            <FormControl>
                                                <Input {...field} placeholder="Album, cassette, book, oral…" />
                                            </FormControl>
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="lyrics_info"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel className="text-sm">Notes</FormLabel>
                                            <FormControl>
                                                <Textarea
                                                    {...field}
                                                    rows={3}
                                                    dir="rtl"
                                                    lang="sd"
                                                    className="min-h-[78px] resize-y font-arabic text-right"
                                                    placeholder="Notes…"
                                                />
                                            </FormControl>
                                        </FormItem>
                                    )}
                                />
                            </CardContent>
                        </Card>

                        <Card className="shadow-sm border-dashed">
                            <CardHeader className="py-3">
                                <CardTitle className="text-sm font-medium flex items-center gap-2">
                                    <BookOpen className="h-4 w-4" /> How lyrics work
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-xs text-muted-foreground space-y-2 leading-relaxed">
                                <p>Stack parts in song order: intro couplet, music cue, spoken line, then sung verse.</p>
                                <p>Use Link poetry for archive couplets, or Link lyrics for another song. Pick a couplet/part or use the whole text.</p>
                                <p>The poetry archive stays untouched. Lyrics only reference it.</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>

            <Dialog
                open={newSingerOpen}
                onOpenChange={(o) => {
                    setNewSingerOpen(o);
                    if (!o) resetNewSinger();
                }}
            >
                <DialogContent className="max-w-2xl max-h-[90dvh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Add singer</DialogTitle>
                        <DialogDescription>
                            Full singer profile, similar to poets. Sindhi fields are for Sindhi text only.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-5">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="sm:col-span-2">
                                <label className="text-sm font-medium">Profile image</label>
                                <Input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                    className="mt-1 focus-visible:ring-0 focus-visible:ring-offset-0"
                                    onChange={(e) => {
                                        const file = e.target.files?.[0] || null;
                                        setSingerImage(file);
                                        if (singerPreview) URL.revokeObjectURL(singerPreview);
                                        setSingerPreview(file ? URL.createObjectURL(file) : '');
                                    }}
                                />
                                {singerPreview && (
                                    <img src={singerPreview} alt="Preview" className="mt-2 h-28 w-28 rounded-md object-cover border" />
                                )}
                            </div>

                            <div>
                                <label className="text-sm font-medium">Name (Sindhi)</label>
                                <Input
                                    dir="rtl"
                                    lang="sd"
                                    className="font-arabic text-right mt-1"
                                    value={newSinger.singer_name}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, singer_name: e.target.value }))}
                                    placeholder="ڳائڻي جو نالو"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Name (roman)</label>
                                <Input
                                    className="mt-1"
                                    value={newSinger.singer_name_roman}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, singer_name_roman: e.target.value }))}
                                    placeholder="Roman name"
                                />
                            </div>

                            <div>
                                <label className="text-sm font-medium">Stage name / laqab (Sindhi)</label>
                                <Input
                                    dir="rtl"
                                    lang="sd"
                                    className="font-arabic text-right mt-1"
                                    value={newSinger.singer_laqab}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, singer_laqab: e.target.value }))}
                                    placeholder="لقب"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Stage name (roman)</label>
                                <Input
                                    className="mt-1"
                                    value={newSinger.singer_laqab_roman}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, singer_laqab_roman: e.target.value }))}
                                    placeholder="Stage name"
                                />
                            </div>

                            <div>
                                <label className="text-sm font-medium">Tagline (Sindhi)</label>
                                <Input
                                    dir="rtl"
                                    lang="sd"
                                    className="font-arabic text-right mt-1"
                                    value={newSinger.tagline}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, tagline: e.target.value }))}
                                    placeholder="مختصر تعارف"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Tagline (roman)</label>
                                <Input
                                    className="mt-1"
                                    value={newSinger.tagline_roman}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, tagline_roman: e.target.value }))}
                                    placeholder="Short tagline"
                                />
                            </div>

                            <div>
                                <label className="text-sm font-medium">Birth place</label>
                                <Input
                                    className="mt-1"
                                    value={newSinger.birth_place}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, birth_place: e.target.value }))}
                                    placeholder="City / town"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Death place</label>
                                <Input
                                    className="mt-1"
                                    value={newSinger.death_place}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, death_place: e.target.value }))}
                                    placeholder="Optional"
                                />
                            </div>

                            <div>
                                <label className="text-sm font-medium">Date of birth</label>
                                <Input
                                    type="date"
                                    className="mt-1"
                                    value={newSinger.date_of_birth}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, date_of_birth: e.target.value }))}
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Date of death</label>
                                <Input
                                    type="date"
                                    className="mt-1"
                                    value={newSinger.date_of_death}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, date_of_death: e.target.value }))}
                                />
                            </div>

                            <div className="sm:col-span-2">
                                <label className="text-sm font-medium">URL slug</label>
                                <Input
                                    className="mt-1 font-mono text-sm"
                                    value={newSinger.singer_slug}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, singer_slug: e.target.value }))}
                                    placeholder="Auto from roman name if empty"
                                />
                            </div>

                            <div className="sm:col-span-2">
                                <label className="text-sm font-medium">Bio (Sindhi)</label>
                                <Textarea
                                    dir="rtl"
                                    lang="sd"
                                    rows={3}
                                    className="mt-1 min-h-[78px] resize-y font-arabic text-right"
                                    value={newSinger.singer_bio}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, singer_bio: e.target.value }))}
                                    placeholder="سوانح حيات…"
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <label className="text-sm font-medium">Bio (roman / English)</label>
                                <Textarea
                                    rows={3}
                                    className="mt-1 min-h-[78px] resize-y"
                                    value={newSinger.singer_bio_roman}
                                    onChange={(e) => setNewSinger((s) => ({ ...s, singer_bio_roman: e.target.value }))}
                                    placeholder="Biography…"
                                />
                            </div>

                            <div className="flex items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 shadow-sm">
                                <span className="text-sm">Visible</span>
                                <Checkbox
                                    checked={newSinger.visibility}
                                    onCheckedChange={(v) => setNewSinger((s) => ({ ...s, visibility: !!v }))}
                                />
                            </div>
                            <div className="flex items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 shadow-sm">
                                <span className="text-sm">Featured</span>
                                <Checkbox
                                    checked={newSinger.is_featured}
                                    onCheckedChange={(v) => setNewSinger((s) => ({ ...s, is_featured: !!v }))}
                                />
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setNewSingerOpen(false);
                                resetNewSinger();
                            }}
                        >
                            Cancel
                        </Button>
                        <Button
                            disabled={!newSinger.singer_name.trim() || createSingerMutation.isPending}
                            onClick={() => createSingerMutation.mutate()}
                        >
                            {createSingerMutation.isPending ? 'Saving…' : 'Create singer'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={openSongPoetry} onOpenChange={(o) => {
                setOpenSongPoetry(o);
                if (!o) setPoetrySearch('');
            }}>
                <DialogContent className="max-w-lg max-h-[80vh] overflow-hidden flex flex-col">
                    <DialogHeader>
                        <DialogTitle>Attach full poetry</DialogTitle>
                        <DialogDescription>
                            Choose a poem from the archive. It will appear on the public song page alongside lyric parts.
                        </DialogDescription>
                    </DialogHeader>
                    <Input
                        value={poetrySearch}
                        onChange={(e) => setPoetrySearch(e.target.value)}
                        placeholder="Search poetry title or poet…"
                        className="mb-2"
                    />
                    <div className="overflow-y-auto space-y-1 flex-1 min-h-[200px]">
                        {poetrySearching && (
                            <p className="text-sm text-muted-foreground py-6 text-center">Searching…</p>
                        )}
                        {!poetrySearching && poetryResults.length === 0 && (
                            <p className="text-sm text-muted-foreground py-6 text-center">No poetry found.</p>
                        )}
                        {poetryResults.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                className="w-full text-right rounded-lg border p-3 hover:bg-muted/50 transition-colors"
                                onClick={() => linkSongPoetry(item)}
                            >
                                <div className="font-arabic text-sm" dir="rtl">{item.title}</div>
                                {item.poet_name && (
                                    <div className="font-arabic text-xs text-muted-foreground mt-1" dir="rtl">
                                        {item.poet_name}
                                    </div>
                                )}
                            </button>
                        ))}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpenSongPoetry(false)}>Cancel</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={!!linkedPoetry} onOpenChange={(o) => !o && setLinkedPoetry(null)}>
                <DialogContent className="max-w-lg max-h-[80vh] overflow-hidden flex flex-col">
                    <DialogHeader>
                        <DialogTitle>Pick a couplet</DialogTitle>
                        <DialogDescription>
                            <span className="font-arabic" dir="rtl">{linkedPoetry?.detail?.title}</span>
                            {linkedPoetry?.detail?.poet_name && (
                                <span className="font-arabic" dir="rtl"> — {linkedPoetry.detail.poet_name}</span>
                            )}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="overflow-y-auto space-y-2 pr-1 flex-1">
                        {(linkedPoetry?.detail?.couplets || []).length === 0 ? (
                            <p className="text-sm text-muted-foreground py-6 text-center">
                                No couplets on this poetry. Link stays; write the text yourself.
                            </p>
                        ) : (
                            linkedPoetry.detail.couplets.map((c, i) => (
                                <button
                                    key={c.id}
                                    type="button"
                                    className="w-full text-right rounded-lg border p-3 hover:bg-muted/50 transition-colors space-y-1"
                                    onClick={() => applyCoupletFromPoetry(linkedPoetry.partKey, c, 'exact')}
                                >
                                    <div className="text-[10px] text-muted-foreground text-left">#{i + 1}</div>
                                    <div className="font-arabic text-base leading-relaxed" dir="rtl">{c.text_sd}</div>
                                    {c.text_roman && (
                                        <div className="text-xs text-muted-foreground text-left font-sans" dir="ltr">{c.text_roman}</div>
                                    )}
                                </button>
                            ))
                        )}
                    </div>
                    <DialogFooter className="flex-wrap gap-2">
                        <Button variant="outline" onClick={() => setLinkedPoetry(null)}>Close</Button>
                        {linkedPoetry?.detail?.couplets?.length > 0 && (
                            <>
                                <Button
                                    variant="secondary"
                                    onClick={() => {
                                        const partKey = linkedPoetry.partKey;
                                        const detail = linkedPoetry.detail;
                                        const couplets = detail.couplets || [];
                                        const newParts = couplets.map((c, i) => ({
                                            ...emptyPart('couplet'),
                                            _key: `poem-part-${detail.id}-${c.id || i}-${Date.now()}`,
                                            role: i === 0 ? 'intro' : (i === couplets.length - 1 ? 'outro' : 'mid'),
                                            relation: 'exact',
                                            poet_id: detail.poet_id?.toString() || '',
                                            poetry_id: detail.id.toString(),
                                            poetry_title: detail.title || '',
                                            couplet_id: c.id?.toString() || '',
                                            text_sd: c.text_sd || '',
                                            text_roman: c.text_roman || '',
                                        }));
                                        setParts((prev) => {
                                            const without = prev.filter((p) => p._key !== partKey);
                                            const base = without.length ? without : [];
                                            return [...base, ...newParts];
                                        });
                                        setIsTransliterated(true);
                                        setLinkedPoetry(null);
                                    }}
                                >
                                    Insert as couplet parts
                                </Button>
                                <Button
                                    variant="secondary"
                                    onClick={() => {
                                        const partKey = linkedPoetry.partKey;
                                        const all = linkedPoetry.detail.couplets
                                            .map((c) => c.text_sd)
                                            .filter(Boolean)
                                            .join('\n\n');
                                        const allRoman = linkedPoetry.detail.couplets
                                            .map((c) => c.text_roman)
                                            .filter(Boolean)
                                            .join('\n\n');
                                        updatePart(partKey, {
                                            text_sd: all,
                                            text_roman: allRoman,
                                            couplet_id: '',
                                            relation: 'exact',
                                            kind: 'sung',
                                        });
                                        if (all && !allRoman) {
                                            schedulePartRoman(partKey, all);
                                        } else {
                                            setIsTransliterated(true);
                                        }
                                        setLinkedPoetry(null);
                                    }}
                                >
                                    Use full poem text
                                </Button>
                            </>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={!!linkedLyrics} onOpenChange={(o) => !o && setLinkedLyrics(null)}>
                <DialogContent className="max-w-lg max-h-[80vh] overflow-hidden flex flex-col">
                    <DialogHeader>
                        <DialogTitle>Pick a lyrics part</DialogTitle>
                        <DialogDescription>
                            <span className="font-arabic" dir="rtl">{linkedLyrics?.detail?.title}</span>
                            {linkedLyrics?.detail?.singer_name && (
                                <span className="font-arabic" dir="rtl"> · {linkedLyrics.detail.singer_name}</span>
                            )}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="overflow-y-auto space-y-2 pr-1 flex-1">
                        {(linkedLyrics?.detail?.parts || []).length === 0 ? (
                            <p className="text-sm text-muted-foreground py-6 text-center">
                                No parts on this lyrics entry. Link stays; write the text yourself.
                            </p>
                        ) : (
                            linkedLyrics.detail.parts.map((p, i) => (
                                <button
                                    key={p.id}
                                    type="button"
                                    className="w-full text-right rounded-lg border p-3 hover:bg-muted/50 transition-colors space-y-1"
                                    onClick={() => applyPartFromLyrics(linkedLyrics.partKey, p, 'exact')}
                                >
                                    <div className="text-[10px] text-muted-foreground text-left capitalize">
                                        #{i + 1} · {p.kind}{p.role ? ` · ${p.role}` : ''}
                                    </div>
                                    {(p.text_sd || p.text_roman) ? (
                                        <>
                                            {p.text_sd && (
                                                <div className="font-arabic text-base leading-relaxed" dir="rtl">{p.text_sd}</div>
                                            )}
                                            {p.text_roman && (
                                                <div className="text-xs text-muted-foreground text-left font-sans" dir="ltr">{p.text_roman}</div>
                                            )}
                                        </>
                                    ) : (
                                        <div className="text-xs text-muted-foreground text-left italic">Empty part</div>
                                    )}
                                </button>
                            ))
                        )}
                    </div>
                    <DialogFooter className="flex-wrap gap-2">
                        <Button variant="outline" onClick={() => setLinkedLyrics(null)}>Close</Button>
                        {linkedLyrics?.detail?.parts?.length > 0 && (
                            <Button
                                variant="secondary"
                                onClick={() => applyWholeLyrics(linkedLyrics.partKey, linkedLyrics.detail)}
                            >
                                Use whole song text
                            </Button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <LyricsEditorJsonModal
                open={jsonModalOpen}
                onClose={() => setJsonModalOpen(false)}
                lyricsId={isEdit ? id : null}
                lyricsTitle={form.watch('lyrics_title') || ''}
                romanTitle={romanTitle}
                parts={parts}
                onApply={({ parts: nextParts, lyrics_title, roman_title }) => {
                    if (nextParts?.length) setParts(nextParts);
                    if (lyrics_title) form.setValue('lyrics_title', lyrics_title);
                    if (roman_title) setRomanTitle(roman_title);
                    allowAutoUpdates.current = true;
                }}
            />
        </Form>
    );
};

export default CreateLyrics;
