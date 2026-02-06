import React from 'react';
import { Link, useParams } from 'react-router-dom';
import Logo from '../components/Logo';

const Privacy = () => {
    const { lang } = useParams();
    const isRtl = lang === 'sd';

    const content = {
        en: {
            title: 'Privacy Policy',
            intro: 'Baakh is an independent, voluntary, non-governmental, and non-profit web archive platform. Therefore, we do not need to use anyone\'s data for marketing or profit. We take the protection of your personal information very seriously.',
            sections: [
                {
                    title: '1. What information do we collect?',
                    points: [
                        'Your account data is used only for Liking and Saving poetry.',
                        'Other information like email will not be used for any other purpose.'
                    ]
                },
                {
                    title: '2. Purpose of Data Usage',
                    points: [
                        'Improving User Experience (UX)',
                        'Counting poetry likes',
                        'Maintaining a list of saved poetry'
                    ]
                },
                {
                    title: '3. Data Security',
                    points: [
                        'End-to-End Encryption (E2EE) is used, so your data is fully encrypted.',
                        'The Baakh team does not have access to view your personal identity.',
                        'You can view how your data is stored on Baakh in your account settings.'
                    ]
                },
                {
                    title: '4. No Sharing with Third Parties',
                    points: [
                        'We do not share your data with any third party.',
                        'Your data will not be used for any advertising or marketing.'
                    ]
                },
                {
                    title: '5. Cookies',
                    points: [
                        'Only limited and necessary cookies are used to ensure the website functions correctly and to improve user experience.',
                        'Your personal identity is not obtained from cookies.'
                    ]
                },
                {
                    title: '6. Data Deletion',
                    points: [
                        'Users have the right to have their account and related data deleted.',
                        'You can decline to use Baakh services in the future.'
                    ]
                },
                {
                    title: '7. Changes to Policy',
                    points: [
                        'Updates will be made from time to time to include new security measures or features.',
                        'Updated policies will be displayed on the website.'
                    ]
                }
            ]
        },
        sd: {
            title: 'پرائيويسي پاليسي',
            intro: 'باک هڪ آزاد، زضاڪارانہ، غير سرڪاري ۽ غير منافع بخش ويب آرڪائيوَ پليٽفارم آهي. انڪري اسان کي ڪنھن بہ انسان جي ڊيٽا کي مارڪيٽنگ، يا منافعي لاءِ استعمال ضرورت ناھي. اسان توهان جي ذاتي معلومات جي حفاظت کي انتھائي سنجيدگيءَ سان وٺون ٿا.',
            sections: [
                {
                    title: '1. اسان ڪهڙي معلومات گڏ ڪريون ٿا؟',
                    points: [
                        'توهان جي اڪائونٽ جو ڊيٽا صرف پسند (Like) ڪرڻ ۽ شاعري محفوظ ڪرڻ لاءِ استعمال ٿئي ٿو.',
                        'ٻين معلومات جهڙوڪ اي ميل ڪنهن ٻئي مقصد لاءِ استعمال نه ٿيندي.'
                    ]
                },
                {
                    title: '2. ڊيٽا استعمال ڪرڻ جو مقصد',
                    points: [
                        'يوزر تجربو بهتر ڪرڻ (UX)',
                        'شاعريءَ جي پسنديدگي ڳڻڻ',
                        'محفوظ ٿيل شاعري جي فهرست رکڻ'
                    ]
                },
                {
                    title: '3. ڊيٽا جي حفاظت',
                    points: [
                        'End-to-End Encryption (E2EE) استعمال ڪيو ويو آهي، تنهنڪري توهان جي ڊيٽا مڪمل طور تي انڪرپٽ ٿيل آهي.',
                        'باک جي ٽيم کي توهان جي ذاتي سڃاڻپ ڏسڻ جي رسائي ناهي.',
                        'اوھان جي ڊيٽا باک ۾ ڪھڙي شڪل ۾ محفوظ ٿيل آھي اوھان پنھنجي کاتي جي سيٽنگ ۾ ڏسي سگھو ٿا.'
                    ]
                },
                {
                    title: '4. ٽئين ڌرين سان شيئر نه ڪرڻ',
                    points: [
                        'اسان توهان جو ڊيٽا ڪنهن به ٽئين ڌر سان شيئر نه ڪندا آهيون.',
                        'ڪو به اشتهار يا مارڪيٽنگ لاءِ توهان جي ڊيٽا استعمال نه ٿيندي.'
                    ]
                },
                {
                    title: '5. ڪوڪيز (Cookies)',
                    points: [
                        'صرف محدود ۽ ضروري ڪوڪيز استعمال ٿينديون آهن، جيئن ويب سائيٽ صحيح ڪم ڪري ۽ يوزر تجربو بهتر ٿئي.',
                        'ڪوڪيز مان توهان جي ذاتي سڃاڻپ حاصل نه ڪئي وڃي ٿي.'
                    ]
                },
                {
                    title: '6. ڊيٽا حذف ڪرڻ',
                    points: [
                        'يوزر کي حق حاصل آهي ته هو پنهنجي اڪائونٽ ۽ لاڳاپيل ڊيٽا حذف ڪرائي.',
                        'مستقبل ۾ باک جون خدمتون استعمال ڪرڻ کان انڪار ڪري سگهجي ٿو.'
                    ]
                },
                {
                    title: '7. پاليسي ۾ تبديليون',
                    points: [
                        'وقت بوقت اپڊيٽون ڪيون وينديون، جيئن نيون سيڪيورٽي تدابير يا فيچرز شامل ٿين.',
                        'اپڊيٽ ٿيل پاليسي ويب سائيٽ تي ظاهر ڪئي ويندي.'
                    ]
                }
            ]
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
                <div className="max-w-3xl mx-auto space-y-12">
                    <div>
                        <h1 className="text-4xl md:text-5xl font-bold tracking-tight mb-6">
                            {currentContent.title}
                        </h1>
                        <p className="text-xl text-gray-600 leading-relaxed font-medium">
                            {currentContent.intro}
                        </p>
                    </div>

                    <div className="space-y-12">
                        {currentContent.sections.map((section, index) => (
                            <div key={index} className="space-y-4">
                                <h2 className="text-2xl font-bold text-gray-900">
                                    {section.title}
                                </h2>
                                <ul className="space-y-3">
                                    {section.points.map((point, pointIndex) => (
                                        <li key={pointIndex} className="flex items-start gap-3 text-lg text-gray-700 leading-relaxed">
                                            <span className="mt-2.5 w-1.5 h-1.5 rounded-full bg-gray-400 shrink-0"></span>
                                            <span>{point}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Privacy;
