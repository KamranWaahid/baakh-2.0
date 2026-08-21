<?php

namespace App\Support;

/**
 * Parametric AEO intents ({poet_entity}, {work_title}, {genre_tag}, {topic_node},
 * {script_variant}) filled from real archive rows. Canonical public URLs stay:
 *   /{lang}/poet/{poet}
 *   /{lang}/poet/{poet}/{genre}/{poem}
 *   /{lang}/{genre}
 *   /{lang}/tag/{topic} and /{lang}/topic/{topic}
 *   /{lang}/couplets
 * No /poetry/{poet}/{genre}/{topic} route, no guessed Wikipedia, no Neo4j layer.
 */
class AeoIntentMatrix
{
    public const SCENARIO_WORK = 'work_lookup';

    public const SCENARIO_INTERSECT = 'poet_genre_topic';

    public const SCENARIO_SNIPPET = 'couplet_snippet';

    public const SCENARIO_BIBLIO = 'bibliographic';

    /**
     * Build URL-bearing tokens from slugs/labels already resolved in SEO.
     *
     * @param  array{
     *     poet?: ?array{slug?: string, label?: string, url?: string},
     *     work?: ?array{slug?: string, label?: string, url?: string},
     *     genre?: ?array{slug?: string, label?: string, url?: string},
     *     topic?: ?array{slug?: string, label?: string, url?: string},
     *     book?: ?array{label?: string}
     * }  $nodes
     * @return array<string, mixed>
     */
    public static function hydrate(string $locale, array $nodes): array
    {
        $locale = $locale === 'sd' ? 'sd' : 'en';
        $poet = self::node($nodes['poet'] ?? null);
        $genre = self::node($nodes['genre'] ?? null);
        $work = self::node($nodes['work'] ?? null);
        $topic = self::node($nodes['topic'] ?? null);
        $bookLabel = trim((string) (($nodes['book']['label'] ?? '') ?: ''));

        if ($poet && ($poet['url'] ?? '') === '' && ($poet['slug'] ?? '') !== '') {
            $poet['url'] = url("{$locale}/poet/" . $poet['slug']);
        }
        if ($genre && ($genre['url'] ?? '') === '' && ($genre['slug'] ?? '') !== '') {
            $genre['url'] = url("{$locale}/" . $genre['slug']);
        }
        if ($topic && ($topic['url'] ?? '') === '' && ($topic['slug'] ?? '') !== '') {
            $topic['url'] = url("{$locale}/tag/" . $topic['slug']);
        }
        if (
            $work
            && ($work['url'] ?? '') === ''
            && ($poet['slug'] ?? '') !== ''
            && ($genre['slug'] ?? '') !== ''
            && ($work['slug'] ?? '') !== ''
        ) {
            $work['url'] = url("{$locale}/poet/" . $poet['slug'] . '/' . $genre['slug'] . '/' . $work['slug']);
        }

        return [
            'locale' => $locale,
            'script_variant' => $locale === 'sd' ? 'sindhi-arabic' : 'english',
            'poet' => $poet,
            'work' => $work,
            'genre' => $genre,
            'topic' => $topic,
            'book_label' => $bookLabel !== '' ? $bookLabel : null,
            'couplets_url' => url("{$locale}/couplets"),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $thing
     * @return array{slug: string, label: string, url: string}|null
     */
    public static function node(?array $thing): ?array
    {
        if (! is_array($thing)) {
            return null;
        }

        $label = trim((string) ($thing['label'] ?? $thing['name'] ?? ''));
        $slug = trim((string) ($thing['slug'] ?? $thing['identifier'] ?? ''));
        $url = trim((string) ($thing['url'] ?? ''));

        if ($slug === '' && $url !== '') {
            $path = trim((string) (parse_url($url, PHP_URL_PATH) ?: ''), '/');
            $parts = explode('/', $path);
            $slug = (string) (end($parts) ?: '');
        }

        if ($label === '' && $slug === '' && $url === '') {
            return null;
        }

        return [
            'slug' => $slug,
            'label' => $label !== '' ? $label : $slug,
            'url' => $url,
        ];
    }

    /**
     * Conversational meta phrases from filled tokens only.
     *
     * @param  array<string, mixed>  $tokens
     * @return list<string>
     */
    public static function keywords(array $tokens): array
    {
        $poet = trim((string) ($tokens['poet']['label'] ?? ''));
        $work = trim((string) ($tokens['work']['label'] ?? ''));
        $genre = trim((string) ($tokens['genre']['label'] ?? ''));
        $topic = trim((string) ($tokens['topic']['label'] ?? ''));
        $out = [];

        if ($work !== '' && $poet !== '') {
            $out[] = $work . ' by ' . $poet;
            $out[] = $poet . ' ' . $work;
        }
        if ($poet !== '' && $genre !== '' && $topic !== '') {
            $out[] = $poet . ' ' . $genre . ' on ' . $topic;
        }
        if ($genre !== '' && $topic !== '') {
            $out[] = 'Sindhi ' . $genre . ' on ' . $topic;
            $out[] = $topic . ' ' . $genre . ' copy paste';
        }
        if ($poet !== '' && $topic !== '') {
            $out[] = $poet . ' ' . $topic;
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  array<string, mixed>  $tokens
     * @return list<array<string, mixed>>
     */
    public static function schema(string $scenario, array $tokens, string $locale): array
    {
        $out = [];
        foreach (self::rows($scenario, $tokens, $locale) as $row) {
            $text = $row['a'];
            foreach ($row['links'] ?? [] as $link) {
                $href = trim((string) ($link['href'] ?? ''));
                if ($href !== '') {
                    $text .= ' ' . $href;
                }
            }
            $out[] = [
                '@type' => 'Question',
                'name' => $row['q'],
                'inLanguage' => $locale,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => trim($text),
                    'inLanguage' => $locale,
                ],
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $existing
     * @param  list<array<string, mixed>>  $extra
     * @return list<array<string, mixed>>
     */
    public static function mergeFaqs(array $existing, array $extra, int $max = 8): array
    {
        $seen = [];
        $out = [];
        foreach (array_merge($existing, $extra) as $faq) {
            $key = mb_strtolower(trim((string) ($faq['name'] ?? '')));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $faq;
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $tokens
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    public static function rows(string $scenario, array $tokens, string $locale): array
    {
        $isSd = $locale === 'sd';
        $labels = [
            '{poet_entity}' => (string) ($tokens['poet']['label'] ?? ''),
            '{work_title}' => (string) ($tokens['work']['label'] ?? ''),
            '{genre_tag}' => (string) ($tokens['genre']['label'] ?? ''),
            '{topic_node}' => (string) ($tokens['topic']['label'] ?? ''),
            '{script_variant}' => $isSd ? 'سنڌي' : 'Sindhi and Roman Sindhi',
        ];

        $templates = match ($scenario) {
            self::SCENARIO_WORK => self::workTemplates($isSd, $tokens),
            self::SCENARIO_INTERSECT => self::intersectTemplates($isSd, $tokens),
            self::SCENARIO_SNIPPET => self::snippetTemplates($isSd, $tokens),
            self::SCENARIO_BIBLIO => self::biblioTemplates($isSd, $tokens),
            default => [],
        };

        $rows = [];
        foreach ($templates as $row) {
            $q = self::fill($row['q'], $labels);
            $a = self::fill($row['a'], $labels);
            if ($q === null || $a === null) {
                continue;
            }
            $filled = ['q' => $q, 'a' => $a];
            if (! empty($row['links'])) {
                $filled['links'] = $row['links'];
            }
            $rows[] = $filled;
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $tokens
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function workTemplates(bool $isSd, array $tokens): array
    {
        $workUrl = (string) ($tokens['work']['url'] ?? '');
        $poetUrl = (string) ($tokens['poet']['url'] ?? '');
        if ($workUrl === '' || ($tokens['poet']['label'] ?? '') === '' || ($tokens['work']['label'] ?? '') === '') {
            return [];
        }

        $links = [
            ['href' => $workUrl, 'label' => $isSd ? 'شعر' : 'Poem'],
        ];
        if ($poetUrl !== '') {
            $links[] = ['href' => $poetUrl, 'label' => $isSd ? 'شاعر' : 'Poet'];
        }

        if ($isSd) {
            return [
                [
                    'q' => '{poet_entity} جو لکيل {work_title} ڪٿي پڙهڻ لاءِ ملندو؟',
                    'a' => 'مڪمل متن باک تي اصل سنڌي رسم الخط ۽ رومن سنڌيءَ ۾ موجود آهي.',
                    'links' => $links,
                ],
            ];
        }

        return [
            [
                'q' => 'Where can I find {work_title} by {poet_entity} online?',
                'a' => 'The full poem text is on Baakh in original Sindhi script and Roman Sindhi (sd-Latn) on the same page.',
                'links' => $links,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $tokens
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function intersectTemplates(bool $isSd, array $tokens): array
    {
        if (
            ($tokens['poet']['label'] ?? '') === ''
            || ($tokens['genre']['label'] ?? '') === ''
            || ($tokens['topic']['label'] ?? '') === ''
        ) {
            return [];
        }

        $links = [];
        foreach (['poet', 'topic', 'genre'] as $key) {
            $href = (string) ($tokens[$key]['url'] ?? '');
            $label = (string) ($tokens[$key]['label'] ?? $key);
            if ($href !== '') {
                $links[] = ['href' => $href, 'label' => $label];
            }
        }
        if ($links === []) {
            return [];
        }

        if ($isSd) {
            return [
                [
                    'q' => '{poet_entity} جا {topic_node} جي موضوع تي بهترين {genre_tag}.',
                    'a' => '{poet_entity} جو {genre_tag} جنهن کي {topic_node} سان ٽيگ ڪيو ويو آهي، شاعر جي پروفائل ۽ موضوع صفحي تي انڊيڪس ٿيل آهي.',
                    'links' => $links,
                ],
            ];
        }

        return [
            [
                'q' => 'Show me {poet_entity} {genre_tag} lines about {topic_node}.',
                'a' => '{poet_entity} {genre_tag} tagged {topic_node} is indexed on the poet profile and the topic hub. Open those pages to read and copy the archive text.',
                'links' => $links,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $tokens
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function snippetTemplates(bool $isSd, array $tokens): array
    {
        $topic = (string) ($tokens['topic']['label'] ?? '');
        $genre = (string) ($tokens['genre']['label'] ?? '');
        if ($topic === '' && $genre === '') {
            return [];
        }

        $links = [
            ['href' => (string) $tokens['couplets_url'], 'label' => $isSd ? 'بند' : 'Couplets'],
        ];
        foreach (['topic', 'genre'] as $key) {
            $href = (string) ($tokens[$key]['url'] ?? '');
            if ($href !== '') {
                $links[] = ['href' => $href, 'label' => (string) ($tokens[$key]['label'] ?? $key)];
            }
        }

        if ($isSd) {
            if ($topic !== '' && $genre !== '') {
                return [[
                    'q' => '{topic_node} جي عنوان تي ٻن سٽن وارا سنڌي {genre_tag}.',
                    'a' => 'باک جي بند صفحي تي ٻن سٽن واريون سٽون ڪاپي ڪري سگهجن ٿيون؛ صنف ۽ موضوع جا صفحا مڪمل ڪلام ڏيکارين ٿا. فائل ڊائون لوڊ ناهي — اصل متن ڪاپي ڪريو.',
                    'links' => $links,
                ]];
            }
            if ($topic !== '') {
                return [[
                    'q' => 'واٽس اپ اسٽيٽس لاءِ {topic_node} واريون سنڌي سٽون.',
                    'a' => 'موضوع سان ٽيگ ٿيل مختصر سٽون بند صفحي ۽ هن موضوع هاب تان ڪاپي ڪريو.',
                    'links' => $links,
                ]];
            }

            return [[
                'q' => 'مختصر سنڌي {genre_tag} جيڪي ڪاپي پيسٽ ڪري سگهجن.',
                'a' => 'باک تي {genre_tag} جو اصل متن صنف صفحي ۽ بند فهرص ۾ آهي.',
                'links' => $links,
            ]];
        }

        if ($topic !== '' && $genre !== '') {
            return [[
                'q' => 'Short {topic_node} quotes in {genre_tag} format for copy paste.',
                'a' => 'Copy two-line verses from the couplets listing; the genre and topic pages index the matching archive text. There is no separate download file.',
                'links' => $links,
            ]];
        }
        if ($topic !== '') {
            return [[
                'q' => 'Where to get localized {topic_node} themed short Sindhi lines?',
                'a' => 'Copy short verses tagged {topic_node} from the couplets page and this topic hub.',
                'links' => $links,
            ]];
        }

        return [[
            'q' => 'Authentic Sindhi {genre_tag} verses for copy paste.',
            'a' => 'Baakh keeps original {genre_tag} text on the genre page and couplets listing.',
            'links' => $links,
        ]];
    }

    /**
     * @param  array<string, mixed>  $tokens
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function biblioTemplates(bool $isSd, array $tokens): array
    {
        if (($tokens['poet']['label'] ?? '') === '' || ($tokens['poet']['url'] ?? '') === '') {
            return [];
        }

        $links = [
            ['href' => (string) $tokens['poet']['url'], 'label' => $isSd ? 'پروفائل' : 'Profile'],
        ];
        $topicUrl = (string) ($tokens['topic']['url'] ?? '');
        if ($topicUrl !== '') {
            $links[] = ['href' => $topicUrl, 'label' => (string) $tokens['topic']['label']];
        }

        $book = (string) ($tokens['book_label'] ?? '');
        $hasTopic = ($tokens['topic']['label'] ?? '') !== '';

        if ($isSd) {
            $rows = [];
            if ($hasTopic) {
                $rows[] = [
                    'q' => 'شاعر {poet_entity} جي سوانح عمري ۽ سندس {topic_node} واري شاعري.',
                    'a' => 'سوانح (جيڪا رڪارڊ ۾ آهي) ۽ {topic_node} سان ٽيگ ٿيل ڪلام شاعر جي باک پروفائل تي آهن.',
                    'links' => $links,
                ];
            }
            if ($book !== '') {
                $rows[] = [
                    'q' => '{poet_entity} ڪهڙي ڪتاب ۾ شامل آهي؟',
                    'a' => 'باک ۾ محفوظ مجموعو: ' . $book . '.',
                    'links' => $links,
                ];
            }

            return $rows;
        }

        $rows = [];
        if ($hasTopic) {
            $rows[] = [
                'q' => 'Find the literary profile of {poet_entity} highlighting {topic_node} themes.',
                'a' => 'Biography fields that exist in the archive, plus {topic_node}-tagged works, are on the poet profile. Baakh does not invent an era or ranking that is not stored.',
                'links' => $links,
            ];
        }
        if ($book !== '') {
            $rows[] = [
                'q' => 'Which book of {poet_entity} is indexed on Baakh?',
                'a' => 'The archive lists this collection: ' . $book . '.',
                'links' => $links,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $labels
     */
    private static function fill(string $template, array $labels): ?string
    {
        $out = strtr($template, $labels);
        if (preg_match('/\{[a-z_]+\}/', $out)) {
            return null;
        }

        return $out;
    }
}
