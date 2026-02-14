import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../api/axios';
import { toast } from 'sonner';
import {
    Activity,
    AlertTriangle,
    ArrowUpRight,
    Bot,
    Bug,
    CheckCircle2,
    Globe,
    Link2,
    Loader2,
    Network,
    RefreshCw,
    Search,
    Shield,
    TrendingUp,
    Wrench,
    Zap,
    XCircle,
} from 'lucide-react';

// ── Score Color Helper ─────────────────────────────
const scoreColor = (score) => {
    if (score >= 90) return 'text-emerald-600';
    if (score >= 70) return 'text-blue-600';
    if (score >= 50) return 'text-amber-600';
    return 'text-red-600';
};

const scoreBg = (score) => {
    if (score >= 90) return 'bg-emerald-50 border-emerald-200';
    if (score >= 70) return 'bg-blue-50 border-blue-200';
    if (score >= 50) return 'bg-amber-50 border-amber-200';
    return 'bg-red-50 border-red-200';
};

const issueBadge = (issue) => {
    const colors = {
        missing_h1: 'bg-red-100 text-red-700',
        missing_meta_description: 'bg-orange-100 text-orange-700',
        missing_schema: 'bg-yellow-100 text-yellow-700',
        missing_canonical: 'bg-purple-100 text-purple-700',
        missing_hreflang: 'bg-indigo-100 text-indigo-700',
        broken_internal_link: 'bg-red-100 text-red-800',
        slow_response: 'bg-pink-100 text-pink-700',
        duplicate_title: 'bg-amber-100 text-amber-700',
        title_too_long: 'bg-sky-100 text-sky-700',
        multiple_h1: 'bg-rose-100 text-rose-700',
        missing_lang_attribute: 'bg-teal-100 text-teal-700',
        missing_title: 'bg-red-100 text-red-700',
    };
    return colors[issue] || 'bg-gray-100 text-gray-700';
};

const issueLabel = (issue) => issue?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

