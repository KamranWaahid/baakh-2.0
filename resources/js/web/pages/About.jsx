import React from 'react';
import { Link, useParams } from 'react-router-dom';
import Logo from '../components/Logo';

const About = () => {
    const { lang } = useParams();
    const isRtl = lang === 'sd';

    const content = {
        en: {
            title: 'Baakh - A Digital Archive of Sindhi Poetry',
            sections: [
                {
                    title: 'Introduction',
                    content: [
                        'Baakh is a comprehensive non-profit and progressive, political and socially conscious digital archive and platform dedicated to preserving and documenting the heritage of Sindhi poetry. Our mission is to bridge centuries of poetic tradition with modern technology, making Sindhi poetry accessible to scholars, students, poets, and enthusiasts worldwide.',
                        'Poetry is a refined art form that has accompanied human civilization from its earliest aesthetic expressions. In Sindhi culture, poetry represents not just literary achievement but the soul of our resistance movements, a vessel carrying history, spirituality, politics, philosophy, and emotion across generations.',
                        'Sindhi poetry\'s history extends deep into antiquity, predating even the Arab period. Ancient forms such as Doha, Bait, Waai, Kaffi, Dohira, Marsiya, and Ashlok have shaped our literary and political landscape for centuries.'
                    ]
                },
                {
                    title: 'The Digital Preservation Challenge',
                    content: [
                        'Traditionally, Sindhi poetry has been preserved in individual books, literary journals, and institutional libraries. From the immortal verses of Shah Abdul Latif Bhittai to the mystical works of Sachal Sarmast, from the mastery of Ustad Bukhari to the modern brilliance of Sheikh Ayaz and Janan Chan, our literary treasures have existed primarily in print form, scattered across private collections and limited library holdings.',
                        'In the digital age, this fragmentation poses a significant challenge. Despite the wealth of Sindhi poetic literature, there existed no centralized, searchable, technologically accessible repository that meets contemporary needs for research, education, and cultural engagement.',
                        'Baakh was created to solve this problem.'
                    ]
                },
                {
                    title: 'Our Vision & Mission',
                    content: [
                        'Publicly Launched on World Poetry Day, March 21, 2024, Baakh represents a journey from ancient poetic traditions to the technological future. This platform aims to:'
                    ],
                    list: [
                        'Preserve the complete spectrum of Sindhi poetry, from classical to contemporary',
                        'Document the works of established and emerging poets in standardized, searchable formats',
                        'Democratize access to Sindhi poetic heritage for global audiences',
                        'Innovate by applying artificial intelligence and machine learning to Sindhi poetic analysis',
                        'Connect generations of readers with centuries of poetic wisdom',
                        'Support contemporary poets by providing a modern platform for their work'
                    ]
                },
                {
                    title: 'Development History',
                    subsections: [
                        {
                            subtitle: 'Origins (2020-2023)',
                            text: 'Baakh was conceived in 2020 by two software engineers, Kamran Wahid and Ubaid Thaheem, who had previously been active in digital initiatives for Sindhi language preservation. Both founders brought valuable experience from their work with the Sindhi Language Authority and independent digital projects including the Sindh Salamat Forum, Sindh Literature Festival. This background gave them unique insight into the urgent need to digitize Sindhi literary heritage and the technical challenges involved in preserving language and culture in the digital age. The project began as Kamran\'s final year university project, initially built on the CodeIgniter framework and supporting only Arabic-Persian script. This early version laid the foundation for what would become a comprehensive literary archive.'
                        },
                        {
                            subtitle: 'Evolution (2023-Present)',
                            text: 'In 2023, the platform underwent a major technical upgrade, migrating to the Laravel framework. This modernization enabled:',
                            list: [
                                'Dual-script support - Both Roman and Arabic-Persian (traditional Sindhi) scripts',
                                'Enhanced search functionality - Advanced filtering by poet, genre, theme, and period',
                                'User interaction features - Favorites, comments, and personalized collections',
                                'Scalable architecture - Designed to grow with our expanding database'
                            ]
                        }
                    ]
                },
                {
                    title: 'Platform Features',
                    subsections: [
                        {
                            subtitle: 'Comprehensive Poet Database',
                            text: 'Baakh features a growing collection of poets, carefully categorized into:',
                            list: [
                                'Classical Poets (ڪلاسيڪل شاعر) - Masters of traditional Sindhi poetry',
                                'Sufi Poets (صوفي شاعر) - Mystics and spiritual voices',
                                'Revolutionary Poets (انقلابي شاعر) - Voices of social change',
                                'Contemporary Poets (نوجوان شاعر) - Modern literary voices',
                                'Emerging Poets (نئين ٽھيءَ جو شاعر) - The next generation'
                            ],
                            footer: 'Each poet profile includes biographical information, complete works, and contextual analysis.'
                        },
                        {
                            subtitle: 'Poetry Genre Archive',
                            text: 'Our archive encompasses distinct poetic forms, including:',
                            nestedList: [
                                {
                                    label: 'Classical Forms',
                                    items: [
                                        'Ghazal (غزل) - The beloved form of romantic and philosophical poetry',
                                        'Waai (وائي) - Traditional Sindhi form exploring love and divine unity',
                                        'Kaffi (ڪافي) - Musical poetry rooted in Sindhi folk traditions',
                                        'Bait (بيت) - The fundamental two-line couplet form',
                                        'Doho (دوهو) - Classical Hindi-Sindhi couplet tradition'
                                    ]
                                },
                                {
                                    label: 'Devotional Poetry',
                                    items: [
                                        'Hamd (حمد) - Praise of the Divine',
                                        'Naat (نعت) - Praise of Prophet Muhammad (PBUH)',
                                        'Manqabat (منقبت) - Tributes to spiritual leaders'
                                    ]
                                },
                                {
                                    label: 'Modern Forms',
                                    items: [
                                        'Nazm (نظم) - Structured modern poetry',
                                        'Free Verse (آزاد نظم) - Modern unrestricted form',
                                        'Prose Poem (نثری نظم) - Poetic prose',
                                        'Haiku (ھائيڪو) - Japanese form adapted to Sindhi'
                                    ]
                                },
                                {
                                    label: 'Structured Forms',
                                    items: [
                                        'Chaustta (چوسٽو) - Four-line stanza',
                                        'Tairru (ٹيڙُو) - Three-line verse',
                                        'Panj Sitta (پنج سٹو) - Five-line form',
                                        'Chhah Sitta (ڇھہ سٽو) - Six-line form',
                                        'Traeel (ترائيل) - French-inspired trio form'
                                    ]
                                }
                            ],
                            footer: 'Each genre page includes historical context, structural analysis, and representative examples.'
                        },
                        {
                            subtitle: 'Thematic Organization',
                            text: 'Poetry is indexed by themes, enabling research and exploration across:',
                            list: [
                                'Emotional landscapes: Love (عشق), Pain (ڏک), Longing (رولاڪ), Disappointment (مايوسي)',
                                'Physical places: Homeland (وطن), Exile (پرديس), Earth (ڌرتي)',
                                'Abstract concepts: Beauty (حسن), Life (زندگي), Journey (سفر), Search (ڳولها)',
                                'Natural imagery: Wind (واءُ), Trees (وڻ), Fragrance (خوشبو), Landscape (منظر)',
                                'Relationships: Beloved (محبوب/محبوبا), Love (محبت)'
                            ]
                        },
                        {
                            subtitle: 'Interactive Features',
                            nestedList: [
                                {
                                    label: 'For Readers',
                                    items: [
                                        'Daily featured ghazal and poet spotlight',
                                        'Personal favorites and reading lists',
                                        'Social sharing capabilities',
                                        'Comment and discussion sections',
                                        'Dual-script toggle (Sindhi ↔ Roman)'
                                    ]
                                },
                                {
                                    label: 'For Scholars',
                                    items: [
                                        'Advanced search and filtering',
                                        'Genre-based analysis',
                                        'Historical period categorization',
                                        'Prosody (ڇَند) reference materials',
                                        'Complete bibliographic information'
                                    ]
                                },
                                {
                                    label: 'For Everyone',
                                    items: [
                                        'Mobile-responsive design',
                                        'Accessible interface',
                                        'Free, open access to all content'
                                    ]
                                }
                            ]
                        }

                    ]
                },
                {
                    title: 'Featured Poets',
                    text: 'Our archive includes works from:',
                    nestedList: [
                        {
                            label: 'Classical Masters',
                            items: ['Shah Abdul Latif Bhittai', 'Sachal Sarmast', 'Ustad Bukhari', 'Rohal Faqir', 'Shah Abdul Karim Bulri Waaro']
                        },
                        {
                            label: 'Modern Icons',
                            items: ['Sheikh Ayaz', 'Shaikh Ayaz', 'Imdad Hussaini', 'Adal Soomro']
                        },
                        {
                            label: 'Contemporary Voices',
                            items: ['Aakash Ansari (1956-2025)', 'Ayaz Gul', 'Iqbal Rind', 'Ali Raza Kosar', 'Ahmed Shakir', 'Haleem Baaghi']
                        }
                    ],
                    footer: 'And many more brilliant voices spanning centuries of Sindhi literary achievement.'
                },
                {
                    title: 'Technical Infrastructure',
                    content: [
                        'Baakh is built on modern web technologies to ensure:'
                    ],
                    list: [
                        'Accessibility - Cross-platform compatibility (desktop, mobile, tablet)',
                        'Scalability - Architecture designed for continuous content expansion',
                        'Searchability - Advanced indexing and filtering capabilities',
                        'Preservation - Secure, backed-up digital storage',
                        'User Experience - Intuitive navigation and clean design'
                    ],
                    footer: 'The platform is actively maintained and regularly updated with new content, features, and improvements.'
                },
                {
                    title: 'Future Development',
                    subsections: [
                        {
                            subtitle: 'Immediate Goals',
                            list: [
                                'Expand the poet database to include comprehensive historical coverage',
                                'Add audio recordings and recitations',
                                'Develop educational resources and study guides',
                                'Create APIs for academic research integration',
                                'Enhance multilingual support'
                            ]
                        },
                        {
                            subtitle: 'Long-term Vision',
                            text: 'Our most ambitious goal is to develop artificial intelligence and machine learning models specifically designed for Sindhi poetry analysis. This will enable:',
                            list: [
                                'Automated prosody and meter analysis',
                                'Style and influence mapping',
                                'Thematic pattern recognition',
                                'Comparative literary analysis',
                                'Preservation of oral traditions through voice recognition',
                                'Intelligent search based on poetic structure and meaning'
                            ]
                        }
                    ]
                },
                {
                    title: 'Community & Engagement',
                    content: [
                        'Baakh is more than a digital archive—it\'s a living community of poetry lovers. We actively encourage:'
                    ],
                    list: [
                        'User feedback - Your suggestions shape our development',
                        'Content contributions - Help us discover and document lesser-known poets',
                        'Educational partnerships - Collaborations with schools and universities',
                        'Cultural events - Virtual poetry readings and discussions',
                        'Research support - Resources for academic study'
                    ]
                },
                {
                    title: 'Our Team',
                    team: [
                        { name: 'Kamran Wahid', role: 'Product Designer & Co-Founder', desc: 'Platform design and technical development' },
                        { name: 'Ubaid Thaheem', role: 'Software Engineer & Co-Founder', desc: 'Backend systems and database management' },
                        { name: 'Charoo', role: 'Content Curator', desc: 'Poetry collection, verification, and documentation' },
                    ]
                },
                {
                    title: 'Acknowledgments',
                    content: [
                        'This platform exists because of centuries of poetic genius, the dedication of publishers and librarians who preserved these works, and the passion of contemporary readers who keep Sindhi literature alive.',
                        'We are grateful to the families and estates of poets who have entrusted us with their literary legacies, and to the broader Sindhi community whose support makes this work possible.'
                    ]
                },
                {
                    title: 'Join Us',
                    content: [
                        'Whether you\'re a scholar conducting research, a student discovering Sindhi literature, a poet seeking inspiration, or simply someone who loves beautiful language—Baakh welcomes you.',
                        'Explore. Discover. Preserve.',
                        'Together, we are ensuring that the voices of Sindhi poetry echo across generations, now accessible at the click of a button, forever preserved in the digital age.'
                    ]
                },
                {
                    title: 'Contact & Contributions',
                    content: [
                        'We welcome your feedback, suggestions, and contributions. Help us make Baakh the definitive archive of Sindhi poetry.',
                        'Platform Rights: All rights reserved to Baakh Foundation'
                    ]
                }
            ],
            quote: {
                text: 'Baakh - How long must this remain buried? Some treasure must surely emerge.',
                original: '"باک - ھِي اُونده ڪيسين رھڻي آ؟ ڪا باک نيٺ تہ ڦُٹڻِي آ"'
            }
        },
        sd: {
            title: 'باک - سنڌي شاعريءَ جو ڊجيٽل آرڪائيو',
            sections: [
                {
                    title: 'تعارف',
                    content: [
                        'باک هڪ جامع غير منافع بخش ۽ ترقي پسند، سياسي ۽ سماجي شعور رکندڙ ڊجيٽل آرڪائيو ۽ پليٽفارم آهي جيڪو سنڌي شاعري جي ورثي کي محفوظ ۽ دستاويز ڪرڻ لاءِ وقف آهي. اسان جو مقصد صديون پراڻي شاعري جي روايت کي جديد ٽيڪنالاجي سان ڳنڍڻ آهي، سنڌي شاعري کي دنيا جي عالمن، شاگردن، شاعرن ۽ شوقينن لاءِ رسائي لائق بڻائڻ.',
                        'شاعري هڪ لطيف فن آهي جيڪو انساني تهذيب سان گڏ ان جي شروعاتي جمالياتي اظهار کان وٺي گڏ رهيو آهي. سنڌي ڪلچر ۾، شاعري رڳو ادبي ڪاميابي نه آهي پر اسان جي مزاحمتي تحريڪن جو روح آهي، هڪ برتن جيڪو تاريخ، روحانيت، سياست، فلسفو ۽ جذبن کي نسلن تائين پهچائي ٿو.',
                        'سنڌي شاعري جي تاريخ گهڻي قديم آهي، جيڪا عرب دور کان به اڳ جي آهي. قديم صنفون جهڙوڪ دوها، بيت، وائي، ڪافي، دوهيرو، مرثيو ۽ اشلوڪ صدين کان اسان جي ادبي ۽ سياسي منظرنامي کي شڪل ڏني آهي.'
                    ]
                },
                {
                    title: 'ڊجيٽل تحفظ جو چئلينج',
                    content: [
                        'روايتي طور تي، سنڌي شاعري انفرادي ڪتابن، ادبي رسالن ۽ ادارن جي لائبريرين ۾ محفوظ رهي آهي. شاهه عبداللطيف ڀٽائيءَ جي لافاني شعرن کان وٺي سچل سرمست جي صوفياتي ڪم تائين، استاد بخاري جي مهارت کان وٺي شيخ اياز ۽ جانڻ چن جي جديد روشنيءَ تائين، اسان جا ادبي خزانا بنيادي طور تي ڇپيل شڪل ۾ موجود آهن، نجي مجموعن ۽ محدود لائبريرين ۾ پکڙيل.',
                        'ڊجيٽل دور ۾، هي ٽڪراءُ هڪ اهم چئلينج آهي. سنڌي شاعري جي دولت جي باوجود، ڪو به مرڪزي، ڳولا لائق، ٽيڪنالاجي طور رسائي لائق ذخيرو موجود نه هو جيڪو تحقيق، تعليم ۽ ثقافتي مشغوليت جي معاصر ضرورتن کي پورو ڪري.',
                        'باک انهيءَ مسئلي کي حل ڪرڻ لاءِ ٺاهيو ويو.'
                    ]
                },
                {
                    title: 'اسان جو نظريو ۽ مشن',
                    content: [
                        'عالمي شاعري جي ڏينهن، 21 مارچ 2024 تي عوامي طور شروع ڪيو ويو، باک قديم شاعري جي روايتن کان ٽيڪنالاجي مستقبل ڏانهن هڪ سفر جي نمائندگي ڪري ٿو. هي پليٽفارم جو مقصد آهي:'
                    ],
                    list: [
                        'محفوظ ڪرڻ - سنڌي شاعري جي مڪمل اسپيڪٽرم، ڪلاسيڪي کان معاصر تائين',
                        'دستاويز ڪرڻ - قائم ٿيل ۽ اڀرندڙ شاعرن جي ڪم کي معياري، ڳولا لائق فارميٽ ۾',
                        'جمهوري بنائڻ - عالمي سامعين لاءِ سنڌي شاعري جي ورثي تائين رسائي',
                        'جدت آڻڻ - مصنوعي ذهانت ۽ مشين لرننگ کي سنڌي شاعري جي تجزيي ۾ لاڳو ڪرڻ',
                        'ڳنڍڻ - صدين جي شاعرانه حڪمت سان پڙهندڙن جي نسلن کي',
                        'سپورٽ ڪرڻ - معاصر شاعرن کي سندن ڪم لاءِ جديد پليٽفارم فراهم ڪري'
                    ]
                },
                {
                    title: 'ترقياتي تاريخ',
                    subsections: [
                        {
                            subtitle: 'شروعات (2020-2023)',
                            text: 'باک جو تصور 2020ع ۾ ٻن سافٽ ويئر انجنيئرن ڪامران واحد ۽ عبيد ٿهيم طرفان ڪيو ويو، جيڪي اڳ ۾ سنڌي ٻولي جي تحفظ لاءِ ڊجيٽل شروعاتن ۾ سرگرم هئا. ٻنهي باني مٿين سنڌي ٻولي اٿارٽي سان گڏ ڪم ڪرڻ ۽ آزاد ڊجيٽل منصوبن جهڙوڪ سنڌ سلامت فورم، سنڌ ليٽريچر فيسٽيول ۾ شامل ٿيڻ جو قيمتي تجربو کڻي آيا. اهو پس منظر انهن کي سنڌي ادبي ورثي کي ڊجيٽل ڪرڻ جي فوري ضرورت ۽ ڊجيٽل دور ۾ ٻولي ۽ ڪلچر جي تحفظ ۾ ٽيڪنيڪل چئلينجن بابت منفرد بصيرت ڏني. اهو منصوبو ڪامران جي فائنل سال يونيورسٽي پروجيڪٽ جي طور شروع ٿيو، شروعات ۾ ڪوڊ اگنائيٽر فريم ورڪ تي ٺهيل ۽ صرف عربي-فارسي رسم الخط جي مدد سان. هن شروعاتي ورزن جو بنياد رکيو جيڪو هڪ جامع ادبي آرڪائيو بڻجي ويو.'
                        },
                        {
                            subtitle: 'ارتقاءُ (2023-حال تائين)',
                            text: '2023ع ۾، پليٽفارم هڪ وڏي ٽيڪنيڪل اپ گريڊ مان گذريو، لاراويل فريم ورڪ ڏانهن منتقل ٿي. هن جديديت فعال ڪيو:',
                            list: [
                                'ٻٽي رسم الخط جي مدد - رومن ۽ عربي-فارسي (روايتي سنڌي) ٻنهي رسم الخط',
                                'بهتر ڳولا جي فعاليت - شاعر، صنف، موضوع ۽ دور جي لحاظ کان جديد فلٽرنگ',
                                'صارف جي رابطي جون خاصيتون - پسنديده، تبصرا ۽ ذاتي مجموعا',
                                'اسڪيل لائق آرڪيٽيڪچر - اسان جي وڌندڙ ڊيٽابيس سان گڏ وڌڻ لاءِ ٺهيل'
                            ]
                        }
                    ]
                },
                {
                    title: 'پليٽفارم جون خاصيتون',
                    subsections: [
                        {
                            subtitle: 'جامع شاعرن جو ڊيٽابيس',
                            text: 'باک هن وقت شاعرن جي خاصيت رکي ٿو، جن کي احتياط سان درجه بندي ڪيو ويو آهي:',
                            list: [
                                'ڪلاسيڪي شاعر (ڪلاسيڪل شاعر) - روايتي سنڌي شاعري جا ماهر',
                                'صوفي شاعر (صوفي شاعر) - صوفي ۽ روحاني آواز',
                                'انقلابي شاعر (انقلابي شاعر) - سماجي تبديلي جا آواز',
                                'معاصر شاعر (نوجوان شاعر) - جديد ادبي آواز',
                                'اڀرندڙ شاعر (نئين ٽھيءَ جو شاعر) - ايندڙ نسل'
                            ],
                            footer: 'هر شاعر جي پروفائل ۾ سوانح عمري جي معلومات، مڪمل ڪم، ۽ سياق و سباق جو تجزيو شامل آهي.'
                        },
                        {
                            subtitle: 'شاعري جي صنف جو آرڪائيو',
                            text: 'اسان جي آرڪائيو ۾ مخصوص شاعرانه صنفون شامل آهن، جن ۾:',
                            nestedList: [
                                {
                                    label: 'ڪلاسيڪي صنفون',
                                    items: [
                                        'غزل (غزل) - رومانوي ۽ فلسفياتي شاعري جي محبوب صنف',
                                        'وائي (وائي) - روايتي سنڌي صنف جيڪا محبت ۽ خدائي اتحاد جي ڳالهه ڪري ٿي',
                                        'ڪافي (ڪافي) - سنڌي لوڪ روايتن ۾ جڙيل موسيقيءَ واري شاعري',
                                        'بيت (بيت) - بنيادي ٻن سطرن جي شعر جي صنف',
                                        'دوهو (دوهو) - ڪلاسيڪي هندي-سنڌي شعر جي روايت'
                                    ]
                                },
                                {
                                    label: 'عبادت واري شاعري',
                                    items: [
                                        'حمد (حمد) - خدا جي ساراهه',
                                        'نعت (نعت) - پيغمبر محمد (ص) جي ساراهه',
                                        'منقبت (منقبت) - روحاني اڳواڻن جو خراج تحسين'
                                    ]
                                },
                                {
                                    label: 'جديد صنفون',
                                    items: [
                                        'نظم (نظم) - منظم جديد شاعري',
                                        'آزاد نظم (آزاد نظم) - جديد غير محدود صنف',
                                        'نثري نظم (نثری نظم) - شاعرانه نثر',
                                        'هائيڪو (ھائيڪو) - جاپاني صنف جيڪا سنڌي ۾ اپنائي وئي'
                                    ]
                                },
                                {
                                    label: 'منظم صنفون',
                                    items: [
                                        'چوسٽو (چوسٽو) - چار سطرن جي بند',
                                        'ٽيڙو (ٹيڙُو) - ٽن سطرن جو شعر',
                                        'پنج سٽو (پنج سٽو) - پنجن سطرن جي صنف',
                                        'ڇهه سٽو (ڇھہ سٽو) - ڇهن سطرن جي صنف',
                                        'ترائيل (ترائيل) - فرانسيسي متاثر ٽريو صنف'
                                    ]
                                }
                            ],
                            footer: 'هر صنف واري صفحي ۾ تاريخي سياق و سباق، ساختي تجزيو، ۽ نمائندي مثال شامل آهن.'
                        },
                        {
                            subtitle: 'موضوعي تنظيم',
                            text: 'شاعري موضوعن جي لحاظ کان انڊيڪس ٿيل آهي، جيڪا تحقيق ۽ ڳولا ممڪن بناندي آهي:',
                            list: [
                                'جذباتي منظرنامو: محبت (عشق), درد (ڏک), تڙپ (رولاڪ), مايوسي (مايوسي)',
                                'جسماني هنڌ: وطن (وطن), جلاوطني (پرديس), زمين (ڌرتي)',
                                'تجريدي تصورات: حسن (حسن), زندگي (زندگي), سفر (سفر), ڳولا (ڳولها)',
                                'قدرتي تصويرون: واءُ (واءُ), وڻ (وڻ), خوشبو (خوشبو), منظر (منظر)',
                                'رشتا: محبوب (محبوب/محبوبا), محبت (محبت)'
                            ]
                        },
                        {
                            subtitle: 'رابطي جون خاصيتون',
                            nestedList: [
                                {
                                    label: 'پڙهندڙن لاءِ',
                                    items: [
                                        'روزاني نمايان غزل ۽ شاعر جي روشني',
                                        'ذاتي پسنديده ۽ پڙهڻ جون فهرستون',
                                        'سماجي حصيداري جون صلاحيتون',
                                        'تبصرو ۽ بحث جا حصا',
                                        'ٻٽي رسم الخط ٽوگل (سنڌي ↔ رومن)'
                                    ]
                                },
                                {
                                    label: 'عالمن لاءِ',
                                    items: [
                                        'جديد ڳولا ۽ فلٽرنگ',
                                        'صنف جي بنياد تي تجزيو',
                                        'تاريخي دور جي درجه بندي',
                                        'عروض (ڇَند) جا حوالا مواد',
                                        'مڪمل ببليوگرافڪ معلومات'
                                    ]
                                },
                                {
                                    label: 'سڀني لاءِ',
                                    items: [
                                        'موبائيل جوابي ڊزائن',
                                        'رسائي لائق انٽرفيس',
                                        'مفت، کليل رسائي سڀني مواد تائين'
                                    ]
                                }
                            ]
                        }

                    ]
                },
                {
                    title: 'نمايان شاعر',
                    text: 'اسان جي آرڪائيو ۾ شامل آهن:',
                    nestedList: [
                        {
                            label: 'ڪلاسيڪي ماهر',
                            items: ['شاهه عبداللطيف ڀٽائي', 'سچل سرمست', 'استاد بخاري', 'روحل فقير', 'شاهه عبدالڪريم بلڙيءَ وارو']
                        },
                        {
                            label: 'جديد عظيم',
                            items: ['شيخ اياز', 'امداد حسيني', 'ادل سومرو']
                        },
                        {
                            label: 'معاصر آواز',
                            items: ['آڪاش انصاري (1956-2025)', 'اياز گُل', 'اقبال رند', 'علي رضا ڪوثر', 'احمد شاڪر', 'حليم باغي']
                        }
                    ],
                    footer: '۽ ٻيا ڪيترائي شاندار آواز جيڪي صدين جي سنڌي ادبي ڪاميابين تي مشتمل آهن.'
                },
                {
                    title: 'ٽيڪنيڪل بنياد',
                    content: [
                        'باک جديد ويب ٽيڪنالاجين تي ٺهيل آهي انهي کي يقيني بنائڻ لاءِ:'
                    ],
                    list: [
                        'رسائي - ڪراس-پليٽفارم مطابقت (ڊيسڪ ٽاپ، موبائيل، ٽيبليٽ)',
                        'اسڪيل ڪرڻ جي صلاحيت - مسلسل مواد جي توسيع لاءِ ٺهيل آرڪيٽيڪچر',
                        'ڳولا لائق - جديد انڊيڪسنگ ۽ فلٽرنگ جون صلاحيتون',
                        'تحفظ - محفوظ، بيڪ اپ ٿيل ڊجيٽل اسٽوريج',
                        'صارف جو تجربو - سمجھ ۾ ايندڙ نيويگيشن ۽ صاف ڊزائن'
                    ],
                    footer: 'پليٽفارم فعال طور برقرار رکيو ۽ باقاعده نئين مواد، خاصيتن ۽ بهتري سان اپڊيٽ ڪيو ويندو آهي.'
                },
                {
                    title: 'مستقبل جي ترقي',
                    subsections: [
                        {
                            subtitle: 'فوري مقصد',
                            list: [
                                'شاعرن جي ڊيٽابيس کي وڌايو وڃي ته جيئن جامع تاريخي ڪوريج شامل ڪئي وڃي',
                                'آڊيو رڪارڊنگ ۽ تلاوت شامل ڪريو',
                                'تعليمي وسيلا ۽ مطالعي جي رهنما تيار ڪريو',
                                'تحقيقي تحقيق جي انضمام لاءِ APIs ٺاهيو',
                                'گهڻ-لساني مدد کي وڌايو'
                            ]
                        },
                        {
                            subtitle: 'ڊگهي مدت جو نظريو',
                            text: 'اسان جو سڀ کان وڌيڪ امڪاني مقصد آهي مصنوعي ذهانت ۽ مشين لرننگ ماڊلز خاص طور تي سنڌي شاعري جي تجزيي لاءِ ٺاهڻ. هي ممڪن بڻائيندو:',
                            list: [
                                'خودڪار عروض ۽ ميٽر جو تجزيو',
                                'انداز ۽ اثر جي نقشي سازي',
                                'موضوعاتي نمونن جي سڃاڻپ',
                                'تقابلي ادبي تجزيو',
                                'آواز جي سڃاڻپ ذريعي زباني روايتن جو تحفظ',
                                'شاعرانه ساخت ۽ معنيٰ جي بنياد تي ذهين ڳولا'
                            ]
                        }
                    ]
                },
                {
                    title: 'ڪميونٽي ۽ مشغوليت',
                    content: [
                        'باک صرف هڪ ڊجيٽل آرڪائيو نه آهي - اهو شاعري سان پيار ڪندڙن جي زندهه ڪميونٽي آهي. اسان فعال طور حوصلا افزائي ڪريون ٿا:'
                    ],
                    list: [
                        'صارف جي راءِ - توهان جون تجويزون اسان جي ترقي کي شڪل ڏين ٿيون',
                        'مواد جي مدد - اسان کي گهٽ ڄاتل شاعرن کي ڳولڻ ۽ دستاويز ڪرڻ ۾ مدد ڪريو',
                        'تعليمي ڀائيواري - اسڪولن ۽ يونيورسٽين سان تعاون',
                        'ثقافتي واقعا - ورچوئل شاعري پڙهڻ ۽ بحث',
                        'تحقيقي مدد - تعليمي مطالعي لاءِ وسيلا'
                    ]
                },
                {
                    title: 'اسان جي ٽيم',
                    team: [
                        { name: 'ڪامران واحد', role: 'پراڊڪٽ ڊزائينر ۽ گڏيل باني', desc: 'پليٽفارم ڊزائن ۽ ٽيڪنيڪل ترقي' },
                        { name: 'عبيد ٿهيم', role: 'سافٽ ويئر انجنيئر ۽ گڏيل باني', desc: 'بيڪ اينڊ سسٽم ۽ ڊيٽابيس مينيجمينٽ' },
                        { name: 'چارو', role: 'مواد جو محافظ', desc: 'شاعري گڏ ڪرڻ، تصديق، ۽ دستاويز سازي' },
                    ]
                },
                {
                    title: 'اعترافات',
                    content: [
                        'هي پليٽفارم صدين جي شاعرانه ذهانت، پبلشرن ۽ لائبريرين جي وقف جي ڪري موجود آهي جن انهن ڪمن کي محفوظ ڪيو، ۽ معاصر پڙهندڙن جي جذبي جي ڪري جيڪي سنڌي ادب کي زنده رکندا آهن.',
                        'اسان شاعرن جي خاندانن ۽ جائدادن جا شڪرگذار آهيون جن اسان کي پنهنجي ادبي ورثي سان اعتماد ڪيو، ۽ وسيع سنڌي ڪميونٽي جو جن جي مدد هن ڪم کي ممڪن بڻائي ٿي.'
                    ]
                },
                {
                    title: 'اسان سان شامل ٿيو',
                    content: [
                        'چاهي توهان هڪ عالم آهيو تحقيق ڪندڙ، هڪ شاگرد سنڌي ادب دريافت ڪندڙ، هڪ شاعر الهام ڳوليندڙ، يا صرف ڪو جيڪو خوبصورت ٻولي سان پيار ڪري ٿو - باک توهان جو استقبال ڪري ٿو.',
                        'ڳوليو. دريافت ڪريو. محفوظ ڪريو.',
                        'گڏجي، اسان يقيني بڻائي رهيا آهيون ته سنڌي شاعري جا آواز نسلن تائين گونجندا رهن، هاڻي هڪ ڪلڪ جي فاصلي تي رسائي لائق، هميشه لاءِ ڊجيٽل دور ۾ محفوظ.'
                    ]
                },
                {
                    title: 'رابطو ۽ مدد',
                    content: [
                        'اسان توهان جي راءِ، تجويزن ۽ مدد جو استقبال ڪريون ٿا. اسان کي باک کي سنڌي شاعري جو حتمي آرڪائيو بنائڻ ۾ مدد ڪريو.',
                        'پليٽفارم جا حق: سڀ حق باک فائونڊيشن وٽ محفوظ آهن'
                    ]
                }
            ],
            quote: {
                text: 'Baakh - How long must this remain buried? Some treasure must surely emerge.',
                original: '"باک - ھِي اُونده ڪيسين رھڻي آ؟ ڪا باک نيٺ تہ ڦُٹڻِي آ"'
            }
        }
    };

    const currentContent = isRtl ? content.sd : content.en;

    return (
        <div className={`min-h-screen bg-white text-black ${isRtl ? 'text-right font-arabic' : 'text-left font-sans'}`}>
            {/* Minimal Header */}
            <header className="px-6 md:px-12 lg:px-24 py-8 flex items-center border-b border-gray-100">
                <Link to={`/${lang}`} className="hover:opacity-80 transition-opacity">
                    <Logo className="h-10 w-10 text-black" />
                </Link>
            </header>

            <div className="py-20 px-6 md:px-12 lg:px-24">
                <div className="max-w-4xl mx-auto space-y-24">

                    {/* Hero */}
                    <div className="space-y-6">
                        <h1 className="text-5xl md:text-7xl font-bold tracking-tight text-gray-900 leading-tight">
                            {currentContent.title}
                        </h1>
                    </div>

                    {/* Sections */}
                    <div className="space-y-20">
                        {currentContent.sections.map((section, index) => (
                            <section key={index} className="space-y-8">
                                <h2 className="text-3xl font-bold text-gray-900 border-b border-gray-200 pb-4 inline-block">
                                    {section.title}
                                </h2>

                                <div className="space-y-6 text-xl text-gray-700 leading-relaxed">
                                    {section.content?.map((text, i) => (
                                        <p key={i}>{text}</p>
                                    ))}

                                    {section.list && (
                                        <ul className="space-y-4 list-disc list-inside mt-6">
                                            {section.list.map((item, i) => (
                                                <li key={i} className="pl-2">{item}</li>
                                            ))}
                                        </ul>
                                    )}

                                    {section.subsections?.map((sub, i) => (
                                        <div key={i} className="mt-8 space-y-4">
                                            <h3 className="text-2xl font-semibold text-gray-900">{sub.subtitle}</h3>
                                            {sub.text && <p>{sub.text}</p>}
                                            {sub.list && (
                                                <ul className="space-y-3 list-disc list-inside">
                                                    {sub.list.map((item, j) => (
                                                        <li key={j} className="pl-2">{item}</li>
                                                    ))}
                                                </ul>
                                            )}
                                            {sub.nestedList && (
                                                <div className="space-y-8 mt-6">
                                                    {sub.nestedList.map((group, k) => (
                                                        <div key={k} className="bg-gray-50 p-6 rounded-2xl">
                                                            <h4 className="font-bold text-lg mb-4 text-gray-900">{group.label}</h4>
                                                            <ul className="space-y-2">
                                                                {group.items.map((item, l) => (
                                                                    <li key={l} className="flex items-start gap-2">
                                                                        <span className="mt-2 w-1.5 h-1.5 bg-gray-400 rounded-full shrink-0"></span>
                                                                        <span>{item}</span>
                                                                    </li>
                                                                ))}
                                                            </ul>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                            {sub.footer && <p className="text-gray-500 italic mt-4">{sub.footer}</p>}
                                        </div>
                                    ))}

                                    {section.nestedList && (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
                                            {section.nestedList.map((group, i) => (
                                                <div key={i}>
                                                    <h3 className="text-2xl font-bold mb-4 text-gray-900">{group.label}</h3>
                                                    <ul className="space-y-3">
                                                        {group.items.map((item, j) => (
                                                            <li key={j} className="flex items-start gap-2 border-b border-gray-100 pb-2 last:border-0">
                                                                <span>{item}</span>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {section.team && (
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mt-8">
                                            {section.team.map((member, i) => (
                                                <div key={i} className="bg-gray-50 p-8 rounded-3xl">
                                                    <h3 className="text-xl font-bold text-gray-900 text-center mb-2">{member.name}</h3>
                                                    <div className="text-sm font-semibold text-gray-500 uppercase tracking-wider text-center mb-4">{member.role}</div>
                                                    <p className="text-center text-gray-600 leading-snug">{member.desc}</p>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {section.footer && (
                                        <p className="font-medium text-gray-900 mt-6 border-l-4 border-black pl-4">
                                            {section.footer}
                                        </p>
                                    )}
                                </div>
                            </section>
                        ))}
                    </div>

                    {/* Footer Quote */}
                    <div className="border-t border-gray-100 pt-16 text-center space-y-4 pb-20">
                        <p className="text-2xl md:text-4xl font-serif font-medium text-gray-900">
                            {currentContent.quote.original}
                        </p>
                        <p className="text-lg text-gray-500 italic">
                            {currentContent.quote.text}
                        </p>
                    </div>

                </div>
            </div>
        </div>
    );
};

export default About;
