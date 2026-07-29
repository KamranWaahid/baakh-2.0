import React, { useEffect, useMemo, useState } from 'react';
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
import { toast } from 'sonner';
import {
    Loader2, Copy, CheckCircle2, FileInput, Save, Sparkles,
} from 'lucide-react';

const AI_ENRICH_PROMPT = `Enrich this Baakh dictionary lemma JSON.

Return ONLY one valid JSON object. No markdown fences. No explanation before or after.

Must keep the same shape and keys (_schema, _instructions, id, public_id, general, completion, morphology, senses, relations, variants, forms).
Keep top-level id/public_id and any existing numeric row ids.
english_equivalents = string array.
Examples: sentence (Sindhi), translation (English), optional romanization/source.
Idioms: phrase, english_gloss, example_sindhi, example_english.
Relations: relation_type is synonym|antonym|hypernym|related|singular|plural|dialect|derived|usage + related_word. derived = محبتي/خوني/پياري. usage = people-say/first-second form; put label in note and example sentence in gloss.
Variant type: spelling|misspelling|dialectal|historical|diacritic|normalized|short_vowel_variant|fully_voweled_variant|fatha_variant.
completion_status: pending|complete. source_confidence: 0-100.
ROMAN ONLY (critical): general.transliteration and every romanization field = plain Latin a–z, spaces, hyphens only. No Arabic/Sindhi script. No zabar/zer/pesh (َ ِ ُ), tashdeed (ّ), sukun (ْ), tanween, or Latin accented letters (ā ī ū). Write aadmi not ādmī / not آدْمي.
PRIMARY DEFINITIONS IN SINDHI (critical): for each sense, definition and short_gloss MUST be Sindhi (Arabic script). Do not use English as the primary definition. Put English only in definition_en (optional). definition_sd may clarify Sindhi when helpful. Prefer language_direction "sindhi".
Add more senses/examples/relations/variants/inflections/idioms when useful. Use Standard Sindhi Arabic script.

JSON:
`;

function extractLemmaJson(raw) {
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

export default function LemmaEditorJsonModal({ lemmaId, onClose }) {
    const queryClient = useQueryClient();
    const { data: lemma, isLoading } = useQuery({
        queryKey: ['lemma-editor-json', lemmaId],
        queryFn: async () => {
            const res = await api.get(`/api/admin/dictionary/lemmas/${lemmaId}/editor-json`);
            return res.data;
        },
        enabled: !!lemmaId,
    });

    const [copied, setCopied] = useState(false);
    const [copiedPrompt, setCopiedPrompt] = useState(false);
    const [mode, setMode] = useState('view'); // view | input
    const [viewText, setViewText] = useState('');
    const [inputJson, setInputJson] = useState('');
    const [parseError, setParseError] = useState(null);

    const aiPayload = useMemo(
        () => (lemma ? AI_ENRICH_PROMPT + JSON.stringify(lemma, null, 2) : ''),
        [lemma]
    );

    useEffect(() => {
        setMode('view');
        setInputJson('');
        setParseError(null);
        setCopied(false);
        setCopiedPrompt(false);
    }, [lemmaId]);

    useEffect(() => {
        if (aiPayload) {
            setViewText(aiPayload);
        }
    }, [aiPayload]);

    const handleCopyJson = async () => {
        if (!lemma) return;
        await navigator.clipboard.writeText(JSON.stringify(lemma, null, 2));
        setCopied(true);
        toast.success('JSON copied — paste into Input JSON after AI edits it.');
        setTimeout(() => setCopied(false), 2000);
    };

    const handleCopyForAi = async () => {
        const payload = viewText.trim() || aiPayload;
        if (!payload) return;
        await navigator.clipboard.writeText(payload);
        setCopiedPrompt(true);
        toast.success('Prompt + JSON copied. Paste into ChatGPT, then paste the reply JSON back via Input JSON.');
        setTimeout(() => setCopiedPrompt(false), 2000);
    };

    const handleOpenInput = () => {
        setMode('input');
        setParseError(null);
        setInputJson('');
    };

    const importJson = useMutation({
        mutationFn: async (payload) => {
            const res = await api.post(`/api/admin/dictionary/lemmas/${lemmaId}/import-json`, payload);
            return res.data;
        },
        onSuccess: (data) => {
            queryClient.setQueryData(['lemma-editor-json', lemmaId], data);
            queryClient.invalidateQueries({ queryKey: ['dictionary-browse'] });
            queryClient.invalidateQueries({ queryKey: ['dictionary-stats'] });
            queryClient.invalidateQueries({ queryKey: ['lemma', lemmaId] });
            queryClient.invalidateQueries({ queryKey: ['lemma-editor-json', lemmaId] });
            setMode('view');
            setParseError(null);
            toast.success('Lemma rewritten from JSON.');
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
            parsed = extractLemmaJson(inputJson);
        } catch (error) {
            setParseError(error.message || 'Invalid JSON. Fix the syntax and try again.');
            return;
        }

        if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
            setParseError('JSON must be an object for a single lemma.');
            return;
        }

        if (!confirm('This will rewrite all editor fields (General, Completion, Morphology, Senses, Relations, Variants, Forms) from the pasted JSON. Continue?')) {
            return;
        }

        importJson.mutate(parsed);
    };

    return (
        <Dialog open={!!lemmaId} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-3xl max-h-[85vh] flex flex-col">
                <DialogHeader>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between pr-6">
                        <div>
                            <DialogTitle>Word Data JSON</DialogTitle>
                            <DialogDescription>
                                {mode === 'view'
                                    ? 'Edit box below has the AI prompt + JSON. Select all / Copy for AI → paste into ChatGPT → paste the reply via Input JSON.'
                                    : 'Paste ONLY the JSON object ChatGPT returned (markdown fences are OK).'}
                            </DialogDescription>
                        </div>
                        <div className="flex flex-wrap gap-2 shrink-0">
                            <Button variant="outline" size="sm" onClick={handleCopyForAi} disabled={!lemma || mode === 'input'}>
                                {copiedPrompt ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <Sparkles className="h-4 w-4 mr-2" />}
                                {copiedPrompt ? 'Copied' : 'Copy for AI'}
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleCopyJson} disabled={!lemma || mode === 'input'}>
                                {copied ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <Copy className="h-4 w-4 mr-2" />}
                                {copied ? 'Copied' : 'Copy JSON'}
                            </Button>
                            <Button variant={mode === 'input' ? 'default' : 'outline'} size="sm" onClick={handleOpenInput} disabled={!lemma}>
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
                            placeholder={'Paste JSON only, e.g.\n{\n  "_schema": "baakh.dictionary.editor_json.v1",\n  ...\n}'}
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
                    <div className="flex flex-col gap-2 min-h-0 flex-1">
                        <p className="text-xs text-muted-foreground">
                            AI prompt + lemma JSON — edit if needed, then copy into ChatGPT.
                        </p>
                        <Textarea
                            value={viewText}
                            onChange={(e) => setViewText(e.target.value)}
                            onFocus={(e) => e.target.select()}
                            className="font-mono text-xs min-h-[420px] flex-1"
                            dir="ltr"
                            spellCheck={false}
                        />
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
