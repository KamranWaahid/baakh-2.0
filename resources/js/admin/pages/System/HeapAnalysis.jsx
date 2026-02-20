import React, { useState } from 'react';
import {
    Cpu,
    Database,
    FileJson,
    AlertTriangle,
    CheckCircle2,
    Info,
    Upload,
    Loader2,
    ArrowRight,
    Zap
} from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    Alert,
    AlertDescription,
    AlertTitle
} from "@/components/ui/alert";
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger
} from "@/components/ui/tabs";
import { toast } from "sonner";
import api from '../../api/axios';
import { cn } from '@/lib/utils';

const HeapAnalysis = () => {
    const [file, setFile] = useState(null);
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState(null);
    const [dragging, setDragging] = useState(false);

    const handleFileChange = (e) => {
        const selectedFile = e.target.files[0];
        if (selectedFile && selectedFile.name.endsWith('.heapsnapshot')) {
            setFile(selectedFile);
            setResults(null);
        } else {
            toast.error("Please select a valid .heapsnapshot file");
        }
    };

    const handleUpload = async () => {
        if (!file) return;

        setLoading(true);
        const formData = new FormData();
        formData.append('snapshot', file);

        try {
            const response = await api.post('/api/admin/performance/analyze-heap', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            setResults(response.data);
            toast.success("Analysis complete!");
        } catch (error) {
            console.error("Analysis failed:", error);
            const status = error.response?.status;
            const msg = error.response?.data?.error || error.response?.data?.message;
            if (status === 413) {
                toast.error(
                    "File too large (413). Increase upload limits: use 'composer serve' instead of 'php artisan serve', or add client_max_body_size 100M to Nginx. See HEAP_413_FIX.md"
                );
            } else {
                toast.error(msg || "Analysis failed. Ensure Python 3 is installed and the heap parser is accessible.");
            }
        } finally {
            setLoading(false);
        }
    };

    const formatSize = (bytes) => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const getSeverityColor = (severity) => {
        switch (severity) {
            case 'high': return 'text-red-500 bg-red-50 border-red-100';
            case 'medium': return 'text-amber-500 bg-amber-50 border-amber-100';
            case 'low': return 'text-blue-500 bg-blue-50 border-blue-100';
            default: return 'text-gray-500 bg-gray-50 border-gray-200';
        }
    };

    return (
        <div className="flex flex-col gap-6">
            <div className="flex flex-col gap-2">
                <h1 className="text-3xl font-bold tracking-tight">Heap Analysis Toolkit</h1>
                <p className="text-muted-foreground">
                    Analyze Chrome heap snapshots to detect memory leaks, detached DOM nodes, and inefficient string usage.
                </p>
            </div>

            {!results ? (
                <Card className={cn(
                    "border-2 border-dashed transition-colors",
                    dragging ? "border-primary bg-primary/5" : "border-muted"
                )}>
                    <CardContent className="flex flex-col items-center justify-center py-12 gap-4">
                        <div className="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                            <Upload className="h-8 w-8" />
                        </div>
                        <div className="text-center space-y-1">
                            <p className="text-lg font-medium">
                                {file ? file.name : "Drop a .heapsnapshot file here"}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Large files may take a few seconds to process.
                            </p>
                        </div>
                        <div className="flex gap-3 mt-2">
                            <input
                                type="file"
                                id="heap-upload"
                                className="hidden"
                                accept=".heapsnapshot"
                                onChange={handleFileChange}
                            />
                            <Button
                                variant="outline"
                                onClick={() => document.getElementById('heap-upload').click()}
                                disabled={loading}
                            >
                                Select File
                            </Button>
                            <Button
                                onClick={handleUpload}
                                disabled={!file || loading}
                                className="gap-2"
                            >
                                {loading ? (
                                    <>
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                        Analyzing...
                                    </>
                                ) : (
                                    <>
                                        <Zap className="h-4 w-4" />
                                        Start Analysis
                                    </>
                                )}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
                    {/* Summary Row */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                    <Database className="h-4 w-4" /> Total Memory
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatSize(results.summary.total_size)}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                    <Cpu className="h-4 w-4" /> Node Count
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{results.summary.total_nodes.toLocaleString()}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                    <FileJson className="h-4 w-4" /> String Entries
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{results.summary.strings_count.toLocaleString()}</div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Findings List */}
                        <Card className="lg:col-span-2">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    Analysis Results
                                    <Badge variant="outline">{results.findings.length} Findings</Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {results.findings.map((finding) => (
                                    <div
                                        key={finding.id}
                                        className={cn(
                                            "flex flex-col gap-3 p-4 rounded-xl border transition-all",
                                            getSeverityColor(finding.severity)
                                        )}
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2 font-bold text-lg">
                                                {finding.severity === 'high' ? <AlertTriangle className="h-5 w-5" /> : <Info className="h-5 w-5" />}
                                                {finding.title}
                                            </div>
                                            <Badge variant={finding.severity === 'high' ? "destructive" : "secondary"}>
                                                {finding.severity.toUpperCase()}
                                            </Badge>
                                        </div>

                                        <div className="text-sm opacity-90">
                                            Found <span className="font-bold">{finding.count}</span> occurrences.
                                            <div className="mt-2 p-2 bg-white/50 rounded-lg font-medium">
                                                <Zap className="h-3 w-3 inline mr-1" /> Recommendation: {finding.action}
                                            </div>
                                        </div>

                                        {finding.items && finding.items.length > 0 && (
                                            <div className="mt-2 space-y-1">
                                                <p className="text-[10px] uppercase font-bold tracking-widest opacity-60">Sample Data</p>
                                                <div className="grid grid-cols-1 gap-1">
                                                    {finding.items.map((item, idx) => (
                                                        <div key={idx} className="text-xs font-mono bg-black/5 p-1 px-2 rounded truncate">
                                                            {item}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}

                                {results.findings.length === 0 && (
                                    <div className="flex flex-col items-center justify-center py-12 gap-4 text-center">
                                        <div className="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                            <CheckCircle2 className="h-6 w-6" />
                                        </div>
                                        <div>
                                            <p className="font-bold">No critical issues found!</p>
                                            <p className="text-sm text-muted-foreground">Your memory management looks solid.</p>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Actions & Tips */}
                        <div className="flex flex-col gap-4">
                            <Card className="bg-primary text-primary-foreground border-none">
                                <CardHeader>
                                    <CardTitle className="text-lg">Quick Actions</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <Button variant="secondary" className="w-full justify-between" onClick={() => setResults(null)}>
                                        Analyze Another File <ArrowRight className="h-4 w-4" />
                                    </Button>
                                    <Button variant="outline" className="w-full bg-white/10 border-white/20 hover:bg-white/20 text-white" disabled>
                                        Download PDF Report
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Performance Tips</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex gap-3">
                                        <div className="h-8 w-8 rounded-lg bg-muted flex items-center justify-center shrink-0">
                                            <Zap className="h-4 w-4" />
                                        </div>
                                        <div className="text-xs">
                                            <p className="font-bold">Lazy Load Images</p>
                                            <p className="text-muted-foreground mt-0.5">Large image buffers are a common cause of high native memory.</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-3">
                                        <div className="h-8 w-8 rounded-lg bg-muted flex items-center justify-center shrink-0">
                                            < Zap className="h-4 w-4" />
                                        </div>
                                        <div className="text-xs">
                                            <p className="font-bold">Clear Global Maps</p>
                                            <p className="text-muted-foreground mt-0.5">WeakMap is often safer for associating data with DOM nodes.</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default HeapAnalysis;
