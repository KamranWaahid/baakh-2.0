import React, { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ChevronLeft, Loader2, Save } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import api from '../../api/axios';
import NotificationPhonePreview from './NotificationPhonePreview';

const formSchema = z.object({
    type: z.string().min(1, 'Type is required'),
    title_sd: z.string().min(1, 'Sindhi title is required').max(120),
    title_en: z.string().max(120).optional(),
    body_sd: z.string().min(1, 'Sindhi body is required').max(500),
    body_en: z.string().max(500).optional(),
    cta_sd: z.string().max(60).optional(),
    cta_en: z.string().max(60).optional(),
    image_url: z.string().max(500).optional(),
    android: z.boolean(),
    ios: z.boolean(),
    audience: z.string().min(1),
    deep_link: z.string().max(255).optional(),
    web_path: z.string().max(255).optional(),
    priority: z.string().min(1),
    status: z.string().min(1),
    is_active: z.boolean(),
    schedule_at: z.string().optional(),
    recurrence: z.string().min(1),
    recurrence_time: z.string().optional(),
    expires_at: z.string().optional(),
});

const emptyValues = {
    type: 'announcement',
    title_sd: '',
    title_en: '',
    body_sd: '',
    body_en: '',
    cta_sd: '',
    cta_en: '',
    image_url: '',
    android: true,
    ios: true,
    audience: 'everyone',
    deep_link: '',
    web_path: '',
    priority: 'normal',
    status: 'draft',
    is_active: true,
    schedule_at: '',
    recurrence: 'once',
    recurrence_time: '',
    expires_at: '',
};

const typeDefaults = {
    daily_verse: { deep_link: 'baakh://couplets', web_path: '/sd/couplets' },
    new_poetry: { deep_link: 'baakh://poetry', web_path: '/sd/poetry' },
    new_poet: { deep_link: 'baakh://poets', web_path: '/sd/poets' },
    featured: { deep_link: 'baakh://featured', web_path: '/sd/' },
    word_of_the_day: { deep_link: 'baakh://dictionary', web_path: '/sd/explore' },
    new_lyrics: { deep_link: 'baakh://lyrics', web_path: '/sd/lyrics' },
    reminder: { deep_link: 'baakh://home', web_path: '/sd/' },
    app_update: { deep_link: 'baakh://settings', web_path: '/sd/' },
    announcement: { deep_link: 'baakh://announcements', web_path: '/sd/' },
    event: { deep_link: 'baakh://events', web_path: '/sd/' },
    bookmark_nudge: { deep_link: 'baakh://bookmarks', web_path: '/sd/' },
};

