import React, { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { FileSearch, Plus, Loader2, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import api from '../../api/axios';
import { Link } from 'react-router-dom';
import RomanizerForm from './RomanizerForm';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";

const RomanizerBulkCheck = () => {
    const [text, setText] = useState('');
    const [missingWords, setMissingWords] = useState([]);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [selectedWord, setSelectedWord] = useState('');

    const checkMutation = useMutation({
        mutationFn: (text) => api.post('/api/admin/romanizer/check-words', { text }),
        onSuccess: (data) => {
            setMissingWords(data.data.missing_words);
        }
    });

    const handleCheck = () => {
        if (!text.trim()) return;
        checkMutation.mutate(text);
    };

    const handleAddWord = (word) => {
        setSelectedWord(word);
        setIsDialogOpen(true);
    };

    const handleAddSuccess = () => {
        setMissingWords(prev => prev.filter(w => w !== selectedWord));
        setIsDialogOpen(false);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Button variant="ghost" size="icon" asChild>
                    <Link to="/romanizer">
                        <ArrowLeft className="h-4 w-4" />
                    </Link>
                </Button>
                <h2 className="text-3xl font-bold tracking-tight">Bulk Check</h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Input Text</CardTitle>
                        <CardDescription>Paste your Sindhi text here to find missing words in the Romanizer dictionary.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Textarea
                            placeholder="پنهنجو سنڌي متن هتي پيسٽ ڪريو..."
                            className="min-h-[300px] text-lg leading-relaxed"
                            dir="rtl"
                            value={text}
                            onChange={(e) => setText(e.target.value)}
                        />
                        <Button
                            className="w-full"
                            onClick={handleCheck}
                            disabled={checkMutation.isPending || !text.trim()}
                        >
                            {checkMutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <FileSearch className="mr-2 h-4 w-4" />}
                            Check Missing Words
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Missing Words ({missingWords.length})</CardTitle>
                        <CardDescription>These words are not in the Romanizer dictionary yet.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {missingWords.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-muted-foreground italic">
                                {checkMutation.isSuccess ? "All words are already in the dictionary!" : "Perform a check to see missing words."}
                            </div>
                        ) : (
                            <div className="flex flex-wrap gap-2 overflow-y-auto max-h-[500px] p-2 border rounded-md">
                                {missingWords.map((word, index) => (
                                    <Badge
                                        key={index}
                                        variant="outline"
                                        className="text-lg py-1 px-3 flex items-center gap-2 cursor-pointer hover:bg-muted transition-colors"
                                        dir="rtl"
                                        onClick={() => handleAddWord(word)}
                                    >
                                        {word}
                                        <Plus className="h-3 w-3 text-primary" />
                                    </Badge>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add Romanized Word</DialogTitle>
                    </DialogHeader>
                    <RomanizerForm
                        entry={{ word_sd: selectedWord, word_roman: '' }}
                        onSuccess={handleAddSuccess}
                    />
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default RomanizerBulkCheck;
