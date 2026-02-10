import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Plus, Trash2, Check, ExternalLink, MapPin, SpellCheck, TriangleAlert } from 'lucide-react';

const Variants = () => {
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Variants & Misspellings</h2>
                    <p className="text-muted-foreground font-arabic text-xl mt-1">ڪتاب (Lemma: Kitab)</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm">Audit All</Button>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <MapPin className="h-4 w-4 text-primary" /> Dialect Variants
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center justify-between p-3 border rounded-lg bg-muted/20">
                            <div>
                                <p className="font-arabic text-lg" dir="rtl">ڪتابڙو</p>
                                <p className="text-xs text-muted-foreground uppercase">Thari Dialect</p>
                            </div>
                            <div className="flex gap-1">
                                <Button variant="ghost" size="icon" className="h-8 w-8 text-destructive"><Trash2 className="h-4 w-4" /></Button>
                                <Button variant="ghost" size="icon" className="h-8 w-8"><Check className="h-4 w-4" /></Button>
                            </div>
                        </div>
                        <Button variant="outline" className="w-full border-dashed"><Plus className="mr-2 h-4 w-4" /> Add Dialect Variant</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <SpellCheck className="h-4 w-4 text-amber-500" /> Common Misspellings
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center justify-between p-3 border rounded-lg bg-orange-50/20 border-orange-200">
                            <div className="flex items-center gap-3">
                                <TriangleAlert className="h-4 w-4 text-amber-500" />
                                <p className="font-arabic text-lg" dir="rtl">ڪتاپ</p>
                            </div>
                            <div className="flex gap-2">
                                <Badge variant="outline" className="bg-amber-100 text-amber-800">Auto-fix</Badge>
                                <Button variant="ghost" size="icon" className="h-8 w-8 text-destructive"><Trash2 className="h-4 w-4" /></Button>
                            </div>
                        </div>
                        <Button variant="outline" className="w-full border-dashed"><Plus className="mr-2 h-4 w-4" /> Add Misspelling</Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default Variants;