const toLocalInput = (value) => {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const MobileNotificationForm = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const isEditing = !!id;
    const [previewLang, setPreviewLang] = useState('sd');

    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: emptyValues,
    });

    const watched = form.watch();

    const { data: meta } = useQuery({
        queryKey: ['mobile-notifications-meta'],
        queryFn: async () => (await api.get('/api/admin/mobile-notifications', { params: { per_page: 1 } })).data,
    });

    const { data: notification, isLoading } = useQuery({
        queryKey: ['mobile-notification', id],
        queryFn: async () => (await api.get(`/api/admin/mobile-notifications/${id}`)).data,
        enabled: isEditing,
    });

    useEffect(() => {
        if (!notification) return;
        const platforms = notification.platforms || [];
        form.reset({
            ...emptyValues,
            ...notification,
            title_en: notification.title_en || '',
            body_en: notification.body_en || '',
            cta_sd: notification.cta_sd || '',
            cta_en: notification.cta_en || '',
            image_url: notification.image_url || '',
            deep_link: notification.deep_link || '',
            web_path: notification.web_path || '',
            android: platforms.includes('android'),
            ios: platforms.includes('ios'),
            is_active: notification.is_active !== false,
            schedule_at: toLocalInput(notification.schedule_at),
            recurrence_time: notification.recurrence_time ? String(notification.recurrence_time).slice(0, 5) : '',
            expires_at: toLocalInput(notification.expires_at),
        });
    }, [notification, form]);

    const mutation = useMutation({
        mutationFn: async (values) => {
            const platforms = [
                values.android ? 'android' : null,
                values.ios ? 'ios' : null,
            ].filter(Boolean);

            if (platforms.length === 0) {
                throw new Error('Select Android, iOS, or both.');
            }

            const payload = {
                type: values.type,
                title_sd: values.title_sd,
                title_en: values.title_en || null,
                body_sd: values.body_sd,
                body_en: values.body_en || null,
                cta_sd: values.cta_sd || null,
                cta_en: values.cta_en || null,
                image_url: values.image_url || null,
                platforms,
                audience: values.audience,
                deep_link: values.deep_link || null,
                web_path: values.web_path || null,
                priority: values.priority,
                status: values.status,
                is_active: values.is_active,
                schedule_at: values.schedule_at || null,
                recurrence: values.recurrence,
                recurrence_time: values.recurrence_time || null,
                expires_at: values.expires_at || null,
            };

            if (isEditing) {
                return api.put(`/api/admin/mobile-notifications/${id}`, payload);
            }
            return api.post('/api/admin/mobile-notifications', payload);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['mobile-notifications'] });
            toast.success(isEditing ? 'Notification updated' : 'Notification created');
            navigate('/admin/mobile-notifications');
        },
        onError: (error) => {
            toast.error(error.response?.data?.message || error.message || 'Failed to save notification');
        },
    });

    if (isEditing && isLoading) {
        return <div className="flex items-center justify-center h-64"><Loader2 className="h-8 w-8 animate-spin" /></div>;
    }

    return (
        <div className="max-w-6xl mx-auto pb-24 md:pb-10 px-4 md:px-0">
            <div className="flex items-center gap-3 mb-6">
                <Button variant="outline" size="icon" className="shrink-0" onClick={() => navigate('/admin/mobile-notifications')}>
                    <ChevronLeft className="h-4 w-4" />
                </Button>
                <div className="min-w-0">
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight">
                        {isEditing ? 'Edit app notification' : 'Create app notification'}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Sindhi and English copy for Android, iOS, or both.
                    </p>
                </div>
            </div>

            <Form {...form}>
                <form onSubmit={form.handleSubmit((values) => mutation.mutate(values))} className="space-y-6">
                    <div className="grid gap-6 grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px]">
                        <div className="space-y-6 min-w-0">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Copy</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <FormField
                                        control={form.control}
                                        name="type"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>Type</FormLabel>
                                                <Select
                                                    onValueChange={(value) => {
                                                        field.onChange(value);
                                                        const defaults = typeDefaults[value];
                                                        if (defaults && !isEditing) {
                                                            form.setValue('deep_link', defaults.deep_link);
                                                            form.setValue('web_path', defaults.web_path);
                                                        }
                                                    }}
                                                    value={field.value}
                                                >
                                                    <FormControl>
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Choose a type" />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent>
                                                        {(meta?.types || []).map((item) => (
                                                            <SelectItem key={item.value} value={item.value}>{item.label}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <FormDescription>Reminders, new poetry, lyrics, dictionary, updates, and more.</FormDescription>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <FormField
                                            control={form.control}
                                            name="title_sd"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Title (Sindhi)</FormLabel>
                                                    <FormControl>
                                                        <Input dir="rtl" lang="sd" className="font-arabic" placeholder="آڄ جو بيت" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                        <FormField
                                            control={form.control}
                                            name="title_en"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Title (English)</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="Today's couplet" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <FormField
                                            control={form.control}
                                            name="body_sd"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Body (Sindhi)</FormLabel>
                                                    <FormControl>
                                                        <Textarea dir="rtl" lang="sd" className="font-arabic min-h-[90px]" placeholder="هڪ ننڍو بيت، هڪ وڏو خيال." {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                        <FormField
                                            control={form.control}
                                            name="body_en"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Body (English)</FormLabel>
                                                    <FormControl>
                                                        <Textarea className="min-h-[90px]" placeholder="A short couplet, a long thought." {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <FormField
                                            control={form.control}
                                            name="cta_sd"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Button (Sindhi)</FormLabel>
                                                    <FormControl>
                                                        <Input dir="rtl" lang="sd" className="font-arabic" placeholder="هاڻي پڙهو" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                        <FormField
                                            control={form.control}
                                            name="cta_en"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Button (English)</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="Read now" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Delivery</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <FormField
                                            control={form.control}
                                            name="android"
                                            render={({ field }) => (
                                                <FormItem className="flex items-center justify-between rounded-md border p-3">
                                                    <FormLabel className="m-0">Android</FormLabel>
                                                    <FormControl>
                                                        <Switch checked={field.value} onCheckedChange={field.onChange} />
                                                    </FormControl>
                                                </FormItem>
                                            )}
                                        />
                                        <FormField
                                            control={form.control}
                                            name="ios"
                                            render={({ field }) => (
                                                <FormItem className="flex items-center justify-between rounded-md border p-3">
                                                    <FormLabel className="m-0">iOS</FormLabel>
                                                    <FormControl>
                                                        <Switch checked={field.value} onCheckedChange={field.onChange} />
                                                    </FormControl>
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <FormField
                                            control={form.control}
                                            name="audience"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Audience</FormLabel>
                                                    <Select onValueChange={field.onChange} value={field.value}>
                                                        <FormControl>
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent>
                                                            <SelectItem value="everyone">Everyone</SelectItem>
                                                            <SelectItem value="signed_in">Signed-in readers</SelectItem>
                                                            <SelectItem value="guests">Guests only</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                        <FormField
                                            control={form.control}
                                            name="status"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Status</FormLabel>
                                                    <Select onValueChange={field.onChange} value={field.value}>
                                                        <FormControl>
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent>
                                                            <SelectItem value="draft">Draft</SelectItem>
                                                            <SelectItem value="scheduled">Scheduled</SelectItem>
                                                            <SelectItem value="published">Published</SelectItem>
                                                            <SelectItem value="archived">Archived</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <FormField
                                            control={form.control}
                                            name="priority"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Priority</FormLabel>
                                                    <Select onValueChange={field.onChange} value={field.value}>
                                                        <FormControl>
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent>
                                                            <SelectItem value="normal">Normal</SelectItem>
                                                            <SelectItem value="high">High</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                        <FormField
                                            control={form.control}
                                            name="is_active"
                                            render={({ field }) => (
                                                <FormItem className="flex items-center justify-between rounded-md border p-3 h-full">
                                                    <div>
                                                        <FormLabel>Active</FormLabel>
                                                        <FormDescription>Hidden from the app when off.</FormDescription>
                                                    </div>
                                                    <FormControl>
                                                        <Switch checked={field.value} onCheckedChange={field.onChange} />
                                                    </FormControl>
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <FormField
                                            control={form.control}
                                            name="deep_link"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>App deep link</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="baakh://poetry/example-slug" {...field} />
                                                    </FormControl>
                                                    <FormDescription>Where Android and iOS open this alert.</FormDescription>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                        <FormField
                                            control={form.control}
                                            name="web_path"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Web path</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="/sd/poetry/example-slug" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                    <FormField
                                        control={form.control}
                                        name="image_url"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>Image URL</FormLabel>
                                                <FormControl>
                                                    <Input placeholder="https://..." {...field} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <FormField
                                            control={form.control}
                                            name="schedule_at"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Schedule</FormLabel>
                                                    <FormControl>
                                                        <Input type="datetime-local" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                        <FormField
                                            control={form.control}
                                            name="expires_at"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Expires</FormLabel>
                                                    <FormControl>
                                                        <Input type="datetime-local" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <FormField
                                            control={form.control}
                                            name="recurrence"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Repeat</FormLabel>
                                                    <Select onValueChange={field.onChange} value={field.value}>
                                                        <FormControl>
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent>
                                                            <SelectItem value="once">Once</SelectItem>
                                                            <SelectItem value="daily">Daily</SelectItem>
                                                            <SelectItem value="weekly">Weekly</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                        <FormField
                                            control={form.control}
                                            name="recurrence_time"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Repeat time</FormLabel>
                                                    <FormControl>
                                                        <Input type="time" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <Card className="xl:sticky xl:top-20 h-fit">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <CardTitle>Preview</CardTitle>
                                <div className="flex rounded-md border p-0.5">
                                    <Button type="button" size="sm" variant={previewLang === 'sd' ? 'default' : 'ghost'} onClick={() => setPreviewLang('sd')}>
                                        SD
                                    </Button>
                                    <Button type="button" size="sm" variant={previewLang === 'en' ? 'default' : 'ghost'} onClick={() => setPreviewLang('en')}>
                                        EN
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <NotificationPhonePreview
                                    lang={previewLang}
                                    titleSd={watched.title_sd}
                                    titleEn={watched.title_en}
                                    bodySd={watched.body_sd}
                                    bodyEn={watched.body_en}
                                    ctaSd={watched.cta_sd}
                                    ctaEn={watched.cta_en}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="fixed inset-x-0 bottom-0 z-30 border-t bg-background/95 p-3 backdrop-blur md:static md:border-0 md:bg-transparent md:p-0 md:backdrop-blur-none">
                        <div className="flex justify-end gap-3 max-w-6xl mx-auto">
                            <Button variant="outline" type="button" onClick={() => navigate('/admin/mobile-notifications')}>Cancel</Button>
                            <Button type="submit" disabled={mutation.isPending}>
                                {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                                {isEditing ? 'Update notification' : 'Create notification'}
                            </Button>
                        </div>
                    </div>
                </form>
            </Form>
        </div>
    );
};

export default MobileNotificationForm;
