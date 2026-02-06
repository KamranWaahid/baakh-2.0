import React from 'react';
import { Link, useParams } from 'react-router-dom';
import Logo from '../components/Logo';

const Terms = () => {
    const { lang } = useParams();
    const isRtl = lang === 'sd';

    const content = {
        en: {
            title: 'Terms of Service',
            intro: 'By using the Baakh web portal, you accept the following terms and conditions. If you do not agree with them, please do not use the website.',
            sections: [
                {
                    title: '1. Purpose of Web Portal',
                    points: [
                        'Baakh is a non-profit and literary platform aimed at digitally collecting Sindhi poetry and making it accessible to general readers. The web portal is for personal and educational use only.'
                    ]
                },
                {
                    title: '2. Content Clarification',
                    points: [
                        'All content on Baakh, especially poetry, is not the property of Baakh. This content is the property of the respective poets, creators, or their heirs. Baakh is merely a platform for presenting poetry in digital form and claims no ownership or rights over any content.'
                    ]
                },
                {
                    title: '3. Content Usage',
                    points: [
                        'Users may read or save poetry for personal and non-commercial purposes only. Re-publication, reproduction, modification, or commercial use of the poetry is the user\'s own responsibility. Baakh accepts no responsibility for such use.'
                    ]
                },
                {
                    title: '4. Rights Complaints',
                    points: [
                        'If any poet, heir, or concerned party has an objection regarding any content, they may contact Baakh. Upon receiving a valid request, the content will be removed or corrected after investigation.'
                    ]
                },
                {
                    title: '5. User Account',
                    points: [
                        'By creating an account, users can use features like saving and liking poetry. Users are personally responsible for the security and use of their accounts.'
                    ]
                },
                {
                    title: '6. Service Changes',
                    points: [
                        'Baakh may make changes to the web portal, features, or policies at any time. The service may also be temporarily unavailable due to technical reasons.'
                    ]
                },
                {
                    title: '7. Limitation of Liability',
                    points: [
                        'Baakh does not guarantee the accuracy, completeness, or uninterrupted availability of content on the web portal. The web portal is provided on an "as is" basis.'
                    ]
                },
                {
                    title: '8. Acceptance of Terms',
                    points: [
                        'By using the web portal, you acknowledge that you have read, understood, and accept all these terms and conditions. Terms will be updated from time to time, and the new version will apply.'
                    ]
                }
            ]
        },
        sd: {
            title: 'شرطون ۽ ضابطا',
            intro: 'باک ويب پورٽل استعمال ڪرڻ سان، توهان هيٺ ڏنل شرطون ۽ ضابطا قبول ڪريو ٿا. جيڪڏهن توهان انهن سان متفق نه آهيو ته مهرباني ڪري ويب سائيٽ استعمال نه ڪريو.',
            sections: [
                {
                    title: '1. ويب پورٽل جو مقصد',
                    points: [
                        'باک هڪ غير منافع بخش ۽ ادبي پليٽفارم آهي، جنهن جو مقصد سنڌي شاعريءَ کي ڊجيٽل صورت ۾ گڏ ڪري عام پڙهندڙن تائين پهچائڻ آهي. ويب پورٽل صرف ذاتي ۽ تعليمي استعمال لاءِ آهي.'
                    ]
                },
                {
                    title: '2. مواد بابت وضاحت',
                    points: [
                        'باک تي موجود سمورو مواد، خاص ڪري شاعري، باک جي ملڪيت ناهي. هي مواد لاڳاپيل شاعرن، تخليقڪارن يا سندن وارثن جي ملڪيت آهي. باک صرف شاعريءَ کي ڊجيٽل صورت ۾ پيش ڪرڻ وارو پليٽفارم آهي ۽ ڪنهن به مواد تي مالڪيءَ يا حقن جي دعويٰ نٿو ڪري.'
                    ]
                },
                {
                    title: '3. مواد جو استعمال',
                    points: [
                        'يوزر شاعري صرف ذاتي ۽ غير تجارتي مقصدن لاءِ پڙهي يا محفوظ ڪري سگهن ٿا. شاعريءَ جو ٻيهر اشاعت، نقل، ترميم يا تجارتي استعمال يوزر جي پنهنجي ذميواري هوندي. باک اهڙي استعمال جي ڪا ذميواري قبول نٿو ڪري.'
                    ]
                },
                {
                    title: '4. حقن بابت شڪايتون',
                    points: [
                        'جيڪڏهن ڪنهن شاعر، وارث يا لاڳاپيل ڌر کي ڪنهن مواد بابت اعتراض هجي ته هو باک سان رابطو ڪري سگهي ٿو. جائز درخواست ملڻ تي، مواد کي جاچ بعد هٽايو يا درست ڪيو ويندو.'
                    ]
                },
                {
                    title: '5. يوزر اڪائونٽ',
                    points: [
                        'اڪائونٽ ٺاهڻ سان يوزر شاعري محفوظ ڪرڻ ۽ پسند ڪرڻ جون سهولتون استعمال ڪري سگهي ٿو. يوزر پنهنجي اڪائونٽ جي حفاظت ۽ استعمال جو پاڻ ذميوار هوندو.'
                    ]
                },
                {
                    title: '6. سروس ۾ تبديليون',
                    points: [
                        'باک ڪنهن به وقت ويب پورٽل، سهولتن يا پاليسين ۾ تبديليون آڻي سگهي ٿو. ٽيڪنيڪل سببن ڪري سروس عارضي طور دستياب نه به ٿي سگهي ٿي.'
                    ]
                },
                {
                    title: '7. ذميواري جي حد',
                    points: [
                        'باک ويب پورٽل تي موجود مواد جي درستگي، مڪمل هجڻ يا هر وقت دستياب هجڻ جي ضمانت نٿو ڏئي. ويب پورٽل موجوده حالت ۾ مهيا ڪيو وڃي ٿو.'
                    ]
                },
                {
                    title: '8. شرطون قبول ڪرڻ',
                    points: [
                        'ويب پورٽل استعمال ڪرڻ سان، توهان انهن سڀني شرطون ۽ ضابطا پڙهي، سمجهي ۽ قبول ڪريو ٿا. شرطون وقت بوقت اپڊيٽ ٿينديون ۽ نئون ورزن لاڳو هوندو.'
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

export default Terms;
