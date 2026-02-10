import React, { useState } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
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
    DialogFooter
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Plus, Trash2, Edit, Layers } from 'lucide-react';

const TopicCategoryList = () => {
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [formData, setFormData] = useState({
        slug: '',
        details: {
            sd: { name: '' },
            en: { name: '' }
        }
    });

    const queryClient = useQueryClient();

    const { data: categories, isLoading, isError } = useQuery({
        queryKey: ['topic-categories'],
        queryFn: async () => {
            const response = await api.get('/api/admin/topic-categories');
            return response.data;
        },
    });

    const createMutation = useMutation({
        mutationFn: (newCategory) => api.post('/api/admin/topic-categories', newCategory),
        onSuccess: () => {
            queryClient.invalidateQueries(['topic-categories']);
            setIsDialogOpen(false);
            resetForm();
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, data }) => api.put(`/api/admin/topic-categories/${id}`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['topic-categories']);
            setIsDialogOpen(false);
            resetForm();
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/topic-categories/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['topic-categories']);
        },
    });

    const resetForm = () => {
        setFormData({
            slug: '',
            details: {
                sd: { name: '' },
                en: { name: '' }
            }
        });
        setEditingCategory(null);
    };

    const handleEdit = (category) => {
        setEditingCategory(category);
        setFormData({
            slug: category.slug,
            details: {
                sd: { name: category.details?.sd?.name || '' },
                en: { name: category.details?.en?.name || '' }
            }
        });
        setIsDialogOpen(true);
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this topic category?')) {
            deleteMutation.mutate(id);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingCategory) {
            updateMutation.mutate({ id: editingCategory.id, data: formData });
        } else {
            createMutation.mutate(formData);
        }
    };

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 className="text-2xl md:text-3xl font-bold tracking-tight">Topic Categories</h2>
                <Dialog open={isDialogOpen} onOpenChange={(open) => {
                    setIsDialogOpen(open);
                    if (!open) resetForm();
                }}>
                    <DialogTrigger asChild>
                        <Button className="w-full sm:w-auto">
                            <Plus className="mr-2 h-4 w-4" /> Add Topic Category
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>{editingCategory ? 'Edit Topic Category' : 'Create New Topic Category'}</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="category-slug">Slug (Unique ID)</Label>
                                <Input
                                    id="category-slug"
                                    value={formData.slug}
                                    onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                                    placeholder="e.g. everyday-life-society"
                                    required
                                    className="w-full"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="category-name-sd">Name (Sindhi)</Label>
                                <Input
                                    id="category-name-sd"
                                    dir="rtl"
                                    value={formData.details.sd.name}
                                    onChange={(e) => setFormData({
                                        ...formData,
                                        details: { ...formData.details, sd: { name: e.target.value } }
                                    })}
                                    placeholder="روزمرہ جي زندگي..."
                                    required
                                    className="w-full text-right font-arabic"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="category-name-en">Name (English)</Label>
                                <Input
                                    id="category-name-en"
                                    value={formData.details.en.name}
                                    onChange={(e) => setFormData({
                                        ...formData,
                                        details: { ...formData.details, en: { name: e.target.value } }
                                    })}
                                    placeholder="Everyday Life & Society"
                                    className="w-full"
                                />
                            </div>
                            <DialogFooter className="gap-2 sm:gap-0">
                                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)} className="w-full sm:w-auto">Cancel</Button>
                                <Button type="submit" className="w-full sm:w-auto" disabled={createMutation.isPending || updateMutation.isPending}>
                                    {createMutation.isPending || updateMutation.isPending ? 'Saving...' : 'Save'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-xl">Fixed Topics</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[80px] hidden sm:table-cell">ID</TableHead>
                                    <TableHead className="min-w-[150px]">Name (SD)</TableHead>
                                    <TableHead className="min-w-[150px]">Name (EN)</TableHead>
                                    <TableHead className="hidden md:table-cell">Slug</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(5).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell className="hidden sm:table-cell"><Skeleton className="h-4 w-12" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell className="hidden md:table-cell"><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell className="text-right"><Skeleton className="h-8 w-16 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center text-red-500">
                                            Error loading topic categories.
                                        </TableCell>
                                    </TableRow>
                                ) : categories?.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center">
                                            No topic categories found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    categories?.map((cat) => (
                                        <TableRow key={cat.id}>
                                            <TableCell className="font-medium hidden sm:table-cell">{cat.id}</TableCell>
                                            <TableCell className="whitespace-nowrap font-medium">
                                                <div className="flex items-center gap-2">
                                                    <Layers className="h-3 w-3 text-muted-foreground" />
                                                    <span lang="sd">{cat.details?.sd?.name || 'N/A'}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap italic text-muted-foreground">
                                                {cat.details?.en?.name || '-'}
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell whitespace-nowrap font-mono text-xs">{cat.slug}</TableCell>
                                            <TableCell className="text-right whitespace-nowrap space-x-1">
                                                <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleEdit(cat)}>
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    onClick={() => handleDelete(cat.id)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default TopicCategoryList;
