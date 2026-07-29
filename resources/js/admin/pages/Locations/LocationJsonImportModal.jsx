import React, { useMemo, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { toast } from 'sonner';
import { Braces, Copy, CheckCircle2, Loader2, Save } from 'lucide-react';

const TEMPLATES = {
    countries: {
        title: 'Import Countries (JSON)',
        endpoint: '/api/admin/locations/import/countries',
        invalidate: ['countries'],
        sample: {
            _schema: 'baakh.locations.countries.v1',
            countries: [
                {
                    abbreviation: 'pk',
                    continent: 'South Asia',
                    names: { en: 'Pakistan', sd: 'پاڪستان' },
                    descriptions: {
                        en: 'Islamic Republic of Pakistan',
                        sd: 'اسلامي جمهوريه پاڪستان',
                    },
                },
                {
                    abbreviation: 'in',
                    continent: 'South Asia',
                    names: { en: 'India', sd: 'هندستان' },
                },
            ],
        },
        prompt: `Return ONLY one valid JSON object for Baakh admin locations. No markdown fences. No explanation.

Schema: baakh.locations.countries.v1

Give a comprehensive list of world countries (or as many as practical).
Each country MUST include:
- abbreviation (ISO-ish lowercase 2-letter when possible, e.g. "pk")
- continent (English, e.g. "South Asia")
- names.en (English)
- names.sd (Sindhi Arabic script)
Optional: descriptions.en, descriptions.sd

Example shape:
{
  "_schema": "baakh.locations.countries.v1",
  "countries": [
    {
      "abbreviation": "pk",
      "continent": "South Asia",
      "names": { "en": "Pakistan", "sd": "پاڪستان" },
      "descriptions": { "en": "...", "sd": "..." }
    }
  ]
}

JSON:
`,
    },
    provinces: {
        title: 'Import Provinces — Pakistan (JSON)',
        endpoint: '/api/admin/locations/import/provinces',
        invalidate: ['provinces', 'countries'],
        sample: {
            _schema: 'baakh.locations.provinces.v1',
            country: {
                abbreviation: 'pk',
                continent: 'South Asia',
                names: { en: 'Pakistan', sd: 'پاڪستان' },
            },
            provinces: [
                { names: { en: 'Sindh', sd: 'سنڌ' } },
                { names: { en: 'Punjab', sd: 'پنجاب' } },
                { names: { en: 'Khyber Pakhtunkhwa', sd: 'خيبر پختونخوا' } },
                { names: { en: 'Balochistan', sd: 'بلوچستان' } },
                { names: { en: 'Islamabad Capital Territory', sd: 'اسلام آباد گاديءَ جو هنڌ' } },
            ],
        },
        prompt: `Return ONLY one valid JSON object for Baakh admin locations. No markdown fences. No explanation.

Schema: baakh.locations.provinces.v1
Focus: Pakistan provinces / territories only.

Include country block for Pakistan (abbreviation "pk", names en+sd).
List all Pakistani provinces and territories with names.en and names.sd.

Example:
{
  "_schema": "baakh.locations.provinces.v1",
  "country": {
    "abbreviation": "pk",
    "continent": "South Asia",
    "names": { "en": "Pakistan", "sd": "پاڪستان" }
  },
  "provinces": [
    { "names": { "en": "Sindh", "sd": "سنڌ" } }
  ]
}

JSON:
`,
    },
    cities: {
        title: 'Import Cities — Sindh (JSON)',
        endpoint: '/api/admin/locations/import/cities',
        invalidate: ['cities', 'provinces', 'districts', 'talukas'],
        sample: {
            _schema: 'baakh.locations.cities.v1',
            province: { name_en: 'Sindh', name_sd: 'سنڌ' },
            cities: [
                {
                    names: { en: 'Karachi', sd: 'ڪراچي' },
                    district: { names: { en: 'Karachi Central', sd: 'ڪراچي سينٽرل' } },
                    taluka: { names: { en: 'Liaquatabad', sd: 'لياقت آباد' } },
                },
                { names: { en: 'Hyderabad', sd: 'حيدرآباد' } },
            ],
        },
        prompt: `Return ONLY one valid JSON object for Baakh admin locations. No markdown fences. No explanation.

Schema: baakh.locations.cities.v1
Focus: Cities of Sindh, Pakistan.

Include province Sindh (name_en/name_sd).
List major and district-level cities of Sindh with names.en and names.sd.
Optional per city: district { names }, taluka { names }, geo_lat, geo_long.

Example:
{
  "_schema": "baakh.locations.cities.v1",
  "province": { "name_en": "Sindh", "name_sd": "سنڌ" },
  "cities": [
    {
      "names": { "en": "Karachi", "sd": "ڪراچي" },
      "district": { "names": { "en": "Karachi Central", "sd": "ڪراچي سينٽرل" } }
    }
  ]
}

JSON:
`,
    },
    districts: {
        title: 'Import Districts — Sindh (JSON)',
        endpoint: '/api/admin/locations/import/districts',
        invalidate: ['districts', 'talukas', 'provinces'],
        sample: {
            _schema: 'baakh.locations.districts.v1',
            province: { name_en: 'Sindh', name_sd: 'سنڌ' },
            districts: [
                {
                    names: { en: 'Hyderabad', sd: 'حيدرآباد' },
                    talukas: [
                        { names: { en: 'Hyderabad City', sd: 'حيدرآباد شهر' } },
                        { names: { en: 'Latifabad', sd: 'لطيف آباد' } },
                    ],
                },
            ],
        },
        prompt: `Return ONLY one valid JSON object for Baakh admin locations. No markdown fences. No explanation.

Schema: baakh.locations.districts.v1
Focus: All districts of Sindh, Pakistan.

Include province Sindh.
Each district: names.en + names.sd.
Optional nested talukas[] with names.en + names.sd.

Example:
{
  "_schema": "baakh.locations.districts.v1",
  "province": { "name_en": "Sindh", "name_sd": "سنڌ" },
  "districts": [
    {
      "names": { "en": "Hyderabad", "sd": "حيدرآباد" },
      "talukas": [
        { "names": { "en": "Latifabad", "sd": "لطيف آباد" } }
      ]
    }
  ]
}

JSON:
`,
    },
    talukas: {
        title: 'Import Talukas — Sindh (JSON)',
        endpoint: '/api/admin/locations/import/talukas',
        invalidate: ['talukas', 'districts'],
        sample: {
            _schema: 'baakh.locations.talukas.v1',
            province: { name_en: 'Sindh', name_sd: 'سنڌ' },
            talukas: [
                {
                    district: { names: { en: 'Hyderabad', sd: 'حيدرآباد' } },
                    names: { en: 'Latifabad', sd: 'لطيف آباد' },
                },
            ],
        },
        prompt: `Return ONLY one valid JSON object for Baakh admin locations. No markdown fences. No explanation.

Schema: baakh.locations.talukas.v1
Focus: Talukas of Sindh, Pakistan.

Include province Sindh.
Each taluka MUST include district { names.en, names.sd } and names.en + names.sd for the taluka.

Example:
{
  "_schema": "baakh.locations.talukas.v1",
  "province": { "name_en": "Sindh", "name_sd": "سنڌ" },
  "talukas": [
    {
      "district": { "names": { "en": "Hyderabad", "sd": "حيدرآباد" } },
      "names": { "en": "Latifabad", "sd": "لطيف آباد" }
    }
  ]
}

JSON:
`,
    },
};

function extractJson(raw) {
    const text = String(raw || '').trim();
    if (!text) throw new Error('Paste JSON first.');

    try {
        return JSON.parse(text);
    } catch {
        // continue
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

export default function LocationJsonImportModal({ open, onOpenChange, type = 'countries' }) {
    const queryClient = useQueryClient();
    const config = TEMPLATES[type] || TEMPLATES.countries;
    const [inputJson, setInputJson] = useState('');
    const [parseError, setParseError] = useState(null);
    const [copiedPrompt, setCopiedPrompt] = useState(false);
    const [copiedSample, setCopiedSample] = useState(false);

    const sampleText = useMemo(() => JSON.stringify(config.sample, null, 2), [config.sample]);

    const importMutation = useMutation({
        mutationFn: async (payload) => {
            const res = await api.post(config.endpoint, payload);
            return res.data;
        },
        onSuccess: (data) => {
            (config.invalidate || []).forEach((key) => {
                queryClient.invalidateQueries({ queryKey: [key] });
            });
            toast.success(data?.message || 'Import complete');
            setInputJson('');
            setParseError(null);
            onOpenChange?.(false);
        },
        onError: (error) => {
            const message = error?.response?.data?.message
                || (error?.response?.data?.errors && Object.values(error.response.data.errors).flat().join(' '))
                || 'Import failed.';
            toast.error(message);
            setParseError(message);
        },
    });

    const handleCopyPrompt = async () => {
        await navigator.clipboard.writeText(config.prompt + sampleText);
        setCopiedPrompt(true);
        toast.success('AI prompt + sample JSON copied. Paste into ChatGPT, then paste the reply here.');
        setTimeout(() => setCopiedPrompt(false), 2000);
    };

    const handleCopySample = async () => {
        await navigator.clipboard.writeText(sampleText);
        setCopiedSample(true);
        toast.success('Sample JSON copied');
        setTimeout(() => setCopiedSample(false), 2000);
    };

    const handleImport = () => {
        setParseError(null);
        let parsed;
        try {
            parsed = extractJson(inputJson);
        } catch (error) {
            setParseError(error.message || 'Invalid JSON');
            return;
        }
        if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
            setParseError('JSON must be an object.');
            return;
        }
        if (!confirm('Import / upsert these locations from JSON? Existing matching names will be updated.')) {
            return;
        }
        importMutation.mutate(parsed);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Braces className="h-5 w-5" />
                        {config.title}
                    </DialogTitle>
                    <DialogDescription>
                        Copy the AI prompt, paste the returned JSON here, then import. Matching names upsert; new ones are created.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" size="sm" onClick={handleCopyPrompt}>
                        {copiedPrompt ? <CheckCircle2 className="h-4 w-4 mr-1.5" /> : <Copy className="h-4 w-4 mr-1.5" />}
                        Copy for AI
                    </Button>
                    <Button type="button" variant="ghost" size="sm" onClick={handleCopySample}>
                        {copiedSample ? <CheckCircle2 className="h-4 w-4 mr-1.5" /> : <Copy className="h-4 w-4 mr-1.5" />}
                        Copy sample JSON
                    </Button>
                </div>

                <div className="space-y-2">
                    <label className="text-sm font-medium">Paste AI JSON</label>
                    <Textarea
                        value={inputJson}
                        onChange={(e) => setInputJson(e.target.value)}
                        placeholder={sampleText}
                        className="min-h-[280px] font-mono text-xs"
                        dir="ltr"
                    />
                    {parseError && (
                        <p className="text-sm text-destructive">{parseError}</p>
                    )}
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="ghost" onClick={() => onOpenChange?.(false)}>
                        Cancel
                    </Button>
                    <Button type="button" onClick={handleImport} disabled={importMutation.isPending || !inputJson.trim()}>
                        {importMutation.isPending
                            ? <Loader2 className="h-4 w-4 mr-1.5 animate-spin" />
                            : <Save className="h-4 w-4 mr-1.5" />}
                        Import JSON
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
