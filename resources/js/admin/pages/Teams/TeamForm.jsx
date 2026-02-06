import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { useMutation, useQueryClient, useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';

const TeamForm = () => {
    const { id } = useParams();
    const isEditMode = !!id;
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { register, handleSubmit, reset, formState: { errors } } = useForm();

    const { data: team, isLoading } = useQuery({
        queryKey: ['team', id],
        queryFn: async () => {
            const response = await api.get(`/api/admin/teams/${id}`);
            return response.data;
        },
        enabled: isEditMode
    });

    useEffect(() => {
        if (team) {
            reset({
                name: team.name,
                description: team.description
            });
        }
    }, [team, reset]);

    const mutation = useMutation({
        mutationFn: async (data) => {
            if (isEditMode) {
                return api.put(`/api/admin/teams/${id}`, data);
            }
            return api.post('/api/admin/teams', data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['teams']);
            navigate('/teams');
        }
    });

    const onSubmit = (data) => {
        mutation.mutate(data);
    };

    if (isEditMode && isLoading) return <div>Loading...</div>;

    return (
        <div className="max-w-2xl mx-auto p-4 md:p-8">
            <Button variant="ghost" onClick={() => navigate('/teams')} className="mb-4 md:mb-6 pl-0 hover:pl-2 transition-all flex items-center">
                <ArrowLeft className="h-4 w-4 mr-2" /> Back to Teams
            </Button>

            <Card>
                <CardHeader>
                    <CardTitle>{isEditMode ? 'Edit Team' : 'Create New Team'}</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Team Name</label>
                            <Input
                                {...register('name', { required: 'Team name is required' })}
                                placeholder="e.g. Content Team"
                            />
                            {errors.name && <p className="text-red-500 text-sm">{errors.name.message}</p>}
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Description</label>
                            <Textarea
                                {...register('description')}
                                placeholder="Brief description of the team's purpose"
                                rows={4}
                            />
                        </div>

                        <Button type="submit" disabled={mutation.isPending} className="w-full">
                            {mutation.isPending ? 'Saving...' : (isEditMode ? 'Update Team' : 'Create Team')}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
};

export default TeamForm;
