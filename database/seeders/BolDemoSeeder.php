<?php

namespace Database\Seeders;

use App\Models\Lyrics;
use App\Models\LyricsPart;
use App\Models\LyricsTranslation;
use App\Models\Singer;
use App\Models\SingerDetail;
use Illuminate\Database\Seeder;

/**
 * Sample visible singers + lyrics for the public Bol site.
 * Safe to re-run: upserts by slug.
 */
class BolDemoSeeder extends Seeder
{
    public function run(): void
    {
        $abida = $this->upsertSinger(
            'abida-parveen',
            true,
            [
                'sd' => [
                    'singer_name' => 'عابده پروين',
                    'tagline' => 'صوفيءَ جي آواز',
                    'singer_bio' => "عابده پروين سنڌ ۽ پاڪستان جي وڏي صوفي ڳائڻي آهي.\nهن جو آواز عشق ۽ معرفت جي ٻولين کي نئين جان ڏئي ٿو.",
                    'birth_place' => 'لارڪاڻو',
                ],
                'en' => [
                    'singer_name' => 'Abida Parveen',
                    'tagline' => 'Voice of Sufi song',
                    'singer_bio' => "Abida Parveen is one of Sindh and Pakistan's great Sufi singers.\nHer voice gives new life to words of love and devotion.",
                    'birth_place' => 'Larkana',
                ],
            ]
        );

        $allan = $this->upsertSinger(
            'allan-faqir',
            false,
            [
                'sd' => [
                    'singer_name' => 'علان فقير',
                    'tagline' => 'سنڌ جو لوڪ آواز',
                    'singer_bio' => 'علان فقير سنڌي لوڪ گيتن جو محبوب ڳائڻو هو.',
                    'birth_place' => 'ڄامشورو',
                ],
                'en' => [
                    'singer_name' => 'Allan Faqir',
                    'tagline' => 'Folk voice of Sindh',
                    'singer_bio' => 'Allan Faqir was a beloved singer of Sindhi folk songs.',
                    'birth_place' => 'Jamshoro',
                ],
            ]
        );

        $this->upsertSong(
            'tuhinje-nainan',
            $abida->id,
            true,
            null,
            null,
            null,
            [
                'sd' => [
                    'title' => 'تنهنجي نينن ۾',
                    'info' => 'هڪ نمايان ٻول — ڊيمو رڪارڊنگ، اصل متن جو نمونو.',
                    'source' => 'Bol demo',
                ],
                'en' => [
                    'title' => 'In Your Eyes',
                    'info' => 'A featured Bol demo recording with sample lyric lines.',
                    'source' => 'Bol demo',
                ],
            ],
            [
                [
                    'kind' => 'sung',
                    'role' => 'intro',
                    'text_sd' => "تنهنجي نينن ۾\nمون کي دنيا نظر آئي",
                    'text_roman' => "Tunhinje nainan mein\nMoon khe dunya nazar aayi",
                ],
                [
                    'kind' => 'couplet',
                    'role' => 'body',
                    'text_sd' => "عشق جو راز سڄو\nتنهنجي نگاهه ۾ لڪل آهي",
                    'text_roman' => "Ishq jo raaz sajjo\nTunhinje nigah mein lukal aahe",
                ],
                [
                    'kind' => 'explanation',
                    'role' => 'mid',
                    'text_sd' => 'هيءَ لائن محبوب جي نگاهه کي دنيا جي مرڪز وانگر بيان ڪري ٿي.',
                    'text_roman' => 'This line treats the beloved’s gaze as the center of the world.',
                ],
                [
                    'kind' => 'music',
                    'role' => 'outro',
                    'text_sd' => 'موسيقي شروع',
                    'text_roman' => 'Music begins',
                ],
                [
                    'kind' => 'sung',
                    'role' => 'outro',
                    'text_sd' => "مون کي دنيا نظر آئي\nتنهنجي نينن ۾",
                    'text_roman' => "Moon khe dunya nazar aayi\nTunhinje nainan mein",
                ],
            ]
        );

        $this->upsertSong(
            'sindhri-te',
            $allan->id,
            false,
            'link',
            null,
            null,
            [
                'sd' => [
                    'title' => 'سنڌڙيءَ تي',
                    'info' => 'لوڪ رنگ وارو نمونو ٻول.',
                    'source' => 'Bol demo',
                ],
                'en' => [
                    'title' => 'On My Sindhri',
                    'info' => 'A folk-flavoured sample lyric.',
                    'source' => 'Bol demo',
                ],
            ],
            [
                [
                    'kind' => 'sung',
                    'role' => 'body',
                    'text_sd' => "سنڌڙيءَ تي منهنجو پيار\nهر صبح نئين گل ٿو کُلي",
                    'text_roman' => "Sindhri te munhinjo pyar\nHar subuh naeen gul tho khule",
                ],
                [
                    'kind' => 'spoken',
                    'role' => 'mid',
                    'text_sd' => 'هي گيت زمين ۽ ماڻهن جي محبت بابت آهي.',
                    'text_roman' => 'This song is about love for the land and its people.',
                ],
            ]
        );

        $this->upsertSong(
            'ishq-jo-rang',
            $abida->id,
            false,
            'audio',
            null,
            null,
            [
                'sd' => [
                    'title' => 'عشق جو رنگ',
                    'info' => 'ٻيو نمونو گيت — منتظر آڊيو لنڪ.',
                    'source' => 'Bol demo',
                ],
                'en' => [
                    'title' => 'Colour of Love',
                    'info' => 'Another sample song — music link pending.',
                    'source' => 'Bol demo',
                ],
            ],
            [
                [
                    'kind' => 'sung',
                    'role' => 'intro',
                    'text_sd' => "عشق جو رنگ مٿي چڙهيو\nدل ۾ ميٺو سوز جاڳيو",
                    'text_roman' => "Ishq jo rang mathe charhiyo\nDil mein mitho soz jagyo",
                ],
                [
                    'kind' => 'couplet',
                    'role' => 'body',
                    'text_sd' => "هر سانس ۾ تنهنجو نالو\nهر خواب ۾ تنهنجو روپ",
                    'text_roman' => "Har saans mein tunhinjo naalo\nHar khwaab mein tunhinjo roop",
                ],
            ]
        );
    }

