import React from 'react';
import { Link, useParams } from 'react-router-dom';
import Logo from '../components/Logo';

const Contact = () => {
    const { lang } = useParams();
    const isRtl = lang === 'sd';

    const content = {
        en: {
            title: 'Contact Baakh',
            intro: 'Baakh is a non-profit digital archive of Sindhi poetry operated by Baakh Foundation. Use this page to reach the team about the archive, a poet or poem listing, privacy requests, or corrections. We read every message sent to the public support address below. This is the public contact page for baakh.com — not a commercial sales desk.',
            sections: [
                {
                    title: 'Email',
                    content: [
                        'Write to support@baakh.com for archive questions, listing corrections, takedown or rights requests, account and privacy requests, and press or partnership notes.',
                        'Please include the public URL of any poet or poem you are writing about (for example /en/poet/{slug} or /sd/poet/{poet}/{genre}/{poem}) so we can find the record quickly. We reply in English or Sindhi.',
                    ],
                },
                {
                    title: 'Postal address',
                    content: [
                        'Office 428, 4th Floor, Mashreq Center, Expo Center, Karachi, Sindh, Pakistan.',
                        'Mail sent to this address should name Baakh Foundation and baakh.com. There is no public telephone number for the archive; email is the reliable channel.',
                    ],
                },
                {
                    title: 'What this address is for',
                    list: [
                        'Corrections to a poet biography, poem text, genre, or transliteration',
                        'Rights, estate, or takedown requests for a work in the archive',
                        'Privacy questions, including account deletion (see also the Privacy page)',
                        'Research, classroom, or library use of the archive',
                        'Reporting a broken public URL',
                    ],
                },
                {
                    title: 'What this address is not for',
                    content: [
                        'Song lyrics and singer pages live on lyrics.baakh.com (Bol), not on this archive. General news, commercial book orders, and unrelated software support are outside Baakh’s scope.',
                    ],
                },
                {
                    title: 'Related pages',
                    content: [
                        'About explains the archive’s mission and history. Privacy describes what account data we store. Help is a reader guide to browsing poets, genres, and search. Agents should start from llms.txt or sitemap.xml rather than probing unknown paths.',
                    ],
                },
            ],
        },
        sd: {
            title: 'باک سان رابطو',
            intro: 'باک، باک فائونڊيشن جو هڪ غير منافع بخش ڊجيٽل آرڪائيو آهي جتي سنڌي شاعري محفوظ آهي. آرڪائيو، شاعر يا شعر جي فهرست، رازداري، يا درستي بابت ٽيم تائين پهچڻ لاءِ هي صفحو استعمال ڪريو. هيٺ ڏنل پبلڪ سپورٽ اي ميل تي موڪليل هر پيغام پڙهيو وڃي ٿو. هي baakh.com جو پبلڪ رابطي جو صفحو آهي — تجارتي سيلز ڊيسڪ ناهي.',
            sections: [
                {
                    title: 'اي ميل',
                    content: [
                        'آرڪائيو جا سوال، فهرست جي درستي، حق يا هٽائڻ جون درخواستون، اڪائونٽ ۽ رازداري، ۽ پريس يا ڀائيواري لاءِ support@baakh.com تي لکو.',
                        'جنهن شاعر يا شعر بابت لکو، ان جو پبلڪ URL شامل ڪريو (مثال /sd/poet/{slug}) ته رڪارڊ جلد ملي. اسان انگريزي يا سنڌيءَ ۾ جواب ڏيون ٿا.',
                    ],
                },
                {
                    title: 'پوسٽل پتو',
                    content: [
                        'آفيس 428، چوٿون منزل، مشرق سينٽر، ايڪسپو سينٽر، ڪراچي، سنڌ، پاڪستان.',
                        'هن پتي تي موڪليل ڊاک تي باک فائونڊيشن ۽ baakh.com لکيو. آرڪائيو لاءِ پبلڪ فون نمبر ناهي؛ اي ميل قابل اعتماد ذريعو آهي.',
                    ],
                },
                {
                    title: 'هي پتو ڪهڙن ڪمن لاءِ آهي',
                    list: [
                        'شاعر جي سوانح، شعر جي متن، صنف، يا رومن ۾ درستي',
                        'آرڪائيو ۾ ڪم لاءِ حق، ورثي، يا هٽائڻ جون درخواستون',
                        'رازداري جا سوال، بشمول اڪائونٽ ختم ڪرڻ (رازداري صفحو به ڏسو)',
                        'تحقيق، ڪلاس، يا لائبريري ۾ آرڪائيو جو استعمال',
                        'ٽٽل پبلڪ URL جي رپورٽ',
                    ],
                },
                {
                    title: 'هي پتو ڪهڙن ڪمن لاءِ ناهي',
                    content: [
                        'گيت ۽ ڳائڻن جا صفحا lyrics.baakh.com (بول) تي آهن، هن آرڪائيو تي نه. عام خبرون، ڪتابن جو واپار، ۽ غير لاڳاپيل سافٽ ويئر سپورٽ باک جي دائري کان ٻاهر آهن.',
                    ],
                },
                {
                    title: 'لاڳاپيل صفحا',
                    content: [
                        'بابت صفحي تي مشن ۽ تاريخ آهي. رازداري صفحي تي اڪائونٽ ڊيٽا بيان آهي. مدد صفحو شاعر، صنف ۽ ڳولا لاءِ رهنمائي آهي. ايجنٽس کي اڻڄاتل رستا آزمائڻ بدران llms.txt يا sitemap.xml سان شروع ڪرڻ گهرجي.',
                    ],
                },
            ],
        },
    };

    const currentContent = isRtl ? content.sd : content.en;
    const privacyHref = `/${lang}/privacy`;
    const aboutHref = `/${lang}/about`;
    const helpHref = `/${lang}/help`;

    return (
        <div className={`min-h-screen bg-white text-black ${isRtl ? 'text-right font-arabic' : 'text-left font-sans'}`}>
            <header className="px-5 md:px-12 lg:px-24 py-6 md:py-8 flex items-center border-b border-gray-100">
                <Link to={`/${lang}`} className="hover:opacity-80 transition-opacity">
                    <Logo className="h-8 w-8 md:h-10 md:w-10 text-black" />
                </Link>
            </header>

            <div className="py-12 md:py-20 px-5 md:px-12 lg:px-24">
                <div className="max-w-3xl mx-auto space-y-12 md:space-y-16">
                    <div>
                        <h1 className="text-3xl md:text-5xl font-bold tracking-tight mb-6 leading-tight">
                            {currentContent.title}
                        </h1>
                        <p className="text-lg md:text-xl text-gray-600 leading-relaxed font-medium">
                            {currentContent.intro}
                        </p>
                    </div>

                    <div className="space-y-12">
                        {currentContent.sections.map((section, index) => (
                            <section key={index} className="space-y-4">
                                <h2 className="text-2xl font-bold text-gray-900">{section.title}</h2>
                                {section.content?.map((text, i) => (
                                    <p key={i} className="text-lg text-gray-700 leading-relaxed">{text}</p>
                                ))}
                                {section.list && (
                                    <ul className="space-y-3 list-disc list-inside text-lg text-gray-700 leading-relaxed">
                                        {section.list.map((item, i) => (
                                            <li key={i}>{item}</li>
                                        ))}
                                    </ul>
                                )}
                            </section>
                        ))}
                    </div>

                    <p className="text-sm text-gray-500">
                        <Link to={aboutHref} className="underline hover:text-black">{isRtl ? 'بابت' : 'About'}</Link>
                        {' · '}
                        <Link to={privacyHref} className="underline hover:text-black">{isRtl ? 'رازداري' : 'Privacy'}</Link>
                        {' · '}
                        <Link to={helpHref} className="underline hover:text-black">{isRtl ? 'مدد' : 'Help'}</Link>
                    </p>
                </div>
            </div>
        </div>
    );
};

export default Contact;
