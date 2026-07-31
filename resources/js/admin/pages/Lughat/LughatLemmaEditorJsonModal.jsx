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
import { Badge } from '@/components/ui/badge';
import {
    Loader2, Copy, CheckCircle2, FileInput, Save, Sparkles, BookMarked,
} from 'lucide-react';

/** Compact prompt — keep under ChatGPT paste limits; rules stay dense. */
const AI_ENRICH_PROMPT = `Enrich this Baakh Lughat lemma JSON. Return ONLY one valid JSON object (no markdown, no prose).

RULE #1 — NEVER IGNORE EMPTY BOXES: empty_editor_fields.fields[] lists every null/empty Baakh Lughat editor input. Fill each path. Keep keys even if still unknown (null or []). Do not drop sections. Server marks complete only when checklist passes — default completion_status=pending.

INPUTS (read-only helpers — omit from output): general_dictionary, relations_checklist, empty_editor_fields, completion.missing_requirements. poetry.* is empty unless couplets are pasted separately in chat.

KEEP output keys: _schema,_name,_instructions,id,public_id,poetry,general,completion,morphology,senses,relations,variants,forms,expression_candidates,occurrence_summary. Keep numeric ids. NO GUILLEMETS «»/‹› (آدمي not «آدمي»).

GENERAL (all boxes): lemma,normalized_lemma,pos,transliteration (ASCII a-z/spaces/hyphens only),ipa,phonetic,pronunciation_simple,audio_url (URL or null),syllabification,source_confidence,status,etymology,notes,search_keywords_sindhi[],search_keywords_english[],search_keywords_romanized[],metadata_region,metadata_dialect_notes,metadata_version,variants_reviewed,examples_reviewed,morphology_reviewed,pronunciation_reviewed,primary_meanings.{definition,definition_sd,definition_en}. Set *reviewed=true only after those sections are filled.

MORPHOLOGY (all boxes): root,pattern,gender,number,case,tense — fill every knowable cell; for particle/postposition/conjunction at least pattern (e.g. غير متصرف حرف اضافت). morphology_reviewed=true when done.

SENSES (every sense — every box): definition,short_gloss,definition_sd (Sindhi Arabic script),definition_en,english_equivalents[] (required array),usage_label,domain,language_direction=sindhi,source_dictionary=Baakh Lughat,publisher=baakh.com,publisher_url=https://baakh.com/,prepared_by=Kamran Wahid,review_status=reviewed,status=approved,examples[{sentence,romanization,translation,source,citation,review_status}]. Keep existing sense ids; new senses omit id. Prefer 2–4 senses (literal + figurative/poetic).

RELATIONS (entry options): synonym|antonym|hypernym|singular|plural|dialect|derived|usage|related. MUST have typed synonym+ when knowable — do NOT dump near-synonyms only into related. Each row: relation_type,related_word,romanization,note,gloss,part_of_speech. Fill relations_checklist.empty buckets.

VARIANTS (every box): variant,normalized_variant,type,romanization,dialect,note,source. Types: spelling|misspelling|dialectal|historical|diacritic|normalized|short_vowel_variant|fully_voweled_variant|fatha_variant. Airab in variant; normalized_variant strips airab. Grammar → forms.inflections. variants_reviewed=true.

FORMS: inflections[{form,normalized_form,romanization,form_type,gender,number,case,person,stem,suffix,confidence,description}] required for noun/verb/adj. idiomatic_expressions[{phrase,romanization,english_gloss,example_sindhi,example_english}] when attested else []. expression_candidates when couplets justify multi-word phrases.

COMPLETION: completion_status=pending unless every required editor box is truly filled. publish_romanization=false.

JSON:
`;

