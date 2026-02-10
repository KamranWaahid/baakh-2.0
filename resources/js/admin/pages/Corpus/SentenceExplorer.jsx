import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Search, Filter, BookOpen, Tag as TagIcon, MoreVertical, ExternalLink } from 'lucide-react';

const SentenceExplorer = () => {
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Sentence Explorer</h2>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm"><Filter className="mr-2 h-4 w-4" /> Filters</Button>
                    <Button size="sm"><BookOpen className="mr-2 h-4 w-4" /> Export Corpus</Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-4">
                        <div className="relative flex-1 max-w-sm">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search sentences and lemmas..."
                                className="pl-8"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-[40%]">Sentence</TableHead>
                                <TableHead>Source</TableHead>
                                <TableHead>Lemma Tags</TableHead>
                                <TableHead>Dialect</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow className="group">
                                <TableCell className="font-arabic text-xl py-4" dir="rtl">
                                    شاهه سائين جي شاعريءَ ۾ حب الوطنيءَ جو عنصر نمايان آهي.
                                </TableCell>
                                <TableCell>
                                    <div className="flex flex-col">
                                        <span className="font-medium">Shah Jo Risalo</span>
                                        <span className="text-xs text-muted-foreground">Kalyan, 1:4</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="flex flex-wrap gap-1">
                                        <Badge variant="secondary" className="text-[10px]"><TagIcon className="mr-1 h-3 w-3" /> شاعري</Badge>
                                        <Badge variant="secondary" className="text-[10px]"><TagIcon className="mr-1 h-3 w-3" /> حب</Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">Vicholi</Badge>
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button variant="ghost" size="icon"><ExternalLink className="h-4 w-4" /></Button>
                                    <Button variant="ghost" size="icon"><MoreVertical className="h-4 w-4" /></Button>
                                </TableCell>
                            </TableRow>
                            {/* More rows... */}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
};

export default SentenceExplorer;
