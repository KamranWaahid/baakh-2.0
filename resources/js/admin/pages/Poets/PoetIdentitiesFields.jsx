import { useFormContext } from 'react-hook-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';

export const EMPTY_POET_IDENTITIES = {
    wikipedia_url: '',
    wikidata_id: '',
    google_kgmid: '',
    website_url: '',
    twitter: '',
    facebook: '',
    instagram: '',
};

export const IDENTITY_KEYS = Object.keys(EMPTY_POET_IDENTITIES);

export function identitiesFromPoet(poet) {
    const raw = poet?.identities && typeof poet.identities === 'object' ? poet.identities : {};
    const next = { ...EMPTY_POET_IDENTITIES };
    IDENTITY_KEYS.forEach((key) => {
        next[key] = raw[key] != null ? String(raw[key]) : '';
    });
    return next;
}

export function appendPoetIdentities(formData, identities) {
    IDENTITY_KEYS.forEach((key) => {
        formData.append(`identities[${key}]`, identities?.[key] ?? '');
    });
}

export default function PoetIdentitiesFields() {
    const { control } = useFormContext();

    return (
        <Card>
            <CardHeader>
                <CardTitle>Official identities</CardTitle>
                <p className="text-sm text-muted-foreground">
                    Optional Wikipedia, Wikidata, Knowledge Graph, website, and social usernames.
                    Person schema <code>sameAs</code> is emitted only from these values — never guessed from the poet slug.
                </p>
            </CardHeader>
            <CardContent className="space-y-4">
                <FormField
                    control={control}
                    name="identities.wikipedia_url"
                    render={({ field }) => (
                        <FormItem>
                            <FormLabel>Wikipedia URL</FormLabel>
                            <FormControl>
                                <Input placeholder="https://en.wikipedia.org/wiki/…" {...field} value={field.value || ''} />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    )}
                />
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormField
                        control={control}
                        name="identities.wikidata_id"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel>Wikidata ID</FormLabel>
                                <FormControl>
                                    <Input placeholder="Q12345" {...field} value={field.value || ''} />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        )}
                    />
                    <FormField
                        control={control}
                        name="identities.google_kgmid"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel>Google Knowledge Graph mid</FormLabel>
                                <FormControl>
                                    <Input placeholder="/g/11g0wghzst" {...field} value={field.value || ''} />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        )}
                    />
                </div>
                <FormField
                    control={control}
                    name="identities.website_url"
                    render={({ field }) => (
                        <FormItem>
                            <FormLabel>Official website</FormLabel>
                            <FormControl>
                                <Input placeholder="https://…" {...field} value={field.value || ''} />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    )}
                />
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <FormField
                        control={control}
                        name="identities.twitter"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel>X / Twitter username</FormLabel>
                                <FormControl>
                                    <Input placeholder="handle" {...field} value={field.value || ''} />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        )}
                    />
                    <FormField
                        control={control}
                        name="identities.facebook"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel>Facebook username</FormLabel>
                                <FormControl>
                                    <Input placeholder="handle" {...field} value={field.value || ''} />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        )}
                    />
                    <FormField
                        control={control}
                        name="identities.instagram"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel>Instagram username</FormLabel>
                                <FormControl>
                                    <Input placeholder="handle" {...field} value={field.value || ''} />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        )}
                    />
                </div>
            </CardContent>
        </Card>
    );
}
