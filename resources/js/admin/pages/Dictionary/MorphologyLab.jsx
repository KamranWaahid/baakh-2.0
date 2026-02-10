import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Plus, Trash2, Check, Link as LinkIcon, Languages, MapPin, SpellCheck } from 'lucide-react';

const MorphologyLab = () => {
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Morphology Lab</h2>
                    <p className="text-muted-foreground font-arabic text-xl mt-1">ڪتاب (Lemma: Kitab)</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm"><Check className="mr-2 h-4 w-4" /> Approve All</Button>
                </div>
            </div>

            <Card>
                <CardContent className="p-6">
                    <Tabs defaultValue="plurals" className="w-full">
                        <TabsList className="grid w-full grid-cols-3 lg:w-[400px]">
                            <TabsTrigger value="plurals">Plurals</TabsTrigger>
                            <TabsTrigger value="cases">Case Forms</TabsTrigger>
                            <TabsTrigger value="dialects">Dialects</TabsTrigger>
                        </TabsList>

                        <TabsContent value="plurals" className="mt-6 space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <Card className="border-l-4 border-l-primary">
                                    <CardContent className="pt-6 space-y-3">
                                        <div className="flex justify-between items-start">
                                            <Label className="text-sm text-muted-foreground">Standard Plural</Label>
                                            <Badge>Approved</Badge>
                                        </div>
                                        <Input value="ڪتابن" className="font-arabic text-xl" dir="rtl" />
                                        <div className="flex justify-end gap-2">
                                            <Button variant="ghost" size="sm" className="h-8 text-destructive"><Trash2 className="h-4 w-4" /></Button>
                                        </div>
                                    </CardContent>
                                </Card>
                                <Button variant="outline" className="h-auto border-dashed border-2 py-8 flex flex-col gap-2">
                                    <Plus className="h-6 w-6" />
                                    <span>Add Plural Form</span>
                                </Button>
                            </div>
                        </TabsContent>

                        <TabsContent value="cases" className="mt-6 space-y-4">
                            <div className="space-y-4">
                                <div className="p-4 rounded-lg border bg-muted/40 flex items-center justify-between">
                                    <div className="flex items-center gap-4">
                                        <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                            <SpellCheck className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p className="font-medium">Oblique Case</p>
                                            <p className="text-sm text-muted-foreground font-arabic" dir="rtl">ڪتاب کي</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button variant="ghost" size="icon"><Trash2 className="h-4 w-4" /></Button>
                                        <Button size="sm">Edit</Button>
                                    </div>
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="dialects" className="mt-6 space-y-4">
                            <div className="flex flex-col gap-4">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="p-4 rounded-lg border space-y-2">
                                        <div className="flex items-center gap-2 text-primary">
                                            <MapPin className="h-4 w-4" />
                                            <span className="text-sm font-semibold uppercase">Lari</span>
                                        </div>
                                        <p className="font-arabic text-xl" dir="rtl">ڪتابي</p>
                                        <Button variant="link" size="sm" className="p-0 h-auto">Link to source</Button>
                                    </div>
                                </div>
                            </div>
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>

            <div className="flex justify-end gap-2 pt-4">
                <Button variant="outline">Reject Variations</Button>
                <Button><Check className="mr-2 h-4 w-4" /> Link & Save All</Button>
            </div>
        </div>
    );
};

export default MorphologyLab;
