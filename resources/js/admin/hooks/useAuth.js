import { useQuery } from '@tanstack/react-query';
import api from '../api/axios';

const useAuth = () => {
    const { data: user, isLoading } = useQuery({
        queryKey: ['auth-user'],
        queryFn: async () => {
            try {
                const response = await api.get('/api/user');
                return response.data;
            } catch (error) {
                return null;
            }
        },
        staleTime: Infinity,
        retry: 1
    });

    const hasRole = (roleName) => {
        if (!user || !user.roles) return false;
        return user.roles.some(r => r.name === roleName);
    };

    const hasAnyRole = (roleNames) => {
        if (!user || !user.roles) return false;
        return user.roles.some(r => roleNames.includes(r.name));
    };

    const isSuperAdmin = hasRole('super_admin');

    // Define higher-level permissions
    // "Manage" implies ability to Edit/Create generally
    const canManage = hasAnyRole(['super_admin', 'admin', 'editor']);

    // "Delete" is restricted to admins usually
    const canDelete = hasAnyRole(['super_admin', 'admin']);

    return {
        user,
        isLoading,
        hasRole,
        hasAnyRole,
        isSuperAdmin,
        canManage,
        canDelete
    };
};

export default useAuth;
