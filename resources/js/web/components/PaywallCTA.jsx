import React from 'react';
import { Button } from '@/components/ui/button';
import { Sparkles } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

const PaywallCTA = ({ authorName, categoryName, isRtl }) => {
    const benefits = isRtl ? [
        "نون شاعرن جا بيت، غزل ۽ نظم بہ ساڳي ئي پيج تان رسائي حاصل ڪري سگھو ٿا.",
        "پنهنجي دلچسپيءَ وارن موضوعن مطابق شاعر چونڊيو ۽ سندن مڪمل شاعري ڏسو.",
        "سنڌ جي ڪلاسيڪل شاعرن جي شاعري پڙھو."
    ] : [
        "Access Bayts, Ghazals, and Nazms from new poets on the same page.",
        "Choose poets according to your interests and explore their complete poetry.",
        "Read poetry from the classical poets of Sindh."
    ];

    const members = [
        { name: "Steve Yegge", title: "ex-Geoworks, ex-Amazon", img: "SY" },
        { name: "Carlos Arguelles", title: "Sr. Staff Engineer", img: "CA" },
        { name: "Tony Yiu", title: "Director, Nasdaq", img: "TY" },
        { name: "Brandeis Marshall", title: "CEO, DataedX", img: "BM" },
        { name: "Cassie Kozyrkov", title: "Chief Decision Scientist", img: "CK" },
        { name: "The Secret Developer", title: "Software Developer", img: "SD" },
        { name: "Austin Starks", title: "Software Engineer", img: "AS" },
        { name: "Camille Fournier", title: "Head of Engineering", img: "CF" },
    ];

    return (
        <div className="w-full max-w-4xl mx-auto py-20 px-4 text-center">
            {/* Headline: Serif, Not Bold, Smaller */}
            <h3 className={`text-3xl md:text-5xl font-normal mb-8 text-gray-900 leading-tight font-serif ${isRtl ? 'font-arabic' : ''}`}>
                {isRtl
                    ? <>{authorName} جي هن {categoryName} کان پوءِ، هي ٻيا شاعر به پڙهڻ جھڙا آهن:</>
                    : <>After reading this {categoryName || 'poem'} by {authorName}, here are other poets worth reading:</>
                }
            </h3>

            {/* Body: Inter (Sans) */}
            <p className={`text-[17px] text-gray-600 mb-14 max-w-2xl mx-auto leading-relaxed ${isRtl ? 'font-arabic' : 'font-sans'}`}>
                {isRtl ? 'باک تي ٻيا مشھور شاعر ڏسو ۽ سندن شاعري پڙهو.' : 'Discover and read poetry from other famous poets on Baakh.'}
            </p>

            {/* List: Inter (Sans) */}
            <ul className={`mb-20 space-y-4 text-left max-w-[600px] mx-auto ${isRtl ? 'text-right font-arabic' : 'font-sans'}`} dir={isRtl ? 'rtl' : 'ltr'}>
                {benefits.map((benefit, index) => (
                    <li key={index} className="flex items-start gap-3 text-[16px] text-gray-900 leading-snug">
                        <Sparkles className="h-4 w-4 text-yellow-500 fill-yellow-500 shrink-0 mt-1" />
                        <span>{benefit}</span>
                    </li>
                ))}
            </ul>

            {/* Avatars: Names in Inter (Sans) */}
            <div className={`grid grid-cols-2 md:grid-cols-4 gap-y-12 gap-x-8 mb-20 justify-items-center ${isRtl ? 'font-sans' : 'font-sans'}`}>
                {members.map((member, i) => (
                    <div key={i} className="flex flex-col items-center text-center">
                        <Avatar className="h-[74px] w-[74px] mb-4 border-2 border-white shadow-sm ring-1 ring-gray-100">
                            <AvatarFallback className="bg-gray-100 text-gray-500 font-bold text-lg font-sans">{member.img}</AvatarFallback>
                        </Avatar>
                        <span className="text-gray-900 text-[15px] leading-tight mb-1">{member.name}</span>
                        <span className="text-[11px] text-gray-500 leading-tight max-w-[140px]">{member.title}</span>
                    </div>
                ))}
            </div>

            {/* Button: Inter (Sans) for consistency */}
            <Button className="rounded-full bg-black hover:bg-gray-800 text-white font-medium text-lg h-12 px-10 min-w-[200px] font-sans">
                {isRtl ? 'وڌيڪ ڏسو' : 'See More'}
            </Button>
        </div>
    );
};

export default PaywallCTA;
