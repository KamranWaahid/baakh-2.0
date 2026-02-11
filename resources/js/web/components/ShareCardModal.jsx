import React from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import ShareCard from './ShareCard';
import { Download, Share2, X } from 'lucide-react';
import { formatSindhiDate } from '../utils/dateUtils';

const ShareCardModal = ({ open, onOpenChange, poem, lang = 'sd' }) => {
    const isRtl = lang === 'sd';

    if (!poem) return null;

    const handleDownload = () => {
        const downloadUrl = `/og-image/poetry/${poem.slug || poem.poetry_slug}?download=1`;
        window.open(downloadUrl, '_blank');
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-4xl p-0 bg-[#FFFAEC] border-none shadow-2xl overflow-hidden rounded-2xl">
                <DialogHeader className="p-6 pb-0 flex flex-row items-center justify-between border-b border-gray-100 bg-white">
                    <div className={isRtl ? 'text-right' : 'text-left'}>
                        <DialogTitle className="text-xl font-bold text-gray-900">
                            {isRtl ? 'شاعري شيئر ڪريو' : 'Share Poetry'}
                        </DialogTitle>
                        <DialogDescription className="text-sm text-gray-500 mt-1">
                            {isRtl ? 'هن خوبصورت ڪارڊ کي ڊائون لوڊ ڪريو يا شيئر ڪريو' : 'Download or share this beautiful card'}
                        </DialogDescription>
                    </div>
                </DialogHeader>

                <div className="p-6 md:p-10 flex flex-col items-center">
                    <div className="w-full max-w-3xl transform hover:scale-[1.01] transition-transform duration-300">
                        <ShareCard
                            poetryTitle={poem.title}
                            categoryName={poem.category?.name}
                            poetName={poem.poet?.name}
                            poetAvatar={poem.poet?.avatar}
                            date={isRtl ? formatSindhiDate(poem.date) : poem.date}
                            lang={lang}
                        />
                    </div>

                    <div className="flex flex-wrap items-center justify-center gap-4 mt-10">
                        <Button
                            variant="default"
                            className="bg-black text-white hover:bg-gray-800 h-12 px-8 rounded-full flex items-center gap-2 text-base font-medium transition-all transform active:scale-95 shadow-lg"
                            onClick={handleDownload}
                        >
                            <Download className="w-5 h-5" />
                            <span>{isRtl ? 'ڊائون لوڊ ڪريو' : 'Download Image'}</span>
                        </Button>

                        <Button
                            variant="outline"
                            className="h-12 px-8 rounded-full flex items-center gap-2 text-base font-medium border-gray-200 hover:border-black transition-all"
                            onClick={() => onOpenChange(false)}
                        >
                            <span>{isRtl ? 'بند ڪريو' : 'Close'}</span>
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default ShareCardModal;
