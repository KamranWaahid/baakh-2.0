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

const AI_ENRICH_PROMPT = `Enrich this Baakh Lughat (poetic dictionary) lemma JSON.

You are given these inputs in the JSON:
1) poetry.* — Sindhi couplets + token_hints where this word appears in verse (READ-ONLY context)
2) general_dictionary.* — meanings + typed synonym/antonym/… lists from site dictionary / Open Lexicon (READ-ONLY)
3) relations_checklist.empty — Linguistic Relations buckets that are still count 0 (READ-ONLY — fill them)
4) senses / forms / relations — current Baakh Lughat editor data (upsert target)

GOAL: return a COMPLETE lemma — fill every empty editor field you can. Do not leave blanks that block completion. Prefer best-effort filled values over empty strings. Only use null when truly unknown for optional grammar slots (case/person).

Return ONLY one valid JSON object. No markdown fences. No explanation before or after.

Schema: baakh.lughat.editor_json.v2
Keep keys: _schema, _name, _instructions, id, public_id, poetry, general, completion, morphology, senses, relations, variants, forms, expression_candidates, occurrence_summary.
You may omit general_dictionary and relations_checklist in the output (they are input-only).
Keep top-level id/public_id and any existing numeric row ids.
Keep poetry.* and occurrence_summary as read-only context (do not invent EN couplet lines).

COMPLETE THE WORD (no empty required boxes):
- general.lemma, general.normalized_lemma — keep/fill (never empty).
- general.transliteration — REQUIRED roman of headword (never empty).
- ROMAN ONLY (critical): transliteration and every romanization field must be plain Latin letters (a–z, spaces, hyphens). NO Arabic/Sindhi script. NO diacritic marks of any kind — no zabar/zer/pesh (َ ِ ُ), no tashdeed/shaddah (ّ), no sukun (ْ), no tanween, no hamza/madda marks, no combining accents (ā ī ū é). Write plain ASCII roman: aadmi not ādmī, not آدْمي, not áádmi. Same rule for relation/form/variant/example romanization.
- general.pos — REQUIRED (noun/verb/adjective/…).
- general.pronunciation_simple and/or general.phonetic — fill when possible; ipa if confident. IPA may use phonetic symbols; romanization fields must NOT.
- morphology.root / gender / number when knowable from forms or dictionary; mark morphology_reviewed=true if you filled morphology.
- At least one curated sense; usually several (see SENSES).
- Every sense MUST fill: short_gloss, definition (primary, Sindhi), definition_sd when useful, optional definition_en, language_direction (prefer "sindhi"), source_dictionary, publisher, publisher_url, prepared_by, review_status="reviewed", status="approved".
- Add ≥1 example per poetic sense when poetry.couplets allow (example_type=poetry_citation).
- If you add variants or examples, set variants_reviewed / examples_reviewed true.
- Set pronunciation_reviewed=true only if pronunciation fields are filled.
- Set completion.completion_status="complete" and a short completion.completion_notes when all of the above are filled; otherwise "pending" and list what is still unknown.
- general.source_confidence: 0–100.
Set publish_romanization=false unless explicitly publishing.

SENSES (most important — for poetry sense-tagging):
- ALWAYS generate NEW senses (omit id for new rows). Keep existing sense ids.
- PRIMARY DEFINITIONS IN SINDHI (critical): sense.definition and short_gloss MUST be written in Sindhi (Arabic script). Do not put English as the primary definition. Put English only in definition_en (optional secondary). definition_sd may repeat/clarify Sindhi when helpful.
- Combine general_dictionary meanings WITH poetry understanding:
  (a) Literal / general senses adapted from general_dictionary.* (usage_label e.g. "general" or "literal"; domain when known).
  (b) NEW poetic / contextual senses based on how the word is used in poetry.source_couplet and poetry.couplets (usage_label e.g. "poetic", "figurative", "mystical", "romantic" as fits).
- Goal: when an editor tags a word in a couplet, they can pick the sense that matches THAT poetic line so readers understand the verse.
- Do NOT only copy one dictionary gloss — expand to cover both everyday and poetic readings.
- Never leave sense definition/gloss/language_direction/source empty.
Source defaults on every sense: source_dictionary="Baakh Lughat", publisher="baakh.com", publisher_url="https://baakh.com/", prepared_by="Kamran Wahid".

FORMS / INFLECTIONS (critical for paradigms like تنھنجو/تنھنجي/تنھنجا/تنھنجون):
Put structured forms in forms.inflections[]. For each form include when known:
form, normalized_form, romanization, form_type, gender, number, case (or null), person, stem, suffix, confidence (0-1 or 0-100).
Use null only for uncertain grammar — do not invent case/agreement. Prefer filling gender/number when clear.

MULTIWORD EXPRESSIONS (separate from lemmas — do NOT make جامِ محبت a lemma):
Keep individual lemmas (جام, محبت). Propose phrases in expression_candidates[] using poetry.token_hints indexes:
{ "surface": "جامِ محبت", "start_token": 1, "end_token": 2, "type": "izafat", "literal_gloss": "cup of love", "poetic_interpretation": "metaphorical vessel of love", "confidence": 0.94 }
Types: izafat|compound|collocation|idiom|metaphor|fixed_phrase|formulaic_phrase|reduplicative|name_or_title|other.
Preserve izafat kasra in surface (جامِ محبت). Candidates enter a review inbox — do not treat as approved.

UPSERT ONLY: do not omit rows to delete them. Set "_replace_missing": false (default).

RELATIONS (critical — Linguistic Relations tab; do NOT leave empty when data exists):
Fill relations[] using the CORRECT relation_type. Do NOT put synonyms into "related".
Allowed relation_type values:
- synonym — same/near meaning (e.g. آدمي ↔ انسان، ماڻهو، بشر)
- antonym — opposite when clear
- hypernym — broader class (e.g. آدمي → جاندار / مخلوق)
- singular / plural — number pair of this lemma
- dialect — regional form; set note to dialect name (e.g. Utradi)
- derived — derived form (noun→agent/adj often ي: محبت→محبتي، خون→خوني); add gloss
- usage — "people say" spoken form; put example sentence in gloss
- related — ONLY leftover associates that are NOT synonyms/antonyms/hypernyms

For EACH relation object include:
{ "relation_type": "synonym", "related_word": "…", "romanization": "…", "note": null, "gloss": null, "part_of_speech": "noun" }
Omit id for new rows; keep id when updating existing.
Pull candidates from general_dictionary.synonyms / antonyms / hypernyms / related / plural / dialect / derived / usage AND from poetry usage.
Also honor relations_checklist.empty — those buckets are currently 0 and should be filled when knowable (e.g. move انسان/ماڻهو/بشر from related → synonym).
Aim for several synonyms when the general dictionary or common Sindhi usage provides them (as with آدمي).
Prefer linking real Baakh Lughat lemmas via related_word that matches an existing headword when known.

Variants = spelling/dialect orthography only — not grammatical inflections (those go in forms.inflections).
Use Standard Sindhi Arabic script.

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

export default function LughatLemmaEditorJsonModal({ lemmaId, onClose }) {
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
                            placeholder={'Paste JSON only, e.g.\n{\n  "_schema": "baakh.lughat.editor_json.v1",\n  ...\n}'}
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
