import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '../../admin/api/axios';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    const checkAuth = async () => {
        try {
            // Check if we have a token or an active session cookie first.
            // But we don't always know, so we make the request.
            const response = await api.get('/api/auth/me', {
                validateStatus: function (status) {
                    return status >= 200 && status < 300 || status === 401; 
                }
            });
            
            if (response.status === 401) {
                setUser(null);
                setLoading(false);
                return null;
            }

            const userData = response.data.user;
            setUser(userData);
            return userData;
        } catch (error) {
            setUser(null);
            return null;
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        checkAuth();
    }, []);

    const logout = async () => {
        try {
            await api.post('/api/auth/logout');
            localStorage.removeItem('auth_token');
            setUser(null);
        } catch (error) {
            console.error('Logout failed', error);
        }
    };

    return (
        <AuthContext.Provider value={{ user, loading, setUser, checkAuth, logout }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};
