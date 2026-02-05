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
    word: z.string().min(1, 'Incorrect word is required'),
    correct: z.string().min(1, 'Correct word is required'),
});

const HesudharForm = ({ entry, onSuccess }) => {
    const queryClient = useQueryClient();
    const isEditing = !!entry;

    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: {
            word: '',
            correct: '',
        },
    });

    useEffect(() => {
        if (entry) {
            form.reset({
                word: entry.word,
                correct: entry.correct,
            });
        }
    }, [entry, form]);

    const mutation = useMutation({
        mutationFn: async (values) => {
            if (isEditing) {
                return api.put(`/api/admin/hesudhar/${entry.id}`, values);
            }
            return api.post('/api/admin/hesudhar', values);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['hesudhar']);
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
                    name="word"
                    render={({ field }) => (
                        <FormItem>
                            <FormLabel>Incorrect Word</FormLabel>
                            <FormControl>
                                <Input dir="rtl" placeholder="Incorrect spelling" {...field} />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    )}
                />

                <FormField
                    control={form.control}
                    name="correct"
                    render={({ field }) => (
                        <FormItem>
                            <FormLabel>Correct Word</FormLabel>
                            <FormControl>
                                <Input dir="rtl" placeholder="Correct spelling" {...field} />
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

export default HesudharForm;
