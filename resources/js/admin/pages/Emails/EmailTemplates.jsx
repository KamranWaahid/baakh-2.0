import React, { useState, useEffect } from 'react';
import { Mail, Loader2, Eye, LayoutTemplate, CheckCircle2 } from 'lucide-react';
import api from '../../api/axios';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const EmailTemplates = () => {
    const [templates, setTemplates] = useState([]);
    const [loading, setLoading] = useState(true);
    const [activeTemplate, setActiveTemplate] = useState('welcome');
    const [htmlContent, setHtmlContent] = useState('');
    const [previewLoading, setPreviewLoading] = useState(false);

    useEffect(() => {
        fetchTemplates();
    }, []);

    useEffect(() => {
        if (activeTemplate && templates.length > 0) {
            fetchPreview(activeTemplate);
        }
    }, [activeTemplate, templates]);

    const fetchTemplates = async () => {
        try {
            const response = await api.get('/api/admin/emails/templates');
            setTemplates(response.data);
            if (response.data.length > 0) {
                setActiveTemplate(response.data[0].id);
            }
        } catch (error) {
            console.error('Failed to load templates', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchPreview = async (id) => {
        setPreviewLoading(true);
        try {
            const response = await api.get(`/api/admin/emails/preview/${id}`, {
                responseType: 'text',
                headers: {
                    'Accept': 'text/html'
                }
            });
            setHtmlContent(response.data);
        } catch (error) {
            console.error('Failed to load preview', error);
            setHtmlContent('<div style="font-family:sans-serif;text-align:center;padding:50px;color:red;">Failed to load preview. Please check network logs.</div>');
        } finally {
            setPreviewLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="flex h-64 items-center justify-center">
                <Loader2 className="h-8 w-8 animate-spin text-primary" />
            </div>
        );
    }

    return (
        <div className="flex-1 space-y-4 p-8 pt-6">
            <div className="flex items-center justify-between space-y-2">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight flex items-center gap-2">
                        <Mail className="h-8 w-8 text-primary" />
                        Email Templates
                    </h2>
                    <p className="text-muted-foreground">
                        Preview and audit the foundational system email layout designs.
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 min-h-[600px] h-auto">
                {/* Navigation Sidebar */}
                <Card className="col-span-1 flex flex-col overflow-hidden max-h-[600px]">
                    <CardHeader className="bg-slate-50/50 pb-4 border-b">
                        <CardTitle className="text-lg flex items-center gap-2">
                            <LayoutTemplate className="h-5 w-5" />
                            System Templates
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex-1 overflow-y-auto p-3 space-y-2">
                        {templates.map(template => (
                            <button
                                key={template.id}
                                onClick={() => setActiveTemplate(template.id)}
                                className={`w-full text-left px-4 py-3 rounded-lg border transition-all ${
                                    activeTemplate === template.id
                                        ? 'bg-blue-50 border-blue-200 ring-1 ring-blue-500/20'
                                        : 'bg-white border-transparent hover:bg-gray-50 hover:border-gray-200'
                                }`}
                            >
                                <div className="flex items-center justify-between mb-1">
                                    <span className={`font-semibold text-sm ${activeTemplate === template.id ? 'text-blue-700' : 'text-gray-900'}`}>
                                        {template.name}
                                    </span>
                                    {activeTemplate === template.id && <CheckCircle2 className="h-4 w-4 text-blue-600" />}
                                </div>
                                <p className="text-xs text-gray-500 line-clamp-2">
                                    {template.description}
                                </p>
                            </button>
                        ))}
                    </CardContent>
                </Card>

                {/* Preview Area */}
                <Card className="col-span-1 lg:col-span-3 flex flex-col shadow-sm overflow-hidden border-slate-200 min-h-[700px]">
                    <CardHeader className="bg-slate-50 py-3 border-b flex flex-row items-center justify-between shrink-0">
                        <div className="flex items-center gap-2">
                            <Eye className="h-5 w-5 text-slate-500" />
                            <CardTitle className="text-base font-semibold text-slate-800">
                                Output Viewer
                            </CardTitle>
                        </div>
                        <div className="flex gap-2">
                            {previewLoading && <Loader2 className="h-4 w-4 animate-spin text-slate-400" />}
                            <span className="text-xs font-mono text-slate-500 bg-white px-2 py-1 rounded border">Responsive Container</span>
                        </div>
                    </CardHeader>
                    <div className="flex-1 bg-slate-200/50 p-4 lg:p-8 w-full relative">
                        <div className="mx-auto w-full max-w-[650px] bg-white shadow-xl rounded-md overflow-hidden min-h-[600px] border border-gray-100 flex flex-col relative transition-all duration-300">
                            {previewLoading && (
                                <div className="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center">
                                    <Loader2 className="h-8 w-8 text-primary animate-spin" />
                                </div>
                            )}
                            <iframe 
                                srcDoc={htmlContent} 
                                className="w-full flex-1 min-h-[600px] bg-white border-0"
                                title="Email Application Preview"
                                sandbox="allow-same-origin allow-popups"
                            />
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    );
};

export default EmailTemplates;
