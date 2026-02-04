import React, { useEffect, useState } from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter, Routes, Route, useNavigate, useLocation } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import Login from './pages/Login';
import AdminLayout from './layouts/AdminLayout';
import api from './api/axios';
import '../../css/admin.css';

const ProtectedRoute = ({ children }) => {
    const [isAuthenticated, setIsAuthenticated] = useState(null); // null = loading
    const navigate = useNavigate();
    const location = useLocation();

    useEffect(() => {
        const checkAuth = async () => {
            try {
                await api.get('/api/user');
                setIsAuthenticated(true);
            } catch (error) {
                setIsAuthenticated(false);
                if (location.pathname !== '/login') {
                    navigate('/login');
                }
            }
        };
        checkAuth();
    }, [navigate, location]);

    if (isAuthenticated === null) return <div className="p-8">Loading...</div>;
    if (!isAuthenticated) return null; // Will redirect in useEffect

    return children;
};

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const queryClient = new QueryClient();

import PoetsList from './pages/Poets/PoetsList';
import CreatePoet from './pages/Poets/CreatePoet';
import EditPoet from './pages/Poets/EditPoet';

const App = () => {
    return (
        <QueryClientProvider client={queryClient}>
            <BrowserRouter basename="/admin/new">
                <Routes>
                    <Route path="/login" element={<Login />} />
                    <Route path="/" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <Dashboard />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/poets" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <PoetsList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/poets/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CreatePoet />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/poets/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <EditPoet />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                </Routes>
            </BrowserRouter>
        </QueryClientProvider>
    );
};

ReactDOM.createRoot(document.getElementById('admin-root')).render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
);
