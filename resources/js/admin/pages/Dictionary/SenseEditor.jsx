import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Trash2, Plus, Merge, Split, Check, Info, BookOpen, Quote } from 'lucide-react';

const SenseEditor = () => {
    const [senses, setSenses] = useState([
        {
            id: 1,
            definition: 'A set of written or printed pages, fastened together inside a cover.',
            domain: 'Daily Use',
            examples: ['I am reading a very interesting book.', 'He bought a book from the shop.']
        }
    ]);

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Sense Editor</h2>
                    <p className="text-muted-foreground font-arabic text-xl mt-1">ڪتاب (Lemma: Kitab)</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm"><Split className="mr-2 h-4 w-4" /> Split</Button>
                    <Button variant="outline" size="sm"><Merge className="mr-2 h-4 w-4" /> Merge</Button>
                    <Button size="sm"><Plus className="mr-2 h-4 w-4" /> Add Sense</Button>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div className="lg:col-span-2 space-y-4">
                    {senses.map((sense, idx) => (
                        <Card key={sense.id}>
                            <CardHeader className="pb-3 border-b bg-muted/20">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Badge>Sense #{idx + 1}</Badge>
                                        <Badge variant="outline">{sense.domain}</Badge>
                                    </div>
                                    <div className="flex gap-1">
                                        <Button variant="ghost" size="icon" className="h-8 w-8 text-destructive"><Trash2 className="h-4 w-4" /></Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="pt-6 space-y-4">
                                <div className="space-y-2">
                                    <Label>Definition</Label>
                                    <Input value={sense.definition} className="text-lg font-medium" />
                                </div>
                                <div className="space-y-3">
                                    <Label className="flex items-center gap-2 text-muted-foreground"><Quote className="h-3 w-3" /> Example Sentences (Corpus Evidence)</Label>
                                    {sense.examples.map((ex, i) => (
                                        <div key={i} className="flex gap-2">
                                            <Input value={ex} className="flex-1 italic" />
                                            <Button variant="ghost" size="icon" className="h-9 w-9 shrink-0"><Trash2 className="h-4 w-4" /></Button>
                                        </div>
                                    ))}
                                    <Button variant="ghost" size="sm" className="w-full border-dashed border-2 h-10"><Plus className="mr-2 h-4 w-4" /> Add Example</Button>
                                </div>
                            </CardContent>
                            <CardFooter className="bg-muted/10 border-t py-3 flex justify-between items-center">
                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm">Domain settings</Button>
                                </div>
                                <Button size="sm"><Check className="mr-2 h-4 w-4" /> Approve Sense</Button>
                            </CardFooter>
                        </Card>
                    ))}
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium flex items-center gap-2">
                                <BookOpen className="h-4 w-4" /> Corpus Evidence
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="p-3 rounded-md bg-muted/40 border text-sm italic">
                                "هي ڪتاب تمام مفيد آهي."
                                <Button variant="link" size="sm" className="h-auto p-0 ml-2">Add to sense</Button>
                            </div>
                            <div className="p-3 rounded-md bg-muted/40 border text-sm italic">
                                "ڪتاب علم جو خزانو آهي."
                                <Button variant="link" size="sm" className="h-auto p-0 ml-2">Add to sense</Button>
                            </div>
                            <Button variant="outline" className="w-full text-xs">Load more evidence</Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium flex items-center gap-2">
                                <Info className="h-4 w-4" /> Frequency Insights
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[200px] flex items-center justify-center text-muted-foreground text-xs italic border rounded-lg border-dashed">
                                Frequency chart placeholder
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
};

export default SenseEditor;