const Mokhii = () => {
    const queryClient = useQueryClient();

    // ── Dashboard Data ──────────────────────────────
    const { data, isLoading, error } = useQuery({
        queryKey: ['mokhii-dashboard'],
        queryFn: async () => {
            const res = await api.get('/api/admin/mokhii/dashboard');
            return res.data;
        },
        refetchInterval: 30000,
    });

    // ── Mutations ───────────────────────────────────
    const crawlMutation = useMutation({
        mutationFn: () => api.post('/api/admin/mokhii/crawl'),
        onSuccess: () => {
            toast.success('Crawl started for up to 50 URLs');
            queryClient.invalidateQueries(['mokhii-dashboard']);
        },
        onError: () => toast.error('Failed to start crawl'),
    });

    const computeMutation = useMutation({
        mutationFn: () => api.post('/api/admin/mokhii/compute'),
        onSuccess: (res) => {
            const s = res.data.stats;
            toast.success(`Graph rebuilt: ${s.edges_created} edges, ${s.pages_computed} pages`);
            queryClient.invalidateQueries(['mokhii-dashboard']);
        },
        onError: () => toast.error('Failed to compute graph'),
    });

    const autofixMutation = useMutation({
        mutationFn: () => api.post('/api/admin/mokhii/autofix'),
        onSuccess: (res) => {
            const s = res.data.stats;
            toast.success(`Auto-fix: ${s.issues_fixed} issues resolved across ${s.pages_fixed} pages`);
            queryClient.invalidateQueries(['mokhii-dashboard']);
        },
        onError: () => toast.error('Failed to run auto-fix'),
    });

    if (isLoading) {
        return (
            <div className="flex items-center justify-center min-h-[60vh]">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-center">
                <XCircle className="h-12 w-12 text-red-400" />
                <p className="text-lg text-muted-foreground">Failed to load Mokhii dashboard</p>
                <p className="text-sm text-muted-foreground">{error.message}</p>
            </div>
        );
    }

    const { health, issues, recent_crawls, knowledge_graph, priorities } = data;
    const hasData = health.pages_audited > 0;

    return (
        <div className="space-y-6">
            {/* ── Header ──────────────────────────────── */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight flex items-center gap-2">
                        <Bot className="h-7 w-7 text-primary" />
                        Mokhii GEO Engine
                    </h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Autonomous GEO optimization • Knowledge graph • Technical audits
                    </p>
                </div>
                <div className="flex gap-2 flex-wrap">
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => crawlMutation.mutate()}
                        disabled={crawlMutation.isPending}
                    >
                        {crawlMutation.isPending ? <Loader2 className="h-4 w-4 animate-spin mr-1" /> : <Search className="h-4 w-4 mr-1" />}
                        Crawl
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => computeMutation.mutate()}
                        disabled={computeMutation.isPending}
                    >
                        {computeMutation.isPending ? <Loader2 className="h-4 w-4 animate-spin mr-1" /> : <Network className="h-4 w-4 mr-1" />}
                        Compute
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => autofixMutation.mutate()}
                        disabled={autofixMutation.isPending}
                    >
                        {autofixMutation.isPending ? <Loader2 className="h-4 w-4 animate-spin mr-1" /> : <Wrench className="h-4 w-4 mr-1" />}
                        Auto-Fix
                    </Button>
                </div>
            </div>

            {!hasData && (
                <Card className="border-dashed border-2">
                    <CardContent className="flex flex-col items-center justify-center py-10 gap-3 text-center">
                        <Globe className="h-12 w-12 text-muted-foreground/50" />
                        <p className="font-medium text-lg">No audit data yet</p>
                        <p className="text-sm text-muted-foreground">Click <strong>Crawl</strong> to run your first GEO audit, then <strong>Compute</strong> to build the knowledge graph.</p>
                    </CardContent>
                </Card>
            )}

            {hasData && (
                <>
                    {/* ── Health Score Cards ──────────────── */}
                    <div className="grid gap-4 grid-cols-2 lg:grid-cols-4">
                        <Card className={`border ${scoreBg(health.overall_score)}`}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Overall Score</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className={`text-3xl font-bold ${scoreColor(health.overall_score)}`}>
                                    {health.overall_score}
                                    <span className="text-lg">/100</span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Pages Audited</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">{health.pages_audited}</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Graph Edges</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">{knowledge_graph.total_edges.toLocaleString()}</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Avg Priority</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">{(priorities.avg_priority * 100).toFixed(1)}%</div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* ── Score Distribution ──────────────── */}
                    <div className="grid gap-4 grid-cols-1 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <Activity className="h-4 w-4" />
                                    Score Distribution
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {[
                                        { label: 'Excellent (90+)', value: health.score_distribution.excellent, color: 'bg-emerald-500' },
                                        { label: 'Good (70-89)', value: health.score_distribution.good, color: 'bg-blue-500' },
                                        { label: 'Fair (50-69)', value: health.score_distribution.fair, color: 'bg-amber-500' },
                                        { label: 'Poor (<50)', value: health.score_distribution.poor, color: 'bg-red-500' },
                                    ].map((item) => (
                                        <div key={item.label} className="flex items-center gap-3">
                                            <span className="text-sm text-muted-foreground w-32">{item.label}</span>
                                            <div className="flex-1 bg-muted rounded-full h-2.5 overflow-hidden">
                                                <div
                                                    className={`h-full rounded-full ${item.color} transition-all`}
                                                    style={{ width: `${health.pages_audited ? (item.value / health.pages_audited * 100) : 0}%` }}
                                                />
                                            </div>
                                            <span className="text-sm font-medium w-8 text-right">{item.value}</span>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        {/* ── Issue Breakdown ────────────────── */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <AlertTriangle className="h-4 w-4" />
                                    Issue Breakdown
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {Object.keys(issues.breakdown || {}).length === 0 ? (
                                    <div className="flex items-center gap-2 text-emerald-600">
                                        <CheckCircle2 className="h-5 w-5" />
                                        <span>No issues detected</span>
                                    </div>
                                ) : (
                                    <div className="space-y-2 max-h-[200px] overflow-y-auto">
                                        {Object.entries(issues.breakdown).map(([issue, count]) => (
                                            <div key={issue} className="flex items-center justify-between">
                                                <Badge className={`${issueBadge(issue)} text-xs font-normal`}>
                                                    {issueLabel(issue)}
                                                </Badge>
                                                <span className="text-sm font-medium">{count}</span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* ── Knowledge Graph Stats ───────────── */}
                    <div className="grid gap-4 grid-cols-1 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <Network className="h-4 w-4" />
                                    Knowledge Graph
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {knowledge_graph.total_edges === 0 ? (
                                    <p className="text-sm text-muted-foreground">No graph data. Click <strong>Compute</strong> to build.</p>
                                ) : (
                                    <div className="space-y-3">
                                        <div className="text-sm font-medium text-muted-foreground">Edges by Relation Type</div>
                                        {Object.entries(knowledge_graph.edges_by_type || {}).map(([type, count]) => (
                                            <div key={type} className="flex items-center justify-between">
                                                <span className="text-sm capitalize">{type.replace(/_/g, ' ')}</span>
                                                <span className="text-sm font-medium">{count.toLocaleString()}</span>
                                            </div>
                                        ))}
                                        <div className="border-t pt-3 mt-3">
                                            <div className="text-sm font-medium text-muted-foreground mb-2">Nodes by Entity</div>
                                            {Object.entries(knowledge_graph.nodes_by_type || {}).map(([type, count]) => (
                                                <div key={type} className="flex items-center justify-between">
                                                    <span className="text-sm capitalize">{type.replace(/_/g, ' ')}</span>
                                                    <span className="text-sm font-medium">{count.toLocaleString()}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* ── Priority Distribution ──────────── */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <TrendingUp className="h-4 w-4" />
                                    Priority Distribution
                                </CardTitle>
                                <CardDescription>{priorities.pages_computed} pages computed</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {priorities.pages_computed === 0 ? (
                                    <p className="text-sm text-muted-foreground">No priority data. Click <strong>Compute</strong> to generate.</p>
                                ) : (
                                    <div className="space-y-3">
                                        {[
                                            { label: 'High (≥0.7)', value: priorities.distribution.high, color: 'bg-emerald-500' },
                                            { label: 'Medium (0.4-0.7)', value: priorities.distribution.medium, color: 'bg-amber-500' },
                                            { label: 'Low (<0.4)', value: priorities.distribution.low, color: 'bg-red-500' },
                                        ].map((item) => (
                                            <div key={item.label} className="flex items-center gap-3">
                                                <span className="text-sm text-muted-foreground w-36">{item.label}</span>
                                                <div className="flex-1 bg-muted rounded-full h-2.5 overflow-hidden">
                                                    <div
                                                        className={`h-full rounded-full ${item.color} transition-all`}
                                                        style={{ width: `${priorities.pages_computed ? (item.value / priorities.pages_computed * 100) : 0}%` }}
                                                    />
                                                </div>
                                                <span className="text-sm font-medium w-8 text-right">{item.value}</span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* ── Worst Pages Table ────────────────── */}
                    {issues.worst_pages?.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <Bug className="h-4 w-4" />
                                    Lowest Scoring Pages
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b text-left">
                                                <th className="pb-2 font-medium text-muted-foreground">URL</th>
                                                <th className="pb-2 font-medium text-muted-foreground text-center">Score</th>
                                                <th className="pb-2 font-medium text-muted-foreground text-center">Status</th>
                                                <th className="pb-2 font-medium text-muted-foreground text-center">Time</th>
                                                <th className="pb-2 font-medium text-muted-foreground">Issues</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {issues.worst_pages.map((page, i) => (
                                                <tr key={i} className="border-b last:border-0">
                                                    <td className="py-2 pr-4 max-w-[300px] truncate">
                                                        <a
                                                            href={page.url}
                                                            target="_blank"
                                                            rel="noopener"
                                                            className="text-primary hover:underline flex items-center gap-1"
                                                        >
                                                            {page.url.replace(/https?:\/\/[^/]+/, '')}
                                                            <ArrowUpRight className="h-3 w-3 shrink-0" />
                                                        </a>
                                                    </td>
                                                    <td className={`py-2 text-center font-bold ${scoreColor(page.score)}`}>{page.score}</td>
                                                    <td className="py-2 text-center">{page.status_code}</td>
                                                    <td className="py-2 text-center text-muted-foreground">{page.response_time_ms}ms</td>
                                                    <td className="py-2">
                                                        <div className="flex flex-wrap gap-1">
                                                            {(page.issues || []).slice(0, 3).map((issue, j) => (
                                                                <Badge key={j} className={`${issueBadge(issue)} text-[10px]`}>
                                                                    {issueLabel(issue)}
                                                                </Badge>
                                                            ))}
                                                            {(page.issues || []).length > 3 && (
                                                                <Badge variant="outline" className="text-[10px]">
                                                                    +{page.issues.length - 3}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* ── Recent Crawls ────────────────────── */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base flex items-center gap-2">
                                <RefreshCw className="h-4 w-4" />
                                Recent Crawls
                            </CardTitle>
                            <CardDescription>
                                Last crawl: {health.last_crawl ? new Date(health.last_crawl).toLocaleString() : 'Never'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="pb-2 font-medium text-muted-foreground">URL</th>
                                            <th className="pb-2 font-medium text-muted-foreground text-center">Score</th>
                                            <th className="pb-2 font-medium text-muted-foreground text-center">Status</th>
                                            <th className="pb-2 font-medium text-muted-foreground text-center">Response</th>
                                            <th className="pb-2 font-medium text-muted-foreground">Crawled</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(recent_crawls || []).map((crawl, i) => (
                                            <tr key={i} className="border-b last:border-0">
                                                <td className="py-2 pr-4 max-w-[300px] truncate">
                                                    <a
                                                        href={crawl.url}
                                                        target="_blank"
                                                        rel="noopener"
                                                        className="text-primary hover:underline flex items-center gap-1"
                                                    >
                                                        {crawl.url.replace(/https?:\/\/[^/]+/, '')}
                                                        <ArrowUpRight className="h-3 w-3 shrink-0" />
                                                    </a>
                                                </td>
                                                <td className={`py-2 text-center font-bold ${scoreColor(crawl.score)}`}>{crawl.score}</td>
                                                <td className="py-2 text-center">
                                                    <Badge variant={crawl.status_code === 200 ? 'default' : 'destructive'} className="text-xs">
                                                        {crawl.status_code}
                                                    </Badge>
                                                </td>
                                                <td className="py-2 text-center text-muted-foreground">{crawl.response_time_ms}ms</td>
                                                <td className="py-2 text-muted-foreground text-xs">
                                                    {crawl.crawled_at ? new Date(crawl.crawled_at).toLocaleString() : '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </>
            )}
        </div>
    );
};

export default Mokhii;
