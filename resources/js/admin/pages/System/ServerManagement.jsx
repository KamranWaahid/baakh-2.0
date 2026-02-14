import React, { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from "@/components/ui/tabs";
import {
    Terminal,
    RefreshCw,
    Settings,
    Trash2,
    Zap,
    ShieldAlert,
    CheckCircle2,
    AlertCircle,
    ServerIcon,
    Database,
    Activity,
    FileText,
    Cpu,
    HardDrive,
    Info,
    Power,
    Edit3,
    Search,
    ShieldCheck,
    History,
    GitBranch,
    Play,
    Layers
} from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const ServerManagement = () => {
    const queryClient = useQueryClient();
    const [consoleOutput, setConsoleOutput] = useState('');
    const [shellInput, setShellInput] = useState('');
    const [shellOutput, setShellOutput] = useState('');
    const [envContent, setEnvContent] = useState('');
    const shellOutputRef = useRef(null);

    // --- QUERIES ---

    // Fetch Commands
    const { data: commands, isLoading: isLoadingCommands } = useQuery({
        queryKey: ['server-commands'],
        queryFn: async () => {
            const response = await api.get('/api/admin/server/commands');
            return response.data;
        }
    });

    // Fetch Stats
    const { data: stats, isLoading: isLoadingStats, refetch: refetchStats } = useQuery({
        queryKey: ['server-stats'],
        queryFn: async () => {
            const response = await api.get('/api/admin/server/stats');
            return response.data;
        },
        refetchInterval: 30000
    });

    // Fetch Logs
    const { data: logsData, isLoading: isLoadingLogs, refetch: refetchLogs } = useQuery({
        queryKey: ['server-logs'],
        queryFn: async () => {
            const response = await api.get('/api/admin/server/logs');
            return response.data;
        },
        enabled: false
    });

    // Fetch Env
    const { data: envData, isLoading: isLoadingEnv, refetch: refetchEnv } = useQuery({
        queryKey: ['server-env'],
        queryFn: async () => {
            const response = await api.get('/api/admin/server/env');
            return response.data;
        },
        enabled: false
    });

    // Fetch Queues
    const { data: queueData, refetch: refetchQueues, isLoading: isLoadingQueues } = useQuery({
        queryKey: ['serverQueues'],
        queryFn: async () => {
            const response = await api.get('/api/admin/server/queues');
            return response.data;
        },
        refetchInterval: 3000, // Dynamic updates every 3 seconds
    });

    // Fetch Search Stats
    const { data: searchStats, isLoading: isLoadingSearch, refetch: refetchSearch } = useQuery({
        queryKey: ['server-search'],
        queryFn: async () => {
            const response = await api.get('/api/admin/server/search/stats');
            return response.data;
        },
        enabled: false
    });

    // Fetch Health
    const { data: healthData, isLoading: isLoadingHealth, refetch: refetchHealth } = useQuery({
        queryKey: ['server-health'],
        queryFn: async () => {
            const response = await api.get('/api/admin/server/health');
            return response.data;
        },
        enabled: false
    });

    // Fetch Git History
    const { data: gitData, isLoading: isLoadingGit, refetch: refetchGit } = useQuery({
        queryKey: ['server-git'],
        queryFn: async () => {
            const response = await api.get('/api/admin/server/deployment/history');
            return response.data;
        },
        enabled: false
    });

    // --- MUTATIONS ---

    // Artisan Mutation
    const runArtisanMutation = useMutation({
        mutationFn: async (command) => {
            setConsoleOutput(prev => `> [${new Date().toLocaleTimeString()}] Running: php artisan ${command}...\n${prev}`);
            const response = await api.post('/api/admin/server/commands/run', { command });
            return response.data;
        },
        onSuccess: (data, command) => {
            setConsoleOutput(prev => `> [${new Date().toLocaleTimeString()}] [SUCCESS] php artisan ${command}\n${data.output || 'No output.'}\n-----------------------------------\n${prev}`);
            if (command === 'up' || command === 'down') refetchStats();
        },
        onError: (error, command) => {
            const errorMsg = error.response?.data?.error || error.response?.data?.message || error.message;
            setConsoleOutput(prev => `> [${new Date().toLocaleTimeString()}] [ERROR] php artisan ${command}\n${errorMsg}\n-----------------------------------\n${prev}`);
        }
    });

    // Shell Mutation
    const runShellMutation = useMutation({
        mutationFn: async (command) => {
            setShellOutput(prev => `${prev}\n$ ${command}`);
            const response = await api.post('/api/admin/server/shell', { command });
            return response.data;
        },
        onSuccess: (data) => {
            setShellOutput(prev => `${prev}\n${data.output}\n`);
        },
        onError: (error) => {
            const errorMsg = error.response?.data?.message || error.message;
            setShellOutput(prev => `${prev}\n[ERROR]: ${errorMsg}\n`);
        }
    });

    // Update Env Mutation
    const updateEnvMutation = useMutation({
        mutationFn: async (content) => {
            const response = await api.post('/api/admin/server/env', { content });
            return response.data;
        },
        onSuccess: (data) => {
            alert(data.message);
            refetchEnv();
        },
        onError: (error) => {
            alert(error.response?.data?.error || error.message);
        }
    });

    // Manage Queue Mutation
    const manageQueueMutation = useMutation({
        mutationFn: async ({ action, id }) => {
            const response = await api.post('/api/admin/server/queues/manage', { action, id });
            return response.data;
        },
        onSuccess: () => {
            refetchQueues();
        }
    });

    // Clear Logs Mutation
    const clearLogsMutation = useMutation({
        mutationFn: async () => {
            const response = await api.post('/api/admin/server/logs/clear');
            return response.data;
        },
        onSuccess: (data) => {
            alert(data.message);
            refetchLogs();
        },
        onError: (error) => {
            alert(error.response?.data?.error || error.message);
        }
    });

    useEffect(() => {
        if (shellOutputRef.current) {
            shellOutputRef.current.scrollTop = shellOutputRef.current.scrollHeight;
        }
    }, [shellOutput]);

    useEffect(() => {
        if (envData?.content) {
            setEnvContent(envData.content);
        }
    }, [envData]);

    const handleShellSubmit = (e) => {
        e.preventDefault();
        if (!shellInput.trim() || runShellMutation.isPending) return;
        runShellMutation.mutate(shellInput.trim());
        setShellInput('');
    };

    const handleUpdateEnv = () => {
        if (window.confirm('Updating .env will restart the application and potentially cause downtime. Continue?')) {
            updateEnvMutation.mutate(envContent);
        }
    };

    if (isLoadingCommands || isLoadingStats) return <div className="p-8">Loading server data...</div>;

    const maintenanceStatus = stats?.is_down;

    return (
        <div className="p-4 lg:p-8 space-y-6 w-full">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Comprehensive Server Management</h1>
                    <p className="text-gray-500 mt-2">Environment: {stats?.server_os}</p>
                </div>
                <div className="flex items-center gap-3 bg-muted p-2 rounded-lg border">
                    <Power className={`h-5 w-5 ${maintenanceStatus ? 'text-red-500' : 'text-green-500'}`} />
                    <div className="flex flex-col">
                        <span className="text-xs font-semibold uppercase text-muted-foreground leading-none">Status</span>
                        <span className={`text-sm font-bold ${maintenanceStatus ? 'text-red-600' : 'text-green-600'}`}>
                            {maintenanceStatus ? 'Maintenance Mode' : 'Live / Active'}
                        </span>
                    </div>
                    <Button
                        size="sm"
                        variant="outline"
                        className="ml-2"
                        onClick={() => runArtisanMutation.mutate(maintenanceStatus ? 'up' : 'down')}
                        disabled={runArtisanMutation.isPending}
                    >
                        {maintenanceStatus ? 'Go Live' : 'Go Down'}
                    </Button>
                </div>
            </div>

            <Tabs defaultValue="stats" className="w-full">
                <TabsList className="flex flex-wrap md:grid md:grid-cols-4 lg:grid-cols-8 w-full h-auto bg-muted p-1">
                    <TabsTrigger value="stats" className="flex-1 min-w-[100px] flex items-center gap-2 py-2">
                        <Activity className="h-4 w-4" /> Stats
                    </TabsTrigger>
                    <TabsTrigger value="artisan" className="flex-1 min-w-[100px] flex items-center gap-2 py-2">
                        <Zap className="h-4 w-4" /> Artisan
                    </TabsTrigger>
                    <TabsTrigger value="config" onClick={() => refetchEnv()} className="flex-1 min-w-[100px] flex items-center gap-2 py-2">
                        <Edit3 className="h-4 w-4" /> Config
                    </TabsTrigger>
                    <TabsTrigger value="queues" onClick={() => refetchQueues()} className="flex-1 min-w-[100px] flex items-center gap-2 py-2">
                        <Layers className="h-4 w-4" /> Queues
                    </TabsTrigger>
                    <TabsTrigger value="search" onClick={() => refetchSearch()} className="flex-1 min-w-[100px] flex items-center gap-2 py-2">
                        <Search className="h-4 w-4" /> Search
                    </TabsTrigger>
                    <TabsTrigger value="health" onClick={() => refetchHealth()} className="flex-1 min-w-[100px] flex items-center gap-2 py-2">
                        <ShieldCheck className="h-4 w-4" /> Health
                    </TabsTrigger>
                    <TabsTrigger value="logs" onClick={() => refetchLogs()} className="flex-1 min-w-[100px] flex items-center gap-2 py-2">
                        <FileText className="h-4 w-4" /> Logs
                    </TabsTrigger>
                    <TabsTrigger value="terminal" className="flex-1 min-w-[100px] flex items-center gap-2 py-2">
                        <Terminal className="h-4 w-4" /> Terminal
                    </TabsTrigger>
                </TabsList>

                {/* --- STATS CONTENT --- */}
                <TabsContent value="stats" className="space-y-6 pt-4 w-full">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium flex items-center gap-2 text-muted-foreground">
                                    <Cpu className="h-4 w-4" /> Software
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between items-center border-b pb-2">
                                    <span className="text-sm font-bold">PHP</span>
                                    <Badge variant="secondary">{stats?.php_version}</Badge>
                                </div>
                                <div className="flex justify-between items-center border-b pb-2">
                                    <span className="text-sm font-bold">Laravel</span>
                                    <Badge variant="secondary">{stats?.laravel_version}</Badge>
                                </div>
                                <div className="flex flex-col gap-1">
                                    <span className="text-xs text-muted-foreground">Software</span>
                                    <span className="text-sm truncate" title={stats?.server_software}>{stats?.server_software}</span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium flex items-center gap-2 text-muted-foreground">
                                    <HardDrive className="h-4 w-4" /> Disk Usage
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between items-end">
                                    <div className="flex flex-col">
                                        <span className="text-2xl font-bold">{stats?.disk?.used}</span>
                                        <span className="text-xs text-muted-foreground">Used of {stats?.disk?.total}</span>
                                    </div>
                                    <span className="text-xl font-bold text-blue-500">{stats?.disk?.percent}%</span>
                                </div>
                                <div className="w-full bg-muted rounded-full h-2.5 overflow-hidden">
                                    <div
                                        className="bg-blue-600 h-full transition-all duration-500"
                                        style={{ width: `${stats?.disk?.percent}%` }}
                                    ></div>
                                </div>
                                <div className="flex justify-between text-xs text-muted-foreground">
                                    <span>Available: {stats?.disk?.free}</span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium flex items-center gap-2 text-muted-foreground">
                                    <Activity className="h-4 w-4" /> Resource
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex flex-col gap-1 border-b pb-2">
                                    <span className="text-xs text-muted-foreground uppercase font-bold tracking-wider">Memory Usage</span>
                                    <span className="text-2xl font-bold text-purple-600">{stats?.memory_usage}</span>
                                </div>
                                <div className="flex flex-col gap-1">
                                    <span className="text-xs text-muted-foreground uppercase font-bold tracking-wider">Uptime</span>
                                    <span className="text-xs font-mono bg-slate-100 p-2 rounded border">{stats?.uptime}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <Card className="w-full overflow-hidden">
                        <CardHeader className="pb-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <div>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <GitBranch className="h-4 w-4 text-primary" /> Recent Deployment History
                                </CardTitle>
                                <CardDescription>Last 10 commits on current branch.</CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" onClick={() => refetchGit()} className="shrink-0">
                                <RefreshCw className={`h-4 w-4 ${isLoadingGit ? 'animate-spin' : ''}`} />
                                <span className="ml-2 sm:hidden">Refresh</span>
                            </Button>
                        </CardHeader>
                        <CardContent className="w-full overflow-hidden">
                            <pre className="p-4 font-mono text-[10px] sm:text-xs bg-slate-50 border rounded h-[200px] overflow-y-auto w-full whitespace-pre-wrap break-words leading-relaxed">
                                {isLoadingGit ? 'Fetching history...' : (gitData?.log || 'Click refresh to view history.')}
                            </pre>
                        </CardContent>
                    </Card>
                </TabsContent>

                {/* --- ARTISAN CONTENT --- */}
                <TabsContent value="artisan" className="space-y-6 pt-4 w-full">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {commands?.map((cmd) => (
                            <Card key={cmd.id} className="flex flex-col hover:shadow-md transition-shadow">
                                <CardHeader className="pb-2">
                                    <div className="flex justify-between items-start mb-1">
                                        <Badge variant={cmd.category === 'Maintenance' ? 'destructive' : 'outline'} className="text-[10px] uppercase">
                                            {cmd.category}
                                        </Badge>
                                        {cmd.danger && (
                                            <Badge variant="destructive" className="h-5 gap-1 text-[10px]">
                                                <ShieldAlert className="h-3 w-3" /> DANGER
                                            </Badge>
                                        )}
                                    </div>
                                    <CardTitle className="text-base flex items-center gap-2">
                                        <Zap className="h-4 w-4 text-orange-500" /> {cmd.name}
                                    </CardTitle>
                                    <CardDescription className="text-xs line-clamp-2 min-h-[32px]">
                                        {cmd.description}
                                    </CardDescription>
                                </CardHeader>
                                <CardFooter className="pt-2">
                                    <Button
                                        size="sm"
                                        className="w-full text-xs h-8"
                                        variant={cmd.danger ? "outline" : "default"}
                                        onClick={() => runArtisanMutation.mutate(cmd.command)}
                                        disabled={runArtisanMutation.isPending && runArtisanMutation.variables === cmd.command}
                                    >
                                        {runArtisanMutation.isPending && runArtisanMutation.variables === cmd.command ? (
                                            <RefreshCw className="mr-2 h-3 w-3 animate-spin" />
                                        ) : (
                                            <Zap className="mr-2 h-3 w-3" />
                                        )}
                                        Execute Action
                                    </Button>
                                </CardFooter>
                            </Card>
                        ))}
                    </div>

                    <Card className="bg-slate-950 text-slate-50 border-slate-800 shadow-2xl">
                        <CardHeader className="border-b border-white/10 flex flex-row items-center justify-between space-y-0 py-2 px-4 bg-slate-900/50">
                            <CardTitle className="text-xs font-mono flex items-center gap-2 text-slate-400">
                                <Terminal className="h-3 w-3" /> artisan_console.log
                            </CardTitle>
                            <Button variant="ghost" size="icon" className="h-6 w-6 text-slate-400 hover:text-white" onClick={() => setConsoleOutput('')}>
                                <Trash2 className="h-3 w-3" />
                            </Button>
                        </CardHeader>
                        <CardContent className="p-0 w-full scroll-smooth">
                            <pre className="p-4 font-mono text-[13px] h-[250px] w-full min-w-full overflow-y-auto whitespace-pre-wrap leading-relaxed text-slate-300 custom-scrollbar scroll-smooth">
                                {consoleOutput || '$ Waiting for input...\n'}
                            </pre>
                        </CardContent>
                    </Card>
                </TabsContent>

                {/* --- CONFIG CONTENT --- */}
                <TabsContent value="config" className="space-y-4 pt-4 w-full flex flex-col">
                    <Card className="w-full">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Edit3 className="h-5 w-5 text-blue-500" /> Environment Configuration (.env)
                            </CardTitle>
                            <CardDescription>
                                Directly edit application variables. Creating a backup is automatic. Be careful with credentials.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {isLoadingEnv ? (
                                <div className="h-[400px] flex items-center justify-center border rounded bg-slate-50">
                                    <RefreshCw className="h-8 w-8 animate-spin text-muted-foreground" />
                                </div>
                            ) : (
                                <textarea
                                    className="w-full h-[500px] p-4 font-mono text-xs bg-slate-950 text-green-400 rounded-md border resize-none outline-none focus:ring-1 focus:ring-blue-500"
                                    value={envContent}
                                    onChange={(e) => setEnvContent(e.target.value)}
                                    spellCheck={false}
                                />
                            )}
                        </CardContent>
                        <CardFooter className="justify-between border-t p-4">
                            <p className="text-xs text-muted-foreground flex items-center gap-1">
                                <Info className="h-3 w-3" />
                                Paths like APP_KEY or DB_PASSWORD are critical.
                            </p>
                            <div className="flex gap-2">
                                <Button variant="outline" size="sm" onClick={() => refetchEnv()}>Reset</Button>
                                <Button size="sm" onClick={handleUpdateEnv} disabled={updateEnvMutation.isPending}>
                                    {updateEnvMutation.isPending ? <RefreshCw className="h-4 w-4 animate-spin mr-2" /> : <CheckCircle2 className="h-4 w-4 mr-2" />}
                                    Save Changes
                                </Button>
                            </div>
                        </CardFooter>
                    </Card>
                </TabsContent>

                {/* --- QUEUES CONTENT --- */}
                <TabsContent value="queues" className="space-y-4 pt-4 w-full">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h3 className="text-lg font-semibold flex items-center gap-2">
                                Queue & Scheduler Monitor
                                <span className="flex h-2 w-2 rounded-full bg-green-500 animate-pulse" title="Live Polling Active"></span>
                            </h3>
                            <p className="text-sm text-muted-foreground italic">{queueData?.scheduler_status}</p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" onClick={() => refetchQueues()} disabled={isLoadingQueues}>
                                <RefreshCw className={`h-4 w-4 mr-2 ${isLoadingQueues ? 'animate-spin' : ''}`} />
                                Refresh
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                className="text-amber-600 border-amber-200 hover:bg-amber-50"
                                onClick={() => manageQueueMutation.mutate({ action: 'retry_all' })}
                                disabled={manageQueueMutation.isPending}
                            >
                                <Play className="h-4 w-4 mr-2" /> Retry All
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                className="text-red-600 border-red-200 hover:bg-red-50"
                                onClick={() => { if (window.confirm('Delete all failed jobs?')) manageQueueMutation.mutate({ action: 'delete_all' }) }}
                                disabled={manageQueueMutation.isPending}
                            >
                                <Trash2 className="h-4 w-4 mr-2" /> Clear All
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Card className="bg-slate-50/50">
                            <CardContent className="pt-6">
                                <div className="text-2xl font-bold">{queueData?.queue_size || 0}</div>
                                <p className="text-xs text-muted-foreground uppercase tracking-wider">Pending Jobs</p>
                                <Badge variant="secondary" className="mt-2 text-[10px] uppercase">{queueData?.connection || 'unknown'}</Badge>
                            </CardContent>
                        </Card>
                        <Card className="bg-slate-50/50">
                            <CardContent className="pt-6">
                                <div className="text-2xl font-bold text-red-600">{queueData?.failed_jobs_count || 0}</div>
                                <p className="text-xs text-muted-foreground uppercase tracking-wider">Failed Jobs</p>
                            </CardContent>
                        </Card>
                        <Card className="bg-slate-50/50 border-l-4 border-l-blue-500">
                            <CardContent className="pt-6">
                                <div className="text-sm font-medium">System Scheduler</div>
                                <p className="text-xs text-muted-foreground mt-1">Automatic tasks & cleanup</p>
                                <div className="mt-2 text-[10px] font-mono bg-blue-50 text-blue-700 px-2 py-1 rounded">
                                    {queueData?.scheduler_status?.includes('ago') ? 'ACTIVE' : 'STANDBY'}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader className="py-3 bg-slate-50/50">
                            <CardTitle className="text-sm">Recent Failures</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="max-h-[300px] overflow-auto w-full">
                                {queueData?.failed_jobs?.length > 0 ? (
                                    <table className="w-full text-xs text-left">
                                        <thead className="bg-slate-50 uppercase text-muted-foreground border-b italic">
                                            <tr>
                                                <th className="p-3">ID</th>
                                                <th className="p-3">Connection</th>
                                                <th className="p-3">Queue</th>
                                                <th className="p-3">Failed At</th>
                                                <th className="p-3 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {queueData.failed_jobs.map((job) => (
                                                <tr key={job.id} className="border-b hover:bg-slate-50">
                                                    <td className="p-3 font-mono">{job.id}</td>
                                                    <td className="p-3">{job.connection}</td>
                                                    <td className="p-3">{job.queue}</td>
                                                    <td className="p-3 text-muted-foreground">{job.failed_at}</td>
                                                    <td className="p-3 text-right flex gap-1 justify-end">
                                                        <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => manageQueueMutation.mutate({ action: 'retry', id: job.id })}>
                                                            <RefreshCw className="h-3 w-3" />
                                                        </Button>
                                                        <Button variant="ghost" size="icon" className="h-7 w-7 text-red-500 hover:text-red-600" onClick={() => manageQueueMutation.mutate({ action: 'delete', id: job.id })}>
                                                            <Trash2 className="h-3 w-3" />
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                ) : (
                                    <div className="p-8 text-center text-muted-foreground">
                                        <CheckCircle2 className="h-8 w-8 mx-auto mb-2 text-green-500/50" />
                                        No failed jobs found. Everything is running smoothly!
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                {/* --- SEARCH CONTENT --- */}
                <TabsContent value="search" className="space-y-4 pt-4 w-full">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Search className="h-5 w-5" /> Search Engine (Laravel Scout)
                            </CardTitle>
                            <CardDescription>Manage your Meilisearch connection and data sync.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between p-4 border rounded bg-slate-50">
                                <div className="flex items-center gap-3">
                                    <div className={`p-2 rounded-full ${searchStats?.enabled ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'}`}>
                                        <Activity className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p className="font-bold text-sm">Status: {searchStats?.status}</p>
                                        <p className="text-xs text-muted-foreground">Driver: {searchStats?.driver}</p>
                                    </div>
                                </div>
                                <Button variant="outline" size="sm" onClick={() => refetchSearch()}>
                                    Check Health
                                </Button>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <Card className="border-dashed">
                                    <CardHeader className="p-4 pb-2">
                                        <CardTitle className="text-sm">Re-index Models</CardTitle>
                                    </CardHeader>
                                    <CardContent className="p-4 pt-0">
                                        <p className="text-xs text-muted-foreground mb-4">Sync all database records to the search index.</p>
                                        <div className="flex flex-wrap gap-2">
                                            <Button size="sm" variant="secondary" onClick={() => runArtisanMutation.mutate('scout:import "App\\Models\\Poetry"')}>Poetry</Button>
                                            <Button size="sm" variant="secondary" onClick={() => runArtisanMutation.mutate('scout:import "App\\Models\\Poets"')}>Poets</Button>
                                            <Button size="sm" variant="secondary" onClick={() => runArtisanMutation.mutate('scout:import "App\\Models\\Tags"')}>Tags</Button>
                                        </div>
                                    </CardContent>
                                </Card>
                                <Card className="border-dashed">
                                    <CardHeader className="p-4 pb-2">
                                        <CardTitle className="text-sm font-bold text-red-600">Flush Index</CardTitle>
                                    </CardHeader>
                                    <CardContent className="p-4 pt-0">
                                        <p className="text-xs text-muted-foreground mb-4">Remove all records from search index (Cleanup).</p>
                                        <Button size="sm" variant="outline" className="text-red-600 border-red-200 hover:bg-red-50" onClick={() => runArtisanMutation.mutate('scout:flush "App\\Models\\Poetry"')}>
                                            Flush All Indexes
                                        </Button>
                                    </CardContent>
                                </Card>
                            </div>
                        </CardContent>
                        <CardFooter className="bg-slate-50 italic text-[10px] text-muted-foreground">
                            Host: {searchStats?.host || 'N/A'}
                        </CardFooter>
                    </Card>
                </TabsContent>

                {/* --- HEALTH CONTENT --- */}
                <TabsContent value="health" className="space-y-4 pt-4 w-full">
                    <div className="flex justify-between items-center">
                        <h3 className="text-lg font-semibold">Security & Health Audit</h3>
                        <Button size="sm" onClick={() => refetchHealth()}>Run Scan</Button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <HealthItem title="Storage Writable" status={healthData?.storage_writable} desc="Allows file uploads and log writing." />
                        <HealthItem title="Cache Writable" status={healthData?.cache_writable} desc="Required for performance optimization." />
                        <HealthItem title="Production Environment" status={healthData?.env_production} desc="Ensure site is not in development mode." />
                        <HealthItem title="Debug Mode (Disabled)" status={!healthData?.debug_mode} desc="Critical security: Should be OFF in production." dangerIfFail />
                        <HealthItem title="Database Connection" status={healthData?.database_connection} desc="Primary data source availability." />
                        <HealthItem title="Disk Space Buffer" status={!healthData?.disk_space_warning} desc="Ensure at least 10% space is free." dangerIfFail />
                    </div>
                </TabsContent>

                {/* --- LOGS CONTENT --- */}
                <TabsContent value="logs" className="space-y-4 pt-4 w-full flex flex-col">
                    <div className="flex justify-between items-center w-full">
                        <div className="flex items-center gap-2">
                            <FileText className="h-5 w-5 text-muted-foreground" />
                            <h3 className="text-lg font-semibold">Application Logs</h3>
                            <Badge variant="outline" className="font-mono text-[10px]">{logsData?.path}</Badge>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={() => refetchLogs()} disabled={isLoadingLogs}>
                                {isLoadingLogs ? <RefreshCw className="h-4 w-4 animate-spin mr-2" /> : <RefreshCw className="h-4 w-4 mr-2" />}
                                Fetch Latest
                            </Button>
                            <Button variant="destructive" size="sm" onClick={() => window.confirm('Are you sure you want to clear the logs?') && clearLogsMutation.mutate()} disabled={clearLogsMutation.isPending}>
                                <Trash2 className="h-4 w-4 sm:mr-2" />
                                <span className="hidden sm:inline">Clear Log</span>
                            </Button>
                        </div>
                    </div>

                    <div className="text-[10px] text-muted-foreground bg-muted p-1 px-3 rounded-md flex justify-between items-center">
                        <span>Current Size: <span className="font-bold">{logsData?.size || '0 bytes'}</span></span>
                        <span className="italic">Only showing last 200 lines for performance.</span>
                    </div>

                    <div className="rounded-md border bg-slate-50 w-full overflow-hidden">
                        <pre className="p-4 font-mono text-xs h-[500px] w-full min-w-full overflow-y-auto whitespace-pre-wrap leading-relaxed bg-slate-950 text-slate-200 custom-scrollbar">
                            {isLoadingLogs ? 'Loading latest logs...' : (logsData?.logs || 'No log data available or file is empty. Click "Fetch Latest" to load.')}
                        </pre>
                    </div>
                </TabsContent>

                {/* --- TERMINAL CONTENT --- */}
                <TabsContent value="terminal" className="space-y-4 pt-4 w-full flex flex-col">
                    <Card className="bg-[#0c0c0c] text-green-500 border-[#333] shadow-2xl overflow-hidden font-mono w-full">
                        <CardHeader className="border-b border-[#333] py-2 px-4 bg-[#1a1a1a] flex flex-row items-center justify-between space-y-0">
                            <CardTitle className="text-xs flex items-center gap-2 text-gray-400">
                                <Terminal className="h-3 w-3" /> shell_tty (bash)
                            </CardTitle>
                            <div className="flex gap-1.5">
                                <div className="h-2.5 w-2.5 rounded-full bg-red-500/50" />
                                <div className="h-2.5 w-2.5 rounded-full bg-yellow-500/50" />
                                <div className="h-2.5 w-2.5 rounded-full bg-green-500/50" />
                            </div>
                        </CardHeader>
                        <CardContent className="p-0 w-full overflow-hidden">
                            <div
                                ref={shellOutputRef}
                                className="p-4 h-[400px] w-full min-w-full overflow-y-auto whitespace-pre-wrap leading-relaxed custom-scrollbar text-[13px] bg-black/50"
                            >
                                {shellOutput || 'Welcome to Baakh Shell v1.0\nType "ls" to start exploring.\n'}
                                {runShellMutation.isPending && <span className="animate-pulse">_</span>}
                            </div>
                            <form onSubmit={handleShellSubmit} className="flex border-t border-[#333] bg-[#1a1a1a]">
                                <div className="flex items-center px-4 text-blue-400 font-bold">$</div>
                                <input
                                    type="text"
                                    className="flex-1 bg-transparent border-none outline-none py-3 pr-4 text-green-500 placeholder:text-gray-700 font-mono text-[13px]"
                                    placeholder="Enter command..."
                                    value={shellInput}
                                    onChange={(e) => setShellInput(e.target.value)}
                                    autoFocus
                                />
                                <Button type="submit" variant="ghost" className="h-auto px-4 hover:bg-green-500/10 text-gray-500 hover:text-green-500" disabled={runShellMutation.isPending}>
                                    Run
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                    <div className="flex flex-wrap gap-2 text-[10px] uppercase font-bold text-muted-foreground">
                        <span>Allowed:</span>
                        {['ls', 'whoami', 'uptime', 'df', 'free', 'pwd', 'date', 'du'].map(c => (
                            <Badge key={c} variant="outline" className="text-[10px] px-1 py-0">{c}</Badge>
                        ))}
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    );
};

const HealthItem = ({ title, status, desc, dangerIfFail }) => (
    <div className={`p-4 border rounded-lg flex items-start gap-3 transition-colors ${status === false ? (dangerIfFail ? 'bg-red-50 border-red-200' : 'bg-orange-50 border-orange-200') : 'bg-green-50 border-green-200'}`}>
        <div className={`mt-0.5 ${status === false ? (dangerIfFail ? 'text-red-600' : 'text-orange-600') : 'text-green-600'}`}>
            {status === false ? <AlertCircle className="h-5 w-5" /> : <CheckCircle2 className="h-5 w-5" />}
        </div>
        <div>
            <p className="font-bold text-sm">{title}</p>
            <p className="text-xs text-muted-foreground">{desc}</p>
        </div>
    </div>
);

export default ServerManagement;
