import React, { useEffect, useState } from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter, Routes, Route, useNavigate, useLocation, Link } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import AdminLayout from './layouts/AdminLayout';
import api from './api/axios';
import { Button } from '@/components/ui/button';
import '../../css/admin.css';

const ProtectedRoute = ({ children }) => {
    const [isAuthenticated, setIsAuthenticated] = useState(null); // null = loading
    const navigate = useNavigate();
    const location = useLocation();

    useEffect(() => {
        const checkAuth = async () => {
            try {
                const response = await api.get('/api/auth/me');
                const user = response.data.user;

                // Check for 'view_dashboard' permission via Laravel Sanctum response
                if (user?.permissions?.includes('view_dashboard')) {
                    setIsAuthenticated(true);
                } else {
                    // Logged in but not authorized for admin
                    setIsAuthenticated(false);
                    window.location.href = '/';
                }
            } catch (error) {
                setIsAuthenticated(false);
                if (location.pathname !== '/login') {
                    window.location.href = '/';
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
import TagForm from './pages/Tags/TagForm';
import CategoriesList from './pages/Categories/CategoriesList';
import CategoryForm from './pages/Categories/CategoryForm';
import HesudharList from './pages/Hesudhar/HesudharList';
import HesudharBulkCheck from './pages/Hesudhar/HesudharBulkCheck';
import RomanizerList from './pages/Romanizer/RomanizerList';
import RomanizerBulkCheck from './pages/Romanizer/RomanizerBulkCheck';
import BookList from './pages/Books/BookList';
import BookForm from './pages/Books/BookForm';

import TeamList from './pages/Teams/TeamList';
import TeamForm from './pages/Teams/TeamForm';
import TeamMembers from './pages/Teams/TeamMembers';
import RolesPermissions from './pages/Teams/RolesPermissions';
import LanguagesList from './pages/Languages/LanguagesList';
import DatabaseList from './pages/Databases/DatabaseList';
import CountriesList from './pages/Locations/CountriesList';
import ProvincesList from './pages/Locations/ProvincesList';
import CitiesList from './pages/Locations/CitiesList';
import UserForm from './pages/Users/UserForm';

import TopicCategoryList from './pages/Topics/TopicCategoryList';
import InformationSystem from './pages/System/InformationSystem';
import ServerManagement from './pages/System/ServerManagement';
import ErrorManagement from './pages/System/ErrorManagement';
import ActivityLogs from './pages/System/ActivityLogs';
import HeapAnalysis from './pages/System/HeapAnalysis';

import LemmaInbox from './pages/Dictionary/LemmaInbox';
import SenseEditor from './pages/Dictionary/SenseEditor';
import MorphologyLab from './pages/Dictionary/MorphologyLab';
import Variants from './pages/Dictionary/Variants';
import DictionaryQA from './pages/Dictionary/DictionaryQA';
import DictionaryHome from './pages/Dictionary/DictionaryHome';
import SindhilaScraper from './pages/Dictionary/SindhilaScraper';

import ReportManagement from './pages/Moderation/Reports';
import FeedbackManagement from './pages/Moderation/Feedback';

import SentenceExplorer from './pages/Corpus/SentenceExplorer';
import ContextClusters from './pages/Corpus/ContextClusters';

import FrequencyStats from './pages/Analytics/FrequencyStats';
import DialectCoverage from './pages/Analytics/DialectCoverage';
import UsageTrends from './pages/Analytics/UsageTrends';

import UnderDevelopment from './components/UnderDevelopment';
import Mokhii from './pages/Mokhii';
import Settings from './pages/Settings';


const App = () => {
    const pathname = window.location.pathname;
    const basename = pathname.match(/^\/(en|sd)(\/|$)/) ? pathname.match(/^\/(en|sd)/)[0] : '';

    return (
        <QueryClientProvider client={queryClient}>
            <BrowserRouter basename={basename} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
                <Routes>
                    <Route path="/admin" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <Dashboard />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/system/performance" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <HeapAnalysis />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/poets" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <PoetsList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/poets/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CreatePoet />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/poets/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <EditPoet />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/books" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <BookList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/books/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <BookForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/books/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <BookForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/poetry" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <PoetryList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/poetry/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CreatePoetry />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/poetry/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CreatePoetry />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/couplet/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CreateCouplet />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/couplet/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CreateCouplet />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/couplets" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CoupletsList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/tags" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TagsList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/tags/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TagForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/tags/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TagForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/topic-categories" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TopicCategoryList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/categories" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CategoriesList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/categories/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CategoryForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/categories/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CategoryForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/hesudhar" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <HesudharList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/hesudhar/check" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <HesudharBulkCheck />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/romanizer" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <RomanizerList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/romanizer/check" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <RomanizerBulkCheck />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    {/* Team Management Routes */}
                    <Route path="/admin/teams" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TeamList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/teams/create" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TeamForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />
                    <Route path="/admin/teams/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TeamForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/teams/:id/members" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <TeamMembers />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />


                    <Route path="/admin/roles" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <RolesPermissions />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/users/:id/edit" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <UserForm />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/languages" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <LanguagesList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/databases" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <DatabaseList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/locations/countries" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CountriesList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/locations/provinces" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <ProvincesList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/locations/cities" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <CitiesList />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/system/info" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <InformationSystem />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/system/server" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <ServerManagement />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/system/errors" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <ErrorManagement />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/system/activity-logs" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <ActivityLogs />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />



                    <Route path="/admin/moderation/reports" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <ReportManagement />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/moderation/feedback" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <FeedbackManagement />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    {/* Dictionary Routes */}
                    <Route path="/admin/dictionary" element={<ProtectedRoute><AdminLayout><DictionaryHome /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/dictionary/sindhila-scraper" element={<ProtectedRoute><AdminLayout><SindhilaScraper /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/dictionary/lemma-inbox" element={<ProtectedRoute><AdminLayout><LemmaInbox /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/dictionary/lemmas/:id" element={<ProtectedRoute><AdminLayout><SenseEditor /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/dictionary/lemmas/:id/morphology" element={<ProtectedRoute><AdminLayout><MorphologyLab /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/dictionary/lemmas/:id/variants" element={<ProtectedRoute><AdminLayout><Variants /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/dictionary/sense-editor" element={<ProtectedRoute><AdminLayout><SenseEditor /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/dictionary/morphology-lab" element={<ProtectedRoute><AdminLayout><MorphologyLab /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/dictionary/variants" element={<ProtectedRoute><AdminLayout><Variants /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/dictionary/qa-search" element={<ProtectedRoute><AdminLayout><DictionaryQA /></AdminLayout></ProtectedRoute>} />

                    {/* Corpus Routes */}
                    <Route path="/admin/corpus/sentence-explorer" element={<ProtectedRoute><AdminLayout><SentenceExplorer /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/corpus/context-clusters" element={<ProtectedRoute><AdminLayout><ContextClusters /></AdminLayout></ProtectedRoute>} />

                    {/* Analytics Routes */}
                    <Route path="/admin/analytics/frequency" element={<ProtectedRoute><AdminLayout><FrequencyStats /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/analytics/dialect" element={<ProtectedRoute><AdminLayout><DialectCoverage /></AdminLayout></ProtectedRoute>} />
                    <Route path="/admin/analytics/trends" element={<ProtectedRoute><AdminLayout><UsageTrends /></AdminLayout></ProtectedRoute>} />

                    {/* Mokhii SEO Engine */}
                    <Route path="/admin/mokhii" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <Mokhii />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    <Route path="/admin/settings" element={
                        <ProtectedRoute>
                            <AdminLayout>
                                <Settings />
                            </AdminLayout>
                        </ProtectedRoute>
                    } />

                    {/* Catch-all 404 Route */}
                    <Route path="/admin/*" element={
                        <div className="p-8 text-center space-y-4">
                            <h1 className="text-2xl font-bold text-red-600">404 - Admin Page Not Found</h1>
                            <p className="text-gray-500">The page you are looking for does not exist in the admin panel.</p>
                            <Button asChild><Link to="/admin">Back to Dashboard</Link></Button>
                        </div>
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