    private function upsertSinger(string $slug, bool $featured, array $detailsByLang): Singer
    {
        $singer = Singer::withTrashed()->updateOrCreate(
            ['singer_slug' => $slug],
            [
                'visibility' => true,
                'is_featured' => $featured,
                'deleted_at' => null,
            ]
        );

        foreach ($detailsByLang as $lang => $detail) {
            SingerDetail::updateOrCreate(
                ['singer_id' => $singer->id, 'lang' => $lang],
                $detail
            );
        }

        return $singer;
    }

    private function upsertSong(
        string $slug,
        int $singerId,
        bool $featured,
        ?string $musicType,
        ?string $musicUrl,
        ?string $musicTitle,
        array $translations,
        array $parts
    ): void {
        $lyrics = Lyrics::withTrashed()->updateOrCreate(
            ['lyrics_slug' => $slug],
            [
                'singer_id' => $singerId,
                'visibility' => true,
                'is_featured' => $featured,
                'content_style' => 'center',
                'music_type' => $musicType,
                'music_url' => $musicUrl,
                'music_title' => $musicTitle,
                'deleted_at' => null,
            ]
        );

        foreach ($translations as $lang => $row) {
            LyricsTranslation::updateOrCreate(
                ['lyrics_id' => $lyrics->id, 'lang' => $lang],
                $row
            );
        }

        $lyrics->parts()->forceDelete();
        foreach ($parts as $i => $part) {
            LyricsPart::create([
                'lyrics_id' => $lyrics->id,
                'sort_order' => $i + 1,
                'kind' => $part['kind'],
                'role' => $part['role'] ?? null,
                'relation' => 'original',
                'text_sd' => $part['text_sd'] ?? null,
                'text_roman' => $part['text_roman'] ?? null,
            ]);
        }
    }
}
