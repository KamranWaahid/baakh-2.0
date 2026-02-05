import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Save, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import api from '../../api/axios';

const formSchema = z.object({
    word_sd: z.string().min(1, 'Sindhi word is required'),
    word_roman: z.string().min(1, 'Roman word is required'),
});

const RomanizerForm = ({ entry, onSuccess }) => {
    const queryClient = useQueryClient();
    const isEditing = !!entry;

    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: {
            word_sd: '',
            word_roman: '',
        },
    });

    useEffect(() => {
        if (entry) {
            form.reset({
                word_sd: entry.word_sd,
                word_roman: entry.word_roman,
            });
        }
    }, [entry, form]);

    const mutation = useMutation({
        mutationFn: async (values) => {
            if (isEditing) {
                return api.put(`/api/admin/romanizer/${entry.id}`, values);
            }
            return api.post('/api/admin/romanizer', values);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['romanizer']);
            onSuccess();
        },
        onError: (error) => {
            if (error.response?.data?.errors) {
                const errors = error.response.data.errors;
                Object.keys(errors).forEach(key => {
                    form.setError(key, { message: errors[key][0] });
                });
            }
        }
    });

    const onSubmit = (values) => {
        mutation.mutate(values);
    };

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4 py-4">
                <FormField
                    control={form.control}
                    name="word_sd"
                    render={({ field }) => (
                        <FormItem>
                            <FormLabel>Sindhi Word</FormLabel>
                            <FormControl>
                                <Input dir="rtl" placeholder="سنڌي لفظ" {...field} />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    )}
                />

                <FormField
                    control={form.control}
                    name="word_roman"
                    render={({ field }) => (
                        <FormItem>
                            <FormLabel>Roman Word</FormLabel>
                            <FormControl>
                                <Input placeholder="Romanized word" {...field} />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    )}
                />

                <div className="flex justify-end gap-2 pt-4">
                    <Button type="button" variant="outline" onClick={onSuccess}>Cancel</Button>
                    <Button type="submit" disabled={mutation.isPending}>
                        {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
                        {isEditing ? 'Update Word' : 'Add Word'}
                    </Button>
                </div>
            </form>
        </Form>
    );
};

export default RomanizerForm;
