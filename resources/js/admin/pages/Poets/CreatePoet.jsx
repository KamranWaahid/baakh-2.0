import React, { useState } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Trash2, Plus } from 'lucide-react';

const poetSchema = z.object({
    poet_slug: z.string().min(3, 'Slug must be at least 3 characters'),
    date_of_birth: z.string().optional().nullable(),
    date_of_death: z.string().optional().nullable(),
    visibility: z.boolean().default(true),
    is_featured: z.boolean().default(false),
    image: z.any().refine((files) => files?.length > 0, "Image is required"),
    details: z.array(z.object({
        poet_name: z.string().min(3, "Name must be at least 3 characters"),
        poet_laqab: z.string().min(3, "Laqab must be at least 3 characters"),
        pen_name: z.string().optional().nullable(),
        tagline: z.string().optional().nullable(),
        poet_bio: z.string().optional().nullable(),
        birth_place: z.string().optional().nullable(),
        death_place: z.string().optional().nullable(),
        lang: z.string().min(1, "Language is required"),
    })).min(1, "At least one language detail is required"),
});

const CreatePoet = () => {
    const navigate = useNavigate();
    const [preview, setPreview] = useState(null);

    const { data: createData } = useQuery({
        queryKey: ['poets-create-data'],
        queryFn: async () => {
            const res = await api.get('/api/admin/poets/create');
            return res.data;
        }
    });

    const form = useForm({
        resolver: zodResolver(poetSchema),
        defaultValues: {
            poet_slug: '',
            date_of_birth: '',
            date_of_death: '',
            visibility: true,
            is_featured: false,
            details: [
                {
                    lang: 'sd',
                    poet_name: '',
                    poet_laqab: '',
                    pen_name: '',
                    tagline: '',
                    poet_bio: '',
                    birth_place: null,
                    death_place: null,
                },
            ],
        },
    });

    const { fields, append, remove } = useFieldArray({
        control: form.control,
        name: "details",
    });

    const onSubmit = async (data) => {
        const formData = new FormData();
        formData.append('poet_slug', data.poet_slug);
        if (data.date_of_birth) formData.append('date_of_birth', data.date_of_birth);
        if (data.date_of_death) formData.append('date_of_death', data.date_of_death);
        formData.append('visibility', data.visibility ? '1' : '0');
        formData.append('is_featured', data.is_featured ? '1' : '0');
        formData.append('image', data.image[0]);

        data.details.forEach((detail, index) => {
            formData.append(`details[${index}][lang]`, detail.lang);
            formData.append(`details[${index}][poet_name]`, detail.poet_name);
            formData.append(`details[${index}][poet_laqab]`, detail.poet_laqab);
            if (detail.pen_name) formData.append(`details[${index}][pen_name]`, detail.pen_name);
            if (detail.tagline) formData.append(`details[${index}][tagline]`, detail.tagline);
            if (detail.poet_bio) formData.append(`details[${index}][poet_bio]`, detail.poet_bio);
            if (detail.birth_place) formData.append(`details[${index}][birth_place]`, detail.birth_place);
            if (detail.death_place) formData.append(`details[${index}][death_place]`, detail.death_place);
        });

        try {
            await api.post('/api/admin/poets', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            navigate('/admin/new/poets');
        } catch (error) {
            console.error(error);
            if (error.response?.data?.errors) {
                const errors = error.response.data.errors;
                Object.keys(errors).forEach(key => {
                    form.setError(key, { message: errors[key][0] });
                });
            }
        }
    };

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setPreview(URL.createObjectURL(file));
            form.setValue('image', e.target.files);
        }
    };

    return (
        <div className="max-w-4xl mx-auto pb-10">
            <h2 className="text-2xl font-bold mb-4">Add New Poet</h2>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <FormField
                                control={form.control}
                                name="poet_slug"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel>Slug (Url)</FormLabel>
                                        <FormControl>
                                            <Input placeholder="e.g. shah-abdul-latif" {...field} />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />

                            <div className="grid grid-cols-2 gap-4">
                                <FormField
                                    control={form.control}
                                    name="date_of_birth"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Date of Birth</FormLabel>
                                            <FormControl>
                                                <Input type="date" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                                <FormField
                                    control={form.control}
                                    name="date_of_death"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Date of Death (Optional)</FormLabel>
                                            <FormControl>
                                                <Input type="date" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                            </div>

                            <div className="flex gap-6">
                                <FormField
                                    control={form.control}
                                    name="visibility"
                                    render={({ field }) => (
                                        <FormItem className="flex flex-row items-start space-x-3 space-y-0 rounded-md border p-4">
                                            <FormControl>
                                                <Checkbox
                                                    checked={field.value}
                                                    onCheckedChange={field.onChange}
                                                />
                                            </FormControl>
                                            <div className="space-y-1 leading-none">
                                                <FormLabel>Visible</FormLabel>
                                            </div>
                                        </FormItem>
                                    )}
                                />
                                <FormField
                                    control={form.control}
                                    name="is_featured"
                                    render={({ field }) => (
                                        <FormItem className="flex flex-row items-start space-x-3 space-y-0 rounded-md border p-4">
                                            <FormControl>
                                                <Checkbox
                                                    checked={field.value}
                                                    onCheckedChange={field.onChange}
                                                />
                                            </FormControl>
                                            <div className="space-y-1 leading-none">
                                                <FormLabel>Featured</FormLabel>
                                            </div>
                                        </FormItem>
                                    )}
                                />
                            </div>

                            <FormField
                                control={form.control}
                                name="image"
                                render={({ field: { value, onChange, ...fieldProps } }) => (
                                    <FormItem>
                                        <FormLabel>Profile Image</FormLabel>
                                        <FormControl>
                                            <Input
                                                {...fieldProps}
                                                type="file"
                                                accept="image/*"
                                                onChange={(event) => {
                                                    handleImageChange(event);
                                                    onChange(event.target.files);
                                                }}
                                            />
                                        </FormControl>
                                        {preview && (
                                            <img src={preview} alt="Preview" className="w-32 h-32 object-cover rounded-md mt-2" />
                                        )}
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />
                        </CardContent>
                    </Card>

                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-xl font-semibold">Language Details</h3>
                            <Button type="button" variant="outline" size="sm" onClick={() => append({ lang: 'sd', poet_name: '', poet_laqab: '', birth_place: null })}>
                                <Plus className="mr-2 h-4 w-4" /> Add Language
                            </Button>
                        </div>

                        {fields.map((field, index) => (
                            <Card key={field.id}>
                                <CardContent className="pt-6 relative space-y-4">
                                    {index > 0 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="absolute top-2 right-2 text-destructive"
                                            onClick={() => remove(index)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    )}

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <FormField
                                            control={form.control}
                                            name={`details.${index}.lang`}
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Language</FormLabel>
                                                    <Select onValueChange={field.onChange} defaultValue={field.value}>
                                                        <FormControl>
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select Language" />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent>
                                                            <SelectItem value="sd">Sindhi</SelectItem>
                                                            <SelectItem value="en">English</SelectItem>
                                                            <SelectItem value="ur">Urdu</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        <FormField
                                            control={form.control}
                                            name={`details.${index}.poet_name`}
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Name</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="Poet Name" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        <FormField
                                            control={form.control}
                                            name={`details.${index}.poet_laqab`}
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Laqab (Title)</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="e.g. Bhittai" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        <FormField
                                            control={form.control}
                                            name={`details.${index}.pen_name`}
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Pen Name</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="Pen Name" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        <FormField
                                            control={form.control}
                                            name={`details.${index}.birth_place`}
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Birth Place</FormLabel>
                                                    <Select onValueChange={field.onChange} value={field.value}>
                                                        <FormControl>
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select Birth City" />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent>
                                                            {createData?.cities?.map(city => (
                                                                <SelectItem key={city.id} value={city.id.toString()}>
                                                                    {city.name}
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
                                            name={`details.${index}.death_place`}
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Death Place</FormLabel>
                                                    <Select onValueChange={field.onChange} value={field.value}>
                                                        <FormControl>
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select Death City" />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent>
                                                            {createData?.cities?.map(city => (
                                                                <SelectItem key={city.id} value={city.id.toString()}>
                                                                    {city.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                    <FormField
                                        control={form.control}
                                        name={`details.${index}.tagline`}
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>Tagline</FormLabel>
                                                <FormControl>
                                                    <Input placeholder="Short description" {...field} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                    <FormField
                                        control={form.control}
                                        name={`details.${index}.poet_bio`}
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>Bio</FormLabel>
                                                <FormControl>
                                                    <Input placeholder="Brief Biography" {...field} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button variant="outline" type="button" onClick={() => navigate('/poets')}>Cancel</Button>
                        <Button type="submit" disabled={form.formState.isSubmitting}>
                            {form.formState.isSubmitting ? 'Saving...' : 'Create Poet'}
                        </Button>
                    </div>
                </form>
            </Form>
        </div>
    );
};

export default CreatePoet;

