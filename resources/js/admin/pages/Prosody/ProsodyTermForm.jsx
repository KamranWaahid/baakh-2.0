import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ChevronLeft, Save, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Form,
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import api from '../../api/axios';

const ICONS = [
    'Scale', 'Ruler', 'Music', 'Info', 'Scissors', 'Columns', 'Wrench',
    'Scroll', 'Footprints', 'Infinity', 'Anchor', 'Sunrise', 'Sunset',
];

const LOGIC_TYPES = [
    { value: 'chhand', label: 'Chhand' },
    { value: 'arooz', label: 'Arooz' },
    { value: 'both', label: 'Both' },
    { value: 'generic', label: 'Generic' },
];

const formSchema = z.object({
    title_sd: z.string().min(1, 'Sindhi title is required'),
    title_en: z.string().min(1, 'English title is required'),
    desc_sd: z.string().optional(),
    desc_en: z.string().optional(),
    tech_detail_sd: z.string().optional(),
    tech_detail_en: z.string().optional(),
    logic_type: z.string().optional(),
    icon: z.string().optional(),
    order: z.coerce.number().int().min(0).max(9999),
});

const emptyValues = {
    title_sd: '',
    title_en: '',
    desc_sd: '',
    desc_en: '',
    tech_detail_sd: '',
    tech_detail_en: '',
    logic_type: 'generic',
    icon: 'Info',
    order: 0,
};

const ProsodyTermForm = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const isEditing = !!id;

    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: emptyValues,
    });

    const { data: term, isLoading } = useQuery({
        queryKey: ['prosody-term', id],
        queryFn: async () => (await api.get(`/api/admin/prosody-terms/${id}`)).data,
        enabled: isEditing,
    });

    useEffect(() => {
        if (!term) return;
        form.reset({
            title_sd: term.title_sd || '',
            title_en: term.title_en || '',
            desc_sd: term.desc_sd || '',
            desc_en: term.desc_en || '',
            tech_detail_sd: term.tech_detail_sd || '',
            tech_detail_en: term.tech_detail_en || '',
            logic_type: term.logic_type || 'generic',
            icon: term.icon || 'Info',
            order: term.order ?? 0,
        });
    }, [term, form]);

    const mutation = useMutation({
        mutationFn: (values) => {
            const payload = {
                ...values,
                logic_type: values.logic_type || null,
                icon: values.icon || 'Info',
            };
            if (isEditing) {
                return api.put(`/api/admin/prosody-terms/${id}`, payload);
            }
            return api.post('/api/admin/prosody-terms', payload);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['prosody-terms'] });
            queryClient.invalidateQueries({ queryKey: ['prosody-term', id] });
            navigate('/admin/prosody');
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to save prosody card');
        },
    });

    if (isEditing && isLoading) {
        return (
            <div className="flex items-center justify-center h-64">
                <Loader2 className="h-8 w-8 animate-spin" />
            </div>
        );
    }

    return (
        <div className="max-w-4xl mx-auto pb-10 px-4 md:px-0">
            <div className="flex items-center gap-4 mb-6">
                <Button variant="outline" size="icon" onClick={() => navigate('/admin/prosody')}>
                    <ChevronLeft className="h-4 w-4" />
                </Button>
                <div>
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight">
                        {isEditing ? 'Edit prosody card' : 'Add prosody card'}
                    </h2>
                    <p className="text-sm text-muted-foreground mt-1">
                        Introduction shows on the public card. Technical notes open in the detail modal.
                    </p>
                </div>
            </div>

            <Form {...form}>
                <form onSubmit={form.handleSubmit((values) => mutation.mutate(values))} className="space-y-6">
                    <div className="grid gap-6 grid-cols-1 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-xl">Sindhi</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <FormField
                                    control={form.control}
                                    name="title_sd"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Title</FormLabel>
                                            <FormControl>
                                                <Input dir="rtl" lang="sd" className="font-arabic text-right" placeholder="علم عروض" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                                <FormField
                                    control={form.control}
                                    name="desc_sd"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Introduction (card)</FormLabel>
                                            <FormControl>
                                                <Textarea dir="rtl" lang="sd" className="font-arabic text-right min-h-[120px]" placeholder="تعارف…" {...field} />
                                            </FormControl>
                                            <FormDescription>Shown on the public card (clamped) and in the modal.</FormDescription>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                                <FormField
                                    control={form.control}
                                    name="tech_detail_sd"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Technical detail (modal)</FormLabel>
                                            <FormControl>
                                                <Textarea dir="rtl" lang="sd" className="font-arabic text-right min-h-[120px]" placeholder="فني تفصيل…" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-xl">English</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <FormField
                                    control={form.control}
                                    name="title_en"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Title</FormLabel>
                                            <FormControl>
                                                <Input placeholder="Ilm Arooz" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                                <FormField
                                    control={form.control}
                                    name="desc_en"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Introduction (card)</FormLabel>
                                            <FormControl>
                                                <Textarea className="min-h-[120px]" placeholder="Short introduction…" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                                <FormField
                                    control={form.control}
                                    name="tech_detail_en"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Technical detail (modal)</FormLabel>
                                            <FormControl>
                                                <Textarea className="min-h-[120px]" placeholder="Technical notes…" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-xl">Display</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 grid-cols-1 sm:grid-cols-3">
                            <FormField
                                control={form.control}
                                name="order"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel>Order</FormLabel>
                                        <FormControl>
                                            <Input type="number" min={0} {...field} />
                                        </FormControl>
                                        <FormDescription>Lower numbers appear first.</FormDescription>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />
                            <FormField
                                control={form.control}
                                name="logic_type"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel>System</FormLabel>
                                        <Select onValueChange={field.onChange} value={field.value || 'generic'}>
                                            <FormControl>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Type" />
                                                </SelectTrigger>
                                            </FormControl>
                                            <SelectContent>
                                                {LOGIC_TYPES.map((type) => (
                                                    <SelectItem key={type.value} value={type.value}>{type.label}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />
                            <FormField
                                control={form.control}
                                name="icon"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel>Icon</FormLabel>
                                        <Select onValueChange={field.onChange} value={field.value || 'Info'}>
                                            <FormControl>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Icon" />
                                                </SelectTrigger>
                                            </FormControl>
                                            <SelectContent>
                                                {ICONS.map((icon) => (
                                                    <SelectItem key={icon} value={icon}>{icon}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Button variant="outline" type="button" onClick={() => navigate('/admin/prosody')}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={mutation.isPending}>
                            {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                            {isEditing ? 'Update card' : 'Create card'}
                        </Button>
                    </div>
                </form>
            </Form>
        </div>
    );
};

export default ProsodyTermForm;
