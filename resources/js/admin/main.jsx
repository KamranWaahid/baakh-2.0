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
                await api.get('/api/auth/me');
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
import PoetryList from './pages/Poetry/PoetryList';
import CreatePoetry from './pages/Poetry/CreatePoetry';
import CreateCouplet from './pages/Poetry/CreateCouplet';
import CoupletsList from './pages/Poetry/CoupletsList';
import TagsList from './pages/Tags/TagsList';
import CategoriesList from './pages/Categories/CategoriesList';
import CategoryForm from './pages/Categories/CategoryForm';
import HesudharList from './pages/Hesudhar/HesudharList';
import RomanizerList from './pages/Romanizer/RomanizerList';
import RomanizerBulkCheck from './pages/Romanizer/RomanizerBulkCheck';

import TeamList from './pages/Teams/TeamList';
import TeamForm from './pages/Teams/TeamForm';
import TeamMembers from './pages/Teams/TeamMembers';
import RolesPermissions from './pages/Teams/RolesPermissions';
import LanguagesList from './pages/Languages/LanguagesList';
import DatabaseList from './pages/Databases/DatabaseList';
import CountriesList from './pages/Locations/CountriesList';
import ProvincesList from './pages/Locations/ProvincesList';
import CitiesList from './pages/Locations/CitiesList';


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
                    <Route path="/poetry" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <PoetryList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/poetry/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CreatePoetry />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/poetry/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CreatePoetry />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/couplet/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CreateCouplet />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/couplet/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CreateCouplet />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/couplets" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CoupletsList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/tags" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TagsList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/categories" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CategoriesList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/categories/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CategoryForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/categories/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CategoryForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/hesudhar" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <HesudharList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/romanizer" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <RomanizerList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/romanizer/check" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <RomanizerBulkCheck />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    {/* Team Management Routes */}
                    <Route path="/teams" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TeamList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/teams/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TeamForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/teams/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TeamForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/teams/:id/members" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TeamMembers />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />


                    <Route path="/roles" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <RolesPermissions />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/languages" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <LanguagesList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/databases" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <DatabaseList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/locations/countries" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CountriesList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/locations/provinces" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <ProvincesList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/locations/cities" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CitiesList />
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
