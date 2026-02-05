<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Period;
use Illuminate\Support\Facades\DB;

class PeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data to avoid duplicates if re-seeded
        DB::table('periods')->truncate();

        $periods = [
            [
                'title_en' => 'Early Period / Pre-Muslim and Early Arab Rule',
                'title_sd' => 'اوائلي دور / مسلمانن کان اڳ ۽ شروعاتي عرب حڪومت',
                'date_range' => 'Pre-712 AD to c. 1030 AD',
                'description_en' => 'The roots of Sindhi poetry are deep, existing even before the Muslim conquest. This era is generally characterized by oral traditions and early literary fragments. Historical records confirm that Sindhi poets were reciting verses before Arab Caliphs in Baghdad in the 8th and 9th centuries A.D. Treatises on secular topics like astronomy and medicine were also being written in Sindhi. Poetic forms included Sindhi qasida (odes), often found in early Sindhi translations of the Holy Quran, marking Sindhi as one of the earliest Eastern languages into which the Quran was translated (8th or 9th century A.D.). The foundational philosophical themes often included Sufistic, Vedantic, Bhakti, and Nathpanthi concepts, particularly articulated by early figures associated with Islamic missionary movements in Sindh.',
                'description_sd' => 'سنڌي شاعريءَ جون پاڙون تمام گهريون آھن، جيڪي مسلمانن جي فتح کان اڳ به موجود ھيون. ھي دور عام طور تي لوڪ روايتن ۽ اوائلي ادبي ٽڪرن سان سڃاتو وڃي ٿو. تاريخي دستاويزن مان تصديق ٿئي ٿي ته سنڌي شاعر 8ين ۽ 9ين صدي عيسويءَ ۾ بغداد ۾ عرب خليفن جي آڏو شعر پڙھندا ھئا. سنڌي زبان ۾ علم هيئت ۽ طب جهڙن دنياوي موضوعن تي به رسالا لکيا پئي ويا. شعري صنفن ۾ سنڌي قصيدو شامل ھو، جيڪو اڪثر قرآن پاڪ جي اوائلي سنڌي ترجمي ۾ ملي ٿو، جنهن مان ثابت ٿئي ٿو ته سنڌي انهن ابتدائي مشرقي ٻولين مان ھڪ آھي جن ۾ قرآن پاڪ جو ترجمو (8ين يا 9ين صدي عيسوي) ڪيو ويو. بنيادي فلسفياڻن موضوعن ۾ اڪثر صوفي، ويداني، ڀڳتي ۽ ناٿ پنٿي تصور شامل ھئا.',
                'order' => 1
            ],
            [
                'title_en' => 'Soomra Dynasty Period',
                'title_sd' => 'سومرن جو دور',
                'date_range' => 'c. 1030 AD – 1351 AD',
                'description_en' => 'This period is recognized as an era of linguistic development and general prosperity in Sindh. Sindhi literature flourished, especially poetry, which linked itself closely to the social changes of the time. The most ancient surviving traces of formal poetry during this period are fragments of ballads that narrate folk historical and pseudo-historical tales, reflecting the original nature and meter of Sindhi poetic forms. The early classical literature was divided into forms such as romantic ballads, devotional hymns, pseudo-romantic ballads, epic poetry, and customary songs. Oral literature and storytelling became precursor to more developed literature. Influences from Hindi poetry led to the popularity of early forms like gathas (or gahun), Doha (couplet), Slokas, and Sorthas, often containing themes of epic heroism, romanticism, and social aspects of daily life. Religious and mystical poetry in the form of Ginans (Ismaili devotional poetry) also gained prominence, often composed on the rhythm of local music.',
                'description_sd' => 'ھي دور سنڌ ۾ لساني ترقي ۽ خوشحاليءَ جو دور مڃيو وڃي ٿو. سنڌي ادب، خاص طور تي شاعري، تمام گھڻي ترقي ڪئي ۽ پاڻ کي ان وقت جي سماجي تبديلين سان ڳنڍي رکيو. ھن دور جي باقاعده شاعريءَ جا قديم ترين آثار اهي ڳاھون آھن جيڪي لوڪ تاريخي ۽ نيم تاريخي قصن کي بيان ڪن ٿيون، جيڪي سنڌي شعري صنفن جي اصل فطرت ۽ وزن جي عڪاسي ڪن ٿيون. شروعاتي ڪلاسيڪي ادب رومانوي داستانن، مذهبي ڀڄنن، نيم رومانوي داستانن، رزميه شاعري ۽ روايتي گيتن جھڙن قسمن ۾ ورھايل ھو. زباني ادب ۽ قصا گوئي وڌيڪ ترقي يافته ادب جو پيش خيمه بڻيا. ھندي شاعريءَ جي اثر ھيٺ گٿا (يا ڳاھون)، دوھو، سلوڪ ۽ سورٺا جھڙيون اوائلي صنفون مقبول ٿيون، جن ۾ اڪثر مهاڀاري شجاعت، رومانيت ۽ روزمره جي زندگي جا سماجي پاسا شامل ھئا. گنانن (اسماعيلي مذھبي شاعري) جي صورت ۾ مذھبي ۽ صوفيانه شاعري پڻ مقبوليت حاصل ڪئي، جيڪا اڪثر مقامي موسيقيءَ جي لئي تي ترتيب ڏني ويندي ھئي.',
                'order' => 2
            ],
            [
                'title_en' => 'Samma and Arghun/Turkhan Dynasties',
                'title_sd' => 'سمن ۽ ارغون/ترخانن جو دور',
                'date_range' => '1351 AD – 1555 AD',
                'description_en' => 'The Samma period saw greater development in Sindhi poetry. Mysticism and romanticism were major practices in the literature. Forms like the Doha and Sortha continued to develop extensively, and riddles in versified form were popular. It is deduced that during this era, the form of Bait began to develop, tracing its origin potentially earlier than the Soomra period. The subsequent Arghun and Turkhan period marked a crucial transition where Persian was the official language, leading to deep Persian influence on Sindhi poetry. Despite this pressure, Sufi and devotional thought, manifested in increasingly modified forms like Bait, Dohiro, Vai, Kafi, and Sloka, continued to thrive, reflecting the yearning love of the human soul for the Divine.',
                'description_sd' => 'سمن جي دور ۾ سنڌي شاعريءَ ۾ وڌيڪ ترقي ٿي. تصوف ۽ رومانيت ادب جا مکيه موضوع رھيا. دوھي ۽ سورٺي جھڙيون صنفون وڏي پيماني تي ترقي ڪنديون رھيون، ۽ منظو مھاڙيون (معمائون) مقبول ٿيون. اندازو آھي ته ھن دور ۾ بيت جي صنف ترقي ڪرڻ شروع ڪئي، جنهن جي شروعات شايد سومرن جي دور کان به اڳ جي ھئي. ان کان پوءِ وارو ارغون ۽ ترخان دور ھڪ اھم تبديليءَ وارو دور ھو جڏھن فارسي سرڪاري زبان هئي، جنهن ڪري سنڌي شاعريءَ تي گھرو فارسي اثر پيو. ان دٻاءَ جي باوجود، بيت، ڏوھيڙو، وائي، ڪافي ۽ سلوڪ جھڙين تبديل ٿيندڙ صنفن ۾ ظاهر ٿيندڙ صوفيانه ۽ مذهبي فڪر ترقي ڪندو رھيو، جيڪو انساني روح جي الھي عشق جي عڪاسي ڪري ٿو.',
                'order' => 3
            ],
            [
                'title_en' => 'Mughal and Kalhora Dynasty Periods (Classical Zenith)',
                'title_sd' => 'مغل ۽ ڪلھوڙا دور (ڪلاسيڪي اوج)',
                'date_range' => 'c. 1592 AD – 1782 AD',
                'description_en' => 'During the Mughal domination and the subsequent Kalhora period, Sindhi language was standardized. The Kalhora age is considered highly significant and marked the full bloom of Sindhi classical poetry. The main literary development was the use of allegory, serving as a concrete device for conveying moral, mystic, and religious lessons. Poetry focused on mystical, spiritual, devotional, didactic, romantic, and lyrical subjects, profoundly reflecting the culture and social life of Sindh and containing early threads of Sindhi nationalism. The rise of the ballad and other forms of Sindhi folklore continued to be important, often dealing with local legends and love stories. This period also witnessed a pronounced impact of Persianized forms and meters, with the first compositions of Ghazals in Persian meters appearing around 1710 AD. Devotional poetry expanded with the form of Moulood (poems in praise of the Prophet, PBUH) composed in Persian meters, and theology being written in the native form of Kabat. The institution of the Mushaira (poetry recitation session) began to move out of royal courts to the general public.',
                'description_sd' => 'مغلن جي تسلط ۽ ان کان پوءِ ڪلھوڙن جي دور ۾ سنڌي زبان کي معياري بڻايو ويو. ڪلھوڙن جو دور انتهائي اھم مڃيو وڃي ٿو ۽ اھو سنڌي ڪلاسيڪي شاعريءَ جي مڪمل جوڀن جو دور ھو. مکيه ادبي اڳڀرائي تمثيل نگاري (Allegory) جو استعمال ھو، جيڪو اخلاقي، صوفيانه ۽ مذهبي سبق پهچائڻ لاءِ ھڪ مؤثر ذريعو بڻيو. شاعريءَ جو مرڪز صوفيانه، روحاني، مذهبي، اصلاحي، رومانوي ۽ گيتي موضوع ھئا، جيڪي سنڌ جي ثقافت ۽ سماجي زندگيءَ جي گھري عڪاسي ڪن پيا ۽ جن ۾ سنڌي قومپرستيءَ جا اوائلي عڪس شامل ھئا. لوڪ داستانن ۽ سنڌي لوڪ ادب جي ٻين صنفن جو عروج اهم رھيو، جيڪي اڪثر مقامي قصن ۽ عشيه داستانن تي ٻڌل ھئا. ھن دور ۾ فارسي صنفن ۽ وزنن جو واضح اثر پيو، ۽ اندازن 1710ع ۾ فارسي وزنن ۾ غزل جون پهريون تخليقون سامهون آيون. مولود (نبي ڪريم ﷺ جي شان ۾ نظم) جي صورت ۾ مذهبي شاعريءَ ۾ واڌارو ٿيو جيڪي فارسي وزنن ۾ چيا ويا، ۽ ڪبت جي مقامي صنف ۾ مذهبي مسئلا لکيا ويا. مشاعري جي روايت شاھي دربارن مان نڪري عام ماڻهن تائين پکڙجڻ شروع ٿي.',
                'order' => 4
            ],
            [
                'title_en' => 'Talpur Dynasty Period',
                'title_sd' => 'ٽالپرن جو دور',
                'date_range' => '1782 AD – 1843 AD',
                'description_en' => 'Literary activity received impetus through the patronage of the Talpur rulers. This era saw the frequent composition of various Persianized forms in Sindhi poetry, including Ghazal, Mathnavi, Qasido, Marthio, and Rubai. The pervasive influence meant that poetry became interlanded with Persian imagery, idioms, and allusions, such as the rose and nightingale. Mysticism remained a main fountainhead of poetry, allowing for rich expressions amidst feelings of polarization and loss of identity. The form of the Sindhi Marsia (elegy) saw notable development, founded on a technically sound basis.',
                'description_sd' => 'ٽالپر حڪمرانن جي سرپرستيءَ سان ادبي سرگرمين کي وڌيڪ ھٿي ملي. ھن دور ۾ سنڌي شاعريءَ ۾ مختلف فارسي صنفن جهڙوڪ غزل، مثنوي، قصيدو، مرثيو ۽ رباعي جي ڪثرت سان تخليق ٿي. وسيع فارسي اثر سبب شاعريءَ ۾ فارسي تشبيهون، محاورا ۽ تلميحون، جھڙوڪ گل ۽ بلبل، شامل ٿي ويا. تصوف شاعريءَ جو مکيه سرچشمو رھيو، جنهن سماجي ورهاست ۽ سڃاڻپ جي گم ٿيڻ جي احساسن جي وچ ۾ ڀرپور اظهار جو موقعو فراهم ڪيو. سنڌي مرثيي جي صنف پڻ نمايان ترقي ڪئي، جنهن جو بنياد فني طور مضبوط ھو.',
                'order' => 5
            ],
            [
                'title_en' => 'Early British Rule / Sangi Age (Transitional Era)',
                'title_sd' => 'اوائلي برطانوي دور / سانگي دور (تغييري دور)',
                'date_range' => '1843 AD – c. 1915 AD',
                'description_en' => 'Following the British subjugation, Sindhi language replaced Persian as the court language in 1843, stimulating growth in native literature. Persianized forms, including Ghazal, Qasido, Rubai, and Marthio, continued to be widely composed. The period of 1881–1915 AD is often called \'The Sangi Age\', characterized by a highly Persianized diction and impressive expression, abundant in Persian and Arabic words, phrases, metaphors, and similes. Symbolic and subtle Protest Poetry began to be sensed, expressing disagreement with alienation and injustice. Towards the end of this era (1914), modern ideas, new knowledge, and unrest began shaking traditional literary systems.',
                'description_sd' => 'برطانوي قبضي کان پوءِ، 1843ع ۾ سنڌي زبان فارسيءَ جي جاءِ تي سرڪاري زبان بڻي، جنهن مقامي ادب جي واڌ ويجهه کي تيز ڪيو. فارسي صنفون، جن ۾ غزل، قصيدو، رباعي ۽ مرثيو شامل آھن، وڏي پيماني تي لکجڻ جاري رھيون. 1881ع کان 1915ع تائين واري دور کي اڪثر ’سانگي دور‘ سڏيو ويندو آھي، جيڪو انتهائي فارسي آميز ٻولي ۽ پر اثر بيان، فارسي ۽ عربي لفظن، جملن، تشبيهن ۽ استعارن جي ڀرمار سبب سڃاتو وڃي ٿو. علامتي ۽ هلڪي مزاحمتي شاعريءَ جو احساس پڻ ٿيڻ لڳو، جنهن ۾ ڌاڙي ۽ ناانصافيءَ سان اختلاف جو اظهار ڪيو ويو. ھن دور جي آخر (1914ع) ۾ جديد خيالن، نئين علم ۽ بيچينيءَ روايتي ادبي نظام کي لوڏڻ شروع ڪيو.',
                'order' => 6
            ],
            [
                'title_en' => 'Evolution of Modern Trends / Bewas School',
                'title_sd' => 'جديد رجحانن جو ارتقا / بيوس اسڪول',
                'date_range' => 'c. 1914 AD – 1930 AD',
                'description_en' => 'This phase marks a fundamental shift, influenced significantly by socio-political changes, including the impact of the First World War (1914–1918). Sindhi poetry moved away from old thoughts toward new expressions and linked literature to contemporary social change. Poetry divided into three main schools of thought: the Misri Shah School (focusing on indigenous forms and Sufistic ideology), the Thattavi School (favoring Persianized forms and metaphors like the rose and nightingale), and the Bewas School (Modern and Progressive). The modern trend initiated around 1925, introducing philosophical topics. It was marked by themes of social and economic discontent, promoting the dignity of labor and equality. The earliest composition of the Progressive School appeared in 1937.',
                'description_sd' => 'ھي دور ھڪ بنيادي تبديليءَ جي نشاندهي ڪري ٿو، جيڪو سماجي ۽ سياسي تبديلين، بشمول پهرين جنگ عظيم (1914-1918) جي اثرن کان متاثر ھو. سنڌي شاعري پراڻن خيالن کان نئين اظهار ڏانهن وڌي ۽ ادب کي همعصر سماجي تبديلين سان ڳنڍيو. شاعري ٽن مکيه مڪتب فڪر ۾ ورهايل ھئي: مصري شاهه اسڪول (مقامي صنفن ۽ صوفيانه نظريي تي زور ڏيندو ھو)، ٺٽوي اسڪول (فارسي صنفن ۽ گل و بلبل جھڙن استعارن کي ترجيح ڏيندو ھو)، ۽ بيوس اسڪول (جديد ۽ ترقي پسند). جديد رجحانن جي شروعات 1925ع ڌاري ٿي، جنهن ۾ فلسفياڻا موضوع متعارف ڪرايا ويا. ھن دور ۾ سماجي ۽ معاشي بيچيني جا موضوع نمايان رھيا، جن محنت جي عظمت ۽ برابريءَ کي فروغ ڏنو. ترقي پسند اسڪول جي پھرين تخليق 1937ع ۾ سامهون آئي.',
                'order' => 7
            ],
            [
                'title_en' => 'Period of National Awakening and Progressive Movement',
                'title_sd' => 'قومي بيداري ۽ ترقي پسند تحريڪ جو دور',
                'date_range' => '1936 AD – 1947 AD',
                'description_en' => 'The broader Progressive Movement (beginning c. 1935) took firm root, driven partly by the existing feudal system and the static nature of overly Persianized poetry. The movement introduced fundamental changes in content, concept, and form. Thematic values focused heavily on national, social, and political aspirations, including nationalism and the struggle for freedom. New forms adopted from Western literature gained ground, such as \'Free verse\' (introduced during the British days), Sonnet, and Triolet. Poetry themes revolved around national awakening, freedom, and internationalism, often discussing the problems faced by Sindh and its people.',
                'description_sd' => 'وسيع ترقي پسند تحريڪ (شروعات تقريبن 1935ع) جا پير مضبوط ٿيا، جنهن جو ھڪ سبب موجوده جاگيردارانه نظام ۽ حد کان وڌيڪ فارسي زده شاعريءَ جو جامد ٿيڻ ھو. ھن تحريڪ مواد، تصور ۽ صنف ۾ بنيادي تبديليون متعارف ڪرايون. موضوعاتي قدرن جو محور گهڻو ڪري قومي، سماجي ۽ سياسي اميدون ھيون، جن ۾ قومپرستي ۽ آزاديءَ جي جدوجهد شامل ھئي. مغربي ادب مان ورتل نيون صنفون جھڙوڪ ’آزاد نظم‘ (جيڪو انگريزن جي دور ۾ متعارف ٿيو)، سانيٽ ۽ ٽرائيلٽ مقبول ٿيون. شاعريءَ جا موضوع قومي بيداري، آزادي ۽ بين الاقواميت جي چوڌاري ڦرندا رھيا، ۽ ان ۾ اڪثر سنڌ ۽ ان جي ماڻهن کي درپيش مسئلن تي بحث ڪيو ويو.',
                'order' => 8
            ],
            [
                'title_en' => 'Post-Independence Era (New Trends and Consolidation)',
                'title_sd' => 'آزاديءَ کان پوءِ وارو دور (نوان رجحان ۽ استحڪام)',
                'date_range' => '1947 AD – Present',
                'description_en' => 'After the partition of India in 1947, a brief literary vacuum was quickly filled by the younger generation who re-established literary societies. Sindhi literature explored broader economic and social topics. The Progressive/Modern School continued to emphasize new ideas, dictions, and themes. The use of European forms such as Triolet, Free-verse, Sonnet, Haiku, Renga, and Tanka reinforced modern trends. This period also saw the rise of an ideological divide between the Progressive/Socialistic school and the religious/Rightist group. After the controversial One Unit period (ended 1970), there was a resurrection of Sindhi poetry, leading to highly vocal and forceful poetry of protest focusing on anti-tyranny, cultural renaissance, and social injustices. Contemporary Sindhi poetry is characterized by its commitment to the soil, its strong political and social content, and its aesthetic versatility, making it a true reflection of the people\'s aspirations. Forms currently popular include Nazm, Geet, Doho, Sortho, Kafi, Vai, and Bait.',
                'description_sd' => '1947ع ۾ هندستان جي ورھاڱي کان پوءِ پيدا ٿيل مختصر ادبي خال کي نوجوان نسل جلد ڀريندي ادبي تنظيمون ٻيهر قائم ڪيون. سنڌي ادب وسيع معاشي ۽ سماجي موضوعن کي ڇيڙيو. ترقي پسند/جديد اسڪول نون خيالن، انداز بيان ۽ موضوعن تي زور ڏيڻ جاري رکيو. يورپي صنفن جھڙوڪ ٽرائيلٽ، آزاد نظم، سانيٽ، هائيڪو، رينگا ۽ ٽانڪا جي استعمال جديد رجحانن کي مضبوط ڪيو. ھن دور ۾ ترقي پسند/سوشلسٽ اسڪول ۽ مذهبي/ساڄي ڌر جي گروهه جي وچ ۾ نظرياتي ورهاست پڻ جنم ورتو. ون يونٽ جي تڪراري دور (ختم 1970ع) کان پوءِ سنڌي شاعريءَ جو احياءُ ٿيو، جنهن مزاحمتي شاعريءَ کي جنم ڏنو جيڪا ظلم جي خلاف، ثقافتي اڀار ۽ سماجي ناانصافين تي زوردار نموني ڳالهائيندڙ ھئي. همعصر سنڌي شاعري ڌرتيءَ سان وفاداري، مضبوط سياسي ۽ سماجي مواد، ۽ جمالياتي رنگارنگيءَ جي ڪري سڃاتي وڃي ٿي، جيڪا عوام جي اميدن جي سچي عڪاس آھي. ھن وقت مقبول صنفن ۾ نظم، گيت، دوھو، سورٺو، ڪافي، وائي ۽ بيت شامل آھن.',
                'order' => 9
            ]
        ];

        foreach ($periods as $period) {
            Period::create($period);
        }
    }
}
