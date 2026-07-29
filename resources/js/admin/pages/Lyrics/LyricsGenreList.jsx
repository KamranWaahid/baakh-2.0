import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../api/axios';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogFooter,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Plus, Trash2, Edit, Layers } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';

const emptyForm = () => ({
    slug: '',
    sort_order: 0,
    visibility: true,
    details: {
        sd: { name: '' },
        en: { name: '' },
    },
});

const LyricsGenreList = () => {
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [formData, setFormData] = useState(emptyForm());
    const queryClient = useQueryClient();

    const { data: genres, isLoading, isError } = useQuery({
        queryKey: ['lyrics-genres'],
        queryFn: async () => (await api.get('/api/admin/lyrics-genres')).data,
    });

    const createMutation = useMutation({
        mutationFn: (payload) => api.post('/api/admin/lyrics-genres', payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['lyrics-genres'] });
            queryClient.invalidateQueries({ queryKey: ['lyrics-meta'] });
            setIsDialogOpen(false);
            resetForm();
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to create genre');
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, data }) => api.put(`/api/admin/lyrics-genres/${id}`, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['lyrics-genres'] });
            queryClient.invalidateQueries({ queryKey: ['lyrics-meta'] });
            setIsDialogOpen(false);
            resetForm();
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to update genre');
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/lyrics-genres/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['lyrics-genres'] });
            queryClient.invalidateQueries({ queryKey: ['lyrics-meta'] });
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to delete genre');
        },
    });

    const resetForm = () => {
        setFormData(emptyForm());
        setEditing(null);
    };

    const handleEdit = (genre) => {
        setEditing(genre);
        setFormData({
            slug: genre.slug || '',
            sort_order: genre.sort_order || 0,
            visibility: genre.visibility !== false,
            details: {
                sd: { name: genre.details?.sd?.name || genre.name || '' },
                en: { name: genre.details?.en?.name || genre.name_en || '' },
            },
        });
        setIsDialogOpen(true);
    };

    const handleDelete = (id) => {
        if (confirm('Delete this music genre? Lyrics using it will keep their text; the genre link will clear.')) {
            deleteMutation.mutate(id);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editing) {
            updateMutation.mutate({ id: editing.id, data: formData });
        } else {
            createMutation.mutate(formData);
        }
    };

    const autoSlug = (name) => {
        if (editing || formData.slug) return;
        const slug = String(name)
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        if (slug) setFormData((prev) => ({ ...prev, slug }));
    };

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight">Music Genres</h2>
                    <p className="text-sm text-muted-foreground mt-1">
                        Genres for Bol / lyrics (Sufi, folk, film, and more).
                    </p>
                </div>
                <Dialog
                    open={isDialogOpen}
                    onOpenChange={(open) => {
                        setIsDialogOpen(open);
                        if (!open) resetForm();
                    }}
                >
                    <DialogTrigger asChild>
                        <Button className="w-full sm:w-auto">
                            <Plus className="mr-2 h-4 w-4" /> Add Genre
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>{editing ? 'Edit Genre' : 'Create Genre'}</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="genre-name-sd">Name (Sindhi)</Label>
                                <Input
                                    id="genre-name-sd"
                                    dir="rtl"
                                    value={formData.details.sd.name}
                                    onChange={(e) => {
                                        const name = e.target.value;
                                        setFormData({
                                            ...formData,
                                            details: { ...formData.details, sd: { name } },
                                        });
                                    }}
                                    placeholder="صوفي"
                                    required
                                    className="w-full text-right font-arabic"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="genre-name-en">Name (English)</Label>
                                <Input
                                    id="genre-name-en"
                                    value={formData.details.en.name}
                                    onChange={(e) => {
                                        const name = e.target.value;
                                        setFormData({
                                            ...formData,
                                            details: { ...formData.details, en: { name } },
                                        });
                                        autoSlug(name);
                                    }}
                                    placeholder="Sufi"
                                    className="w-full"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="genre-slug">Slug</Label>
                                <Input
                                    id="genre-slug"
                                    value={formData.slug}
                                    onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                                    placeholder="sufi"
                                    required
                                    className="w-full"
                                />
                            </div>
                            <div className="flex items-center justify-between gap-3 rounded-md border p-3">
                                <div>
                                    <Label htmlFor="genre-visibility">Visible</Label>
                                    <p className="text-xs text-muted-foreground">Show in lyrics genre picker</p>
                                </div>
                                <Switch
                                    id="genre-visibility"
                                    checked={!!formData.visibility}
                                    onCheckedChange={(checked) => setFormData({ ...formData, visibility: checked })}
                                />
                            </div>
                            <DialogFooter className="gap-2 sm:gap-0">
                                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={createMutation.isPending || updateMutation.isPending}
                                >
                                    {createMutation.isPending || updateMutation.isPending ? 'Saving...' : 'Save'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-xl flex items-center gap-2">
                        <Layers className="h-5 w-5" /> Manage Genres
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border overflow-hidden">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead className="hidden sm:table-cell">Slug</TableHead>
                                    <TableHead className="hidden md:table-cell">Lyrics</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading && (
                                    Array(3).fill(0).map((_, i) => (
                                        <TableRow key={i}>
                                            <TableCell><Skeleton className="h-4 w-28" /></TableCell>
                                            <TableCell className="hidden sm:table-cell"><Skeleton className="h-4 w-20" /></TableCell>
                                            <TableCell className="hidden md:table-cell"><Skeleton className="h-4 w-8" /></TableCell>
                                            <TableCell><Skeleton className="h-5 w-16" /></TableCell>
                                            <TableCell><Skeleton className="h-8 w-16 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                )}
                                {isError && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center text-destructive py-8">
                                            Could not load genres.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {!isLoading && !isError && (genres || []).length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                                            No genres yet. Add Sufi, folk, film, or your own.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {(genres || []).map((genre) => (
                                    <TableRow key={genre.id}>
                                        <TableCell>
                                            <div className="font-arabic text-right sm:text-left" dir="rtl" lang="sd">
                                                {genre.name}
                                            </div>
                                            {genre.name_en && (
                                                <div className="text-xs text-muted-foreground mt-0.5">{genre.name_en}</div>
                                            )}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell font-mono text-xs">
                                            {genre.slug}
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {genre.lyrics_count || 0}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={genre.visibility ? 'secondary' : 'outline'}>
                                                {genre.visibility ? 'Visible' : 'Hidden'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="inline-flex gap-1">
                                                <Button variant="ghost" size="icon" onClick={() => handleEdit(genre)}>
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-destructive"
                                                    onClick={() => handleDelete(genre.id)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default LyricsGenreList;
