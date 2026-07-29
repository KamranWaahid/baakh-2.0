import React, { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { FileSearch, CheckCircle2, Loader2, ArrowLeft, AlertCircle, Plus, BookPlus, Link2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import api from '../../api/axios';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';

const HesudharBulkCheck = () => {
    const [text, setText] = useState('');
    const [mistakes, setMistakes] = useState([]);
    const [missingLughat, setMissingLughat] = useState([]);
    const [addOpen, setAddOpen] = useState(false);
    const [selectedWord, setSelectedWord] = useState('');
    const [expressionEnabled, setExpressionEnabled] = useState(false);
    const [expressionText, setExpressionText] = useState('');
    const [literalGloss, setLiteralGloss] = useState('');
    const [poeticGloss, setPoeticGloss] = useState('');

    const checkMutation = useMutation({
        mutationFn: (payload) => api.post('/api/admin/hesudhar/check-words', { text: payload }),
        onSuccess: (data) => {
            setMistakes(data.data.mistakes || []);
            setMissingLughat(data.data.missing_lughat_words || []);
        },
        onError: (error) => {
            toast.error(error.response?.data?.message || 'Analyze failed.');
        },
    });

    const addStubMutation = useMutation({
        mutationFn: (payload) => api.post('/api/admin/lughat/add-stubs', payload),
        onSuccess: (res) => {
            const created = res.data.created || [];
            const skipped = res.data.skipped_existing || [];
            toast.success(
                created.length
                    ? `Added ${created.length} word(s) to Baakh Lughat.`
                    : 'No new words added (already in Lughat).'
            );
            if (skipped.length) {
                toast.message(`Skipped existing: ${skipped.join('، ')}`);
            }
            if (res.data.expression?.expression) {
                toast.success(`Expression saved: ${res.data.expression.expression}`);
            }
            const createdSet = new Set(created.map((c) => c.lemma));
            const skippedSet = new Set(skipped);
            setMissingLughat((prev) => prev.filter((w) => !createdSet.has(w) && !skippedSet.has(w)));
            setAddOpen(false);
            resetAddForm();
        },
        onError: (error) => {
            toast.error(error.response?.data?.message || 'Failed to add to Baakh Lughat.');
        },
    });

    const resetAddForm = () => {
        setSelectedWord('');
        setExpressionEnabled(false);
        setExpressionText('');
        setLiteralGloss('');
        setPoeticGloss('');
    };

    const handleCheck = () => {
        if (!text.trim()) return;
        checkMutation.mutate(text);
    };

    const openAdd = (word) => {
        setSelectedWord(word);
        setExpressionEnabled(false);
        setExpressionText('');
        setLiteralGloss('');
        setPoeticGloss('');
        setAddOpen(true);
    };

    const submitAdd = () => {
        if (!selectedWord.trim()) return;
        const words = [selectedWord.trim()];
        const payload = { words };

        if (expressionEnabled && expressionText.trim()) {
            const parts = expressionText.trim().split(/\s+/u).filter(Boolean);
            if (parts.length < 2) {
                toast.error('Poetic expression needs two or more words.');
                return;
            }
            // Include all expression parts so component lemmas are created if missing
            for (const p of parts) {
                if (!words.includes(p)) words.push(p);
            }
            payload.words = words;
            payload.expression = {
                expression: expressionText.trim(),
                expression_type: 'izafat',
                literal_gloss: literalGloss.trim() || null,
                poetic_gloss: poeticGloss.trim() || null,
            };
        }

        addStubMutation.mutate(payload);
    };

    const handleFixAll = () => {
        let fixedText = text;
        mistakes.forEach((m) => {
            const escapeRegExp = (string) => string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const pattern = new RegExp(`(^|[^\\u0600-\\u06FF])(${escapeRegExp(m.word)})([^\\u0600-\\u06FF]|$)`, 'gu');
            fixedText = fixedText.replace(pattern, `$1${m.correct}$3`);
        });
        setText(fixedText);
        setMistakes([]);
    };

    const handleFixIndividual = (mistake) => {
        const escapeRegExp = (string) => string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const pattern = new RegExp(`(^|[^\\u0600-\\u06FF])(${escapeRegExp(mistake.word)})([^\\u0600-\\u06FF]|$)`, 'gu');

        setText((prev) => prev.replace(pattern, `$1${mistake.correct}$3`));
        setMistakes((prev) => prev.filter((m) => m.word !== mistake.word));
    };

    return (
        <div className="min-w-0 w-full max-w-full space-y-4 p-0 sm:space-y-6">
            <div className="flex min-w-0 items-center gap-3 md:gap-4">
                <Button variant="ghost" size="icon" asChild className="h-8 w-8 shrink-0 md:h-10 md:w-10">
                    <Link to="/admin/hesudhar">
                        <ArrowLeft className="h-4 w-4" />
                    </Link>
                </Button>
                <div className="min-w-0 space-y-1">
                    <h2 className="text-2xl font-bold tracking-tight md:text-3xl">Hesudhar Checker</h2>
                    <p className="text-sm text-muted-foreground md:text-base">
                        Find spelling errors and add missing words to Baakh Lughat
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card className="flex flex-col">
                    <CardHeader>
                        <CardTitle>Input Text</CardTitle>
                        <CardDescription>
                            Paste Sindhi text to check for incorrect &apos;ھ&apos; and other dictionary mistakes.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex-1 flex flex-col space-y-4">
                        <Textarea
                            placeholder="پنهنجو سنڌي متن هتي پيسٽ ڪريو..."
                            className="flex-1 min-h-[400px] text-lg leading-relaxed font-arabic"
                            dir="rtl"
                            value={text}
                            onChange={(e) => setText(e.target.value)}
                        />
                        <div className="flex flex-col sm:flex-row gap-2">
                            <Button
                                className="flex-[2]"
                                onClick={handleCheck}
                                disabled={checkMutation.isPending || !text.trim()}
                            >
                                {checkMutation.isPending ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <FileSearch className="mr-2 h-4 w-4" />
                                )}
                                Analyze Text
                            </Button>
                            <div className="flex gap-2 flex-1">
                                {mistakes.length > 0 && (
                                    <Button variant="secondary" className="flex-1" onClick={handleFixAll}>
                                        <CheckCircle2 className="mr-2 h-4 w-4" /> Fix All
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    <Card className="flex flex-col">
                        <CardHeader>
                            <CardTitle>Identified Mistakes ({mistakes.length})</CardTitle>
                            <CardDescription>Click Apply to use the suggestion in the text.</CardDescription>
                        </CardHeader>
                        <CardContent className="flex-1">
                            {mistakes.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-12 text-muted-foreground italic space-y-2">
                                    {checkMutation.isSuccess ? (
                                        <>
                                            <CheckCircle2 className="h-10 w-10 text-green-500 mb-2" />
                                            <span>No mistakes found in the analyzed text!</span>
                                        </>
                                    ) : (
                                        <>
                                            <AlertCircle className="h-10 w-10 opacity-20 mb-2" />
                                            <span>Analyze text to find spelling errors.</span>
                                        </>
                                    )}
                                </div>
                            ) : (
                                <div className="max-h-[280px] divide-y overflow-y-auto pr-1 sm:pr-2">
                                    {mistakes.map((mistake, index) => (
                                        <div key={index} className="flex items-center justify-between gap-3 py-3 group sm:gap-4">
                                            <div className="min-w-0 flex items-center">
                                                <div className="flex min-w-0 flex-col">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className="font-arabic text-base text-red-500 line-through decoration-2 opacity-70 break-words">
                                                            {mistake.word}
                                                        </span>
                                                        <Badge variant="secondary" className="shrink-0 px-1 py-0 text-[9px] uppercase opacity-50">
                                                            {mistake.type === 'normalization' ? 'Std' : 'Spelling'}
                                                        </Badge>
                                                    </div>
                                                    <span className="font-arabic text-lg font-bold text-green-600 break-words">
                                                        {mistake.correct}
                                                    </span>
                                                </div>
                                            </div>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="shrink-0 transition-opacity md:opacity-0 md:group-hover:opacity-100"
                                                onClick={() => handleFixIndividual(mistake)}
                                            >
                                                Apply
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="flex flex-col">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <BookPlus className="h-4 w-4" />
                                Missing from Baakh Lughat ({missingLughat.length})
                            </CardTitle>
                            <CardDescription>
                                Words not in Baakh Lughat yet. Add as a single lemma, optionally with a poetic expression.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {!checkMutation.isSuccess ? (
                                <div className="flex flex-col items-center justify-center py-10 text-muted-foreground italic space-y-2">
                                    <BookPlus className="h-10 w-10 opacity-20" />
                                    <span>Analyze text to list words missing from Baakh Lughat.</span>
                                </div>
                            ) : missingLughat.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-10 text-muted-foreground italic space-y-2">
                                    <CheckCircle2 className="h-10 w-10 text-green-500" />
                                    <span>All analyzed words are already in Baakh Lughat.</span>
                                </div>
                            ) : (
                                <div className="flex flex-wrap gap-2 max-h-[280px] overflow-y-auto p-2 border rounded-md">
                                    {missingLughat.map((word) => (
                                        <Badge
                                            key={word}
                                            variant="outline"
                                            className="text-base py-1.5 px-3 flex items-center gap-2 cursor-pointer hover:bg-muted font-arabic"
                                            dir="rtl"
                                            onClick={() => openAdd(word)}
                                        >
                                            {word}
                                            <Plus className="h-3.5 w-3.5 text-primary shrink-0" />
                                        </Badge>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Dialog
                open={addOpen}
                onOpenChange={(open) => {
                    setAddOpen(open);
                    if (!open) resetAddForm();
                }}
            >
                <DialogContent className="sm:max-w-md" dir="rtl">
                    <DialogHeader className="text-right">
                        <DialogTitle className="font-arabic text-2xl">{selectedWord}</DialogTitle>
                        <DialogDescription dir="ltr" className="text-left">
                            Add this word to Baakh Lughat (word-only stub). Optionally attach a multiword poetic expression.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="rounded-md border bg-muted/30 px-3 py-2 text-sm" dir="ltr">
                            Main word: <span className="font-arabic text-base font-semibold" dir="rtl">{selectedWord}</span>
                        </div>

                        <label className="flex items-center gap-2 text-sm cursor-pointer" dir="ltr">
                            <Checkbox
                                checked={expressionEnabled}
                                onCheckedChange={(checked) => {
                                    const on = checked === true;
                                    setExpressionEnabled(on);
                                    if (on && !expressionText) {
                                        setExpressionText(selectedWord + ' ');
                                    }
                                }}
                            />
                            <Link2 className="h-3.5 w-3.5" />
                            Also add poetic expression (2+ words)
                        </label>

                        {expressionEnabled && (
                            <div className="space-y-2 border rounded-md p-3">
                                <Input
                                    dir="rtl"
                                    lang="sd"
                                    className="font-arabic text-lg"
                                    placeholder="جامِ محبت"
                                    value={expressionText}
                                    onChange={(e) => setExpressionText(e.target.value)}
                                />
                                <Input
                                    dir="ltr"
                                    placeholder="Literal gloss — cup of love"
                                    value={literalGloss}
                                    onChange={(e) => setLiteralGloss(e.target.value)}
                                />
                                <Textarea
                                    dir="rtl"
                                    lang="sd"
                                    className="font-arabic min-h-[70px]"
                                    placeholder="Poetic meaning (optional)"
                                    value={poeticGloss}
                                    onChange={(e) => setPoeticGloss(e.target.value)}
                                />
                            </div>
                        )}
                    </div>

                    <DialogFooter className="gap-2 sm:justify-between" dir="ltr">
                        <Button type="button" variant="ghost" onClick={() => setAddOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="button" onClick={submitAdd} disabled={addStubMutation.isPending}>
                            {addStubMutation.isPending ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <BookPlus className="mr-2 h-4 w-4" />
                            )}
                            Add to Baakh Lughat
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default HesudharBulkCheck;
