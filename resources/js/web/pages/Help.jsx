import React from 'react';
import { Link, useParams } from 'react-router-dom';
import Logo from '../components/Logo';

const Help = () => {
    const { lang } = useParams();
    const isRtl = lang === 'sd';

    const content = {
        en: {
            title: 'Help & Guide',
            intro: 'Welcome to the Baakh Help Center. This guide will help you navigate and make the most of the platform\'s features.',
            sections: [
                {
                    title: '1. Getting Started',
                    content: [
                        'Baakh is a comprehensive digital archive of Sindhi poetry. You can explore thousands of poems from classical to contemporary poets, organized by genre, theme, period, and poet.',
                        'No account is needed to read poetry. However, creating an account allows you to save favorites, create collections, and participate in discussions.'
                    ]
                },
                {
                    title: '2. Browsing Poetry',
                    subsections: [
                        {
                            subtitle: 'By Poet',
                            text: 'Click on "Poets" in the main menu to browse alphabetically or by category (Classical, Sufi, Revolutionary, Contemporary, Emerging). Click any poet to view their biography and complete works.'
                        },
                        {
                            subtitle: 'By Genre',
                            text: 'Explore distinct forms like Ghazal, Nazm, Waai, and Bait. Each genre page provides historical context and structural examples.'
                        },
                        {
                            subtitle: 'By Theme & Period',
                            text: 'Discover poetry through themes (Love, Homeland, Nature) or travel through time by browsing historical periods in the "Period" section.'
                        }
                    ]
                },
                {
                    title: '3. Search Features',
                    content: [
                        'Use the search bar at the top of any page to find poets, poems, or specific keywords. You can search in both Sindhi and English/Roman scripts.',
                        'Pro Tip: Press Cmd+K (Mac) or Ctrl+K (Windows) to instantly open the search dialog.'
                    ]
                },
                {
                    title: '4. User Account',
                    list: [
                        'Register: Click "Sign in" or "Get started" to create an account.',
                        'Profile: Manage your personal details and settings.',
                        'Favorites: Save poems by clicking the heart icon (♡).',
                        'Collections: Organize your favorite poetry into custom lists.'
                    ]
                },
                {
                    title: '5. Language & Script',
                    content: [
                        'Baakh offers a dual-language experience:',
                        '• Sindhi (سنڌي): Traditional Arabic-Persian script.',
                        '• English/Roman: English interface with Romanized Sindhi support.',
                        'To switch, simply click the language toggle (English / سنڌي) in the navigation bar. Your preference is automatically saved.'
                    ]
                },
                {
                    title: '6. Mobile Experience',
                    content: [
                        'Baakh is fully optimized for mobile devices. Enjoy a responsive design that fits all screen sizes, touch-friendly navigation, and swipe gestures for browsing content.',
                        'Tip: Use "Add to Home Screen" on your mobile browser for an app-like experience.'
                    ]
                },
                {
                    title: '7. Troubleshooting',
                    nestedList: [
                        {
                            label: 'Text Display Issues',
                            items: [
                                'Ensure your browser supports Sindhi fonts.',
                                'Try switching the language toggle.',
                                'Clear browser cache if styles load incorrectly.'
                            ]
                        },
                        {
                            label: 'Login Problems',
                            items: [
                                'Verify your email and password.',
                                'Use "Forgot Password" to reset credentials.',
                                'Ensure your browser accepts cookies.'
                            ]
                        }
                    ]
                },
                {
                    title: '8. Contact & Support',
                    content: [
                        'Need more help? We are here to assist you.',
                        'Email us at: support@baakh.com',
                        'Join our community on social media for updates and tips.'
                    ]
                }
            ]
        },
        sd: {
            title: 'مدد ۽ رهنمائي',
            intro: 'باک مدد سينٽر ۾ ڀليڪار. هي رهنمائي توهان کي پليٽفارم جي خاصيتن کي استعمال ڪرڻ ۽ ان مان وڌ کان وڌ فائدو حاصل ڪرڻ ۾ مدد ڪندي.',
            sections: [
                {
                    title: '1. شروعات ڪرڻ',
                    content: [
                        'باک سنڌي شاعري جو هڪ جامع ڊجيٽل آرڪائيو آهي. توهان ڪلاسيڪي کان معاصر شاعرن تائين هزارين شعر ڳولي سگهو ٿا، جيڪي صنف، موضوع، دور ۽ شاعر جي لحاظ کان منظم ٿيل آهن.',
                        'شاعري پڙهڻ لاءِ اڪائونٽ جي ضرورت ناهي. تنهن هوندي، اڪائونٽ ٺاهڻ توهان کي پسنديده محفوظ ڪرڻ، مجموعا ٺاهڻ ۽ بحثن ۾ حصو وٺڻ جي اجازت ڏئي ٿو.'
                    ]
                },
                {
                    title: '2. شاعري ڳولڻ',
                    subsections: [
                        {
                            subtitle: 'شاعر جي لحاظ کان',
                            text: 'مکيه مينيو ۾ "شاعر" تي ڪلڪ ڪريو ۽ حروف تهجي يا درجي (ڪلاسيڪل، صوفي، انقلابي، نوجوان، نئين ٽهي) جي لحاظ کان ڳوليو. ڪنهن به شاعر تي ڪلڪ ڪري سندن سوانح عمري ۽ مڪمل ڪم ڏسو.'
                        },
                        {
                            subtitle: 'صنف جي لحاظ کان',
                            text: 'مختلف صنفن جهڙوڪ غزل، نظم، وائي ۽ بيت کي ڳوليو. هر صنف وارو صفحو تاريخي سياق ۽ ساخت جا مثال فراهم ڪري ٿو.'
                        },
                        {
                            subtitle: 'موضوع ۽ دور جي لحاظ کان',
                            text: 'موضوعن (محبت، وطن، قدرت) ذريعي شاعري دريافت ڪريو يا "دور" سيڪشن ۾ مختلف تاريخي دورن جو سفر ڪريو.'
                        }
                    ]
                },
                {
                    title: '3. ڳولا جون خاصيتون',
                    content: [
                        'ڪنهن به صفحي جي مٿي تي موجود ڳولا بار استعمال ڪريو شاعر، شعر يا مخصوص لفظ ڳولڻ لاءِ. توهان سنڌي ۽ انگريزي/رومن ٻنهي رسم الخط ۾ ڳولي سگهو ٿا.',
                        'ٽپ: ڳولا ڊائلاگ فوري طور کولڻ لاءِ Ctrl+K يا Cmd+K دٻايو.'
                    ]
                },
                {
                    title: '4. صارف اڪائونٽ',
                    list: [
                        'رجسٽر: اڪائونٽ ٺاهڻ لاءِ "لاگ ان" يا "شروعات ڪريو" تي ڪلڪ ڪريو.',
                        'پروفائل: پنهنجي ذاتي تفصيل ۽ سيٽنگن کي منظم ڪريو.',
                        'پسنديده: دل جي آئڪن (♡) تي ڪلڪ ڪري شعر محفوظ ڪريو.',
                        'مجموعا: پنهنجي پسنديده شاعري کي ڪسٽم لسٽن ۾ ترتيب ڏيو.'
                    ]
                },
                {
                    title: '5. ٻولي ۽ رسم الخط',
                    content: [
                        'باک ٻن ٻولين جو تجربو پيش ڪري ٿو:',
                        '• سنڌي: روايتي عربي-فارسي رسم الخط.',
                        '• انگريزي/رومن: انگريزي انٽرفيس رومن سنڌي سپورٽ سان.',
                        'تبديل ڪرڻ لاءِ، صرف نيويگيشن بار ۾ ٻولي ٽوگل (English / سنڌي) تي ڪلڪ ڪريو. توهان جي ترجيح خودڪار طور محفوظ ٿي ويندي آهي.'
                    ]
                },
                {
                    title: '6. موبائيل تجربو',
                    content: [
                        'باک موبائيل ڊوائيسز لاءِ مڪمل طور تي بهتر ڪيو ويو آهي. سڀني اسڪرين سائزن لاءِ جوابي ڊزائن، رابطي لاءِ آسان نيويگيشن ۽ مواد کي براؤز ڪرڻ لاءِ سوائپ اشارن مان لطف اندوز ٿيو.',
                        'ٽپ: ايپ جهڙو تجربو حاصل ڪرڻ لاءِ پنهنجي موبائيل براؤزر تي "Add to Home Screen" استعمال ڪريو.'
                    ]
                },
                {
                    title: '7. مسئلا حل ڪرڻ',
                    nestedList: [
                        {
                            label: 'متن ڏيکارڻ جا مسئلا',
                            items: [
                                'پڪ ڪريو ته توهان جو برائوزر سنڌي فونٽس سپورٽ ڪري ٿو.',
                                'ٻولي ٽوگل تبديل ڪرڻ جي ڪوشش ڪريو.',
                                'جيڪڏهن اسٽائل غلط لوڊ ٿي رهيا آهن ته برائوزر ڪيش صاف ڪريو.'
                            ]
                        },
                        {
                            label: 'لاگ ان جا مسئلا',
                            items: [
                                'پنهنجي اي ميل ۽ پاسورڊ جي تصديق ڪريو.',
                                'پاسورڊ ري سيٽ ڪرڻ لاءِ "Forgot Password" استعمال ڪريو.',
                                'پڪ ڪريو ته توهان جو برائوزر ڪوڪيز قبول ڪري ٿو.'
                            ]
                        }
                    ]
                },
                {
                    title: '8. رابطو ۽ مدد',
                    content: [
                        'وڌيڪ مدد جي ضرورت آهي؟ اسان توهان جي مدد لاءِ موجود آهيون.',
                        'اسان کي اي ميل ڪريو: support@baakh.com',
                        'اپڊيٽس ۽ ٽوٽڪن لاءِ سوشل ميڊيا تي اسان جي ڪميونٽي ۾ شامل ٿيو.'
                    ]
                }
            ]
        }
    };

    const currentContent = isRtl ? content.sd : content.en;

    return (
        <div className={`min-h-screen bg-white text-black ${isRtl ? 'text-right font-arabic' : 'text-left font-sans'}`}>
            {/* Minimal Header */}
            <header className="px-5 md:px-12 lg:px-24 py-6 md:py-8 flex items-center border-b border-gray-100">
                <Link to={`/${lang}`} className="hover:opacity-80 transition-opacity">
                    <Logo className="h-8 w-8 md:h-10 md:w-10 text-black" />
                </Link>
            </header>

            <div className="py-12 md:py-20 px-5 md:px-12 lg:px-24">
                <div className="max-w-4xl mx-auto space-y-16 md:space-y-24">

                    {/* Header */}
                    <div>
                        <h1 className="text-3xl md:text-5xl font-bold tracking-tight mb-6 leading-tight">
                            {currentContent.title}
                        </h1>
                        <p className="text-lg md:text-xl text-gray-600 leading-relaxed font-medium">
                            {currentContent.intro}
                        </p>
                    </div>

                    {/* Content Sections */}
                    <div className="space-y-16">
                        {currentContent.sections.map((section, index) => (
                            <section key={index} className="space-y-6">
                                <h2 className="text-2xl md:text-3xl font-bold text-gray-900 border-b border-gray-200 pb-4 inline-block">
                                    {section.title}
                                </h2>

                                {/* Regular Content Paragraphs */}
                                {section.content && (
                                    <div className="space-y-4 text-lg md:text-xl text-gray-700 leading-relaxed">
                                        {section.content.map((text, i) => (
                                            <p key={i}>{text}</p>
                                        ))}
                                    </div>
                                )}

                                {/* Simple Lists */}
                                {section.list && (
                                    <ul className="space-y-3 list-disc list-inside text-lg md:text-xl text-gray-700 leading-relaxed mt-4">
                                        {section.list.map((item, i) => (
                                            <li key={i} className={`${isRtl ? 'pr-2' : 'pl-2'}`}>{item}</li>
                                        ))}
                                    </ul>
                                )}

                                {/* Subsections */}
                                {section.subsections && (
                                    <div className="space-y-8 mt-6">
                                        {section.subsections.map((sub, i) => (
                                            <div key={i} className="bg-gray-50 rounded-2xl p-6 md:p-8">
                                                <h3 className="text-xl font-bold text-gray-900 mb-3">{sub.subtitle}</h3>
                                                <p className="text-gray-700 leading-relaxed text-lg">{sub.text}</p>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {/* Nested Lists (Troubleshooting/Features) */}
                                {section.nestedList && (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                        {section.nestedList.map((group, i) => (
                                            <div key={i} className="border border-gray-100 rounded-2xl p-6">
                                                <h3 className="text-lg font-bold mb-4 text-gray-900">{group.label}</h3>
                                                <ul className="space-y-2 text-gray-700">
                                                    {group.items.map((item, j) => (
                                                        <li key={j} className="flex items-start gap-2">
                                                            <span className="mt-2 w-1.5 h-1.5 bg-gray-400 rounded-full shrink-0"></span>
                                                            <span>{item}</span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </section>
                        ))}
                    </div>

                </div>
            </div>
        </div>
    );
};

export default Help;
