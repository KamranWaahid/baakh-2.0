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
import { ImagePlus, Mic2, X } from 'lucide-react';

const emptyForm = {
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
};

const picUrl = (path) => {
    if (!path) return '';
    if (/^https?:\/\//i.test(path) || path.startsWith('blob:') || path.startsWith('/')) return path;
    return `/${path}`;
};

const CreateSinger = () => {
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

    const { data: singer, isLoading } = useQuery({
        queryKey: ['singer', id],
        queryFn: async () => {
            const res = await api.get(`/api/admin/singers/${id}`);
            return res.data;
        },
        enabled: isEdit,
    });

    useEffect(() => {
        if (!singer) return;
        setForm({
            singer_name: singer.singer_name || '',
            singer_name_roman: singer.singer_name_roman || '',
            singer_laqab: singer.singer_laqab || '',
            singer_laqab_roman: singer.singer_laqab_roman || '',
            tagline: singer.tagline || '',
            tagline_roman: singer.tagline_roman || '',
            birth_place: singer.birth_place || '',
            death_place: singer.death_place || '',
            singer_bio: singer.singer_bio || '',
            singer_bio_roman: singer.singer_bio_roman || '',
            singer_slug: singer.singer_slug || '',
            date_of_birth: singer.date_of_birth || '',
            date_of_death: singer.date_of_death || '',
            visibility: !!singer.visibility,
            is_featured: !!singer.is_featured,
        });
        setImagePreview(singer.singer_pic ? picUrl(singer.singer_pic) : '');
        setImageFile(null);
        setRemoveImage(false);
    }, [singer]);

    const setField = (key, value) => setForm((prev) => ({ ...prev, [key]: value }));

    const checkSlug = async (slug) => {
        if (!slug?.trim()) {
            setSlugError('');
            return;
        }
        try {
            const res = await api.get('/api/admin/singers/check-slug', {
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
                if (value === '' || value === null || value === undefined) return;
                if (typeof value === 'boolean') {
                    fd.append(key, value ? '1' : '0');
                } else {
                    fd.append(key, value);
                }
            });
            if (imageFile) fd.append('image', imageFile);
            if (removeImage) fd.append('remove_image', '1');

            if (isEdit) {
                fd.append('_method', 'PUT');
                return api.post(`/api/admin/singers/${id}`, fd);
            }
            return api.post('/api/admin/singers', fd);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['singers'] });
            navigate('/admin/singers');
        },
        onError: (err) => {
            alert(err.response?.data?.message || 'Failed to save singer');
        },
    });

    const handleImageSelect = (e) => {
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
        setImageFile(file);
        setRemoveImage(false);
        setImagePreview(URL.createObjectURL(file));
    };

    const handleImageRemove = () => {
        setImageFile(null);
        setImagePreview('');
        setRemoveImage(true);
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    const onSubmit = (e) => {
        e.preventDefault();
        if (!form.singer_name.trim()) {
            alert('Sindhi name is required');
            return;
        }
        if (slugError) return;
        mutation.mutate();
    };

    if (isEdit && isLoading) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-10 w-48" />
                <Skeleton className="h-64 w-full" />
            </div>
        );
    }

    return (
        <form onSubmit={onSubmit} className="min-w-0 w-full max-w-full space-y-4 p-0">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="space-y-1">
                    <h2 className="text-2xl font-bold tracking-tight md:text-3xl">
                        {isEdit ? 'Edit Singer' : 'Add Singer'}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Singer profile for lyrics. Sindhi fields are for Sindhi text only.
                    </p>
                </div>
                <div className="flex gap-2">
                    <Button type="button" variant="outline" onClick={() => navigate('/admin/singers')}>
                        Cancel
                    </Button>
                    <Button type="submit" disabled={mutation.isPending || !!slugError}>
                        {mutation.isPending ? 'Saving…' : (isEdit ? 'Update' : 'Create')}
                    </Button>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="space-y-4 lg:col-span-2">
                    <Card>
                        <CardHeader className="py-3">
                            <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                <Mic2 className="h-4 w-4" /> Profile
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label className="text-sm font-medium">Name (Sindhi)</label>
                                <Input
                                    dir="rtl"
                                    lang="sd"
                                    className="mt-1 font-arabic text-right"
                                    value={form.singer_name}
                                    onChange={(e) => setField('singer_name', e.target.value)}
                                    placeholder="ڳائڻي جو نالو"
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Name (roman)</label>
                                <Input
                                    className="mt-1"
                                    value={form.singer_name_roman}
                                    onChange={(e) => setField('singer_name_roman', e.target.value)}
                                    placeholder="Roman name"
                                />
                            </div>

                            <div>
                                <label className="text-sm font-medium">Stage name / laqab (Sindhi)</label>
                                <Input
                                    dir="rtl"
                                    lang="sd"
                                    className="mt-1 font-arabic text-right"
                                    value={form.singer_laqab}
                                    onChange={(e) => setField('singer_laqab', e.target.value)}
                                    placeholder="لقب"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Stage name (roman)</label>
                                <Input
                                    className="mt-1"
                                    value={form.singer_laqab_roman}
                                    onChange={(e) => setField('singer_laqab_roman', e.target.value)}
                                    placeholder="Stage name"
                                />
                            </div>

                            <div>
                                <label className="text-sm font-medium">Tagline (Sindhi)</label>
                                <Input
                                    dir="rtl"
                                    lang="sd"
                                    className="mt-1 font-arabic text-right"
                                    value={form.tagline}
                                    onChange={(e) => setField('tagline', e.target.value)}
                                    placeholder="مختصر تعارف"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Tagline (roman)</label>
                                <Input
                                    className="mt-1"
                                    value={form.tagline_roman}
                                    onChange={(e) => setField('tagline_roman', e.target.value)}
                                    placeholder="Short tagline"
                                />
                            </div>

                            <div>
                                <label className="text-sm font-medium">Birth place</label>
                                <Input
                                    className="mt-1"
                                    value={form.birth_place}
                                    onChange={(e) => setField('birth_place', e.target.value)}
                                    placeholder="City / town"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Death place</label>
                                <Input
                                    className="mt-1"
                                    value={form.death_place}
                                    onChange={(e) => setField('death_place', e.target.value)}
                                    placeholder="Optional"
                                />
                            </div>

                            <div>
                                <label className="text-sm font-medium">Date of birth</label>
                                <Input
                                    type="date"
                                    className="mt-1"
                                    value={form.date_of_birth}
                                    onChange={(e) => setField('date_of_birth', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Date of death</label>
                                <Input
                                    type="date"
                                    className="mt-1"
                                    value={form.date_of_death}
                                    onChange={(e) => setField('date_of_death', e.target.value)}
                                />
                            </div>

                            <div className="sm:col-span-2">
                                <label className="text-sm font-medium">URL slug</label>
                                <Input
                                    className="mt-1 font-mono text-sm"
                                    value={form.singer_slug}
                                    onChange={(e) => setField('singer_slug', e.target.value)}
                                    onBlur={(e) => checkSlug(e.target.value)}
                                    placeholder="Auto from roman name if empty"
                                />
                                {slugError && <p className="mt-1 text-[10px] text-destructive">{slugError}</p>}
                            </div>

                            <div className="sm:col-span-2">
                                <label className="text-sm font-medium">Bio (Sindhi)</label>
                                <Textarea
                                    dir="rtl"
                                    lang="sd"
                                    rows={4}
                                    className="mt-1 min-h-[96px] resize-y font-arabic text-right"
                                    value={form.singer_bio}
                                    onChange={(e) => setField('singer_bio', e.target.value)}
                                    placeholder="سوانح حيات…"
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <label className="text-sm font-medium">Bio (roman / English)</label>
                                <Textarea
                                    rows={4}
                                    className="mt-1 min-h-[96px] resize-y"
                                    value={form.singer_bio_roman}
                                    onChange={(e) => setField('singer_bio_roman', e.target.value)}
                                    placeholder="Biography…"
                                />
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-between bg-muted/10 py-3">
                            <Button type="button" variant="ghost" className="text-destructive" onClick={() => navigate('/admin/singers')}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={mutation.isPending || !!slugError}>
                                {mutation.isPending ? 'Saving…' : (isEdit ? 'Update' : 'Create')}
                            </Button>
                        </CardFooter>
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card>
                        <CardHeader className="py-3">
                            <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                <ImagePlus className="h-4 w-4" /> Profile image
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {imagePreview ? (
                                <div className="relative group">
                                    <img
                                        src={imagePreview}
                                        alt="Singer preview"
                                        className="aspect-square w-full rounded-lg border object-cover"
                                    />
                                    <button
                                        type="button"
                                        onClick={handleImageRemove}
                                        className="absolute right-2 top-2 rounded-full bg-destructive p-1.5 text-destructive-foreground shadow opacity-90"
                                        aria-label="Remove image"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => fileInputRef.current?.click()}
                                    className="flex aspect-square w-full flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-muted-foreground/30 transition-colors hover:border-foreground/40 hover:bg-muted/30"
                                >
                                    <ImagePlus className="h-6 w-6 text-muted-foreground" />
                                    <span className="text-sm text-muted-foreground">Upload photo</span>
                                    <span className="text-[11px] text-muted-foreground/70">JPEG, PNG or WebP · Max 10MB</span>
                                </button>
                            )}
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                onChange={handleImageSelect}
                                className="hidden"
                            />
                            {imagePreview && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="w-full"
                                    onClick={() => fileInputRef.current?.click()}
                                >
                                    Change image
                                </Button>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="py-3">
                            <CardTitle className="text-sm font-medium">Publishing</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center justify-between rounded-md border border-input px-3 py-2 shadow-sm">
                                <span className="text-sm">Visible</span>
                                <Checkbox
                                    checked={form.visibility}
                                    onCheckedChange={(v) => setField('visibility', !!v)}
                                />
                            </div>
                            <div className="flex items-center justify-between rounded-md border border-input px-3 py-2 shadow-sm">
                                <span className="text-sm">Featured</span>
                                <Checkbox
                                    checked={form.is_featured}
                                    onCheckedChange={(v) => setField('is_featured', !!v)}
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </form>
    );
};

export default CreateSinger;
