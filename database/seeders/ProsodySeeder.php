<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProsodyTerm;

class ProsodySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terms = [
            [
                'title_sd' => 'ڇند وديا',
                'title_en' => 'Chhand Widya',
                'desc_sd' => 'ڪلاسيڪل سنڌي شاعريءَ جو مقامي نظام، جيڪو آوازن جي اچار لاءِ گهربل وقت جي يونٽ تي ٻڌل آهي.',
                'desc_en' => 'The indigenous quantitative system used for classical Sindhi poetry, based on the time-unit required to pronounce sounds.',
                'tech_detail_sd' => 'ماترائن (وقت جي يونٽن) ۾ ماپيو ويندو آهي. هي ٻاهرين قانونن جي بدران سنڌي لساني روايتن جي پيروي ڪري ٿو.',
                'tech_detail_en' => 'Measured in Matras (time-units). It follows the Sindhui linguistic tradition rather than foreign rules.',
                'logic_type' => 'chhand',
                'icon' => 'Ruler',
                'order' => 1,
            ],
            [
                'title_sd' => 'علم عروض',
                'title_en' => 'Ilm Arooz',
                'desc_sd' => 'شاعريءَ جي وزن ۽ بحرن جو علم، جيڪو خاص ڪري جديد صنفن جهڙوڪ غزل ۽ نظم لاءِ استعمال ٿيندو آهي.',
                'desc_en' => 'The science of poetry\'s weight and meters, primarily used for modern forms like Ghazals and Nazms.',
                'tech_detail_sd' => 'هي هڪ تارازي وانگر ڪم ڪري ٿو جتي مصرع کي فرضي لفظن (ارڪان) سان توريو ويندو آهي.',
                'tech_detail_en' => 'Functions as a Beam Balance (Salami) where the verse is weighed against dummy words called Arkan.',
                'logic_type' => 'arooz',
                'icon' => 'Scale',
                'order' => 2,
            ],
            [
                'title_sd' => 'تقطيع',
                'title_en' => 'Taqti (Scansion)',
                'desc_sd' => 'شعر جي وزن کي پرکڻ لاءِ ان کي ان جي تال وارن حصن (ارڪان) ۾ تقسيم ڪرڻ جو عمل.',
                'desc_en' => 'The process of fragmenting a verse into its rhythmic components to verify its meter.',
                'tech_detail_sd' => 'هي لکيل اکرن جي بجاءِ اچاريل آوازن (لفظي) تي ٻڌل هوندو آهي. خاموش اکرن کي خارج ڪيو ويندو آهي.',
                'tech_detail_en' => 'Based on pronounced sounds (Lafzi) rather than written letters (Maktubi). Silent letters are dropped.',
                'logic_type' => 'generic',
                'icon' => 'Scissors',
                'order' => 3,
            ],
            [
                'title_sd' => 'ماترا',
                'title_en' => 'Matra',
                'desc_sd' => 'ڇند وديا جو بنيادي يونٽ، جيڪو حرف جي ادائگي جو وقت ماپي ٿو.',
                'desc_en' => 'The fundamental unit of Chhand Widya, measuring the duration of a syllable.',
                'tech_detail_sd' => 'لگهو (ننڍو) = 1 ماترا؛ گرو (وڏو) = 2 ماترائون. مثال طور "وطن" = 1+1+1=3 ماترائون.',
                'tech_detail_en' => 'Laghoo (short) = 1 matra; Guroo (long) = 2 matras. A word like Watan is 1+1+1=3 matras.',
                'logic_type' => 'chhand',
                'icon' => 'Music',
                'order' => 4,
            ],
            [
                'title_sd' => 'رڪن / گن',
                'title_en' => 'Rukan / Gan',
                'desc_sd' => 'شعر جي سٽ جو بنيادي تال وارو حصو يا ٿنڀو.',
                'desc_en' => 'The basic rhythmic "foot" or "pillar" of a poetic line.',
                'tech_detail_sd' => 'عروض ۾ 8 بنيادي ارڪان (مثال: فعولن) آهن؛ ڇند ۾ 12 گن (مثال: مگن، يگن) آهن.',
                'tech_detail_en' => 'In Arooz, there are 8 basic Arkan (e.g., Faulun); in Chhand, there are 12 Gans (e.g., Magan, Yagan).',
                'logic_type' => 'both',
                'icon' => 'Columns',
                'order' => 5,
            ],
            [
                'title_sd' => 'بحر',
                'title_en' => 'Beher (Meter)',
                'desc_sd' => 'ارڪان جي هڪ خاص تال واري ترتيب يا نمونو.',
                'desc_en' => 'The specific rhythmic pattern formed by a repeated or mixed set of Arkan.',
                'tech_detail_sd' => 'ڊيگهه جي لحاظ کان ورهايل: مثمن (8 رڪن)، مسدس (6 رڪن)، يا مربع (4 رڪن).',
                'tech_detail_en' => 'Classified by length: Musamman (8 feet), Musaddas (6 feet), or Murabba (4 feet).',
                'logic_type' => 'arooz',
                'icon' => 'Ruler',
                'order' => 6,
            ],
            [
                'title_sd' => 'زحافات',
                'title_en' => 'Zuhaaf (Disciplines)',
                'desc_sd' => 'شعر جي تال کي بحر ۾ برابر ڪرڻ لاءِ بنيادي رڪن ۾ ڪيل تبديليون.',
                'desc_en' => 'Changes or deviations made to a standard foot (Rukan) to fit a specific poetic meter.',
                'tech_detail_sd' => 'شامل آهن قصر (آخري اکر ڪيرائڻ)، حذف (ٻه اکر ڪيرائڻ)، يا خبن (ٻيو اکر ڪيرائڻ).',
                'tech_detail_en' => 'Includes Qasr (dropping the last letter), Hazf (dropping two letters), or Khabn (dropping the second letter).',
                'logic_type' => 'arooz',
                'icon' => 'Wrench',
                'order' => 7,
            ],
            [
                'title_sd' => 'بيت',
                'title_en' => 'Bait',
                'desc_sd' => 'ڪلاسيڪل سنڌي دوهي جي صورت، جيڪا پنهنجي اندروني قافيي ۽ ماترا جي بناوت سان سڃاتي ويندي آهي.',
                'desc_en' => 'A classical Sindhi couplet form categorized by its internal rhyme and matra structure.',
                'tech_detail_sd' => 'ورهايل چونڊ صنفن ۾: دوهو (13+11 ماترائون)، سورٺو (11+13 ماترائون) ۽ هائبرڊ شڪليون.',
                'tech_detail_en' => 'Divided into Doha (13+11 matras), Soratha (11+13 matras), and hybrid Doha-Soratha forms.',
                'logic_type' => 'chhand',
                'icon' => 'Scroll',
                'order' => 8,
            ],
            [
                'title_sd' => 'پد / چرڻ',
                'title_en' => 'Pad / Charan',
                'desc_sd' => 'شعر جي سٽ جو هڪ حصو يا اڌ سٽ.',
                'desc_en' => 'A hemistich or a specific section of a poetic line (usually half a line).',
                'tech_detail_sd' => 'هڪ معياري دوهي بيت ۾ چار چرڻ (هر سٽ ۾ ٻه چرڻ) هوندا آهن.',
                'tech_detail_en' => 'A standard Doha Bait is composed of four Charan (two per line).',
                'logic_type' => 'chhand',
                'icon' => 'Footprints',
                'order' => 9,
            ],
            [
                'title_sd' => 'دنڊڪ ڇند',
                'title_en' => 'Dandak Chhand',
                'desc_sd' => 'ڊگهي نموني جي بحرن جي ڪيٽيگري جيڪا معياري ڊيگهه کان وڌيڪ هجي.',
                'desc_en' => 'A category for long-form meters that exceed standard lengths.',
                'tech_detail_sd' => 'اهڙي ڪا به سٽ جنهن ۾ 32 ماترائون کان وڌيڪ هجن (عام طور تي وائي يا ڪافي ۾ ملندڙ).',
                'tech_detail_en' => 'Any poetic line containing more than 32 matras (common in long Waai or Kafis).',
                'logic_type' => 'chhand',
                'icon' => 'Infinity',
                'order' => 10,
            ],
            [
                'title_sd' => 'قافيو',
                'title_en' => 'Kafiyo (Rhyme)',
                'desc_sd' => 'هم آهنگي پيدا ڪرڻ لاءِ سٽن جي خاص هنڌن تي ورجايو ويندڙ آخري آواز.',
                'desc_en' => 'The repeating sound at specific positions in the line to create harmony.',
                'tech_detail_sd' => 'دوهي ۾ قافيا آخر ۾ هوندا آهن؛ سورٺي ۾ اهي مصرع جي وچ ۾ ايندا آهن.',
                'tech_detail_en' => 'In Doha, rhymes are at the end; in Soratha, they appear in the middle of the line (misra).',
                'logic_type' => 'generic',
                'icon' => 'Music',
                'order' => 11,
            ],
            [
                'title_sd' => 'رديف',
                'title_en' => 'Radif (Refrain)',
                'desc_sd' => 'قافيي کان پوءِ ورجايو ويندڙ لفظ يا لفظن جو مجموعو.',
                'desc_en' => 'A word or phrase repeated immediately after the rhyme (Kafiyo).',
                'tech_detail_sd' => 'غزل ۾ مختلف بندن کي هڪٻئي سان ڳنڍڻ لاءِ هڪ مرڪزي لنگر طور ڪم ڪري ٿو.',
                'tech_detail_en' => 'Acts as a consistent anchor that links different couplets in a Ghazal.',
                'logic_type' => 'generic',
                'icon' => 'Anchor',
                'order' => 12,
            ],
            [
                'title_sd' => 'مطلع',
                'title_en' => 'Matla',
                'desc_sd' => 'شعر جو پهريون بند جنهن جون ٻئي سٽون هم قافيو ۽ هم رديف هجن.',
                'desc_en' => 'The opening couplet of a poem where both lines must rhyme and follow the same refrain.',
                'tech_detail_sd' => 'سڄي نظم يا غزل جي معيار، وزن ۽ قافيي جو بنياد رکندو آهي.',
                'tech_detail_en' => 'Establishes the standard weight and rhyming scheme for the entire poem.',
                'logic_type' => 'generic',
                'icon' => 'Sunrise',
                'order' => 13,
            ],
            [
                'title_sd' => 'مقطع',
                'title_en' => 'Maqta',
                'desc_sd' => 'شعر جو آخري بند، جنهن ۾ عام طور تي شاعر جو تخلص شامل هوندو آهي.',
                'desc_en' => 'The concluding couplet of a poem, typically containing the poet’s pen name (Takhalus).',
                'tech_detail_sd' => 'شعر جي رسمي خاتمي جي نشاندهي ڪري ٿو ۽ تخليقڪار جي سڃاڻپ ڪرائي ٿو.',
                'tech_detail_en' => 'Marks the formal end and identifies the author (e.g., Shah Abdul Latif or Shaikh Ayaz).',
                'logic_type' => 'generic',
                'icon' => 'Sunset',
                'order' => 14,
            ],
        ];

        foreach ($terms as $term) {
            ProsodyTerm::create($term);
        }
    }
}
