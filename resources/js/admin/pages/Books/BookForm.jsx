import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
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
import { Loader2, ArrowLeft, Check, ChevronsUpDown, ImagePlus, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

const BookForm = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const isEdit = !!id;
    const [openPoet, setOpenPoet] = useState(false);
    const fileInputRef = useRef(null);

    const [formData, setFormData] = useState({
        poet_id: '',
        title: '',
        total_pages: '',
        edition: '',
        publisher: '',
        published_year: '',
        isbn: '',
        notes: '',
        visibility: true,
        is_featured: false,
    });

    const [coverImage, setCoverImage] = useState(null);       // File object for upload
    const [coverPreview, setCoverPreview] = useState(null);    // Preview URL (blob or server)
    const [existingCover, setExistingCover] = useState(null);  // Existing server path

    // Fetch poets for dropdown
    const { data: poetsData, isLoading: isLoadingPoets } = useQuery({
        queryKey: ['poets-lookup'],
        queryFn: async () => {
            const response = await api.get('/api/admin/poets/create');
            return response.data;
        }
    });

    // Fetch book data if editing
    const { data: bookData, isLoading: isLoadingBook } = useQuery({
        queryKey: ['poet-book', id],
        queryFn: async () => {
            const response = await api.get(`/api/admin/poet-books/${id}`);
            return response.data;
        },
        enabled: isEdit,
    });

    useEffect(() => {
        if (bookData) {
            setFormData({
                poet_id: bookData.poet_id?.toString() || '',
                title: bookData.title || '',
                total_pages: bookData.total_pages?.toString() || '',
                edition: bookData.edition || '',
                publisher: bookData.publisher || '',
                published_year: bookData.published_year || '',
                isbn: bookData.isbn || '',
                notes: bookData.notes || '',
                visibility: !!bookData.visibility,
                is_featured: !!bookData.is_featured,
            });
            if (bookData.cover_image) {
                setExistingCover(bookData.cover_image);
                setCoverPreview('/' + bookData.cover_image);
            }
        }
    }, [bookData]);

    const mutation = useMutation({
        mutationFn: async (data) => {
            const fd = new FormData();

            // Append all form fields
            fd.append('poet_id', data.poet_id);
            fd.append('title', data.title);
            fd.append('total_pages', data.total_pages);
            if (data.edition) fd.append('edition', data.edition);
            if (data.publisher) fd.append('publisher', data.publisher);
            if (data.published_year) fd.append('published_year', data.published_year);
            if (data.isbn) fd.append('isbn', data.isbn);
            if (data.notes) fd.append('notes', data.notes);
            fd.append('visibility', data.visibility ? '1' : '0');
            fd.append('is_featured', data.is_featured ? '1' : '0');

            // Append cover image if selected
            if (coverImage) {
                fd.append('cover_image', coverImage);
            }

            if (isEdit) {
                fd.append('_method', 'PUT');
                return await api.post(`/api/admin/poet-books/${id}`, fd, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
            }
            return await api.post('/api/admin/poet-books', fd, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        },
        onSuccess: () => {
            toast.success(isEdit ? 'Book updated successfully' : 'Book created successfully');
            queryClient.invalidateQueries(['poet-books']);
            navigate('/admin/books');
        },
        onError: (error) => {
            toast.error(error.response?.data?.message || 'Something went wrong');
        }
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        mutation.mutate(formData);
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleImageSelect = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            toast.error('Please select a valid image file (JPEG, PNG, or WebP)');
            return;
        }

        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            toast.error('Image size must be less than 10MB');
            return;
        }

        setCoverImage(file);
        setCoverPreview(URL.createObjectURL(file));
    };

    const handleRemoveImage = () => {
        setCoverImage(null);
        setCoverPreview(null);
        setExistingCover(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    if (isEdit && isLoadingBook) {
        return (
            <div className="flex items-center justify-center min-h-[400px]">
                <Loader2 className="h-8 w-8 animate-spin text-primary" />
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Button variant="outline" size="icon" onClick={() => navigate(-1)}>
                    <ArrowLeft className="h-4 w-4" />
                </Button>
                <h2 className="text-2xl font-bold tracking-tight">
                    {isEdit ? 'Edit Book' : 'Add New Book'}
                </h2>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Book Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="title">Book Title</Label>
                                    <Input
                                        id="title"
                                        name="title"
                                        value={formData.title}
                                        onChange={handleChange}
                                        placeholder="e.g. Shah Jo Risalo"
                                        required
                                    />
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="poet_id">Poet</Label>
                                        <Popover open={openPoet} onOpenChange={setOpenPoet}>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    role="combobox"
                                                    aria-expanded={openPoet}
                                                    className={cn(
                                                        "w-full justify-between font-normal",
                                                        !formData.poet_id && "text-muted-foreground"
                                                    )}
                                                    disabled={isEdit}
                                                >
                                                    {formData.poet_id
                                                        ? poetsData?.poets?.find((poet) => poet.id.toString() === formData.poet_id)?.name
                                                        : "Select Poet"}
                                                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-full p-0" align="start">
                                                <Command>
                                                    <CommandInput placeholder="Search poet..." className="h-9 font-arabic text-right" />
                                                    <CommandList>
                                                        <CommandEmpty>No poet found.</CommandEmpty>
                                                        <CommandGroup>
                                                            {poetsData?.poets?.map((poet) => (
                                                                <CommandItem
                                                                    value={`${poet.name} ${poet.id}`}
                                                                    key={poet.id}
                                                                    onSelect={() => {
                                                                        setFormData(prev => ({ ...prev, poet_id: poet.id.toString() }));
                                                                        setOpenPoet(false);
                                                                    }}
                                                                    className="font-arabic text-right flex flex-row-reverse justify-between"
                                                                >
                                                                    {poet.name}
                                                                    <Check
                                                                        className={cn(
                                                                            "ml-2 h-4 w-4",
                                                                            poet.id.toString() === formData.poet_id
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
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="total_pages">Total Pages</Label>
                                        <Input
                                            id="total_pages"
                                            name="total_pages"
                                            type="number"
                                            value={formData.total_pages}
                                            onChange={handleChange}
                                            placeholder="e.g. 500"
                                            required
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="notes">Notes/Description</Label>
                                    <Textarea
                                        id="notes"
                                        name="notes"
                                        value={formData.notes}
                                        onChange={handleChange}
                                        placeholder="Optional notes about this edition..."
                                        rows={4}
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Edition Information</CardTitle>
                            </CardHeader>
                            <CardContent className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="publisher">Publisher</Label>
                                    <Input
                                        id="publisher"
                                        name="publisher"
                                        value={formData.publisher}
                                        onChange={handleChange}
                                        placeholder="e.g. Sindhi Adabi Board"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="published_year">Year</Label>
                                    <Input
                                        id="published_year"
                                        name="published_year"
                                        value={formData.published_year}
                                        onChange={handleChange}
                                        placeholder="e.g. 1995"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="edition">Edition</Label>
                                    <Input
                                        id="edition"
                                        name="edition"
                                        value={formData.edition}
                                        onChange={handleChange}
                                        placeholder="e.g. 1st Edition"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="isbn">ISBN</Label>
                                    <Input
                                        id="isbn"
                                        name="isbn"
                                        value={formData.isbn}
                                        onChange={handleChange}
                                        placeholder="e.g. 978-..."
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        {/* Cover Image Upload Card */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ImagePlus className="h-4 w-4" /> Cover Image
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {coverPreview ? (
                                    <div className="relative group">
                                        <img
                                            src={coverPreview}
                                            alt="Book cover preview"
                                            className="w-full aspect-[3/4] object-cover rounded-lg border shadow-sm"
                                        />
                                        <button
                                            type="button"
                                            onClick={handleRemoveImage}
                                            className="absolute top-2 right-2 bg-destructive text-destructive-foreground rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition-opacity shadow-lg"
                                        >
                                            <X className="h-4 w-4" />
                                        </button>
                                    </div>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => fileInputRef.current?.click()}
                                        className="w-full aspect-[3/4] border-2 border-dashed border-muted-foreground/25 rounded-lg flex flex-col items-center justify-center gap-3 hover:border-primary/50 hover:bg-primary/5 transition-all cursor-pointer"
                                    >
                                        <div className="h-12 w-12 rounded-full bg-muted flex items-center justify-center">
                                            <ImagePlus className="h-6 w-6 text-muted-foreground" />
                                        </div>
                                        <div className="text-center">
                                            <p className="text-sm font-medium text-muted-foreground">Upload Cover</p>
                                            <p className="text-xs text-muted-foreground/60 mt-1">JPEG, PNG or WebP • Max 10MB</p>
                                        </div>
                                    </button>
                                )}

                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={handleImageSelect}
                                    className="hidden"
                                />

                                {coverPreview && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="w-full"
                                        onClick={() => fileInputRef.current?.click()}
                                    >
                                        Change Image
                                    </Button>
                                )}
                            </CardContent>
                        </Card>

                        {/* Publishing Card */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Publishing</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="flex items-center justify-between space-x-2">
                                    <div className="space-y-0.5">
                                        <Label>Visibility</Label>
                                        <p className="text-xs text-muted-foreground">Visible on public site (future use)</p>
                                    </div>
                                    <Switch
                                        checked={formData.visibility}
                                        onCheckedChange={(val) => setFormData(p => ({ ...p, visibility: val }))}
                                    />
                                </div>
                                <div className="flex items-center justify-between space-x-2">
                                    <div className="space-y-0.5">
                                        <Label>Is Featured</Label>
                                        <p className="text-xs text-muted-foreground">Highlight this book</p>
                                    </div>
                                    <Switch
                                        checked={formData.is_featured}
                                        onCheckedChange={(val) => setFormData(p => ({ ...p, is_featured: val }))}
                                    />
                                </div>

                                <Button type="submit" className="w-full" disabled={mutation.isPending}>
                                    {mutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    {isEdit ? 'Update Book' : 'Add Book'}
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    );
};

export default BookForm;
