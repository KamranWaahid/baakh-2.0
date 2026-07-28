import React, { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { toast } from 'sonner';
import {
    Loader2, Copy, CheckCircle2, FileInput, Save, Sparkles,
} from 'lucide-react';

const AI_ENRICH_PROMPT = `Enrich this Baakh poet profile JSON.

Return ONLY one valid JSON object. No markdown fences. No explanation before or after.

Must keep the same shape and keys (_schema, _instructions, id, poet_slug, date_of_birth, date_of_death, visibility, is_featured, details).
Do NOT include image, poet_pic, or any photo fields.
Keep top-level id and any existing detail ids when present.
details[] MUST include BOTH languages: one object with lang="sd" AND one with lang="en" (ur optional).
Fill all fields for each language: poet_name, poet_laqab, pen_name, tagline, poet_bio, birth_place, birth_place_name, death_place, death_place_name.
poet_name and poet_laqab are required (min 3 chars) per language.
poet_bio / tagline / pen_name: write accurate, concise literary biography text.
birth_place / death_place: city id as string when known, otherwise set birth_place_name / death_place_name (city name).
Dates: YYYY-MM-DD or null. visibility / is_featured: boolean.
Use Standard Sindhi Arabic script for sd fields; English for en fields.

JSON:
`;

function extractPoetJson(raw) {
    const text = String(raw || '').trim();
    if (!text) {
        throw new Error('Paste JSON first.');
    }

    try {
        return JSON.parse(text);
    } catch {
        // AI often wraps output in ```json ... ```
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

export default function PoetEditorJsonModal({ poetId, open, onClose }) {
    const queryClient = useQueryClient();
    const { data: poetJson, isLoading } = useQuery({
        queryKey: ['poet-editor-json', poetId],
        queryFn: async () => {
            const res = await api.get(`/api/admin/poets/${poetId}/editor-json`);
            return res.data;
        },
        enabled: !!poetId && open,
    });

    const [copied, setCopied] = useState(false);
    const [copiedPrompt, setCopiedPrompt] = useState(false);
    const [mode, setMode] = useState('view'); // view | input
    const [inputJson, setInputJson] = useState('');
    const [parseError, setParseError] = useState(null);

    useEffect(() => {
        if (!open) return;
        setMode('view');
        setInputJson('');
        setParseError(null);
        setCopied(false);
        setCopiedPrompt(false);
    }, [poetId, open]);

    const handleCopyJson = async () => {
        if (!poetJson) return;
        await navigator.clipboard.writeText(JSON.stringify(poetJson, null, 2));
        setCopied(true);
        toast.success('JSON copied — paste into Input JSON after AI edits it.');
        setTimeout(() => setCopied(false), 2000);
    };

    const handleCopyForAi = async () => {
        if (!poetJson) return;
        const payload = AI_ENRICH_PROMPT + JSON.stringify(poetJson, null, 2);
        await navigator.clipboard.writeText(payload);
        setCopiedPrompt(true);
        toast.success('Prompt + JSON copied. Paste into ChatGPT, then paste the reply JSON back here.');
        setTimeout(() => setCopiedPrompt(false), 2000);
    };

    const handleOpenInput = () => {
        setMode('input');
        setParseError(null);
        setInputJson('');
    };

    const importJson = useMutation({
        mutationFn: async (payload) => {
            const res = await api.post(`/api/admin/poets/${poetId}/import-json`, payload);
            return res.data;
        },
        onSuccess: (data) => {
            queryClient.setQueryData(['poet-editor-json', poetId], data);
            queryClient.invalidateQueries({ queryKey: ['poet', String(poetId)] });
            queryClient.invalidateQueries({ queryKey: ['poet', poetId] });
            queryClient.invalidateQueries({ queryKey: ['poets'] });
            setMode('view');
            setParseError(null);
            toast.success('Poet updated from JSON.');
            onClose?.();
        },
        onError: (error) => {
            const message = error?.response?.data?.message
                || (error?.response?.data?.errors && Object.values(error.response.data.errors).flat().join(' '))
                || 'Failed to import JSON.';
            toast.error(message);
            setParseError(message);
        },
    });

    const handleSubmitJson = () => {
        setParseError(null);
        let parsed;
        try {
            parsed = extractPoetJson(inputJson);
        } catch (error) {
            setParseError(error.message || 'Invalid JSON. Fix the syntax and try again.');
            return;
        }

        if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
            setParseError('JSON must be an object for a single poet.');
            return;
        }

        if (!confirm('This will rewrite poet fields and language details from the pasted JSON (image is ignored). Continue?')) {
            return;
        }

        importJson.mutate(parsed);
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="max-w-3xl max-h-[85vh] flex flex-col">
                <DialogHeader>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between pr-6">
                        <div>
                            <DialogTitle>Poet Data JSON</DialogTitle>
                            <DialogDescription>
                                {mode === 'view'
                                    ? 'Includes both sd and en details. Copy for AI → paste ChatGPT reply via Input JSON. Images ignored.'
                                    : 'Paste ONLY the JSON object ChatGPT returned (markdown fences are OK).'}
                            </DialogDescription>
                        </div>
                        <div className="flex flex-wrap gap-2 shrink-0">
                            <Button variant="outline" size="sm" onClick={handleCopyForAi} disabled={!poetJson || mode === 'input'}>
                                {copiedPrompt ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <Sparkles className="h-4 w-4 mr-2" />}
                                {copiedPrompt ? 'Copied' : 'Copy for AI'}
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleCopyJson} disabled={!poetJson || mode === 'input'}>
                                {copied ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <Copy className="h-4 w-4 mr-2" />}
                                {copied ? 'Copied' : 'Copy JSON'}
                            </Button>
                            <Button variant={mode === 'input' ? 'default' : 'outline'} size="sm" onClick={handleOpenInput} disabled={!poetJson}>
                                <FileInput className="h-4 w-4 mr-2" />
                                Input JSON
                            </Button>
                        </div>
                    </div>
                </DialogHeader>
                {isLoading ? (
                    <div className="flex h-40 items-center justify-center">
                        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                    </div>
                ) : mode === 'input' ? (
                    <div className="flex flex-col gap-3 min-h-0 flex-1">
                        <Textarea
                            value={inputJson}
                            onChange={(e) => setInputJson(e.target.value)}
                            className="font-mono text-xs min-h-[420px] flex-1"
                            dir="ltr"
                            spellCheck={false}
                            placeholder={'Paste JSON only, e.g.\n{\n  "_schema": "baakh.poet.editor_json.v1",\n  ...\n}'}
                        />
                        {parseError && (
                            <p className="text-sm text-destructive" role="alert">{parseError}</p>
                        )}
                        <div className="flex justify-end gap-2">
                            <Button variant="outline" onClick={() => setMode('view')} disabled={importJson.isPending}>
                                Cancel
                            </Button>
                            <Button onClick={handleSubmitJson} disabled={importJson.isPending || !inputJson.trim()}>
                                {importJson.isPending ? <Loader2 className="h-4 w-4 mr-2 animate-spin" /> : <Save className="h-4 w-4 mr-2" />}
                                Submit & Rewrite
                            </Button>
                        </div>
                    </div>
                ) : (
                    <ScrollArea className="flex-1 bg-muted/50 rounded-md border p-4">
                        <pre className="text-xs font-mono whitespace-pre-wrap break-all" dir="ltr">
                            {JSON.stringify(poetJson, null, 2)}
                        </pre>
                    </ScrollArea>
                )}
            </DialogContent>
        </Dialog>
    );
}
