<?php

namespace Database\Seeders;

use App\Models\MobileNotification;
use Illuminate\Database\Seeder;

class MobileNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            [
                'type' => 'daily_verse',
                'title_sd' => 'آڄ جو بيت',
                'title_en' => "Today's couplet",
                'body_sd' => 'هڪ ننڍو بيت، هڪ وڏو خيال. اڄ جو چونڊيل بيت پڙهو.',
                'body_en' => 'A short couplet, a long thought. Read today’s selected verse.',
                'cta_sd' => 'هاڻي پڙهو',
                'cta_en' => 'Read now',
                'deep_link' => 'baakh://couplets',
                'web_path' => '/sd/couplets',
                'audience' => 'everyone',
                'recurrence' => 'daily',
                'recurrence_time' => '08:00',
                'status' => 'published',
            ],
            [
                'type' => 'new_poetry',
                'title_sd' => 'نئين شاعري آئي آهي',
                'title_en' => 'New poetry just arrived',
                'body_sd' => 'آرکائيو ۾ تازي شاعري شامل ٿي آهي. پهريون پڙهندڙ بڻجو.',
                'body_en' => 'Fresh poetry was added to the archive. Be among the first to read it.',
                'cta_sd' => 'ڏسو',
                'cta_en' => 'Open',
                'deep_link' => 'baakh://poetry',
                'web_path' => '/sd/poetry',
                'audience' => 'everyone',
                'status' => 'published',
            ],
            [
                'type' => 'new_poet',
                'title_sd' => 'نئون شاعر شامل ٿيو',
                'title_en' => 'A new poet was added',
                'body_sd' => 'باک ۾ هڪ نئون شاعر متعارف ٿيو آهي. سوانح ۽ شاعري ڏسو.',
                'body_en' => 'Baakh now includes a newly listed poet. Open their profile and work.',
                'cta_sd' => 'شاعر ڏسو',
                'cta_en' => 'View poet',
                'deep_link' => 'baakh://poets',
                'web_path' => '/sd/poets',
                'audience' => 'everyone',
                'status' => 'published',
            ],
            [
                'type' => 'featured',
                'title_sd' => 'اڄ جي چونڊ',
                'title_en' => "Today's featured pick",
                'body_sd' => 'ايڊيٽرن جي چونڊيل شاعري اڄ لاءِ تيار آهي.',
                'body_en' => 'A staff pick is ready for you today.',
                'cta_sd' => 'چونڊ پڙهو',
                'cta_en' => 'Read the pick',
                'deep_link' => 'baakh://featured',
                'web_path' => '/sd/',
                'audience' => 'everyone',
                'status' => 'published',
            ],
            [
                'type' => 'word_of_the_day',
                'title_sd' => 'آڄ جو لفظ',
                'title_en' => 'Word of the day',
                'body_sd' => 'لغت مان هڪ نئون لفظ، معنيٰ ۽ مثال سان.',
                'body_en' => 'A dictionary word, with meaning and an example from poetry.',
                'cta_sd' => 'لغت کوليو',
                'cta_en' => 'Open dictionary',
                'deep_link' => 'baakh://dictionary',
                'web_path' => '/sd/explore',
                'audience' => 'everyone',
                'recurrence' => 'daily',
                'recurrence_time' => '09:30',
                'status' => 'published',
            ],
            [
                'type' => 'new_lyrics',
                'title_sd' => 'نوان ٻول',
                'title_en' => 'New lyrics',
                'body_sd' => 'تازا ٻول ۽ راڳ شامل ٿيا آهن. ٻڌو ۽ پڙهو.',
                'body_en' => 'New lyrics and songs are in. Listen and read along.',
                'cta_sd' => 'ٻول ڏسو',
                'cta_en' => 'Open lyrics',
                'deep_link' => 'baakh://lyrics',
                'web_path' => '/sd/lyrics',
                'audience' => 'everyone',
                'status' => 'published',
            ],
            [
                'type' => 'reminder',
                'title_sd' => 'پڙهڻ جو وقت',
                'title_en' => 'Time to read',
                'body_sd' => 'پنج منٽ، هڪ بيت. اڄ به هڪ سٽ پاڻ سان کڻي وڃو.',
                'body_en' => 'Five minutes, one couplet. Take a line with you today.',
                'cta_sd' => 'جاري رکو',
                'cta_en' => 'Continue',
                'deep_link' => 'baakh://home',
                'web_path' => '/sd/',
                'audience' => 'signed_in',
                'recurrence' => 'daily',
                'recurrence_time' => '20:00',
                'status' => 'published',
            ],
            [
                'type' => 'app_update',
                'title_sd' => 'ايپ جو نئون ورزن',
                'title_en' => 'A new app version',
                'body_sd' => 'بهتر پڙهڻ، تيز ڳولا، ۽ نوان اطلاع. اپڊيٽ ڪريو.',
                'body_en' => 'Better reading, faster search, and new alerts. Update when you can.',
                'cta_sd' => 'اپڊيٽ',
                'cta_en' => 'Update',
                'deep_link' => 'baakh://settings',
                'web_path' => '/sd/',
                'audience' => 'everyone',
                'priority' => 'high',
                'status' => 'draft',
            ],
            [
                'type' => 'event',
                'title_sd' => 'ادبي ميڙ',
                'title_en' => 'A literary gathering',
                'body_sd' => 'هفتے ۾ هڪ مشاعرو يا پڙهڻ جي دعوت. تفصيل ايپ ۾.',
                'body_en' => 'A mushaira or reading this week. Details are in the app.',
                'cta_sd' => 'تفصيل',
                'cta_en' => 'Details',
                'deep_link' => 'baakh://events',
                'web_path' => '/sd/',
                'audience' => 'everyone',
                'status' => 'draft',
            ],
            [
                'type' => 'announcement',
                'title_sd' => 'باک کان اطلاع',
                'title_en' => 'A note from Baakh',
                'body_sd' => 'آرکائيو بابت هڪ ننڍو اطلاع. جڏهن توهان وٽ وقت هجي، پڙهي وٺجو.',
                'body_en' => 'A short note from the archive. Read it when you have a moment.',
                'cta_sd' => 'پڙهو',
                'cta_en' => 'Read',
                'deep_link' => 'baakh://announcements',
                'web_path' => '/sd/',
                'audience' => 'everyone',
                'status' => 'draft',
            ],
        ];

        foreach ($samples as $sample) {
            $type = $sample['type'];
            $meta = MobileNotification::TYPE_META[$type] ?? [];

            MobileNotification::updateOrCreate(
                [
                    'type' => $type,
                    'title_en' => $sample['title_en'],
                ],
                array_merge([
                    'platforms' => ['android', 'ios'],
                    'priority' => 'normal',
                    'is_active' => true,
                    'icon' => $meta['icon'] ?? 'Bell',
                    'color' => $meta['color'] ?? 'blue',
                    'recurrence' => 'once',
                ], $sample)
            );
        }
    }
}
