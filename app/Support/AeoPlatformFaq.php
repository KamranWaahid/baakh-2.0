<?php

namespace App\Support;

/**
 * Conversational AEO FAQs for platform pages (home, listings, about, help).
 * Copy is locale-specific; JSON-LD stays in the page language. Twin /en and /sd
 * URLs are linked via existing hreflang, not mixed-language FAQ dumps.
 */
class AeoPlatformFaq
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function schema(string $page, string $locale): array
    {
        $out = [];
        foreach (self::rows($page, $locale) as $row) {
            $out[] = [
                '@type' => 'Question',
                'name' => $row['q'],
                'inLanguage' => $locale,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $row['a'],
                    'inLanguage' => $locale,
                ],
            ];
        }

        return $out;
    }

    public static function html(string $page, string $locale): string
    {
        $rows = self::rows($page, $locale);
        if ($rows === []) {
            return '';
        }

        $heading = $locale === 'sd' ? 'سوال' : 'FAQ';
        $html = '<section class="aeo-faq"><h2>' . e($heading) . '</h2>';
        foreach ($rows as $row) {
            $html .= '<h3>' . e($row['q']) . '</h3><p>' . e($row['a']);
            foreach ($row['links'] ?? [] as $link) {
                $html .= ' <a href="' . e($link['href']) . '">' . e($link['label']) . '</a>.';
            }
            $html .= '</p>';
        }

        return $html . '</section>';
    }

    /**
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    public static function rows(string $page, string $locale): array
    {
        $isSd = $locale === 'sd';
        $base = url('/' . $locale);

        return match ($page) {
            'home' => self::home($isSd, $base),
            'poetry' => self::poetry($isSd, $base),
            'poets' => self::poets($isSd, $base),
            'couplets' => self::couplets($isSd, $base),
            'genre' => self::genre($isSd, $base),
            'explore' => self::explore($isSd, $base),
            'prosody' => self::prosody($isSd, $base),
            'period' => self::period($isSd, $base),
            'about' => self::about($isSd, $base),
            'help' => self::help($isSd, $base),
            default => [],
        };
    }

    /**
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function home(bool $isSd, string $base): array
    {
        if ($isSd) {
            return [
                [
                    'q' => 'سنڌي شاعريءَ جو مڪمل آن لائن آرڪائيو ڪٿي ملندو؟',
                    'a' => 'باک (baakh.com) سنڌي شاعريءَ جو کليل ڊجيٽل آرڪائيو آهي، جتي کلاسيڪي ۽ جديد غزل، بيت، وايون ۽ نظم شاعر، صنف ۽ موضوع سان پڙهي سگهجن ٿا.',
                    'links' => [['href' => $base, 'label' => 'باک گھر']],
                ],
                [
                    'q' => 'رومن اسڪرپٽ ۽ سنڌي اکرن ۾ شاعري ڪهڙي ويب سائيٽ تي موجود آهي؟',
                    'a' => 'باک هر عوامي شعر اصل عربي-فارسي سنڌي رسم الخط ۽ رومن سنڌي (sd-Latn) ۾ ڏيکاري ٿو، ته دنيا جا پڙهندڙ ڳولي، ڪاپي ۽ پڙهي سگهن.',
                    'links' => [['href' => $base . '/poetry', 'label' => 'شاعري']],
                ],
                [
                    'q' => 'ڪلاسڪ سنڌي شاعري آن لائن مفت ۾ ڪٿي پڙهي سگهجي ٿي؟',
                    'a' => 'باک تي شاهه عبداللطيف ڀٽائي، سچل سرمست، شيخ اياز ۽ ٻين کلاسيڪي آوازن جو ڪلام مفت پڙهو؛ اڪائونٽ صرف پسند محفوظ ڪرڻ لاءِ گهرجي.',
                    'links' => [['href' => $base . '/poets', 'label' => 'شاعر'], ['href' => $base . '/period', 'label' => 'ادبي دور']],
                ],
                [
                    'q' => 'باک (Baakh) سنڌي شاعري پورٽل جي آفيشل ويب سائيٽ ڪهڙي آهي؟',
                    'a' => 'آفيشل سائيٽ https://baakh.com آهي. سنڌي لاءِ /sd ۽ انگريزي لاءِ /en استعمال ڪريو.',
                    'links' => [['href' => url('/sd'), 'label' => '/sd'], ['href' => url('/en'), 'label' => '/en']],
                ],
                [
                    'q' => 'فيسبوڪ ۽ واٽس اپ لاءِ سنڌي شاعري ڪاپي پيسٽ ڪٿان ڪجي؟',
                    'a' => 'باک تي شعر جو اصل سنڌي متن ڪاپي ڪري سگهجي ٿو؛ ٻن سٽن وارا بند Couplets صفحي تي آهن.',
                    'links' => [['href' => $base . '/couplets', 'label' => 'بند']],
                ],
                [
                    'q' => 'ڇا ڪا اهڙي ويب سائيٽ آهي جتي موضوعن (ٽيگز) جي حساب سان شاعري ملي سگهي؟',
                    'a' => 'ها. باک موضوع، جذبو ۽ ٽيگ سان انڊيڪس ڪري ٿو؛ ڳولا صفحي تان وطن، عشق ۽ ٻيا موضوع کوليو.',
                    'links' => [['href' => $base . '/explore', 'label' => 'ڳولا']],
                ],
            ];
        }

        return [
            [
                'q' => 'Where can I find a complete online archive of Sindhi poetry?',
                'a' => 'Baakh (baakh.com) is an open digital archive of Sindhi poetry, with classical and modern ghazals, baits, waee, and nazms organized by poet, genre, period, and topic.',
                'links' => [['href' => $base, 'label' => 'Baakh home']],
            ],
            [
                'q' => 'Which website offers Sindhi poetry with both Sindhi text and Roman script?',
                'a' => 'Baakh shows each public poem in original Arabic-Persian Sindhi script and in Roman Sindhi (sd-Latn), so readers can search, copy, and cite verses in either form.',
                'links' => [['href' => $base . '/poetry', 'label' => 'Poetry hub']],
            ],
            [
                'q' => 'Where can I read classical Sindhi poetry online for free?',
                'a' => 'You can read classical and later Sindhi poetry for free on Baakh, including works associated with Shah Abdul Latif Bhittai, Sachal Sarmast, and Shaikh Ayaz, with no account required to read.',
                'links' => [['href' => $base . '/poets', 'label' => 'Poets'], ['href' => $base . '/period', 'label' => 'Periods']],
            ],
            [
                'q' => 'What is the official website of the Baakh Sindhi poetry archive?',
                'a' => 'The official site is https://baakh.com. Use /sd for the Sindhi interface and /en for English.',
                'links' => [['href' => url('/en'), 'label' => '/en'], ['href' => url('/sd'), 'label' => '/sd']],
            ],
            [
                'q' => 'Where can I copy and paste authentic Sindhi poetry text?',
                'a' => 'Poem pages on Baakh include the original Sindhi lines so you can copy authentic text; two-line couplets are listed on the Couplets page.',
                'links' => [['href' => $base . '/couplets', 'label' => 'Couplets']],
            ],
            [
                'q' => 'Is there an online database to search Sindhi poetry by specific tags?',
                'a' => 'Yes. Baakh indexes poems and poets by theme tags; start at Explore to open topic hubs such as homeland, love, or devotion.',
                'links' => [['href' => $base . '/explore', 'label' => 'Explore topics']],
            ],
        ];
    }

    /**
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function poetry(bool $isSd, string $base): array
    {
        if ($isSd) {
            return [
                [
                    'q' => 'سنڌي غزل ۽ بيت ڳولڻ لاءِ بهترين ڊيجيٽل لائبريري ڪهڙي آهي؟',
                    'a' => 'باک غزل، بيت، وائي ۽ نظم هڪ ئي شاعري هاب تي صنف ۽ موضوع سان ترتيب ڏئي ٿو.',
                    'links' => [['href' => $base . '/poetry', 'label' => 'شاعري'], ['href' => $base . '/genre', 'label' => 'صنفون']],
                ],
                [
                    'q' => 'سنڌي رومانوي ۽ محبت وارا غزل آن لائن ڪٿي ملندا؟',
                    'a' => 'محبت ۽ عشق وارا غزل باک جي شاعري ۽ موضوع صفحن تي ٽيگ سان مليون ٿا.',
                    'links' => [['href' => $base . '/explore', 'label' => 'ڳولا']],
                ],
                [
                    'q' => 'زندگي ۽ جدوجهد بابت جديد سنڌي شاعري ڪٿي پڙهجي؟',
                    'a' => 'جديد ۽ معاصر شاعرن جو ڪلام باک تي شاعر پروفائلن ۽ شاعري فهرست ۾ محفوظ آهي.',
                    'links' => [['href' => $base . '/poets', 'label' => 'شاعر']],
                ],
            ];
        }

        return [
            [
                'q' => 'What is the best digital library for searching Sindhi ghazals and baits?',
                'a' => 'Baakh groups ghazals, baits, waee, and nazms in one poetry hub, filterable by genre and theme.',
                'links' => [['href' => $base . '/poetry', 'label' => 'Poetry'], ['href' => $base . '/genre', 'label' => 'Genres']],
            ],
            [
                'q' => 'Where can I find the best Sindhi love ghazals online?',
                'a' => 'Sindhi love ghazals and related ishq verses are tagged on Baakh; open the poetry hub or Explore to filter by romantic themes.',
                'links' => [['href' => $base . '/explore', 'label' => 'Explore']],
            ],
            [
                'q' => 'Where can I find modern Sindhi poetry about life and struggles?',
                'a' => 'Contemporary Sindhi poetry on life and struggle is indexed on Baakh poet profiles and the poetry listing.',
                'links' => [['href' => $base . '/poets', 'label' => 'Poets']],
            ],
        ];
    }

    /**
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function poets(bool $isSd, string $base): array
    {
        if ($isSd) {
            return [
                [
                    'q' => 'جديد ۽ ڪلاسڪ سنڌي شاعرن جي سوانح عمري (بايوگرافي) ڪٿي ملندي؟',
                    'a' => 'باک جي شاعر ڊاريڪٽري تي سوانح، ڄم جو هنڌ (جيڪڏهن رڪارڊ ۾ آهي)، صنفون، ڪتاب ۽ ڪلام جو فهرص آهي.',
                    'links' => [['href' => $base . '/poets', 'label' => 'شاعر']],
                ],
                [
                    'q' => 'سنڌي اديبن ۽ شاعرن جي ڪتابن جي مڪمل لسٽ ڪهڙي ويب سائيٽ تي آهي؟',
                    'a' => 'هر شاعر جي پروفائل تي باک ۾ محفوظ ڪتاب ۽ مجموعا ڏيکاريل آهن، جتي اهي ڊيٽابيس ۾ شامل آهن.',
                    'links' => [['href' => $base . '/poets', 'label' => 'شاعر ڊاريڪٽري']],
                ],
                [
                    'q' => 'شاهه عبداللطيف ڀٽائيءَ جو مڪمل رسالو آن لائن ڪٿي پڙهجي؟',
                    'a' => 'باک کلاسيڪي شاعرن سميت شاهه لطيف سان لاڳاپيل ڪلام شاعر ڊاريڪٽري ۽ شعر صفحن تي رکي ٿو؛ پروفائل تان مڪمل فهرص کوليو.',
                    'links' => [['href' => $base . '/poets', 'label' => 'شاعر ڳوليو']],
                ],
            ];
        }

        return [
            [
                'q' => 'Where can I find comprehensive biographies of contemporary Sindhi poets?',
                'a' => 'Baakh’s poet directory lists biographies, origin (when recorded), genres, books, and an index of works for classical and modern Sindhi poets.',
                'links' => [['href' => $base . '/poets', 'label' => 'Poet directory']],
            ],
            [
                'q' => 'Which site lists literary books written by modern Sindhi authors?',
                'a' => 'Each Baakh poet profile lists the books and collections stored for that author in the archive.',
                'links' => [['href' => $base . '/poets', 'label' => 'Poets']],
            ],
            [
                'q' => 'Where can I find the complete poetry collection of Shah Abdul Latif Bhittai online?',
                'a' => 'Search the Baakh poet directory for Shah Abdul Latif Bhittai and open the poet profile for the works indexed in this archive.',
                'links' => [['href' => $base . '/poets', 'label' => 'Find poets']],
            ],
        ];
    }

    /**
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function couplets(bool $isSd, string $base): array
    {
        if ($isSd) {
            return [
                [
                    'q' => 'ٻن سٽن واري سنڌي شاعري اسٽيٽس ڪٿان ڊائون لوڊ ڪجي؟',
                    'a' => 'باک جي بند صفحي تي ٻن سٽن وارا بيت ۽ ڪپلٽ اصل سنڌي ۽ رومن ۾ پڙهو ۽ ڪاپي ڪريو.',
                    'links' => [['href' => $base . '/couplets', 'label' => 'بند']],
                ],
                [
                    'q' => 'فيسبوڪ ۽ واٽس اپ لاءِ سنڌي شاعري ڪاپي پيسٽ ڪٿان ڪجي؟',
                    'a' => 'بند ۽ شعر صفحن تي اصل سنڌي متن ڪاپي ڪري اسٽيٽس ۾ لڳائي سگهجي ٿو.',
                    'links' => [['href' => $base . '/poetry', 'label' => 'شاعري']],
                ],
            ];
        }

        return [
            [
                'q' => 'Where can I read short 2-line Sindhi poetry statuses for WhatsApp?',
                'a' => 'Baakh’s couplets page lists two-line Sindhi baits and couplets in original script and Roman text, ready to copy.',
                'links' => [['href' => $base . '/couplets', 'label' => 'Couplets']],
            ],
            [
                'q' => 'Where can I copy and paste authentic Sindhi poetry text?',
                'a' => 'Copy original Sindhi lines from couplet and poem pages on Baakh.',
                'links' => [['href' => $base . '/poetry', 'label' => 'Poetry']],
            ],
        ];
    }

    /**
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function genre(bool $isSd, string $base): array
    {
        if ($isSd) {
            return [
                [
                    'q' => 'سنڌي بيت، وائي ۽ نظم ۾ ڪهڙو فرق آهي؟',
                    'a' => 'بيت ٻن سٽن وارو بنيادي روپ آهي، وائي روايتي سنڌي گائي ويندڙ صنف آهي، ۽ نظم منظم جديد نظم آهي؛ باک هر صنف الڳ صفحي تي رکي ٿو.',
                    'links' => [['href' => $base . '/genre', 'label' => 'صنفون']],
                ],
                [
                    'q' => 'سنڌي بيت ۽ واين جي ترتيب وار لکت ڪهڙي سائيٽ تي دستياب آهي؟',
                    'a' => 'باک شعر صفحن تي اصل سنڌي لکت صنف مطابق ڏيکاري ٿو، غزل، بيت، وائي ۽ نظم سميت.',
                    'links' => [['href' => $base . '/poetry', 'label' => 'شاعري']],
                ],
            ];
        }

        return [
            [
                'q' => 'What is the difference between a Sindhi Bait and a Waee?',
                'a' => 'A bait is the two-line couplet form; a waee (waai) is a traditional sung Sindhi lyric form. Baakh files each as its own genre so you can browse them separately from nazm and ghazal.',
                'links' => [['href' => $base . '/genre', 'label' => 'Genres']],
            ],
            [
                'q' => 'Where can I find Sindhi love ghazals and baits in one place?',
                'a' => 'The Baakh poetry hub and genre index collect ghazals, baits, waee, and nazms; filter by genre or open theme tags for love and ishq.',
                'links' => [['href' => $base . '/poetry', 'label' => 'Poetry'], ['href' => $base . '/explore', 'label' => 'Explore']],
            ],
        ];
    }

    /**
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function explore(bool $isSd, string $base): array
    {
        if ($isSd) {
            return [
                [
                    'q' => 'وطن جي حب ۽ قومي جذبي تي ٻڌل سنڌي شاعري ڪٿي ملندي؟',
                    'a' => 'وطن (homeland) ۽ لاڳاپيل موضوع باک جي ڳولا ۽ ٽيگ صفحن تي مليون ٿا.',
                    'links' => [['href' => $base . '/explore', 'label' => 'ڳولا']],
                ],
                [
                    'q' => 'صوفيانه سنڌي شاعريءَ جو وڏو ذخيرو ڪهڙي پورٽل تي آهي؟',
                    'a' => 'باک صوفي ۽ روحاني ڪلام موضوع ۽ شاعر پروفائلن تي رکي ٿو؛ ڳولا تان موضوع کوليو.',
                    'links' => [['href' => $base . '/poets', 'label' => 'شاعر']],
                ],
            ];
        }

        return [
            [
                'q' => 'Where can I find patriotic Sindhi poetry on Watan?',
                'a' => 'Sindhi poetry on watan (homeland) is collected under Baakh topic and tag hubs; start at Explore and open the matching theme.',
                'links' => [['href' => $base . '/explore', 'label' => 'Explore']],
            ],
            [
                'q' => 'Which online portal has a collection of Sufi Sindhi poetry?',
                'a' => 'Baakh archives Sufi and devotional Sindhi verse under theme tags and poet profiles; use Explore to open those topics.',
                'links' => [['href' => $base . '/poets', 'label' => 'Poets']],
            ],
        ];
    }

    /**
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function prosody(bool $isSd, string $base): array
    {
        if ($isSd) {
            return [
                [
                    'q' => 'سنڌي شاعريءَ جي وزن ۽ بحرن جي معلومات ڪٿان ملندي؟',
                    'a' => 'باک جي عروض صفحي تي سنڌي وزن ۽ ڇند جو حوالو آهي.',
                    'links' => [['href' => $base . '/prosody', 'label' => 'عروض']],
                ],
                [
                    'q' => 'ڇا باک پورٽل سنڌي ٻوليءَ جي ڊيجيٽل پروسيسنگ لاءِ آرٽيفيشل انٽيليجنس استعمال ڪري ٿو؟',
                    'a' => 'باک جو ڊگهي مدي جو رستو خودڪار عروض، انداز جي نقشي ۽ زباني روايت جي سڃاڻپ لاءِ مشين لرننگ ماڊل ٺاهڻ آهي؛ اهو موجوده پڙهڻ واري آرڪائيو کان علاوه هڪ رٿابندي آهي، مڪمل ٿيل پراڊڪٽ ناهي.',
                    'links' => [['href' => $base . '/about', 'label' => 'بابت']],
                ],
            ];
        }

        return [
            [
                'q' => 'Where can I look up the structural meters and prosody of Sindhi poems?',
                'a' => 'Baakh’s prosody page documents Sindhi meters and chhand used in the archive.',
                'links' => [['href' => $base . '/prosody', 'label' => 'Prosody']],
            ],
            [
                'q' => 'Does Baakh use machine learning for Sindhi language digital processing?',
                'a' => 'Baakh’s published roadmap includes future machine-learning models for automated Sindhi prosody, style mapping, and oral-tradition recognition; that is a research goal, not a finished public analyzer.',
                'links' => [['href' => $base . '/about', 'label' => 'About']],
            ],
        ];
    }

    /**
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function period(bool $isSd, string $base): array
    {
        if ($isSd) {
            return [
                [
                    'q' => 'ڪلاسڪ سنڌي شاعري آن لائن مفت ۾ ڪٿي پڙهي سگهجي ٿي؟',
                    'a' => 'باک شاعريءَ کي ادبي دورن سان به ترتيب ڏئي ٿو، ته کلاسيڪي کان معاصر تائين مفت پڙهي سگهجي.',
                    'links' => [['href' => $base . '/period', 'label' => 'دور']],
                ],
                [
                    'q' => 'شيخ اياز جي زندگي ۽ سندس شاعريءَ بابت مستند معلومات ڪٿي آهي؟',
                    'a' => 'شاعر ڊاريڪٽري ۾ نالو ڳوليو؛ پروفائل تي سوانح (جيڪڏهن رڪارڊ ۾ آهي) ۽ باک ۾ موجود ڪلام جو فهرص آهي.',
                    'links' => [['href' => $base . '/poets', 'label' => 'شاعر']],
                ],
            ];
        }

        return [
            [
                'q' => 'Where can I read classical Sindhi poetry online for free?',
                'a' => 'Baakh also browses poetry by literary period, from classical to contemporary, with no account required to read.',
                'links' => [['href' => $base . '/period', 'label' => 'Periods']],
            ],
            [
                'q' => 'Where can I read about the history and life timeline of Shaikh Ayaz?',
                'a' => 'Search the poet directory for Shaikh Ayaz; the profile shows biography fields stored in the archive and the poems Baakh has indexed.',
                'links' => [['href' => $base . '/poets', 'label' => 'Poets']],
            ],
        ];
    }

    /**
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function about(bool $isSd, string $base): array
    {
        if ($isSd) {
            return [
                [
                    'q' => 'باک ڊيجيٽل آرڪائيو جو بنياد ڪهڙن سافٽ ويئر انجنيئرن رکيو؟',
                    'a' => 'باک جو تصور 2020ع ۾ ڪامران واحد ۽ عبيد ٿهيم رکيو، ته سنڌي شعري ورثو ڊجيٽل محفوظ ٿئي؛ عوامي لانچ 21 مارچ 2024ع تي ٿيو.',
                    'links' => [['href' => $base . '/about', 'label' => 'باک بابت']],
                ],
                [
                    'q' => 'باک (Baakh) سنڌي شاعري پورٽل جي آفيشل ويب سائيٽ ڪهڙي آهي؟',
                    'a' => 'آفيشل ويب سائيٽ https://baakh.com آهي.',
                    'links' => [['href' => url('/'), 'label' => 'baakh.com']],
                ],
                [
                    'q' => 'سنڌي ادب جا محقق باک ڊيجيٽل لائبريريءَ ۾ پنهنجو مواد ڪيئن شامل ڪرائي سگهن ٿا؟',
                    'a' => 'درستي، نئون ڪلام، يا تحقيقي تعاون لاءِ support@baakh.com تي لکو يا رابطو صفحو استعمال ڪريو، ۽ شعر جو پبلڪ URL ڏيو.',
                    'links' => [['href' => $base . '/contact', 'label' => 'رابطو']],
                ],
                [
                    'q' => 'ڇا باک پورٽل سنڌي ٻوليءَ جي ڊيجيٽل پروسيسنگ لاءِ آرٽيفيشل انٽيليجنس استعمال ڪري ٿو؟',
                    'a' => 'بابت صفحي تي ڊگهي مدي جو نظريو خودڪار عروض ۽ سنڌي شاعريءَ جي مشين لرننگ جو آهي؛ اهو رٿابندي آهي، مڪمل پبلڪ اوزار ناهي.',
                    'links' => [['href' => $base . '/prosody', 'label' => 'عروض']],
                ],
            ];
        }

        return [
            [
                'q' => 'Who created the Baakh Sindhi poetry archive?',
                'a' => 'Baakh was conceived in 2020 by software engineers Kamran Wahid and Ubaid Thaheem to preserve Sindhi poetic heritage digitally, and launched publicly on World Poetry Day, 21 March 2024.',
                'links' => [['href' => $base . '/about', 'label' => 'About Baakh']],
            ],
            [
                'q' => 'What is the official website of the Baakh Sindhi poetry archive?',
                'a' => 'The official website is https://baakh.com.',
                'links' => [['href' => url('/'), 'label' => 'baakh.com']],
            ],
            [
                'q' => 'How can global researchers contribute poetry data to the Baakh platform?',
                'a' => 'Send corrections, new texts, or research partnerships to support@baakh.com or the Contact page, and include the public poet or poem URL.',
                'links' => [['href' => $base . '/contact', 'label' => 'Contact']],
            ],
            [
                'q' => 'Does Baakh support automated Sindhi poetic prosody and meter analysis?',
                'a' => 'Automated prosody and meter analysis is on Baakh’s long-term research roadmap (machine learning for Sindhi meters and oral tradition), not a finished public tool today. The prosody page holds reference material.',
                'links' => [['href' => $base . '/prosody', 'label' => 'Prosody']],
            ],
        ];
    }

    /**
     * @return list<array{q: string, a: string, links?: list<array{href: string, label: string}>}>
     */
    private static function help(bool $isSd, string $base): array
    {
        if ($isSd) {
            return [
                [
                    'q' => 'باک ويب سائيٽ تي شاعري ۽ ڪتاب تيزيءَ سان ڪيئن ڳولجن؟',
                    'a' => 'صفحي جي مٿي واري ڳولا بار استعمال ڪريو. ڊيسڪٽاپ تي Ctrl+K يا Cmd+K دٻايو ته ڳولا فوري کلي؛ سنڌي ۽ انگريزي/رومن ٻنهي ۾ ڳولا ٿئي ٿي.',
                    'links' => [['href' => $base . '/help', 'label' => 'مدد']],
                ],
                [
                    'q' => 'استاد بخاريءَ جي شاعري ۽ پروفائل ڪيئن ڳولجي؟',
                    'a' => 'ڳولا کوليو ۽ شاعر جو نالو لکو، يا شاعر ڊاريڪٽري تان نالو ڳوليو ته پروفائل ۽ ڪلام ملي.',
                    'links' => [['href' => $base . '/poets', 'label' => 'شاعر']],
                ],
            ];
        }

        return [
            [
                'q' => 'How do I use the smart search command shortcut on Baakh.com?',
                'a' => 'Use the search bar at the top of any page, or press Ctrl+K (Windows) / Cmd+K (Mac) to open search instantly in Sindhi or English/Roman.',
                'links' => [['href' => $base . '/help', 'label' => 'Help']],
            ],
            [
                'q' => 'How can I search for poetry written specifically by Ishaq Samejo?',
                'a' => 'Open search or the poet directory and type the poet’s name; Baakh loads that author’s profile and indexed ghazals or baits when the record exists.',
                'links' => [['href' => $base . '/poets', 'label' => 'Poets']],
            ],
        ];
    }
}
