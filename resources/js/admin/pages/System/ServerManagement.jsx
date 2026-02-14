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
    Power
} from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";

const ServerManagement = () => {
    const queryClient = useQueryClient();
    const [consoleOutput, setConsoleOutput] = useState('');
    const [shellInput, setShellInput] = useState('');
    const [shellOutput, setShellOutput] = useState('');
    const shellOutputRef = useRef(null);
    const consoleOutputRef = useRef(null);

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
        refetchInterval: 30000 // Refresh stats every 30 seconds
    });

    // Fetch Logs
    const { data: logsData, isLoading: isLoadingLogs, refetch: refetchLogs } = useQuery({
        queryKey: ['server-logs'],
        queryFn: async () => {
            const response = await api.get('/api/admin/server/logs');
            return response.data;
        },
        enabled: false // Only fetch manually
    });

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

    useEffect(() => {
        if (shellOutputRef.current) {
            shellOutputRef.current.scrollTop = shellOutputRef.current.scrollHeight;
        }
    }, [shellOutput]);

    const handleShellSubmit = (e) => {
        e.preventDefault();
        if (!shellInput.trim() || runShellMutation.isPending) return;
        runShellMutation.mutate(shellInput.trim());
        setShellInput('');
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
                <TabsList className="grid grid-cols-2 md:grid-cols-4 w-full">
                    <TabsTrigger value="stats" className="flex items-center gap-2">
                        <Activity className="h-4 w-4" /> Stats
                    </TabsTrigger>
                    <TabsTrigger value="artisan" className="flex items-center gap-2">
                        <Zap className="h-4 w-4" /> Artisan
                    </TabsTrigger>
                    <TabsTrigger value="logs" className="flex items-center gap-2">
                        <FileText className="h-4 w-4" /> Logs
                    </TabsTrigger>
                    <TabsTrigger value="terminal" className="flex items-center gap-2">
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

                    <Alert>
                        <Info className="h-4 w-4" />
                        <AlertTitle>System Information</AlertTitle>
                        <AlertDescription>
                            Stats are automatically refreshed every 30 seconds. You can manually trigger a refresh by switching tabs.
                        </AlertDescription>
                    </Alert>
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

                {/* --- LOGS CONTENT --- */}
                <TabsContent value="logs" className="space-y-4 pt-4 w-full flex flex-col">
                    <div className="flex justify-between items-center w-full">
                        <div className="flex items-center gap-2">
                            <FileText className="h-5 w-5 text-muted-foreground" />
                            <h3 className="text-lg font-semibold">Application Logs</h3>
                            <Badge variant="outline" className="font-mono text-[10px]">{logsData?.path}</Badge>
                        </div>
                        <Button variant="outline" size="sm" onClick={() => refetchLogs()} disabled={isLoadingLogs}>
                            {isLoadingLogs ? <RefreshCw className="h-4 w-4 animate-spin mr-2" /> : <RefreshCw className="h-4 w-4 mr-2" />}
                            Fetch Latest
                        </Button>
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

export default ServerManagement;
