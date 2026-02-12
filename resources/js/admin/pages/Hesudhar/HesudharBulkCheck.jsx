import React, { useState, useMemo } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { FileSearch, CheckCircle2, Loader2, ArrowLeft, RefreshCw, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import api from '../../api/axios';
import { Link } from 'react-router-dom';

const HesudharBulkCheck = () => {
    const [text, setText] = useState('');
    const [mistakes, setMistakes] = useState([]);

    const checkMutation = useMutation({
        mutationFn: (text) => api.post('/api/admin/hesudhar/check-words', { text }),
        onSuccess: (data) => {
            setMistakes(data.data.mistakes || []);
        }
    });

    const handleCheck = () => {
        if (!text.trim()) return;
        checkMutation.mutate(text);
    };

    const handleStandardizeHeh = async () => {
        if (!text.trim()) return;
        try {
            const response = await api.post('/api/admin/hesudhar/standardize', { text });
            setText(response.data.standardized_text);
            // Re-check after bulk standardization to clear fixed mistakes
            checkMutation.mutate(response.data.standardized_text);
        } catch (error) {
            console.error("Bulk standardization failed:", error);
        }
    };

    const handleFixAll = () => {
        let fixedText = text;
        mistakes.forEach(m => {
            // Using regex to replace all occurrences while respecting word boundaries if possible
            // But since these are Sindhi words, we'll do a simple global replace for now
            fixedText = fixedText.split(m.word).join(m.correct);
        });
        setText(fixedText);
        setMistakes([]);
    };

    const handleFixIndividual = (mistake) => {
        setText(prev => prev.split(mistake.word).join(mistake.correct));
        setMistakes(prev => prev.filter(m => m.word !== mistake.word));
    };

    return (
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex items-center gap-3 md:gap-4">
                <Button variant="ghost" size="icon" asChild className="h-8 w-8 md:h-10 md:w-10">
                    <Link to="/admin/hesudhar">
                        <ArrowLeft className="h-4 w-4" />
                    </Link>
                </Button>
                <div className="space-y-1">
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight">Hesudhar Checker</h2>
                    <p className="text-gray-500 text-sm md:text-base">Find and fix spell errors in bulk</p>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card className="flex flex-col">
                    <CardHeader>
                        <CardTitle>Input Text</CardTitle>
                        <CardDescription>Paste Sindhi text to check for incorrect 'ھ' and other dictionary mistakes.</CardDescription>
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
                                {checkMutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <FileSearch className="mr-2 h-4 w-4" />}
                                Analyze Text
                            </Button>
                            <div className="flex gap-2 flex-1">
                                <Button variant="outline" className="flex-1" onClick={handleStandardizeHeh} title="Convert all 'ه' and 'ہ' to standard 'ھ'">
                                    <RefreshCw className="mr-2 h-4 w-4" /> Standardize
                                </Button>
                                {mistakes.length > 0 && (
                                    <Button variant="secondary" className="flex-1" onClick={handleFixAll}>
                                        <CheckCircle2 className="mr-2 h-4 w-4" /> Fix All
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="flex flex-col">
                    <CardHeader>
                        <CardTitle>Identified Mistakes ({mistakes.length})</CardTitle>
                        <CardDescription>Click on a mistake to apply the suggestion.</CardDescription>
                    </CardHeader>
                    <CardContent className="flex-1">
                        {mistakes.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-20 text-muted-foreground italic space-y-2">
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
                            <div className="divide-y max-h-[600px] overflow-y-auto pr-2">
                                {mistakes.map((mistake, index) => (
                                    <div key={index} className="py-3 flex items-center justify-between group gap-4">
                                        <div className="flex items-center">
                                            <div className="flex flex-col">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-red-500 font-arabic text-base line-through decoration-2 opacity-70">
                                                        {mistake.word}
                                                    </span>
                                                    <Badge variant="secondary" className="text-[9px] py-0 px-1 uppercase opacity-50 shrink-0">
                                                        {mistake.type === 'normalization' ? 'Std' : 'Spelling'}
                                                    </Badge>
                                                </div>
                                                <span className="text-green-600 font-arabic text-lg font-bold">
                                                    {mistake.correct}
                                                </span>
                                            </div>
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            className="md:opacity-0 md:group-hover:opacity-100 transition-opacity shrink-0"
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
            </div>
        </div>
    );
};

export default HesudharBulkCheck;
