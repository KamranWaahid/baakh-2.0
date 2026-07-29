import React, { useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Skeleton } from '@/components/ui/skeleton';
import { ImagePlus, Users, X } from 'lucide-react';

const emptyForm = {
    band_name: '',
    band_name_roman: '',
    tagline: '',
    tagline_roman: '',
    band_bio: '',
    band_bio_roman: '',
    band_slug: '',
    formed_year: '',
    visibility: true,
    is_featured: false,
    youtube_url: '',
    spotify_url: '',
    deezer_url: '',
};

const picUrl = (path) => {
    if (!path) return '';
    if (/^https?:\/\//i.test(path) || path.startsWith('blob:') || path.startsWith('/')) return path;
    return `/${path}`;
};

const CreateBand = () => {
    const { id } = useParams();
    const isEdit = !!id;
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const fileInputRef = useRef(null);

    const [form, setForm] = useState(emptyForm);
    const [imageFile, setImageFile] = useState(null);
    const [imagePreview, setImagePreview] = useState('');
    const [removeImage, setRemoveImage] = useState(false);
    const [slugError, setSlugError] = useState('');
    const [singerIds, setSingerIds] = useState([]);

    const { data: band, isLoading } = useQuery({
        queryKey: ['band', id],
        queryFn: async () => (await api.get(`/api/admin/bands/${id}`)).data,
        enabled: isEdit,
    });

    const { data: singersData } = useQuery({
        queryKey: ['singers-options'],
        queryFn: async () => (await api.get('/api/admin/singers', { params: { per_page: 100 } })).data,
    });

    const singers = singersData?.data || [];

    useEffect(() => {
        if (!band) return;
        setForm({
            band_name: band.band_name || '',
            band_name_roman: band.band_name_roman || '',
            tagline: band.tagline || '',
            tagline_roman: band.tagline_roman || '',
            band_bio: band.band_bio || '',
            band_bio_roman: band.band_bio_roman || '',
            band_slug: band.band_slug || '',
            formed_year: band.formed_year?.toString() || '',
            visibility: !!band.visibility,
            is_featured: !!band.is_featured,
            youtube_url: band.youtube_url || band.listen_links?.youtube || '',
            spotify_url: band.spotify_url || band.listen_links?.spotify || '',
            deezer_url: band.deezer_url || band.listen_links?.deezer || '',
        });
        setSingerIds((band.singer_ids || []).map((n) => n.toString()));
        setImagePreview(band.band_pic ? picUrl(band.band_pic) : '');
        setImageFile(null);
        setRemoveImage(false);
    }, [band]);

    const setField = (key, value) => setForm((prev) => ({ ...prev, [key]: value }));

    const toggleSinger = (sid) => {
        const key = sid.toString();
        setSingerIds((prev) => (prev.includes(key) ? prev.filter((x) => x !== key) : [...prev, key]));
    };

    const checkSlug = async (slug) => {
        if (!slug?.trim()) {
            setSlugError('');
            return;
        }
        try {
            const res = await api.get('/api/admin/bands/check-slug', {
                params: { slug, id: isEdit ? id : undefined },
            });
            setSlugError(res.data.exists ? 'Slug already taken' : '');
        } catch (_) {
            setSlugError('');
        }
    };

    const mutation = useMutation({
        mutationFn: async () => {
            const fd = new FormData();
            Object.entries(form).forEach(([key, value]) => {
                if (['youtube_url', 'spotify_url', 'deezer_url'].includes(key)) {
                    fd.append(key, value || '');
                    return;
                }
                if (value === '' || value === null || value === undefined) return;
                if (typeof value === 'boolean') {
                    fd.append(key, value ? '1' : '0');
                } else {
                    fd.append(key, value);
                }
            });
            fd.append('sync_members', '1');
            singerIds.forEach((sid) => {
                fd.append('singer_ids[]', sid);
            });
            if (imageFile) fd.append('image', imageFile);
            if (removeImage) fd.append('remove_image', '1');

            if (isEdit) {
                fd.append('_method', 'PUT');
                return api.post(`/api/admin/bands/${id}`, fd);
            }
            return api.post('/api/admin/bands', fd);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['bands'] });
            navigate('/admin/bands');
        },
    });

    if (isEdit && isLoading) {
        return <div className="space-y-4"><Skeleton className="h-10 w-48" /><Skeleton className="h-64 w-full" /></div>;
    }

    return (
        <div className="max-w-3xl mx-auto space-y-6">
            <div>
                <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
                    <Users className="h-6 w-6" />
                    {isEdit ? 'Edit band' : 'Create band'}
                </h1>
                <p className="text-sm text-muted-foreground">بينڊ — attach member artists and later link lyrics.</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Details</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Name (Sindhi)</label>
                            <Input className="font-arabic text-right" dir="rtl" value={form.band_name} onChange={(e) => setField('band_name', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Name (Roman)</label>
                            <Input value={form.band_name_roman} onChange={(e) => setField('band_name_roman', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Tagline (Sindhi)</label>
                            <Input className="font-arabic text-right" dir="rtl" value={form.tagline} onChange={(e) => setField('tagline', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Slug</label>
                            <Input
                                value={form.band_slug}
                                onChange={(e) => setField('band_slug', e.target.value)}
                                onBlur={() => checkSlug(form.band_slug)}
                            />
                            {slugError && <p className="text-xs text-destructive">{slugError}</p>}
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Formed year</label>
                            <Input type="number" value={form.formed_year} onChange={(e) => setField('formed_year', e.target.value)} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">Bio (Sindhi)</label>
                        <Textarea className="font-arabic text-right min-h-[100px]" dir="rtl" value={form.band_bio} onChange={(e) => setField('band_bio', e.target.value)} />
                    </div>

                    <div className="flex flex-wrap gap-6">
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox checked={form.visibility} onCheckedChange={(v) => setField('visibility', !!v)} /> Visible
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox checked={form.is_featured} onCheckedChange={(v) => setField('is_featured', !!v)} /> Featured
                        </label>
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">Image</label>
                        <div className="flex items-center gap-3">
                            <div className="h-20 w-20 rounded-md bg-muted overflow-hidden flex items-center justify-center">
                                {imagePreview
                                    ? <img src={imagePreview} alt="" className="h-full w-full object-cover" />
                                    : <ImagePlus className="h-6 w-6 text-muted-foreground" />}
                            </div>
                            <div className="space-y-2">
                                <Input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => {
                                        const file = e.target.files?.[0];
                                        setImageFile(file || null);
                                        setRemoveImage(false);
                                        setImagePreview(file ? URL.createObjectURL(file) : '');
                                    }}
                                />
                                {imagePreview && (
                                    <Button type="button" variant="ghost" size="sm" onClick={() => {
                                        setImageFile(null);
                                        setImagePreview('');
                                        setRemoveImage(true);
                                        if (fileInputRef.current) fileInputRef.current.value = '';
                                    }}>
                                        <X className="h-4 w-4 mr-1" /> Remove
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Listen</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-1">
                    <div>
                        <label className="text-sm font-medium">YouTube</label>
                        <Input
                            className="mt-1"
                            value={form.youtube_url}
                            onChange={(e) => setField('youtube_url', e.target.value)}
                            placeholder="https://youtube.com/…"
                        />
                    </div>
                    <div>
                        <label className="text-sm font-medium">Spotify</label>
                        <Input
                            className="mt-1"
                            value={form.spotify_url}
                            onChange={(e) => setField('spotify_url', e.target.value)}
                            placeholder="https://open.spotify.com/…"
                        />
                    </div>
                    <div>
                        <label className="text-sm font-medium">Deezer</label>
                        <Input
                            className="mt-1"
                            value={form.deezer_url}
                            onChange={(e) => setField('deezer_url', e.target.value)}
                            placeholder="https://www.deezer.com/…"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Member artists</CardTitle>
                </CardHeader>
                <CardContent className="space-y-2 max-h-72 overflow-auto">
                    {singers.length === 0 && (
                        <p className="text-sm text-muted-foreground">No artists available. Create artists first.</p>
                    )}
                    {singers.map((s) => {
                        const sid = s.id.toString();
                        const checked = singerIds.includes(sid);
                        return (
                            <label key={s.id} className="flex items-center gap-3 rounded-md border px-3 py-2 text-sm">
                                <Checkbox checked={checked} onCheckedChange={() => toggleSinger(s.id)} />
                                <span className="font-arabic">{s.singer_name || s.singer_slug}</span>
                            </label>
                        );
                    })}
                </CardContent>
                <CardFooter className="justify-between">
                    <Button type="button" variant="outline" onClick={() => navigate('/admin/bands')}>Cancel</Button>
                    <Button
                        type="button"
                        disabled={!form.band_name.trim() || !!slugError || mutation.isPending}
                        onClick={() => mutation.mutate()}
                    >
                        {mutation.isPending ? 'Saving…' : (isEdit ? 'Update band' : 'Create band')}
                    </Button>
                </CardFooter>
            </Card>

            {mutation.isError && (
                <p className="text-sm text-destructive">
                    {mutation.error?.response?.data?.message || 'Failed to save band'}
                </p>
            )}
        </div>
    );
};

export default CreateBand;
