import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { CircleCheck, CircleAlert, CircleHelp, Loader2 } from 'lucide-react';
import { Link } from 'react-router-dom';

const DictionaryQA = () => {
    const { data: report, isLoading } = useQuery({
        queryKey: ['dictionary-qa-report'],
        queryFn: async () => {
            const res = await api.get('/api/admin/dictionary/qa');
            return res.data;
        }
    });

    const summary = report?.summary || {};
    const issues = report?.issues || {};
    const totalIssues = Object.values(summary).reduce((total, value) => total + Number(value || 0), 0);

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">QA & Search</h2>
                    <p className="text-muted-foreground mt-1">Bounded quality checks against dictionary tables.</p>
                </div>
                {isLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <SummaryCard label="Pending Lemmas" value={summary.pending_lemmas} />
                <SummaryCard label="Without Senses" value={summary.lemmas_without_senses} />
                <SummaryCard label="Missing Normalized Form" value={summary.lemmas_without_normalized_form} />
                <SummaryCard label="Duplicate Groups" value={summary.duplicate_lemma_groups} />
            </div>

            {totalIssues === 0 && !isLoading ? (
                <Card>
                    <CardContent className="py-12 text-center text-muted-foreground">
                        <CircleCheck className="h-8 w-8 mx-auto mb-3 text-green-600" />
                        No QA issues found in the current dictionary snapshot.
                    </CardContent>
                </Card>
            ) : (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <IssueList title="Pending Review" items={issues.pending || []} icon={CircleHelp} />
                    <IssueList title="Missing Senses" items={issues.missing_senses || []} icon={CircleAlert} />
                    <IssueList title="Missing Normalized Form" items={issues.missing_normalized || []} icon={CircleAlert} />
                    <DuplicateList items={issues.duplicate_lemmas || []} />
                </div>
            )}
        </div>
    );
};

const SummaryCard = ({ label, value }) => (
    <Card>
        <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">{label}</CardTitle>
        </CardHeader>
        <CardContent>
            <div className="text-2xl font-bold">{Number(value || 0).toLocaleString()}</div>
        </CardContent>
    </Card>
);

const IssueList = ({ title, items, icon: Icon }) => (
    <Card>
        <CardHeader>
            <CardTitle className="text-lg flex items-center gap-2">
                <Icon className="h-5 w-5 text-amber-500" /> {title}
            </CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
            {items.length > 0 ? items.map((lemma) => (
                <div key={lemma.id} className="flex items-center justify-between rounded-lg border p-3">
                    <div>
                        <p className="font-arabic text-lg" dir="rtl">{lemma.lemma}</p>
                        <Badge variant="outline">{lemma.status}</Badge>
                    </div>
                    <Button size="sm" variant="outline" asChild>
                        <Link to={`/admin/dictionary/lemmas/${lemma.id}`}>Review</Link>
                    </Button>
                </div>
            )) : (
                <p className="text-sm text-muted-foreground py-6 text-center">No issues in this category.</p>
            )}
        </CardContent>
    </Card>
);

const DuplicateList = ({ items }) => (
    <Card>
        <CardHeader>
            <CardTitle className="text-lg flex items-center gap-2">
                <CircleAlert className="h-5 w-5 text-amber-500" /> Duplicate Lemma Groups
            </CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
            {items.length > 0 ? items.map((item) => (
                <div key={item.lemma} className="flex items-center justify-between rounded-lg border p-3">
                    <span className="font-arabic text-lg" dir="rtl">{item.lemma}</span>
                    <Badge variant="secondary">{item.total} records</Badge>
                </div>
            )) : (
                <p className="text-sm text-muted-foreground py-6 text-center">No duplicate groups found.</p>
            )}
        </CardContent>
    </Card>
);

export default DictionaryQA;
