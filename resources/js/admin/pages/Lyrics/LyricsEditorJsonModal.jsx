import React, { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { toast } from 'sonner';
import {
    CheckCircle2, Copy, FileInput, Save, Sparkles, Type,
} from 'lucide-react';
import {
    AI_LYRICS_PROMPT,
    buildEditorJson,
    extractJsonObject,
    normalizeImportedParts,
    parseStructuredLyricsText,
} from './lyricsStructure';

/**
 * Client-side lyrics structure helper (create + edit).
 * Copy for AI → paste JSON or Genius-style labeled text → applies into the editor.
 */
export default function LyricsEditorJsonModal({
    open,
    onClose,
    lyricsId = null,
    lyricsTitle = '',
    romanTitle = '',
    parts = [],
    onApply,
}) {
    const editorJson = useMemo(
        () => buildEditorJson({
            id: lyricsId,
            lyrics_title: lyricsTitle,
            roman_title: romanTitle,
            parts,
        }),
        [lyricsId, lyricsTitle, romanTitle, parts],
    );

    const [copied, setCopied] = useState(false);
    const [copiedPrompt, setCopiedPrompt] = useState(false);
    const [mode, setMode] = useState('view'); // view | json | text
    const [input, setInput] = useState('');
    const [parseError, setParseError] = useState(null);

    useEffect(() => {
        if (!open) return;
        setMode('view');
        setInput('');
        setParseError(null);
        setCopied(false);
        setCopiedPrompt(false);
    }, [open, lyricsId]);

    const handleCopyJson = async () => {
        await navigator.clipboard.writeText(JSON.stringify(editorJson, null, 2));
        setCopied(true);
        toast.success('JSON copied — paste into Input JSON after AI edits it.');
        setTimeout(() => setCopied(false), 2000);
    };

    const handleCopyForAi = async () => {
        const payload = AI_LYRICS_PROMPT + JSON.stringify(editorJson, null, 2);
        await navigator.clipboard.writeText(payload);
        setCopiedPrompt(true);
        toast.success('Prompt + JSON copied. Paste into ChatGPT, then paste the reply back here.');
        setTimeout(() => setCopiedPrompt(false), 2000);
    };

    const handleCopyEmptyPrompt = async () => {
        const blank = buildEditorJson({
            lyrics_title: lyricsTitle || '',
            roman_title: romanTitle || '',
            parts: [],
        });
        blank._instructions = 'Paste raw lyrics after this JSON in the chat, then return filled parts.';
        const payload = `${AI_LYRICS_PROMPT}${JSON.stringify(blank, null, 2)}

--- RAW LYRICS (format these into parts above) ---

`;
        await navigator.clipboard.writeText(payload);
        setCopiedPrompt(true);
        toast.success('Empty structure prompt copied. Paste raw lyrics after the marker in ChatGPT.');
        setTimeout(() => setCopiedPrompt(false), 2000);
    };

    const applyNormalized = (normalizedParts, meta = {}) => {
        if (!normalizedParts.length) {
            setParseError('No lyric parts found.');
            return;
        }
        if (!confirm(`Replace the current timeline with ${normalizedParts.length} structured parts?`)) {
            return;
        }
        onApply?.({
            parts: normalizedParts,
            lyrics_title: meta.lyrics_title,
            roman_title: meta.roman_title,
        });
        toast.success(`Loaded ${normalizedParts.length} parts into the editor. Review and save.`);
        onClose?.();
    };

    const handleSubmitJson = () => {
        setParseError(null);
        let parsed;
        try {
            parsed = extractJsonObject(input);
        } catch (error) {
            setParseError(error.message || 'Invalid JSON.');
            return;
        }

        if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
            setParseError('JSON must be an object.');
            return;
        }

        try {
            const normalized = normalizeImportedParts(parsed.parts, (i) => `json-${Date.now()}-${i}`);
            applyNormalized(normalized, {
                lyrics_title: parsed.lyrics_title,
                roman_title: parsed.roman_title,
            });
        } catch (error) {
            setParseError(error.message || 'Could not import parts.');
        }
    };

    const handleSubmitText = () => {
        setParseError(null);
        try {
            const parsedParts = parseStructuredLyricsText(input);
            const normalized = normalizeImportedParts(parsedParts, (i) => `text-${Date.now()}-${i}`);
            applyNormalized(normalized, {});
        } catch (error) {
            setParseError(error.message || 'Could not parse structured lyrics.');
        }
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="max-w-3xl max-h-[85vh] flex flex-col">
                <DialogHeader>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between pr-6">
                        <div>
                            <DialogTitle>Lyrics structure JSON</DialogTitle>
                            <DialogDescription>
                                {mode === 'view' && 'Copy for AI → get [Intro]/[Verse]/[Chorus] parts as JSON → paste back. Or paste labeled lyrics text.'}
                                {mode === 'json' && 'Paste the JSON object ChatGPT returned (markdown fences OK).'}
                                {mode === 'text' && 'Paste Genius-style lyrics with [Intro], [Verse 1], [Chorus], [Outro] headers.'}
                            </DialogDescription>
                        </div>
                        <div className="flex flex-wrap gap-2 shrink-0">
                            <Button variant="outline" size="sm" onClick={handleCopyForAi} disabled={mode !== 'view'}>
                                {copiedPrompt ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <Sparkles className="h-4 w-4 mr-2" />}
                                {copiedPrompt ? 'Copied' : 'Copy for AI'}
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleCopyEmptyPrompt} disabled={mode !== 'view'}>
                                <Sparkles className="h-4 w-4 mr-2" />
                                Prompt + blank
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleCopyJson} disabled={mode !== 'view'}>
                                {copied ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <Copy className="h-4 w-4 mr-2" />}
                                {copied ? 'Copied' : 'Copy JSON'}
                            </Button>
                            <Button
                                variant={mode === 'json' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => { setMode('json'); setParseError(null); setInput(''); }}
                            >
                                <FileInput className="h-4 w-4 mr-2" />
                                Input JSON
                            </Button>
                            <Button
                                variant={mode === 'text' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => { setMode('text'); setParseError(null); setInput(''); }}
                            >
                                <Type className="h-4 w-4 mr-2" />
                                Paste lyrics
                            </Button>
                        </div>
                    </div>
                </DialogHeader>

                {mode === 'json' || mode === 'text' ? (
                    <div className="flex flex-col gap-3 min-h-0 flex-1">
                        <Textarea
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            className="font-mono text-xs min-h-[420px] flex-1"
                            dir="auto"
                            spellCheck={false}
                            placeholder={mode === 'json'
                                ? '{\n  "_schema": "baakh.lyrics.editor_json.v1",\n  "lyrics_title": "…",\n  "parts": [ … ]\n}'
                                : '[Intro]\n\n…\n\n[Chorus]\n\n…\n\n[Verse 1]\n\n…'}
                        />
                        {parseError && (
                            <p className="text-sm text-destructive" role="alert">{parseError}</p>
                        )}
                        <div className="flex justify-end gap-2">
                            <Button variant="outline" onClick={() => setMode('view')}>Cancel</Button>
                            <Button
                                onClick={mode === 'json' ? handleSubmitJson : handleSubmitText}
                                disabled={!input.trim()}
                            >
                                <Save className="h-4 w-4 mr-2" />
                                Apply to editor
                            </Button>
                        </div>
                    </div>
                ) : (
                    <ScrollArea className="flex-1 bg-muted/50 rounded-md border p-4">
                        <pre className="text-xs font-mono whitespace-pre-wrap break-all" dir="ltr">
                            {JSON.stringify(editorJson, null, 2)}
                        </pre>
                    </ScrollArea>
                )}
            </DialogContent>
        </Dialog>
    );
}
