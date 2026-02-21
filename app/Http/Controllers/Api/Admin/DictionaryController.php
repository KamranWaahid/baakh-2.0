<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lemma;
use App\Models\Sense;
use App\Models\SenseExample;
use App\Models\Morphology;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DictionaryController extends Controller
{
    public function index(Request $request)
    {
        $query = Lemma::withCount(['senses', 'lemmaRelations'])->with('morphology');

        if ($request->has('search')) {
            $query->where('lemma', 'like', '%' . $request->search . '%');
        }

        if ($request->has('pos')) {
            $query->where('pos', $request->pos);
        }

        if ($request->has('status')) {
            if ($request->status !== 'all') {
                $query->where('status', $request->status);
            }
        } else {
            // Default to only showing approved words on the main dictionary browse page
            $query->where('status', 'approved');
        }

        return response()->json($query->latest()->paginate($request->get('limit', 20)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lemma' => 'required|string',
            'pos' => 'nullable|string',
            'transliteration' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $lemma = Lemma::create($validated);

        // Sync with Romanizer
        if (!empty($validated['transliteration'])) {
            \App\Models\Romanizer::updateOrCreate(
                ['word_sd' => $lemma->lemma],
                [
                    'word_roman' => $validated['transliteration'],
                    'user_id' => auth()->id() ?? 1
                ]
            );
        }

        return response()->json($lemma, 201);
    }

    public function show($id)
    {
        $lemma = Lemma::with(['senses.examples', 'morphology', 'variants', 'lemmaRelations'])->findOrFail($id);

        // Auto-fetch transliteration from Romanizer if it's empty
        if (empty($lemma->transliteration)) {
            $roman = \App\Models\Romanizer::where('word_sd', $lemma->lemma)->first();
            if ($roman) {
                // Attach the transliteration just for the response so the frontend receives it
                $lemma->transliteration = $roman->word_roman;
            }
        }

        return response()->json($lemma);
    }

    public function update(Request $request, $id)
    {
        $lemma = Lemma::findOrFail($id);

        $validated = $request->validate([
            'lemma' => 'string',
            'pos' => 'nullable|string',
            'transliteration' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $lemma->update($validated);

        // Sync with Romanizer
        if (!empty($validated['transliteration'])) {
            \App\Models\Romanizer::updateOrCreate(
                ['word_sd' => $lemma->lemma],
                [
                    'word_roman' => $validated['transliteration'],
                    'user_id' => auth()->id() ?? 1
                ]
            );
        }

        // Nested updates for senses, morphology, variants could be added here if needed
        // For a full CRUD, usually we have separate endpoints or a complex sync logic.
        // Given the UI shows separate sections, we'll keep it simple for now and expand as needed.

        return response()->json($lemma);
    }

    public function destroy($id)
    {
        $lemma = Lemma::findOrFail($id);
        $lemma->delete();
        return response()->json(null, 204);
    }

    // Sense Methods
    public function storeSense(Request $request)
    {
        $validated = $request->validate([
            'lemma_id' => 'required|exists:lemmas,id',
            'definition' => 'nullable|string',
            'definition_en' => 'nullable|string',
            'definition_sd' => 'nullable|string',
            'domain' => 'nullable|string',
        ]);

        $sense = Sense::create($validated);
        return response()->json($sense, 201);
    }

    public function updateSense(Request $request, $id)
    {
        $sense = Sense::findOrFail($id);
        $validated = $request->validate([
            'definition' => 'string',
            'definition_en' => 'nullable|string',
            'definition_sd' => 'nullable|string',
            'domain' => 'nullable|string',
            'status' => 'nullable|in:pending,approved',
        ]);

        $sense->update($validated);
        return response()->json($sense);
    }

    public function destroySense($id)
    {
        $sense = Sense::findOrFail($id);
        $sense->delete();
        return response()->json(null, 204);
    }

    // Example Methods
    public function updateExample(Request $request, $id)
    {
        $example = SenseExample::findOrFail($id);
        $validated = $request->validate([
            'sentence' => 'string',
            'source' => 'nullable|string',
            'corpus_sentence_id' => 'nullable|integer',
        ]);

        $example->update($validated);
        return response()->json($example);
    }

    public function destroyExample($id)
    {
        $example = SenseExample::findOrFail($id);
        $example->delete();
        return response()->json(null, 204);
    }

    // Morphology Methods
    public function updateMorphology(Request $request, $lemmaId)
    {
        $lemma = Lemma::findOrFail($lemmaId);
        $validated = $request->validate([
            'root' => 'nullable|string',
            'pattern' => 'nullable|string',
            'gender' => 'nullable|string',
            'number' => 'nullable|string',
            'case' => 'nullable|string',
            'aspect' => 'nullable|string',
            'tense' => 'nullable|string',
        ]);

        $morphology = Morphology::updateOrCreate(
            ['lemma_id' => $lemmaId],
            $validated
        );

        return response()->json($morphology);
    }

    // Variant Methods
    public function storeVariant(Request $request, $lemmaId)
    {
        $validated = $request->validate([
            'variant' => 'required|string',
            'type' => 'required|in:dialectal,misspelling,historical',
            'dialect' => 'nullable|string',
        ]);

        $variant = Variant::create([
            'lemma_id' => $lemmaId,
            'variant' => $validated['variant'],
            'type' => $validated['type'],
            'dialect' => $validated['dialect'],
        ]);

        return response()->json($variant, 201);
    }

    public function destroyVariant($id)
    {
        $variant = Variant::findOrFail($id);
        $variant->delete();
        return response()->json(null, 204);
    }

    public function approve($id)
    {
        $lemma = Lemma::findOrFail($id);
        $lemma->update(['status' => 'approved']);
        return response()->json(['message' => 'Lemma approved successfully']);
    }

    // Relation Methods
    public function storeRelation(Request $request, $lemmaId)
    {
        $validated = $request->validate([
            'relation_type' => 'required|in:synonym,antonym,hypernym',
            'related_word' => 'required|string',
        ]);

        $relation = \App\Models\LemmaRelation::create([
            'lemma_id' => $lemmaId,
            'relation_type' => $validated['relation_type'],
            'related_word' => $validated['related_word'],
        ]);

        return response()->json($relation, 201);
    }

    public function destroyRelation($id)
    {
        $relation = \App\Models\LemmaRelation::findOrFail($id);
        $relation->delete();
        return response()->json(null, 204);
    }

    // Scraping Method
    public function scrapeSindhila(Request $request, $id)
    {
        $lemma = Lemma::findOrFail($id);

        // Strip diacritics
        $word = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $lemma->lemma);

        // Normalize characters (e.g., standardizing different forms of Heh and Ye)
        // This is crucial because Sindhila uses specific normalized forms for index matching.
        $word = \App\Helpers\SindhiNormalizer::normalize($word);

        // Fetch from Sindhila dictionary using GET
        $url = 'https://dic.sindhila.edu.pk/index.php?txtsrch=' . urlencode($word);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->get($url);

            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to reach Sindhila website.'], 500);
            }

            $html = $response->body();

            // Suppress DOMDocument warnings for malformed HTML
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            // Using HTML-ENTITIES to handle UTF-8 correctly
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOBLANKS | LIBXML_NOWARNING);
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);

            // The results are usually inside a panel-body. We can just target that.
            $contentDivs = $xpath->query('//div[contains(@class, "panel-body")]');

            $results = [];

            if ($contentDivs->length > 0) {
                // The first one is usually the main content or abbreviations. 
                // We'll iterate through all of them to be safe, or just pick the ones with actual definitions.
                // Usually there is only 1 or 2.
                foreach ($contentDivs as $container) {
                    $blocks = [];
                    $currentSource = "General";

                    // include div headers like sheading2 which separate sections
                    $nodes = $xpath->query('.//p | .//h4 | .//h5 | .//li | .//div[contains(@class, "sheading2")] | .//div[contains(@class, "sheadingsd2")] | .//h2[contains(@class, "medium")]', $container);

                    foreach ($nodes as $node) {
                        $text = trim($node->textContent);
                        if (empty($text))
                            continue;

                        // Filter out UI noise
                        if (
                            str_contains($text, 'مخففن جي سمجھاڻي') ||
                            str_contains($text, 'Abbreviations') ||
                            str_contains($text, 'وڌيڪ نتيجا ڏسو') ||
                            str_contains($text, 'Abbreviations con =')
                        ) {
                            continue;
                        }

                        // Detect source headers
                        if (str_ends_with($text, ':') || str_contains($text, 'لغات ۾') || str_contains($text, 'ڊڪشنريءَ مان') || str_contains($text, 'لغت مان')) {
                            $currentSource = str_replace(':', '', $text);
                            continue;
                        }

                        $blocks[] = [
                            'source' => trim($currentSource),
                            'text' => $text
                        ];
                    }

                    // Group by source and further filter
                    foreach ($blocks as $block) {
                        if ($block['text'] === $word)
                            continue;

                        // Exclude navigation related links that get picked up as list items
                        if (
                            str_contains($block['text'], 'سان لاڳاپيل لفظ') ||
                            str_contains($block['text'], 'بابت وڌيڪ اصطلاح') ||
                            str_contains($block['text'], 'عام استعمال')
                        ) {
                            continue;
                        }

                        $results[] = [
                            'source' => $block['source'],
                            'text' => trim(preg_replace('/\s+/', ' ', $block['text']))
                        ];
                    }
                }
            }

            // If structure parsing failed, fallback to a simpler text extraction of the main body
            if (empty($results)) {
                // Just extract all paragraphs in that div
                $paragraphs = $xpath->query('//div[@class="col-md-9 column"]//p');
                foreach ($paragraphs as $p) {
                    $text = trim($p->textContent);
                    if (!empty($text) && $text !== $word) {
                        $results[] = [
                            'source' => 'Extracted Text',
                            'text' => preg_replace('/\s+/', ' ', $text)
                        ];
                    }
                }
            }

            return response()->json([
                'word' => $word,
                'results' => $results,
                'raw_html' => null // Can set to $html if needed for debugging
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function scrapeSindhilaByWord(Request $request)
    {
        $request->validate([
            'word' => 'required|string',
        ]);

        $word = $request->input('word');

        // Strip diacritics
        $cleanWord = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $word);

        // Normalize characters
        $normalizedWord = \App\Helpers\SindhiNormalizer::normalize($cleanWord);

        // Fetch from Sindhila dictionary using GET
        $url = 'https://dic.sindhila.edu.pk/index.php?txtsrch=' . urlencode($normalizedWord);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->get($url);

            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to reach Sindhila website.'], 500);
            }

            $html = $response->body();

            // Suppress DOMDocument warnings for malformed HTML
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            // Using HTML-ENTITIES to handle UTF-8 correctly
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOBLANKS | LIBXML_NOWARNING);
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);

            // The results are usually inside a panel-body.
            $contentDivs = $xpath->query('//div[contains(@class, "panel-body")]');

            $results = [];

            if ($contentDivs->length > 0) {
                foreach ($contentDivs as $container) {
                    $blocks = [];
                    $currentSource = "General";

                    // include div headers like sheading2 which separate sections
                    $nodes = $xpath->query('.//p | .//h4 | .//h5 | .//li | .//div[contains(@class, "sheading2")] | .//div[contains(@class, "sheadingsd2")] | .//h2[contains(@class, "medium")]', $container);

                    foreach ($nodes as $node) {
                        $text = trim($node->textContent);
                        if (empty($text))
                            continue;

                        // Filter out UI noise
                        if (
                            str_contains($text, 'مخففن جي سمجھاڻي') ||
                            str_contains($text, 'Abbreviations') ||
                            str_contains($text, 'وڌيڪ نتيجا ڏسو') ||
                            str_contains($text, 'Abbreviations con =')
                        ) {
                            continue;
                        }

                        // Detect source headers
                        if (str_ends_with($text, ':') || str_contains($text, 'لغات ۾') || str_contains($text, 'ڊڪشنريءَ مان') || str_contains($text, 'لغت مان')) {
                            $currentSource = str_replace(':', '', $text);
                            continue;
                        }

                        $blocks[] = [
                            'source' => trim($currentSource),
                            'text' => $text
                        ];
                    }

                    // Group by source and further filter
                    foreach ($blocks as $block) {
                        if ($block['text'] === $normalizedWord)
                            continue;

                        // Exclude navigation related links that get picked up as list items
                        if (
                            str_contains($block['text'], 'سان لاڳاپيل لفظ') ||
                            str_contains($block['text'], 'بابت وڌيڪ اصطلاح') ||
                            str_contains($block['text'], 'عام استعمال')
                        ) {
                            continue;
                        }

                        $results[] = [
                            'source' => $block['source'],
                            'text' => trim(preg_replace('/\s+/', ' ', $block['text']))
                        ];
                    }
                }
            }

            // If structure parsing failed, fallback to a simpler text extraction of the main body
            if (empty($results)) {
                $paragraphs = $xpath->query('//div[@class="col-md-9 column"]//p');
                foreach ($paragraphs as $p) {
                    $text = trim($p->textContent);
                    if (!empty($text) && $text !== $normalizedWord) {
                        $results[] = [
                            'source' => 'Extracted Text',
                            'text' => preg_replace('/\s+/', ' ', $text)
                        ];
                    }
                }
            }

            return response()->json([
                'word' => $normalizedWord,
                'original_word' => $word,
                'results' => $results,
                'raw_html' => null
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function scrapeBatchMissing(Request $request)
    {
        $count = $request->input('count', 10);

        // 1. Find $count words from corpus_stats that do NOT exist in lemmas table
        // We order by frequency (desc) to get common words first, or random if preferred.
        // Let's get top 100 most frequent missing words and pick $count randomly from them to avoid always hitting the same un-scrapable words.
        $missingWordsQuery = \App\Models\CorpusStat::whereNotIn('word', function ($query) {
            $query->select('word')->from('sindhila_scrapes');
        })
            ->orderBy('frequency', 'desc')
            ->limit(100)
            ->get();

        if ($missingWordsQuery->isEmpty()) {
            return response()->json(['message' => 'No missing words found in corpus.'], 404);
        }

        // Pick random N words from the top missing
        $selectedWords = $missingWordsQuery->random(min($count, $missingWordsQuery->count()))->pluck('word');
        $results = [];

        foreach ($selectedWords as $word) {
            $cleanWord = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $word);
            $normalizedWord = \App\Helpers\SindhiNormalizer::normalize($cleanWord);

            $url = 'https://dic.sindhila.edu.pk/index.php?txtsrch=' . urlencode($normalizedWord);

            try {
                $response = \Illuminate\Support\Facades\Http::timeout(10)->get($url);
                if (!$response->successful())
                    continue;

                $html = $response->body();
                libxml_use_internal_errors(true);
                $dom = new \DOMDocument();
                $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOBLANKS | LIBXML_NOWARNING);
                libxml_clear_errors();

                $xpath = new \DOMXPath($dom);
                $contentDivs = $xpath->query('//div[contains(@class, "panel-body")]');
                $scrapedSenses = [];

                if ($contentDivs->length > 0) {
                    foreach ($contentDivs as $container) {
                        $blocks = [];
                        $currentSource = "General";
                        $nodes = $xpath->query('.//p | .//h4 | .//h5 | .//li | .//div[contains(@class, "sheading2")] | .//div[contains(@class, "sheadingsd2")] | .//h2[contains(@class, "medium")]', $container);

                        foreach ($nodes as $node) {
                            $text = trim($node->textContent);
                            if (empty($text))
                                continue;
                            if (str_contains($text, 'مخففن جي سمجھاڻي') || str_contains($text, 'Abbreviations') || str_contains($text, 'وڌيڪ نتيجا ڏسو'))
                                continue;

                            if (str_ends_with($text, ':') || str_contains($text, 'لغات ۾') || str_contains($text, 'ڊڪشنريءَ مان') || str_contains($text, 'لغت مان')) {
                                $currentSource = str_replace(':', '', $text);
                                continue;
                            }
                            $blocks[] = ['source' => trim($currentSource), 'text' => $text];
                        }

                        foreach ($blocks as $block) {
                            if ($block['text'] === $normalizedWord)
                                continue;
                            if (str_contains($block['text'], 'سان لاڳاپيل لفظ') || str_contains($block['text'], 'بابت وڌيڪ اصطلاح') || str_contains($block['text'], 'عام استعمال'))
                                continue;

                            $scrapedSenses[] = [
                                'source' => $block['source'],
                                'text' => trim(preg_replace('/\s+/', ' ', $block['text']))
                            ];
                        }
                    }
                }

                // Only create the scrape record if we actually found definitions
                if (!empty($scrapedSenses)) {
                    \App\Models\SindhilaScrape::create([
                        'word' => $normalizedWord,
                        'scraped_data' => $scrapedSenses,
                        'status' => 'pending'
                    ]);

                    $results[] = [
                        'word' => $normalizedWord,
                        'status' => 'success',
                        'senses_added' => count($scrapedSenses)
                    ];
                } else {
                    $results[] = [
                        'word' => $normalizedWord,
                        'status' => 'not_found'
                    ];
                }

            } catch (\Exception $e) {
                \App\Models\SindhilaScrape::create([
                    'word' => $normalizedWord,
                    'scraped_data' => null,
                    'status' => 'error_parsing'
                ]);

                $results[] = [
                    'word' => $normalizedWord,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'processed' => count($selectedWords),
            'results' => $results
        ]);
    }

    public function autoScrapeStep(Request $request)
    {
        // Find 1 word from corpus_stats that hasn't been checked yet
        $stat = \App\Models\CorpusStat::whereNull('sindhila_status')
            ->whereNotIn('word', function ($query) {
                // Ensure we don't scrape words already in the isolated scrape queue
                $query->select('word')->from('sindhila_scrapes');
            })
            ->orderBy('frequency', 'desc')
            ->first();

        // Count how many are left
        $remaining = \App\Models\CorpusStat::whereNull('sindhila_status')
            ->whereNotIn('word', function ($query) {
                $query->select('word')->from('sindhila_scrapes');
            })
            ->count();

        if (!$stat) {
            return response()->json(['message' => 'No more missing words to check!', 'remaining' => 0], 200);
        }

        $word = $stat->word;
        $cleanWord = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $word);
        $normalizedWord = \App\Helpers\SindhiNormalizer::normalize($cleanWord);

        $url = 'https://dic.sindhila.edu.pk/index.php?txtsrch=' . urlencode($normalizedWord);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($url);

            if (!$response->successful()) {
                $stat->update(['sindhila_status' => 'error_http']);
                return response()->json(['word' => $word, 'status' => 'error_http', 'remaining' => $remaining]);
            }

            $html = $response->body();
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOBLANKS | LIBXML_NOWARNING);
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);
            $contentDivs = $xpath->query('//div[contains(@class, "panel-body")]');
            $scrapedSenses = [];

            if ($contentDivs->length > 0) {
                foreach ($contentDivs as $container) {
                    $blocks = [];
                    $currentSource = "General";
                    $nodes = $xpath->query('.//p | .//h4 | .//h5 | .//li | .//div[contains(@class, "sheading2")] | .//div[contains(@class, "sheadingsd2")] | .//h2[contains(@class, "medium")]', $container);

                    foreach ($nodes as $node) {
                        $text = trim($node->textContent);
                        if (empty($text))
                            continue;
                        if (str_contains($text, 'مخففن جي سمجھاڻي') || str_contains($text, 'Abbreviations') || str_contains($text, 'وڌيڪ نتيجا ڏسو'))
                            continue;

                        if (str_ends_with($text, ':') || str_contains($text, 'لغات ۾') || str_contains($text, 'ڊڪشنريءَ مان') || str_contains($text, 'لغت مان')) {
                            $currentSource = str_replace(':', '', $text);
                            continue;
                        }
                        $blocks[] = ['source' => trim($currentSource), 'text' => $text];
                    }

                    foreach ($blocks as $block) {
                        if ($block['text'] === $normalizedWord)
                            continue;
                        if (str_contains($block['text'], 'سان لاڳاپيل لفظ') || str_contains($block['text'], 'بابت وڌيڪ اصطلاح') || str_contains($block['text'], 'عام استعمال'))
                            continue;

                        $scrapedSenses[] = [
                            'source' => $block['source'],
                            'text' => trim(preg_replace('/\s+/', ' ', $block['text']))
                        ];
                    }
                }
            }

            if (empty($scrapedSenses)) {
                $paragraphs = $xpath->query('//div[@class="col-md-9 column"]//p');
                foreach ($paragraphs as $p) {
                    $text = trim($p->textContent);
                    if (!empty($text) && $text !== $normalizedWord) {
                        $scrapedSenses[] = ['source' => 'Extracted Text', 'text' => preg_replace('/\s+/', ' ', $text)];
                    }
                }
            }

            if (empty($scrapedSenses)) {
                $stat->update(['sindhila_status' => 'not_found']);
                return response()->json(['word' => $word, 'status' => 'not_found', 'remaining' => $remaining]);
            }

            // Success: Create isolated scraped entity instead of throwing directly into Lemmas
            $newScrape = \App\Models\SindhilaScrape::create([
                'word' => $normalizedWord,
                'scraped_data' => $scrapedSenses,
                'status' => 'pending'
            ]);

            $stat->update(['sindhila_status' => 'found']);
            return response()->json([
                'word' => $word,
                'status' => 'success',
                'senses_added' => count($scrapedSenses),
                'remaining' => $remaining
            ]);

        } catch (\Exception $e) {
            $stat->update(['sindhila_status' => 'error_parsing']);
            return response()->json(['word' => $word, 'status' => 'error_parsing', 'message' => $e->getMessage(), 'remaining' => $remaining]);
        }
    }
}
