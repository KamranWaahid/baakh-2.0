import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Sparkles, User } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

const PaywallCTA = ({ authorName, categoryName, poets = [], isRtl }) => {
    const benefits = isRtl ? [
        "نون شاعرن جا بيت، غزل ۽ نظم بہ ساڳي ئي پيج تان رسائي حاصل ڪري سگھو ٿا.",
        "پنهنجي دلچسپيءَ وارن موضوعن مطابق شاعر چونڊيو ۽ سندن مڪمل شاعري ڏسو.",
        "سنڌ جي ڪلاسيڪل شاعرن جي شاعري پڙھو."
    ] : [
        "Access Bayts, Ghazals, and Nazms from new poets on the same page.",
        "Choose poets according to your interests and explore their complete poetry.",
        "Read poetry from the classical poets of Sindh."
    ];

    // Use dynamic poets if available, otherwise fallback (or show empty)
    // For now we assume dynamic data is passed.
    const displayPoets = poets && poets.length > 0 ? poets : [];

    return (
        <div className="w-full max-w-4xl mx-auto py-20 px-4 text-center">
            {/* Headline: Serif, Not Bold, Smaller */}
            <h3 className={`text-3xl md:text-5xl font-normal mb-8 text-gray-900 leading-tight font-serif ${isRtl ? 'font-arabic' : ''}`}>
                {isRtl
                    ? <>{authorName} جي هن {categoryName} کان پوءِ، هي ٻيا شاعر بہ پڙهڻ جھڙا آهن:</>
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
            {displayPoets.length > 0 && (
                <div className={`grid grid-cols-2 md:grid-cols-4 gap-y-12 gap-x-8 mb-20 justify-items-center ${isRtl ? 'font-arabic' : 'font-sans'}`}>
                    {displayPoets.map((poet, i) => (
                        <div key={i} className="flex flex-col items-center text-center">
                            <Link to={`/${isRtl ? 'sd' : 'en'}/poet/${poet.slug}`} className="group">
                                <Avatar className="h-[74px] w-[74px] mb-4 border-2 border-white shadow-sm ring-1 ring-gray-100 transition-all">
                                    {poet.img ? (
                                        <AvatarImage src={poet.img} className="object-cover" />
                                    ) : (
                                        <div className="h-full w-full bg-gray-50 flex items-center justify-center text-gray-400">
                                            <User className="h-8 w-8" />
                                        </div>
                                    )}
                                </Avatar>
                            </Link>
                            <span className="text-gray-900 text-[15px] leading-tight mb-1 font-bold">{poet.name}</span>
                            <span className="text-[11px] text-gray-500 leading-tight max-w-[140px]">{poet.title}</span>
                        </div>
                    ))}
                </div>
            )}

            {/* Button: Inter (Sans) for consistency */}
            {/* Button: Inter (Sans) for consistency */}
            <Link to={`/${isRtl ? 'sd' : 'en'}/poets`}>
                <Button className="rounded-full bg-black hover:bg-gray-800 text-white font-medium text-lg h-12 px-10 min-w-[200px] font-sans">
                    {isRtl ? 'وڌيڪ ڏسو' : 'See More'}
                </Button>
            </Link>
        </div>
    );
};

export default PaywallCTA;