function slimLemmaForAi(lemma) {
    if (!lemma || typeof lemma !== 'object') return lemma;

    const gd = lemma.general_dictionary && typeof lemma.general_dictionary === 'object'
        ? { ...lemma.general_dictionary }
        : lemma.general_dictionary;

    if (gd && typeof gd === 'object') {
        delete gd.full_entry;
        delete gd._note;
        if (Array.isArray(gd.entries)) {
            gd.entries = gd.entries.slice(0, 6).map((e) => ({
                match_type: e.match_type,
                id: e.id,
                lemma: e.lemma,
                pos: e.pos,
                transliteration: e.transliteration,
            }));
        }
        if (Array.isArray(gd.senses)) {
            gd.senses = gd.senses.slice(0, 8);
        }
    }

    const forms = lemma.forms && typeof lemma.forms === 'object'
        ? {
            inflections: Array.isArray(lemma.forms.inflections) ? lemma.forms.inflections : [],
            idiomatic_expressions: Array.isArray(lemma.forms.idiomatic_expressions)
                ? lemma.forms.idiomatic_expressions
                : [],
            expressions: [],
        }
        : lemma.forms;

    const emptyFields = lemma.empty_editor_fields && typeof lemma.empty_editor_fields === 'object'
        ? {
            count: lemma.empty_editor_fields.count,
            fields: Array.isArray(lemma.empty_editor_fields.fields)
                ? lemma.empty_editor_fields.fields.slice(0, 80)
                : [],
        }
        : undefined;

    return {
        _schema: lemma._schema,
        _name: lemma._name,
        id: lemma.id,
        public_id: lemma.public_id,
        general_dictionary: gd,
        general: lemma.general,
        completion: lemma.completion,
        morphology: lemma.morphology,
        senses: lemma.senses,
        relations: lemma.relations,
        relations_checklist: lemma.relations_checklist
            ? { empty: lemma.relations_checklist.empty, counts: lemma.relations_checklist.counts }
            : undefined,
        empty_editor_fields: emptyFields,
        variants: lemma.variants,
        forms,
        expression_candidates: Array.isArray(lemma.expression_candidates) ? lemma.expression_candidates : [],
        occurrence_summary: lemma.occurrence_summary,
    };
}

function buildAiPayload(lemma) {
    if (!lemma) return '';
    // Compact JSON (no pretty-print) — much smaller for ChatGPT paste.
    return AI_ENRICH_PROMPT + JSON.stringify(slimLemmaForAi(lemma));
}

function formatBytes(n) {
    if (n < 1024) return `${n} chars`;
    return `${(n / 1024).toFixed(1)} KB`;
}

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

