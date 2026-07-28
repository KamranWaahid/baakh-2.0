import { useQuery } from '@tanstack/react-query';
import api from '../api/axios';

const roleNameOf = (role) => {
    if (!role) return null;
    if (typeof role === 'string') return role;
    return role.name || null;
};

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

    const roleNames = (user?.roles || [])
        .map(roleNameOf)
        .filter(Boolean);

    const hasRole = (roleName) => roleNames.includes(roleName);

    const hasAnyRole = (names) => names.some((name) => roleNames.includes(name));

    const isSuperAdmin = hasRole('super_admin');

    // Legacy DB also uses "Admins" (capital A) alongside "admin"
    const canManage = hasAnyRole(['super_admin', 'admin', 'Admins', 'editor']);
    const canDelete = hasAnyRole(['super_admin', 'admin', 'Admins']);
    const canManageUsers = isSuperAdmin || canDelete;

    return {
        user,
        isLoading,
        hasRole,
        hasAnyRole,
        isSuperAdmin,
        canManage,
        canDelete,
        canManageUsers,
        roleNames,
    };
};

export default useAuth;
