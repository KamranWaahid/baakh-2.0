import React, { useEffect, useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import api from '../../api/axios';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { toast } from 'sonner';
import {
    CheckCircle2, Copy, FileInput, Loader2, Save, Sparkles, Tags,
} from 'lucide-react';

function extractJsonObject(raw) {
    const text = String(raw || '').trim();
    if (!text) throw new Error('Paste JSON first.');

    try {
        return JSON.parse(text);
    } catch {
        // fall through
    }

    const fence = text.match(/```(?:json)?\s*([\s\S]*?)```/i);
    if (fence?.[1]) {
        return JSON.parse(fence[1].trim());
    }

    const start = text.indexOf('{');
    const end = text.lastIndexOf('}');
    if (start >= 0 && end > start) {
        return JSON.parse(text.slice(start, end + 1));
    }

    throw new Error('Invalid JSON. Fix the syntax and try again.');
}

function stripHtml(value) {
    return String(value || '')
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<\/p>/gi, '\n')
        .replace(/<[^>]+>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/\s+\n/g, '\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

export default function PoetryTaxonomyJsonModal({
    open,
    onClose,
    title = '',
    poetryText = '',
    topicCategoryId = '',
    tagIds = [],
    onApplied,
}) {
    const [mode, setMode] = useState('view');
    const [inputJson, setInputJson] = useState('');
    const [parseError, setParseError] = useState(null);
    const [copiedKind, setCopiedKind] = useState(null);

    useEffect(() => {
        if (!open) return;
        setMode('view');
        setInputJson('');
        setParseError(null);
        setCopiedKind(null);
    }, [open]);

    const copyMutation = useMutation({
        mutationFn: async (kind) => {
            const response = await api.post('/api/admin/poetry/taxonomy-json', {
                kind,
                title,
                text: stripHtml(poetryText),
                topic_category_id: topicCategoryId || null,
                tag_ids: tagIds,
            });
            return { kind, payload: response.data };
        },
        onSuccess: async ({ kind, payload }) => {
            await navigator.clipboard.writeText(JSON.stringify(payload, null, 2));
            setCopiedKind(kind);
            toast.success(
                kind === 'topic_categories'
                    ? 'Topic categories JSON copied. Paste into ChatGPT, then import the reply.'
                    : 'Tags JSON copied. Paste into ChatGPT, then import the reply.'
            );
            setTimeout(() => setCopiedKind(null), 2000);
        },
        onError: (error) => {
            toast.error(error.response?.data?.message || 'Could not build taxonomy JSON.');
        },
    });

    const applyMutation = useMutation({
        mutationFn: async (payload) => {
            const response = await api.post('/api/admin/poetry/taxonomy-json/apply', { payload });
            return response.data;
        },
        onSuccess: (data) => {
            onApplied?.(data);
            const createdCats = data?.created?.topic_categories?.length || 0;
            const createdTags = data?.created?.tags?.length || 0;
            const matchedTags = data?.poetry_tags?.length || 0;
            toast.success(
                createdCats || createdTags
                    ? `Applied. Linked existing items; added ${createdCats} categor${createdCats === 1 ? 'y' : 'ies'} and ${createdTags} tag${createdTags === 1 ? '' : 's'}.`
                    : `Applied. Linked ${data?.topic_category_applied ? 'topic category' : ''}${data?.topic_category_applied && data?.tags_applied ? ' and ' : ''}${data?.tags_applied ? `${matchedTags} tag${matchedTags === 1 ? '' : 's'}` : ''}.`
            );
            onClose?.();
        },
        onError: (error) => {
            toast.error(error.response?.data?.message || 'Could not apply taxonomy JSON.');
        },
    });

    const handleApply = () => {
        setParseError(null);
        try {
            applyMutation.mutate(extractJsonObject(inputJson));
        } catch (error) {
            setParseError(error.message || 'Invalid JSON.');
        }
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose?.()}>
            <DialogContent className="max-w-2xl max-h-[85vh] flex flex-col">
                <DialogHeader>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between pr-6">
                        <div>
                            <DialogTitle className="flex items-center gap-2">
                                <Tags className="h-4 w-4" /> Topic & tags JSON
                            </DialogTitle>
                            <DialogDescription>
                                Copy the catalog JSON for ChatGPT. It must pick existing ids first.
                                New category or tag only if nothing in the database matches.
                            </DialogDescription>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {mode === 'view' ? (
                                <Button variant="outline" size="sm" onClick={() => setMode('input')}>
                                    <FileInput className="h-4 w-4 mr-2" />
                                    Input JSON
                                </Button>
                            ) : (
                                <Button variant="outline" size="sm" onClick={() => setMode('view')}>
                                    Back
                                </Button>
                            )}
                        </div>
                    </div>
                </DialogHeader>

                {mode === 'view' ? (
                    <div className="space-y-4">
                        <p className="text-sm text-muted-foreground">
                            Two separate JSON payloads: topic categories, then tags. Paste poetry text first so the model can classify against the live catalog.
                        </p>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Button
                                type="button"
                                variant="outline"
                                className="h-auto py-3 justify-start"
                                disabled={copyMutation.isPending}
                                onClick={() => copyMutation.mutate('topic_categories')}
                            >
                                {copiedKind === 'topic_categories'
                                    ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500 shrink-0" />
                                    : copyMutation.isPending && copyMutation.variables === 'topic_categories'
                                        ? <Loader2 className="h-4 w-4 mr-2 animate-spin shrink-0" />
                                        : <Sparkles className="h-4 w-4 mr-2 shrink-0" />}
                                <span className="text-left">
                                    <span className="block text-sm font-medium">Copy topic categories</span>
                                    <span className="block text-xs text-muted-foreground font-normal">Existing catalog + pick one</span>
                                </span>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-auto py-3 justify-start"
                                disabled={copyMutation.isPending}
                                onClick={() => copyMutation.mutate('tags')}
                            >
                                {copiedKind === 'tags'
                                    ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500 shrink-0" />
                                    : copyMutation.isPending && copyMutation.variables === 'tags'
                                        ? <Loader2 className="h-4 w-4 mr-2 animate-spin shrink-0" />
                                        : <Copy className="h-4 w-4 mr-2 shrink-0" />}
                                <span className="text-left">
                                    <span className="block text-sm font-medium">Copy tags</span>
                                    <span className="block text-xs text-muted-foreground font-normal">Existing catalog + pick several</span>
                                </span>
                            </Button>
                        </div>
                        <div className="rounded-md border bg-muted/30 p-3 text-xs text-muted-foreground space-y-1">
                            <p>1. Copy topic categories JSON → ChatGPT → paste reply via Input JSON.</p>
                            <p>2. Copy tags JSON → ChatGPT → paste reply via Input JSON (can include both keys in one object).</p>
                            <p>Prefer <code>existing_id</code> / <code>existing_ids</code>. Use <code>create</code> only when the catalog has no match.</p>
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-col gap-3 min-h-0 flex-1">
                        <Textarea
                            value={inputJson}
                            onChange={(e) => setInputJson(e.target.value)}
                            className="font-mono text-xs min-h-[320px] flex-1"
                            dir="ltr"
                            spellCheck={false}
                            placeholder={'Paste JSON only, e.g.\n{\n  "topic_category": { "existing_id": 12 },\n  "tags": { "existing_ids": [4, 9] }\n}'}
                        />
                        {parseError && (
                            <p className="text-sm text-destructive">{parseError}</p>
                        )}
                        <div className="flex justify-end">
                            <Button
                                type="button"
                                onClick={handleApply}
                                disabled={applyMutation.isPending || !inputJson.trim()}
                            >
                                {applyMutation.isPending
                                    ? <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                    : <Save className="h-4 w-4 mr-2" />}
                                Apply to form
                            </Button>
                        </div>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