export default function LughatLemmaEditorJsonModal({ lemmaId, onClose, onImported }) {
    const queryClient = useQueryClient();
    const { data: lemma, isLoading } = useQuery({
        queryKey: ['lughat-lemma-editor-json', lemmaId],
        queryFn: async () => {
            const res = await api.get(`/api/admin/lughat/lemmas/${lemmaId}/editor-json`);
            return res.data;
        },
        enabled: !!lemmaId,
    });

    const [copied, setCopied] = useState(false);
    const [copiedPrompt, setCopiedPrompt] = useState(false);
    const [copiedDictionary, setCopiedDictionary] = useState(false);
    const [mode, setMode] = useState('view'); // view | input
    const [viewText, setViewText] = useState('');
    const [inputJson, setInputJson] = useState('');
    const [parseError, setParseError] = useState(null);

    const aiPayload = useMemo(() => buildAiPayload(lemma), [lemma]);

    const dictionaryContext = lemma?.general_dictionary;
    const dictionaryFound = !!dictionaryContext?.found;
    const dictionaryMatchCount = dictionaryContext?.match_count
        || (Array.isArray(dictionaryContext?.entries) ? dictionaryContext.entries.length : 0);

    useEffect(() => {
        setMode('view');
        setInputJson('');
        setParseError(null);
        setCopied(false);
        setCopiedPrompt(false);
        setCopiedDictionary(false);
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
        const payload = buildAiPayload(lemma) || viewText.trim();
        if (!payload) return;
        await navigator.clipboard.writeText(payload);
        setCopiedPrompt(true);
        toast.success(
            `Compact prompt copied (${formatBytes(payload.length)}). Poetry omitted — paste couplets separately if needed.`
        );
        setTimeout(() => setCopiedPrompt(false), 2000);
    };

    const handleCopyDictionary = async () => {
        if (!dictionaryContext) return;
        // Compact dictionary only (same slim snapshot as AI).
        const slim = slimLemmaForAi({ general_dictionary: dictionaryContext })?.general_dictionary
            || dictionaryContext;
        await navigator.clipboard.writeText(JSON.stringify(slim, null, 2));
        setCopiedDictionary(true);
        toast.success(
            dictionaryFound
                ? 'Compact general dictionary copied.'
                : 'No general dictionary match found for this word (empty snapshot copied).'
        );
        setTimeout(() => setCopiedDictionary(false), 2000);
    };

    const handleOpenInput = () => {
        setMode('input');
        setParseError(null);
        setInputJson('');
    };

    const importJson = useMutation({
        mutationFn: async (payload) => {
            const res = await api.post(`/api/admin/lughat/lemmas/${lemmaId}/import-json`, payload);
            return res.data;
        },
        onSuccess: (data) => {
            queryClient.setQueryData(['lughat-lemma-editor-json', lemmaId], data);
            queryClient.invalidateQueries({ queryKey: ['lughat-browse'] });
            queryClient.invalidateQueries({ queryKey: ['lughat-stats'] });
            queryClient.invalidateQueries({ queryKey: ['lughat-lemma', lemmaId] });
            queryClient.invalidateQueries({ queryKey: ['lughat-lemma-editor-json', lemmaId] });
            setMode('view');
            setParseError(null);
            const roman = data?.general?.transliteration;
            toast.success(
                roman
                    ? `Lemma updated. Roman “${roman}” saved to Romanizer + poetry EN.`
                    : 'Lemma rewritten from JSON.'
            );
            onImported?.(data);
        },
        onError: (error) => {
            const message = error?.response?.data?.message
                || (error?.response?.data?.errors && Object.values(error.response.data.errors).flat().join(' '))
                || 'Failed to import JSON.';
            toast.error(message);
            setParseError(message);
        },
    });

    const handleSubmitImport = () => {
        let parsed;
        try {
            parsed = extractLemmaJson(inputJson);
            setParseError(null);
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
                                    ? 'Compact AI payload (no poetry, slim dictionary). Copy for AI → ChatGPT → paste reply via Input JSON.'
                                    : 'Paste ONLY the JSON object ChatGPT returned (markdown fences are OK).'}
                            </DialogDescription>
                            {lemma && (
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {dictionaryFound ? (
                                        <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-100">
                                            Dictionary: {dictionaryContext?.word || 'found'}
                                            {dictionaryMatchCount > 1 ? ` · ${dictionaryMatchCount} variants` : ''}
                                            {dictionaryContext?.match_type ? ` · ${dictionaryContext.match_type}` : ''}
                                        </Badge>
                                    ) : (
                                        <Badge variant="outline" className="text-amber-800 border-amber-300">
                                            Dictionary: not found
                                        </Badge>
                                    )}
                                    {aiPayload && (
                                        <Badge variant="outline" className="text-muted-foreground">
                                            AI paste ~{formatBytes(aiPayload.length)}
                                        </Badge>
                                    )}
                                </div>
                            )}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {mode === 'view' ? (
                                <Button variant="outline" size="sm" onClick={handleOpenInput} disabled={!lemma}>
                                    <FileInput className="h-4 w-4 mr-2" />
                                    Input JSON
                                </Button>
                            ) : (
                                <Button variant="outline" size="sm" onClick={() => setMode('view')}>
                                    Back
                                </Button>
                            )}
                            <Button variant="outline" size="sm" onClick={handleCopyForAi} disabled={!lemma || mode === 'input'}>
                                {copiedPrompt ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <Sparkles className="h-4 w-4 mr-2" />}
                                {copiedPrompt ? 'Copied' : 'Copy for AI'}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleCopyDictionary}
                                disabled={!lemma || mode === 'input'}
                                title="Copy only general_dictionary (compact)"
                            >
                                {copiedDictionary ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <BookMarked className="h-4 w-4 mr-2" />}
                                {copiedDictionary ? 'Copied' : 'Copy dictionary'}
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleCopyJson} disabled={!lemma || mode === 'input'}>
                                {copied ? <CheckCircle2 className="h-4 w-4 mr-2 text-green-500" /> : <Copy className="h-4 w-4 mr-2" />}
                                {copied ? 'Copied' : 'Copy JSON'}
                            </Button>
                        </div>
                    </div>
                </DialogHeader>

                {isLoading ? (
                    <div className="flex items-center justify-center py-16 text-muted-foreground">
                        <Loader2 className="h-6 w-6 animate-spin mr-2" />
                        Loading lemma JSON…
                    </div>
                ) : mode === 'input' ? (
                    <div className="flex flex-col gap-3 min-h-0 flex-1">
                        <Textarea
                            value={inputJson}
                            onChange={(e) => setInputJson(e.target.value)}
                            className="font-mono text-xs min-h-[420px] flex-1"
                            dir="ltr"
                            spellCheck={false}
                            placeholder={'Paste JSON only, e.g.\n{\n  "_schema": "baakh.lughat.editor_json.v2",\n  ...\n}'}
                        />
                        {parseError && (
                            <p className="text-sm text-destructive" role="alert">{parseError}</p>
                        )}
                        <div className="flex justify-end gap-2">
                            <Button variant="outline" onClick={() => setMode('view')} disabled={importJson.isPending}>
                                Cancel
                            </Button>
                            <Button onClick={handleSubmitImport} disabled={importJson.isPending || !inputJson.trim()}>
                                {importJson.isPending ? <Loader2 className="h-4 w-4 mr-2 animate-spin" /> : <Save className="h-4 w-4 mr-2" />}
                                Submit & Rewrite
                            </Button>
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-col gap-2 min-h-0 flex-1">
                        <p className="text-xs text-muted-foreground">
                            Compact AI prompt + lemma JSON — edit if needed, then copy into ChatGPT.
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
