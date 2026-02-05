import React, { useState } from 'react';
import { Sparkles, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import FeedbackModal from './FeedbackModal';

const FeedbackBanner = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [isVisible, setIsVisible] = useState(true);

    if (!isVisible) return null;

    return (
        <div className="bg-white border-b border-gray-100 px-4 py-2 relative flex items-center justify-center min-h-[48px] font-sans">
            <div className={`flex items-center gap-2 ${isRtl ? 'flex-row-reverse' : ''}`}>
                <Sparkles className="h-4 w-4 text-yellow-500 fill-yellow-500 shrink-0" />
                <p className={`text-sm text-gray-900 leading-none ${isRtl ? 'font-arabic' : ''} text-center`}>
                    {isRtl ? 'باک جو ويبسائيٽ استعمال ڪندي توهان جو تجربو ڪهڙو رهيو؟ ' : 'How was your experience using Baakh’s UI? '}

                    <FeedbackModal
                        isRtl={isRtl}
                        trigger={
                            <button className="font-medium underline hover:text-gray-600 transition-colors cursor-pointer decoration-gray-400 underline-offset-4 decoration-1 mx-1.5">
                                {isRtl ? 'توهان جي راءِ اسان لاءِ اهم آهي۔' : 'Your feedback matters to us.'}
                            </button>
                        }
                    />
                </p>
            </div>

            <Button
                variant="ghost"
                size="icon"
                className="absolute right-4 top-1/2 -translate-y-1/2 h-8 w-8 text-gray-400 hover:text-black shrink-0"
                onClick={() => setIsVisible(false)}
            >
                <X className="h-4 w-4" />
            </Button>
        </div>
    );
};

export default FeedbackBanner;
